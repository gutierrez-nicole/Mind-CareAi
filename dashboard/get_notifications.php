<?php
// /dashboard/get_notifications.php
session_start();
require_once __DIR__ . '/../config/dbcon.php';

// Admin-only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  http_response_code(403);
  echo '<p class="text-muted text-center mb-0">Forbidden</p>';
  exit;
}

// Mark all admin notifications as read when opening modal
if ($conn && !$conn->connect_errno) {
  $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
}

// Helper to format relative time
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
    if ($diff->days <= 7) return $diff->days . ' day' . ($diff->days == 1 ? '' : 's') . ' ago';
    return $dt->format('M j, Y H:i');
  } catch (Exception $e) { return htmlspecialchars($dtstr); }
}

// Fetch latest admin notifications (including mood tracker alerts)
$items = [];
$usedFallback = false; // if true, $items contains assessment_results rows
if ($conn && !$conn->connect_errno) {
  $sql = "SELECT id, title, body, link, created_at, is_read FROM admin_notifications ORDER BY created_at DESC LIMIT 100";
  if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) { $items[] = $r; }
    $res->free();
  }
  // Fallback: if no admin_notifications exist yet, show recent assessment results
  if (empty($items)) {
    $sql2 = "SELECT ar.id, ar.score, ar.category, ar.created_at, u.name AS user_name, a.title AS assessment_title
             FROM assessment_results ar
             JOIN users u ON u.id = ar.user_id
             JOIN assessments a ON a.id = ar.assessment_id
             ORDER BY ar.created_at DESC
             LIMIT 50";
    if ($res2 = $conn->query($sql2)) {
      while ($r2 = $res2->fetch_assoc()) { $items[] = $r2; }
      $res2->free();
      $usedFallback = true;
    }
  }
}

ob_start();
if (empty($items)) {
  echo '<p class="text-muted text-center mb-0">No notifications yet</p>';
} else if ($usedFallback) {
  // Render recent assessment submissions (fallback when admin_notifications is empty)
  echo '<div class="list-group">';
  foreach ($items as $it) {
    $user = htmlspecialchars($it['user_name'] ?? '');
    $title = htmlspecialchars($it['assessment_title'] ?? '');
    $score = (int)($it['score'] ?? 0);
    $cat   = htmlspecialchars($it['category'] ?? '');
    $time  = _rel_time($it['created_at'] ?? '');
    $catLower = strtolower($it['category'] ?? '');
    $cls = 'bg-secondary';
    if ($catLower === 'good') $cls = 'bg-success';
    elseif ($catLower === 'fair') $cls = 'bg-warning text-dark';
    elseif ($catLower === 'bad') $cls = 'bg-danger';

    echo '<div class="list-group-item d-flex justify-content-between align-items-start">';
      echo '<div><strong>' . $user . '</strong> — ' . $score . '% <span class="badge ' . $cls . '">' . $cat . '</span><br><small class="text-muted">' . $title . '</small></div>';
      echo '<small class="text-muted ms-3">' . $time . '</small>';
    echo '</div>';
  }
  echo '</div>';
} else {
  // Render admin_notifications
  echo '<div class="list-group">';
  foreach ($items as $it) {
    $title = htmlspecialchars($it['title'] ?? '');
    $body  = htmlspecialchars($it['body'] ?? '');
    $link  = trim($it['link'] ?? '');
    $time  = _rel_time($it['created_at'] ?? '');

    echo '<div class="list-group-item d-flex justify-content-between align-items-start">';
      echo '<div>';
        echo '<div class="fw-semibold">' . $title . '</div>';
        if ($body !== '') {
          echo '<div class="small text-muted mb-1" style="white-space: pre-line;">' . $body . '</div>';
        }
        if ($link !== '') {
          $href = htmlspecialchars($link);
          echo '<a class="small" href="' . $href . '">Open</a>';
        }
      echo '</div>';
      echo '<small class="text-muted ms-3">' . $time . '</small>';
    echo '</div>';
  }
  echo '</div>';
}
$html = ob_get_clean();

header('Content-Type: text/html; charset=UTF-8');
echo $html;