<?php
session_start();

// Require auth
if (!isset($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit;
}
require_once __DIR__ . '/../config/dbcon.php';

// Accept POSTs from the floating widget to store latest distribution in session (per user)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $emotion = strtolower(trim($_POST['emotion'] ?? ''));
  $percentage = isset($_POST['percentage']) ? (float)$_POST['percentage'] : null;
  $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : null;
  $distRaw = $_POST['distribution'] ?? '';
  $distribution = [];
  if ($distRaw !== '') {
    $tmp = json_decode($distRaw, true);
    if (is_array($tmp)) {
      foreach ($tmp as $k => $v) {
        $distribution[strtolower((string)$k)] = (float)$v;
      }
    }
  }
  $_SESSION['last_emotional_analysis'] = [
    'at' => date('c'),
    'emotion' => $emotion,
    'percentage' => $percentage,
    'duration' => $duration,
    'distribution' => $distribution,
  ];
  echo json_encode(['success' => true]);
  exit;
}

// Optional JSON access to last snapshot
if (isset($_GET['json'])) {
  header('Content-Type: application/json');
  echo json_encode($_SESSION['last_emotional_analysis'] ?? null);
  exit;
}

// Recent moods (JSON) for auto-refresh list (per-user)
if (isset($_GET['recent_json'])) {
  header('Content-Type: application/json');
  $out = [];
  if (isset($conn) && $conn && !$conn->connect_errno && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    if ($stmt = $conn->prepare("SELECT emotion, percentage, created_at FROM mood_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10")) {
      $stmt->bind_param('i', $uid);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) { $out[] = $r; }
      }
      $stmt->close();
    }
  }
  echo json_encode($out);
  exit;
}

$uid = (int)($_SESSION['user_id'] ?? 0);
$last = $_SESSION['last_emotional_analysis'] ?? null;
$dist = is_array($last['distribution'] ?? null) ? $last['distribution'] : [];
if (!empty($dist)) arsort($dist, SORT_NUMERIC);
$labels = array_keys($dist);
$values = array_values($dist);

// Per-user recent (sidebar)
$recentRows = [];
if ($uid && isset($conn) && $conn && !$conn->connect_errno) {
  if ($stmt = $conn->prepare("SELECT emotion, percentage, created_at FROM mood_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10")) {
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      while ($r = $res->fetch_assoc()) $recentRows[] = $r;
    }
    $stmt->close();
  }
}

// Per-user logs table (full)
$userLogs = [];
if ($uid && isset($conn) && $conn && !$conn->connect_errno) {
  if ($stmt = $conn->prepare("SELECT mood_logs.*, users.name FROM mood_logs LEFT JOIN users ON mood_logs.user_id = users.id WHERE mood_logs.user_id = ? ORDER BY created_at DESC LIMIT 1000")) {
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      while ($r = $res->fetch_assoc()) $userLogs[] = $r;
    }
    $stmt->close();
  }
}

// KPIs and stats (mix of per-user and global to match admin layout)
$kpi_total_logs = 0;
if ($uid && isset($conn) && $conn && !$conn->connect_errno) {
  if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM mood_logs WHERE user_id = ?")) {
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) $kpi_total_logs = (int)$row['cnt'];
    }
    $stmt->close();
  }
}

// Unique users (global)
$uniqueUsers = 0;
if (isset($conn) && $conn && !$conn->connect_errno) {
  $res = $conn->query("SELECT COUNT(DISTINCT user_id) AS cnt FROM mood_logs");
  if ($res && $row = $res->fetch_assoc()) $uniqueUsers = (int)$row['cnt'];
}

// Date range for this user's logs
$firstLog = null; $lastLog = null;
if ($uid && isset($conn) && $conn && !$conn->connect_errno) {
  if ($stmt = $conn->prepare("SELECT MIN(created_at) AS first_log, MAX(created_at) AS last_log FROM mood_logs WHERE user_id = ?")) {
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
        $firstLog = $row['first_log'] ?? null;
        $lastLog = $row['last_log'] ?? null;
      }
    }
    $stmt->close();
  }
}

// Top emotion for this user
$topEmotion = ['emotion' => null, 'cnt' => 0];
if ($uid && isset($conn) && $conn && !$conn->connect_errno) {
  if ($stmt = $conn->prepare("SELECT emotion, COUNT(*) AS cnt FROM mood_logs WHERE user_id = ? GROUP BY emotion ORDER BY cnt DESC LIMIT 1")) {
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) $topEmotion = ['emotion' => $row['emotion'], 'cnt' => (int)$row['cnt']];
    }
    $stmt->close();
  }
}

// Emotion breakdown for this user (chart)
$emotionBreakdown = [];
if ($uid && isset($conn) && $conn && !$conn->connect_errno) {
  if ($stmt = $conn->prepare("SELECT emotion, COUNT(*) AS cnt FROM mood_logs WHERE user_id = ? GROUP BY emotion ORDER BY cnt DESC")) {
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) $emotionBreakdown[$row['emotion']] = (int)$row['cnt'];
    }
    $stmt->close();
  }
}

// Trend (last 14 days) for this user
$daysBack = 13;
$trendData = [];
for ($i = $daysBack; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $trendData[$d] = 0; }
if ($uid && isset($conn) && $conn && !$conn->connect_errno) {
  if ($stmt = $conn->prepare("
    SELECT DATE(created_at) AS d, COUNT(*) AS cnt
    FROM mood_logs
    WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY d ASC
  ")) {
    $stmt->bind_param('ii', $uid, $daysBack);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) $trendData[$row['d']] = (int)$row['cnt'];
    }
    $stmt->close();
  }
}

// Helper mappings
function emotionBadgeClass($emotion) {
  $map = [
    'happy' => 'bg-success',
    'joy' => 'bg-success',
    'sad' => 'bg-secondary',
    'angry' => 'bg-danger',
    'anger' => 'bg-danger',
    'neutral' => 'bg-dark',
    'surprised' => 'bg-warning text-dark',
    'surprise' => 'bg-warning text-dark',
    'fear' => 'bg-info text-dark',
    'disgust' => 'bg-primary'
  ];
  $key = strtolower((string)$emotion);
  return $map[$key] ?? 'bg-secondary';
}
function emotionIconClass($emotion) {
  $map = [
    'happy' => 'bi-emoji-smile',
    'joy' => 'bi-emoji-laughing',
    'sad' => 'bi-emoji-frown',
    'angry' => 'bi-emoji-angry',
    'anger' => 'bi-emoji-angry',
    'neutral' => 'bi-emoji-neutral',
    'surprised' => 'bi-emoji-dizzy',
    'surprise' => 'bi-emoji-dizzy',
    'fear' => 'bi-emoji-frown',
    'disgust' => 'bi-emoji-expressionless'
  ];
  $key = strtolower((string)$emotion);
  return $map[$key] ?? 'bi-emoji-neutral';
}

// Palette for charts (same as admin)
$chartPalette = ['#6C63FF', '#00C9A7', '#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#a855f7', '#f97316'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Emotional Analysis — Your Logs</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --brand: #4f46e5; --brand-100: #eef2ff; --muted-bg: #f6f8fb;
      --card-radius: 14px; --elev-1: 0 2px 12px rgba(0,0,0,.06); --elev-2: 0 8px 24px rgba(0,0,0,.08);
    }
    body { background: var(--muted-bg); }
    .app-header { position: sticky; top:0; z-index:1030; background: linear-gradient(180deg, rgba(79,70,229,.95), rgba(79,70,229,.9)); color:#fff; padding: .6rem 0; }
    .brand { font-weight:600; display:flex; gap:.6rem; align-items:center; }
    .content-wrap { padding: 1.2rem; }
    .card-stat, .table-card, .card-ana { border:0; border-radius:var(--card-radius); box-shadow:var(--elev-1); background:#fff; transition: .18s; }
    .card-hover-lift:hover { transform: translateY(-3px); box-shadow:var(--elev-2); }
    .icon { width:48px;height:48px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:1.25rem; }
    .emo-pill { border-radius:999px; border:1px solid rgba(0,0,0,.08); padding:.35rem .6rem; display:inline-flex; align-items:center; gap:.35rem; cursor:pointer; background:#fff; }
    .emo-pill.active { background:var(--brand-100); color:var(--brand); box-shadow: 0 0 0 .15rem rgba(99,102,241,.08); }
    .badge-emo { margin-right:6px; margin-bottom:4px; display:inline-flex; align-items:center; gap:.35rem; }
    .pct-wrap { display:flex; align-items:center; gap:.5rem; min-width:140px; }
    .pct-bar { height:6px; border-radius:4px; flex:1; background:#e9ecef; overflow:hidden; }
    .pct-fill{ height:100%; border-radius:4px; transition:width .5s ease; }
    .table-responsive { max-height: 55vh; overflow:auto; }
    .small-muted { color:#6b7280; font-size:.85rem; }
    @media (prefers-reduced-motion:reduce){ *{animation:none!important;transition:none!important;} }
  </style>
</head>
<body>
  <header class="app-header">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <a href="../dashboard/user_dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i></a>
          <div class="brand"><i class="bi bi-activity"></i> <span>Emotional Analysis</span></div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-light btn-sm" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="mt-1 small text-white-50">Personal view — charts, filters and your logs</div>
    </div>
  </header>

  <div class="content-wrap container-fluid">

    <!-- Stats Row (mirrors admin) -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 card-hover-lift h-100">
          <div class="d-flex align-items-center">
            <div class="icon bg-primary-subtle text-primary me-3"><i class="bi bi-collection"></i></div>
            <div>
              <div class="text-muted small">Your Logs</div>
              <div class="h4 mb-0"><span class="count-up" data-count-to="<?php echo (int)$kpi_total_logs; ?>">0</span></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 card-hover-lift h-100">
          <div class="d-flex align-items-center">
            <div class="icon bg-success-subtle text-success me-3"><i class="bi bi-people"></i></div>
            <div>
              <div class="text-muted small">Unique Users</div>
              <div class="h4 mb-0"><span class="count-up" data-count-to="<?php echo (int)$uniqueUsers; ?>">0</span></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 card-hover-lift h-100">
          <div class="d-flex align-items-center">
            <div class="icon bg-warning-subtle text-warning me-3"><i class="bi bi-calendar2-week"></i></div>
            <div>
              <div class="text-muted small">Date Range</div>
              <div class="mb-0 text-nowrap">
                <span class="fw-semibold"><?php echo $firstLog ? htmlspecialchars(date('M d, Y', strtotime($firstLog))) : '—'; ?></span>
                <span class="text-muted">to</span>
                <span class="fw-semibold"><?php echo $lastLog ? htmlspecialchars(date('M d, Y', strtotime($lastLog))) : '—'; ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 card-hover-lift h-100">
          <div class="d-flex align-items-center">
            <div class="icon bg-danger-subtle text-danger me-3"><i class="bi bi-bar-chart"></i></div>
            <div>
              <div class="text-muted small">Top Emotion</div>
              <div class="mb-0">
                <?php if ($topEmotion['emotion']): ?>
                  <span class="badge <?php echo emotionBadgeClass($topEmotion['emotion']); ?> badge-emo">
                    <i class="bi <?php echo emotionIconClass($topEmotion['emotion']); ?>"></i>
                    <?php echo htmlspecialchars(ucfirst($topEmotion['emotion'])); ?>
                  </span>
                  <span class="small text-muted"><span class="count-up" data-count-to="<?php echo (int)$topEmotion['cnt']; ?>">0</span></span>
                <?php else: ?>
                  —
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="row g-3">
      <div class="col-12 col-lg-6">
        <div class="card table-card p-3 card-hover-lift h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold"><i class="bi bi-pie-chart me-1"></i> Emotion Distribution</div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-secondary btn-sm" id="downloadEmotionChart" title="Download chart"><i class="bi bi-download"></i></button>
              <button class="btn btn-outline-secondary btn-sm" id="refreshRecent" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
          </div>

          <div>
            <?php if (empty($emotionBreakdown)): ?>
              <div class="text-center py-5 text-muted">
                <i class="bi bi-emoji-neutral mb-2" style="font-size:1.6rem;"></i>
                <div>No data to display yet.</div>
              </div>
            <?php else: ?>
              <canvas id="emotionChart" height="200" aria-label="Emotion distribution chart" role="img"></canvas>
              <div class="mt-3 d-flex flex-wrap gap-2">
                <?php $i = 0; foreach ($emotionBreakdown as $emo => $cnt): ?>
                  <div class="d-flex align-items-center gap-2 small-muted">
                    <span style="width:12px;height:12px;background:<?php echo $chartPalette[$i % count($chartPalette)]; ?>;border-radius:4px;display:inline-block;"></span>
                    <span><?php echo htmlspecialchars(ucfirst($emo)); ?> <span class="text-muted">· <?php echo (int)$cnt; ?></span></span>
                  </div>
                <?php $i++; endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card table-card p-3 card-hover-lift h-100">
          <div class="card-header align-items-center d-flex justify-content-between pb-2" style="border-bottom:none">
            <div class="fw-semibold"><i class="bi bi-graph-up me-1"></i> Logs: Last 14 Days</div>
            <button class="btn btn-outline-secondary btn-sm" id="downloadTrendChart" title="Download trend"><i class="bi bi-download"></i></button>
          </div>
          <div>
            <?php if (array_sum($trendData) === 0): ?>
              <div class="text-center py-5 text-muted">
                <i class="bi bi-emoji-neutral mb-2" style="font-size:1.6rem;"></i>
                <div>No activity in the last 14 days.</div>
              </div>
            <?php else: ?>
              <canvas id="trendChart" height="180" aria-label="Logs trend chart" role="img"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters / quick pills -->
    <div class="mt-4">
      <div class="card table-card p-3 card-hover-lift">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="emo-pill active" data-emo=""><i class="bi bi-ui-checks-grid"></i> All</span>
            <?php foreach (array_keys($emotionBreakdown) as $emo): ?>
              <span class="emo-pill" data-emo="<?php echo htmlspecialchars(strtolower($emo)); ?>"><i class="bi <?php echo emotionIconClass($emo); ?>"></i> <?php echo htmlspecialchars(ucfirst($emo)); ?></span>
            <?php endforeach; ?>
          </div>
          <div class="d-flex gap-2">
            <div class="input-icon" style="position:relative">
              <i class="bi bi-search" style="position:absolute;left:10px;top:9px;color:#6c757d"></i>
              <input id="searchInput" class="form-control form-control-sm" style="padding-left:34px" placeholder="Search emotion" />
            </div>
            <button id="clearFilters" class="btn btn-outline-secondary btn-sm">Clear</button>
            <button id="exportCsv" class="btn btn-outline-primary btn-sm"><i class="bi bi-filetype-csv"></i> Export</button>
          </div>
        </div>
        <div class="small text-muted">Filter your logs by quick pills or search. Table below shows your records.</div>
      </div>
    </div>

    <!-- Logs table -->
    <div class="card table-card mt-3 p-3 card-hover-lift">
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="logsTable">
          <thead class="table-light">
            <tr>
              <th>User</th>
              <th>Emotion</th>
              <th>Percentage</th>
              <th>Recorded At</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($userLogs)): ?>
              <tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No logs found.</td></tr>
            <?php else: ?>
              <?php foreach ($userLogs as $row): 
                $userName = $row['name'] ?? 'You';
                $emotion = $row['emotion'] ?? '';
                $percent = isset($row['percentage']) ? (float)$row['percentage'] : null;
                $createdAt = $row['created_at'] ?? '';
                $dateOnly = $createdAt ? date('Y-m-d', strtotime($createdAt)) : '';
                $pctInt = $percent !== null ? (int)round($percent) : null;
              ?>
                <tr data-emotion="<?php echo htmlspecialchars(mb_strtolower($emotion)); ?>" data-date="<?php echo htmlspecialchars($dateOnly); ?>">
                  <td class="fw-medium"><i class="bi bi-person-circle text-muted me-1"></i> <?php echo htmlspecialchars($userName); ?></td>
                  <td>
                    <span class="badge <?php echo emotionBadgeClass($emotion); ?> badge-emo" title="<?php echo htmlspecialchars(ucfirst($emotion)); ?>">
                      <i class="bi <?php echo emotionIconClass($emotion); ?>"></i>
                      <?php echo htmlspecialchars(ucfirst($emotion)); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($pctInt !== null): ?>
                      <div class="pct-wrap" title="<?php echo $pctInt; ?>%">
                        <div class="pct-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $pctInt; ?>">
                          <div class="pct-fill <?php echo emotionBadgeClass($emotion); ?>" style="width: <?php echo max(0,min(100,$pctInt)); ?>%;"></div>
                        </div>
                        <span class="text-muted small"><?php echo htmlspecialchars(number_format($pctInt,0)); ?>%</span>
                      </div>
                    <?php else: ?> — <?php endif; ?>
                  </td>
                  <td><span class="text-nowrap" title="<?php echo htmlspecialchars(date('c', strtotime($createdAt))); ?>"><i class="bi bi-clock-history text-muted me-1"></i><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($createdAt))); ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    // Count-up
    const counters = document.querySelectorAll('.count-up[data-count-to]');
    const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
    const runCounter = el => {
      const end = parseInt(el.getAttribute('data-count-to'), 10) || 0;
      const dur = 800, start = performance.now(), fmt = new Intl.NumberFormat();
      function tick(n){ const p = Math.min(1,(n-start)/dur); el.textContent = fmt.format(Math.round(end*easeOutCubic(p))); if (p<1) requestAnimationFrame(tick); }
      requestAnimationFrame(tick);
    };
    const io = new IntersectionObserver(entries => { entries.forEach(e=>{ if(e.isIntersecting){ runCounter(e.target); io.unobserve(e.target); } }); }, {threshold:0.6});
    counters.forEach(c=>io.observe(c));

    // Charts data from PHP
    const emotionLabels = <?php echo json_encode(array_keys($emotionBreakdown)); ?>;
    const emotionValues = <?php echo json_encode(array_values($emotionBreakdown), JSON_NUMERIC_CHECK); ?>;
    const trendLabels = <?php echo json_encode(array_map(function($d){ return date('M d', strtotime($d)); }, array_keys($trendData))); ?>;
    const trendValues = <?php echo json_encode(array_values($trendData), JSON_NUMERIC_CHECK); ?>;
    const palette = <?php echo json_encode($chartPalette); ?>;

    // Emotion Chart (doughnut)
    let emotionChart = null;
    const ec = document.getElementById('emotionChart');
    if (ec && emotionLabels.length) {
      emotionChart = new Chart(ec, {
        type: 'doughnut',
        data: { labels: emotionLabels.map(l=>l.charAt(0).toUpperCase()+l.slice(1)), datasets: [{ data: emotionValues, backgroundColor: emotionLabels.map((_,i)=>palette[i % palette.length]), borderWidth:0 }] },
        options: { animation:{duration:900,easing:'easeInOutQuart'}, plugins:{legend:{position:'bottom'}, tooltip:{callbacks:{label:ctx=>`${ctx.label}: ${ctx.formattedValue}`}}}, cutout:'60%' }
      });
    }

    // Trend Chart (line)
    let trendChart = null;
    const tc = document.getElementById('trendChart');
    if (tc) {
      trendChart = new Chart(tc, {
        type: 'line',
        data: { labels: trendLabels, datasets: [{ label:'Logs', data: trendValues, tension:.35, borderColor:'#0d6efd', backgroundColor:'rgba(13,110,253,.12)', fill:true, pointRadius:3 }] },
        options: { animation:{duration:900,easing:'easeInOutQuart'}, plugins:{legend:{display:false}}, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
      });
    }

    // Download chart buttons
    const dlEmotion = document.getElementById('downloadEmotionChart');
    if (dlEmotion && emotionChart) dlEmotion.addEventListener('click', ()=>{ const a=document.createElement('a'); a.href=emotionChart.toBase64Image(); a.download='emotion-distribution.png'; a.click(); });
    const dlTrend = document.getElementById('downloadTrendChart');
    if (dlTrend && trendChart) dlTrend.addEventListener('click', ()=>{ const a=document.createElement('a'); a.href=trendChart.toBase64Image(); a.download='trend-14-days.png'; a.click(); });

    // Recent load
    async function loadRecent(){ try{ const r = await fetch('?recent_json=1',{credentials:'include'}); if(!r.ok) return; const d = await r.json(); renderRecent(d);}catch(e){} }
    function renderRecent(items){
      const cont = document.getElementById('recentList');
      if (!cont) return;
      if (!items || !items.length) { cont && (cont.innerHTML = '<div class="text-center py-3 text-muted">No recent logs yet.</div>'); return; }
      cont.innerHTML = items.map(r=>{
        const emo = (r.emotion||'').toLowerCase();
        const pct = r.percentage!=null ? Math.round(r.percentage) : null;
        const when = r.created_at ? new Date(r.created_at).toLocaleString() : '';
        return `<div class="d-flex justify-content-between border-bottom py-2"><div><span class="badge ${badgeClass(emo)}">${escapeText(cap(emo))}</span>${pct!==null?` <span class="small text-muted ms-1">${pct}%</span>`:''}</div><div class="small text-muted">${escapeText(when)}</div></div>`;
      }).join('');
    }
    function cap(s){ return s ? s.charAt(0).toUpperCase()+s.slice(1) : ''; }
    function escapeText(s){ const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function badgeClass(emo){ const m={happy:'bg-success',joy:'bg-success',sad:'bg-secondary',angry:'bg-danger',anger:'bg-danger',neutral:'bg-dark',surprised:'bg-warning text-dark',surprise:'bg-warning text-dark',fear:'bg-info text-dark',disgust:'bg-primary'}; return m[emo]||'bg-secondary'; }
    const rbtn = document.getElementById('refreshRecent'); if (rbtn) rbtn.addEventListener('click', loadRecent); setInterval(loadRecent,10000); loadRecent();

    // Filters: search + quick pills
    const searchInput = document.getElementById('searchInput');
    const clearFilters = document.getElementById('clearFilters');
    const exportBtn = document.getElementById('exportCsv');
    document.querySelectorAll('.emo-pill').forEach(p=>{
      p.addEventListener('click', ()=> {
        document.querySelectorAll('.emo-pill').forEach(x=>x.classList.remove('active'));
        p.classList.add('active');
        const filter = (p.dataset.emo||'').toLowerCase();
        document.querySelectorAll('#logsTable tbody tr').forEach(tr=>{
          const te = tr.getAttribute('data-emotion')||'';
          tr.style.display = (!filter || te===filter) ? '' : 'none';
        });
      });
    });
    if (searchInput) searchInput.addEventListener('input', ()=> {
      const q = (searchInput.value||'').trim().toLowerCase();
      document.querySelectorAll('#logsTable tbody tr').forEach(tr=>{
        const text = tr.innerText.toLowerCase();
        tr.style.display = q ? (text.includes(q) ? '' : 'none') : '';
      });
    });
    if (clearFilters) clearFilters.addEventListener('click', ()=>{
      searchInput && (searchInput.value='');
      document.querySelectorAll('.emo-pill').forEach((p,i)=>p.classList.toggle('active', i===0));
      document.querySelectorAll('#logsTable tbody tr').forEach(tr=>tr.style.display='');
    });

    // CSV export of visible rows
    if (exportBtn) exportBtn.addEventListener('click', ()=>{
      const rows = Array.from(document.querySelectorAll('#logsTable tbody tr')).filter(r => r.style.display!=='none' && r.children.length>1);
      if (!rows.length) return alert('No visible rows to export.');
      const header = ['User','Emotion','Percentage','Recorded At'];
      const data = rows.map(tr=>{
        const tds = tr.querySelectorAll('td');
        return [tds[0].innerText.trim(), tds[1].innerText.trim(), tds[2].innerText.trim(), tds[3].innerText.trim()];
      });
      const csv = [header, ...data].map(r => r.map(v=>`"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
      const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=`my-logs-${new Date().toISOString().slice(0,10)}.csv`; a.click();
    });

  });
  </script>
</body>
</html>
