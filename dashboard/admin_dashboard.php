<?php
session_start();
require_once '../config/dbcon.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../index.php");
  exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$parts = preg_split('/\s+/', trim($name));
$initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));

// Mood chart data
$chart = $conn->query("SELECT emotion, COUNT(*) as total FROM mood_logs GROUP BY emotion");
$chart_data = [];
while ($row = $chart->fetch_assoc()) {
  $chart_data[] = $row;
}
$chart_labels = [];
$chart_counts = [];
foreach ($chart_data as $d) {
  $chart_labels[] = $d['emotion'];
  $chart_counts[] = (int) $d['total'];
}

// Users with mood logs (for per-user filtering)
$usersWithLogs = [];
if ($conn && !$conn->connect_errno) {
  $sqlUsers = "SELECT DISTINCT u.id, COALESCE(u.name, CONCAT('User #', m.user_id)) AS name
               FROM mood_logs m
               LEFT JOIN users u ON m.user_id = u.id
               ORDER BY name";
  if ($resU = $conn->query($sqlUsers)) {
    while ($rowU = $resU->fetch_assoc()) { $usersWithLogs[] = $rowU; }
    $resU->free();
  }
}

// Stats
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_admins = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetch_row()[0];
$total_logs = $conn->query("SELECT COUNT(*) FROM mood_logs")->fetch_row()[0];
$active_sessions = rand(1, 10);

// Fetch unseen notifications count
$notif_count = 0;
if ($conn && !$conn->connect_errno) {
  $sql = "SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0 AND (LOWER(title) LIKE '%assessment%' OR LOWER(body) LIKE '%assessment%' OR LOWER(title) LIKE '%mood%' OR LOWER(body) LIKE '%mood%')";
  if ($res = $conn->query($sql)) { $row = $res->fetch_row(); $notif_count = (int)($row[0] ?? 0); $res->free(); }
}

// Preload latest submissions (assessment_results) for immediate display in modal
$notif_items = [];
$listRes = $conn->query("SELECT ar.id, ar.score, ar.category, ar.created_at, u.name AS user_name, a.title AS assessment_title FROM assessment_results ar JOIN users u ON u.id = ar.user_id JOIN assessments a ON a.id = ar.assessment_id ORDER BY ar.created_at DESC LIMIT 50");
if ($listRes && $listRes->num_rows > 0) {
  while ($r = $listRes->fetch_assoc()) {
    $notif_items[] = $r;
  }
}

function _rel_time($dtstr) {
  if (!$dtstr) return '';
  try {
    $dt = new DateTime($dtstr);
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');
    $d = $dt->format('Y-m-d');
    if ($d === $today) return 'Today ' . $dt->format('H:i');
    if ($d === $yesterday) return 'Yesterday ' . $dt->format('H:i');
    $diff = $now->diff($dt);
    if ($diff->days <= 7) return $diff->days . ' day' . ($diff->days==1?'':'s') . ' ago';
    return $dt->format('M j, Y H:i');
  } catch (Exception $e) { return htmlspecialchars($dtstr); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | MindCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    :root {
      --sidebar-bg: #ffffff;
      --text-color: #212529;
      --primary: #6C63FF;
      --accent: #00C9A7;
      --bg: #f5f7fb;
      --card-bg: #ffffff;
      --muted: #6c757d;
      --ring: rgba(108, 99, 255, 0.2);
      --soft-shadow: 0 6px 24px rgba(18, 38, 63, 0.06);
      --soft-shadow-2: 0 16px 32px rgba(18, 38, 63, 0.08);
      --radius: 14px;
    }
    body {
      background: radial-gradient(1200px 800px at -10% -20%, rgba(108, 99, 255, 0.06), transparent 60%),
                  radial-gradient(1200px 800px at 110% 20%, rgba(0, 201, 167, 0.06), transparent 60%),
                  var(--bg);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      color: var(--text-color);
      line-height: 1.55;
    }

    /* Sidebar - kept intact, just subtle polish */
    .sidebar {
      position: fixed; height: 100vh; width: 250px; background: var(--sidebar-bg);
      transition: 0.3s; box-shadow: 0 0 20px rgba(0,0,0,0.05); z-index: 100;
      border-right: 1px solid #eef0f5;
    }
    .sidebar.collapsed { width: 78px; }
    .sidebar .logo-details {
      height: 64px; display: flex; align-items: center; justify-content: space-between;
      padding: 0 15px; border-bottom: 1px solid #eef0f5;
      background: linear-gradient(90deg, rgba(108,99,255,0.06), transparent);
    }
    .sidebar .logo_name { font-size: 20px; font-weight: 700; color: var(--text-color); letter-spacing: 0.2px; }
    .sidebar .toggle-btn { font-size: 24px; background: none; border: none; color: var(--text-color); cursor: pointer; }
    .sidebar .nav-links { margin-top: 12px; list-style: none; padding-left: 0; }
    .sidebar .nav-links li { margin: 8px 10px; }
    .sidebar .nav-links li a {
      display: flex; align-items: center; text-decoration: none;
      padding: 10px 14px; color: var(--text-color); gap: 10px;
      font-size: 15px; border-radius: 10px; transition: 0.25s ease;
    }
    .sidebar .nav-links li a i { min-width: 24px; font-size: 18px; }
    .sidebar .nav-links li a:hover, .sidebar .nav-links li a.active {
      background: linear-gradient(135deg, rgba(108, 99, 255, 0.14), rgba(0, 201, 167, 0.14));
      color: #1f1f1f;
    }
    .sidebar.collapsed .logo_name, .sidebar.collapsed .sidebar-text { display: none; }

    /* Main content wrapper */
    .main { margin-left: 250px; padding: 2rem; transition: 0.3s; }
    .sidebar.collapsed + .main { margin-left: 78px; }

    /* Top header / hero */
    .hero {
      background: linear-gradient(135deg, rgba(108,99,255,0.12), rgba(0,201,167,0.12));
      border: 1px solid rgba(108,99,255,0.10);
      border-radius: var(--radius);
      padding: 18px 18px;
      box-shadow: var(--soft-shadow);
    }
    .hero .title {
      font-weight: 800; letter-spacing: 0.2px;
      background: linear-gradient(90deg, #1f1f1f, #3b3b98 60%, #1f1f1f);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      margin: 0 0 2px 0;
    }
    .hero .subtitle {
      color: var(--muted); font-size: 14px; margin: 0;
    }
    .top-actions {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .searchbar {
      position: relative; min-width: 220px;
    }
    .searchbar input {
      border: 1px solid #e9ecf5; background: #fff; border-radius: 10px;
      padding: 9px 36px 9px 12px; outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .searchbar input:focus {
      border-color: var(--primary); box-shadow: 0 0 0 4px var(--ring);
    }
    .searchbar i {
      position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #9098a3;
    }
    .avatar {
      width: 38px; height: 38px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff; display: grid; place-items: center; font-weight: 700; letter-spacing: 0.5px;
      box-shadow: var(--soft-shadow);
    }
    .btn-gradient {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: white; border: none; border-radius: 10px; padding: 10px 14px; font-weight: 600;
      box-shadow: var(--soft-shadow);
      transition: transform 0.15s ease, opacity 0.2s ease;
    }
    .btn-gradient:hover { opacity: 0.96; transform: translateY(-1px); }

    /* KPI cards */
    .kpi {
      background: var(--card-bg); border-radius: var(--radius);
      padding: 16px 16px; box-shadow: var(--soft-shadow);
      border: 1px solid #eef0f5;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      height: 100%;
    }
    .kpi:hover { transform: translateY(-2px); box-shadow: var(--soft-shadow-2); }
    .kpi .icon {
      width: 40px; height: 40px; border-radius: 12px;
      display: grid; place-items: center; color: #fff; font-size: 18px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      box-shadow: var(--soft-shadow);
    }
    .kpi .label { color: var(--muted); font-size: 13px; margin-bottom: 2px; }
    .kpi .value { font-size: 28px; font-weight: 800; letter-spacing: 0.3px; color: #1f1f1f; }

    /* Cards */
    .card-lite {
      background: var(--card-bg); border-radius: var(--radius);
      padding: 16px; box-shadow: var(--soft-shadow);
      border: 1px solid #eef0f5; height: 100%;
    }
    .section-title {
      font-size: 16px; font-weight: 700; margin: 0 0 8px 0;
      color: #2b2f36; letter-spacing: 0.2px;
    }
    .muted { color: var(--muted) !important; }

    /* Chart sizing */
    canvas { max-height: 320px !important; }

    /* List items */
    .list-item {
      border: 1px solid #eef0f5; border-radius: 12px; padding: 12px;
      display: flex; justify-content: space-between; align-items: start;
      transition: background 0.15s ease;
    }
    .list-item:hover { background: #fafbff; }

    /* Notification bell */
    .notif-bell { position: relative; cursor: pointer; }
    .notif-count {
      position: absolute; top: -6px; right: -6px; background: #ff4d4f; color: white;
      font-size: 12px; font-weight: 700; border-radius: 999px; padding: 3px 7px;
      animation: pulse 1.2s infinite;
      box-shadow: 0 0 0 3px #fff;
    }
    @keyframes pulse {
      0% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.15); opacity: 0.75; }
      100% { transform: scale(1); opacity: 1; }
    }

    /* Glassy modal */
    .glassy {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      box-shadow: var(--soft-shadow-2);
      overflow: hidden;
      animation: fadeIn 0.3s ease;
      border: 1px solid #eef0f5;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

    /* Keep existing assessment UI styles for compatibility (trimmed/modernized) */
    .assessment-question {
      background: #ffffff; border-radius: 16px; padding: 16px; margin-bottom: 16px;
      border: 1px solid #eee; box-shadow: 0 4px 12px rgba(0,0,0,0.04);
      transition: 0.2s ease;
    }
    .assessment-question:hover { transform: scale(1.01); }

    /* Responsive tweaks */
    @media (max-width: 992px) {
      .main { padding: 1.25rem; }
      .hero { padding: 14px; }
    }
    @media (max-width: 576px) {
      .top-actions { justify-content: space-between; }
      .searchbar { flex: 1; min-width: 160px; }
    }
  /* Mobile sidebar (off-canvas) */
    .sidebar-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.25); backdrop-filter: blur(2px); z-index: 90; display: none; }
    .sidebar-backdrop.show { display: block; }
    body.no-scroll { overflow: hidden; }
    @media (max-width: 992px) {
      .sidebar { transform: translateX(-100%); width: 260px; left: 0; top: 0; }
      .sidebar.open { transform: translateX(0); }
      .sidebar.collapsed { width: 260px; } /* ignore collapsed on mobile */
      .main { margin-left: 0; padding: 1rem; }
    }
  </style>
</head>
<body>

<!-- Sidebar: do not modify/remove per request -->
<div class="sidebar" id="sidebar">
  <div class="logo-details">
    <span class="logo_name sidebar-text">MindCare</span>
    <button class="toggle-btn" onclick="toggleSidebar()">
      <i class="bi bi-list" id="sidebarIcon"></i>
    </button>
  </div>
  <ul class="nav-links">
    <li><a href="admin_dashboard.php" class="active"><i class="bi bi-bar-chart-line"></i> <span class="sidebar-text">Dashboard</span></a></li>
    <li><a href="../admin/manage_users.php"><i class="bi bi-people-fill"></i> <span class="sidebar-text">Manage Users</span></a></li>
    <li><a href="../admin/view_journals.php"><i class="bi bi-journal-bookmark"></i> <span class="sidebar-text">Journal Logs</span></a></li>
    <li><a href="../admin/admin_mood_logs.php"><i class="bi bi-activity"></i> <span class="sidebar-text">Mood Logs</span></a></li>
    <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> <span class="sidebar-text">Logout</span></a></li>
    <li><a href="../admin/emergency.php"><i class="bi bi-exclamation-triangle-fill text-danger"></i> <span class="sidebar-text text-danger">Emergency Page</span></a></li>
  </ul>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="main">
  <!-- Hero / Topbar -->
  <div class="hero mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between">
      <div class="mb-2">
        <div class="d-flex align-items-center gap-2 d-lg-none mb-2">
          <button class="btn btn-light border" onclick="toggleSidebar()" aria-label="Open menu"><i class="bi bi-list"></i></button>
          <span class="fw-semibold">Menu</span>
        </div>
        <h3 class="title">Admin Dashboard</h3>
        <p class="subtitle">Welcome back, <strong><?= htmlspecialchars($name) ?></strong>. Here’s a quick snapshot of MindCare today.</p>
      </div>
      <div class="top-actions">
        <div class="searchbar">
          <input type="text" placeholder="Quick search..." aria-label="search" />
          <i class="bi bi-search"></i>
        </div>

        <button class="btn btn-gradient" onclick="window.location.href='../admin/createassessment.php'">
          <i class="bi bi-plus-circle me-1"></i> Create Assessment
        </button>

        <div class="notif-bell d-inline-block position-relative" data-bs-toggle="modal" data-bs-target="#notifModal" onclick="loadNotifications()" aria-label="Notifications">
          <i class="bi bi-bell-fill fs-4 text-primary"></i>
          <span id="notifCount" class="notif-count" style="<?= $notif_count == 0 ? 'display:none;' : '' ?>"><?= $notif_count ?></span>
        </div>

        <div class="avatar ms-1" title="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($initials) ?></div>
      </div>
    </div>
  </div>

  <!-- KPI Row -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="kpi">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="label">Total Users</div>
            <div class="value" data-countup="<?= (int)$total_users ?>">0</div>
          </div>
          <div class="icon"><i class="bi bi-person-fill"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="label">Admins</div>
            <div class="value" data-countup="<?= (int)$total_admins ?>">0</div>
          </div>
          <div class="icon" style="background: linear-gradient(135deg, #18a558, #36cfc9)"><i class="bi bi-person-gear"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="label">Mood Logs</div>
            <div class="value" data-countup="<?= (int)$total_logs ?>">0</div>
          </div>
          <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #f97316)"><i class="bi bi-emoji-smile"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="label">Active Sessions</div>
            <div class="value" data-countup="<?= (int)$active_sessions ?>">0</div>
          </div>
          <div class="icon" style="background: linear-gradient(135deg, #ef4444, #f87171)"><i class="bi bi-activity"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts and Recent Submissions -->
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card-lite h-100">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <div class="section-title">Mood Distribution</div>
            <div class="muted small">Overview of logged emotions</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <select id="userFilter" class="form-select form-select-sm" style="width:220px" aria-label="Filter by user">
              <option value="0">All Users</option>
              <?php foreach ($usersWithLogs as $u): ?>
                <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name'] ?: ('User #' . (int)$u['id'])) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="dropdown">
              <button class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Options</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><button class="dropdown-item" onclick="refreshMoodChart()">Refresh</button></li>
              </ul>
            </div>
          </div>
        </div>
        <canvas id="moodChart" aria-label="Mood Distribution Chart"></canvas>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card-lite h-100">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <div class="section-title">Recent Submissions</div>
            <div class="muted small">Latest assessment results</div>
          </div>
        <a href="#" class="small text-decoration-none" data-bs-toggle="modal" data-bs-target="#notifModal" onclick="loadNotifications()">View all</a>
        </div>
        <?php if (empty($notif_items)) : ?>
          <div class="text-center py-5 muted">No submissions yet</div>
        <?php else: ?>
          <div class="d-flex flex-column gap-2">
            <?php
              $count = 0;
              foreach ($notif_items as $it):
                if ($count++ >= 6) break; // preview top 6
                $user = htmlspecialchars($it['user_name'] ?? '');
                $title = htmlspecialchars($it['assessment_title'] ?? '');
                $score = (int)($it['score'] ?? 0);
                $cat = htmlspecialchars($it['category'] ?? '');
                $time = _rel_time($it['created_at'] ?? '');
                $catLower = strtolower($it['category'] ?? '');
                $cls = 'bg-secondary';
                if ($catLower === 'good') $cls = 'bg-success';
                elseif ($catLower === 'fair') $cls = 'bg-warning text-dark';
                elseif ($catLower === 'bad') $cls = 'bg-danger';
            ?>
            <div class="list-item">
              <div>
                <strong><?= $user ?></strong>
                <span class="ms-1 badge <?= $cls ?>"><?= $cat ?></span>
                <div class="small muted"><?= $title ?></div>
              </div>
              <div class="text-end">
                <div class="fw-bold"><?= $score ?>%</div>
                <div class="small muted"><?= $time ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- System snapshot -->
  <div class="row g-3 mt-1">
    <div class="col-lg-6">
      <div class="card-lite">
        <div class="section-title mb-2">Quick Actions</div>
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-light border"><i class="bi bi-file-earmark-plus me-1"></i>New Assessment</button>
          <button class="btn btn-light border" onclick="window.location.href='../admin/manage_users.php'"><i class="bi bi-person-plus me-1"></i>Invite User</button>
          <button class="btn btn-light border" onclick="window.location.href='../admin/view_journals.php'"><i class="bi bi-journal-text me-1"></i>Review Journals</button>
          <button class="btn btn-light border" onclick="window.location.href='../admin/admin_mood_logs.php'"><i class="bi bi-activity me-1"></i>Mood Logs</button>
          <button class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#notifModal"><i class="bi bi-bell me-1"></i>Notifications</button>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card-lite">
        <div class="section-title mb-2">Summary</div>
        <ul class="list-unstyled m-0">
          <li class="d-flex justify-content-between py-1 border-bottom">
            <span class="muted">Users to Admin ratio</span>
            <span class="fw-semibold"><?= $total_admins > 0 ? round(($total_users - $total_admins) / max(1, $total_admins), 1) : '—' ?> : 1</span>
          </li>
          <li class="d-flex justify-content-between py-1 border-bottom">
            <span class="muted">Average logs per user</span>
            <span class="fw-semibold"><?= $total_users > 0 ? round($total_logs / $total_users, 2) : '0' ?></span>
          </li>
          <li class="d-flex justify-content-between py-1">
            <span class="muted">Unseen notifications</span>
            <span class="fw-semibold"><?= (int)$notif_count ?></span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Notifications Modal -->
<div class="modal fade" id="notifModal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content glassy">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-bell-fill text-warning"></i> Notifications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="notifList">
        <?php if (empty($notif_items)): ?>
          <p class="text-muted text-center mb-0">No submissions yet</p>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($notif_items as $it): ?>
              <?php $user = htmlspecialchars($it['user_name'] ?? '');
                    $title = htmlspecialchars($it['assessment_title'] ?? '');
                    $score = (int)($it['score'] ?? 0);
                    $cat = htmlspecialchars($it['category'] ?? '');
                    $time = _rel_time($it['created_at'] ?? '');
                    $catLower = strtolower($it['category'] ?? '');
                    $cls = 'bg-secondary';
                    if ($catLower === 'good') $cls = 'bg-success';
                    elseif ($catLower === 'fair') $cls = 'bg-warning text-dark';
                    elseif ($catLower === 'bad') $cls = 'bg-danger';
              ?>
              <div class="list-group-item d-flex justify-content-between align-items-start">
                <div><strong><?= $user ?></strong> — <?= $score ?>% <span class="badge <?= $cls ?>"><?= $cat ?></span><br><small class="text-muted"><?= $title ?></small></div>
                <small class="text-muted ms-3"><?= $time ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const isMobile = window.matchMedia('(max-width: 992px)').matches;
    if (isMobile) {
      const willOpen = !sb.classList.contains('open');
      sb.classList.toggle('open', willOpen);
      document.body.classList.toggle('no-scroll', willOpen);
      if (backdrop) backdrop.classList.toggle('show', willOpen);
    } else {
      sb.classList.toggle('collapsed');
    }
  }

  // Backdrop interactions and resize handling
  (function(){
    const backdrop = document.getElementById('sidebarBackdrop');
    if (backdrop) {
      backdrop.addEventListener('click', () => {
        const sb = document.getElementById('sidebar');
        sb.classList.remove('open');
        document.body.classList.remove('no-scroll');
        backdrop.classList.remove('show');
      });
    }
    window.addEventListener('resize', () => {
      const sb = document.getElementById('sidebar');
      const isMobile = window.matchMedia('(max-width: 992px)').matches;
      if (!isMobile) {
        sb.classList.remove('open');
        document.body.classList.remove('no-scroll');
        if (backdrop) backdrop.classList.remove('show');
      }
    });
  })();

  // Count-up animation for KPI values
  function animateValue(el, end, duration = 1000) {
    end = parseInt(end || 0, 10);
    const start = 0;
    const diff = end - start;
    if (diff === 0) { el.textContent = end; return; }
    const startTime = performance.now();
    function step(now) {
      const p = Math.min((now - startTime) / duration, 1);
      const val = Math.floor(start + diff * p);
      el.textContent = val.toLocaleString();
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  document.querySelectorAll('[data-countup]').forEach(el => {
    const end = el.getAttribute('data-countup');
    animateValue(el, end, 900);
  });

  // Mood Chart (live + per-user)
  let moodChart;
  const baseColors = ['#6C63FF', '#00C9A7', '#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#a855f7', '#f97316'];

  function drawMoodChart(labels, counts) {
    const ctx = document.getElementById('moodChart');
    if (!ctx) return;
    const colors = labels.map((_, i) => baseColors[i % baseColors.length]);
    if (moodChart) moodChart.destroy();
    moodChart = new Chart(ctx, {
      type: 'doughnut',
      data: { labels, datasets: [{ label: 'Logs', data: counts, backgroundColor: colors, borderWidth: 1, borderColor: '#fff' }] },
      options: {
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 14, usePointStyle: true } },
          tooltip: { callbacks: { label: (c) => `${c.label}: ${c.formattedValue}` } }
        },
        cutout: '62%', animation: { animateRotate: true, animateScale: true }
      }
    });
  }

  async function fetchMoodData(userId) {
    try {
      const url = 'mood_stats.php' + (userId && userId > 0 ? ('?user_id=' + encodeURIComponent(userId)) : '');
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) throw new Error('Fetch failed');
      const data = await res.json();
      const labels = Array.isArray(data.labels) ? data.labels : [];
      const counts = Array.isArray(data.counts) ? data.counts : [];
      drawMoodChart(labels, counts);
    } catch (e) {
      // Fallback to server-side embedded data
      drawMoodChart(<?= json_encode($chart_labels) ?>, <?= json_encode($chart_counts) ?>);
    }
  }

  function currentUserId() {
    const sel = document.getElementById('userFilter');
    return sel ? parseInt(sel.value || '0', 10) : 0;
  }
  function refreshMoodChart() { fetchMoodData(currentUserId()); }

  (function initMoodChart(){
    const sel = document.getElementById('userFilter');
    if (sel) sel.addEventListener('change', () => fetchMoodData(currentUserId()));
    fetchMoodData(currentUserId());
    // auto-refresh every 10s
    setInterval(() => fetchMoodData(currentUserId()), 10000);
  })();

  // Notification badge helpers
  function updateNotifBadge(count) {
    const el = document.getElementById('notifCount');
    if (!el) return;
    const c = parseInt(count || 0, 10);
    if (c > 0) {
      el.textContent = c;
      el.style.display = 'inline-block';
    } else {
      el.style.display = 'none';
    }
  }
  function isNotifModalOpen() {
    const modal = document.getElementById('notifModal');
    return modal && modal.classList.contains('show');
  }

  // Polling fallback
  function checkNotifications() {
    $.get('check_notifications.php?type=assessment', function(count) {
      updateNotifBadge(count);
    });
  }
  var pollTimer = null;
  function startPolling() {
    if (!pollTimer) pollTimer = setInterval(checkNotifications, 5000);
  }
  startPolling();

  function loadNotifications() {
    $.get('get_notifications.php', function(data) {
      $('#notifList').html(data);
      // Badge will reset on next SSE/poll tick if endpoint marks read
    });
  }

  // Auto-refresh notifications while modal is open
  (function() {
    var modalEl = document.getElementById('notifModal');
    var timer = null;
    if (!modalEl) return;
    modalEl.addEventListener('shown.bs.modal', function() {
      loadNotifications();
      if (timer) clearInterval(timer);
      timer = setInterval(loadNotifications, 5000);
    });
    modalEl.addEventListener('hidden.bs.modal', function() {
      if (timer) { clearInterval(timer); timer = null; }
    });
  })();

  // Real-time notifications via SSE (Server-Sent Events)
  (function setupRealtimeNotifications() {
    if (!('EventSource' in window)) return; // Older browsers: polling only

    try {
      const source = new EventSource('notifications_stream.php');

      source.onopen = function() {
        // SSE connected; stop polling to save resources
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
      };

      source.addEventListener('count', function (evt) {
        let data = { count: 0 };
        try { data = JSON.parse(evt.data || '{}'); } catch (_e) {}
        updateNotifBadge(data.count);
        if (data.count > 0 && isNotifModalOpen()) {
          loadNotifications();
        }
      });

      source.onerror = function() {
        // If connection is closed, re-enable polling until browser reconnects
        if (source.readyState === 2 /* CLOSED */) {
          startPolling();
        }
        // The browser will attempt to reconnect automatically
      };
    } catch (e) {
      // If something goes wrong, your existing polling keeps working
      startPolling();
    }
  })();

  // Legacy assessment form hooks (kept for compatibility)
  let qIndex = 0;
  function addQuestion() {
    qIndex++;
    let html = `
      <div class="assessment-question">
        <label><b>Question ${qIndex}</b></label>
        <textarea class="form-control mb-2" name="questions[${qIndex}]" placeholder="Enter your question..." required></textarea>
      </div>
    `;
    $('#questionsContainer').append(html);
  }
  if (document.getElementById('questionsContainer')) {
    addQuestion();
  }

  $('#assessmentForm').on('submit', function(e) {
    e.preventDefault();
    $.post('save_assessment.php', $(this).serialize(), function(response) {
      alert(response);
      $('#assessmentModal').modal('hide');
      $('#questionsContainer').html("");
      qIndex = 0; addQuestion();
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>