<?php
session_start();
require_once __DIR__ . '/../config/dbcon.php';

header('Content-Type: application/json; charset=UTF-8');

// Admin-only endpoint
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
  http_response_code(403);
  echo json_encode(['error' => 'Forbidden']);
  exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$labels = [];
$counts = [];

try {
  if ($conn && !$conn->connect_errno) {
    if ($userId > 0) {
      $sql = "SELECT emotion, COUNT(*) AS total FROM mood_logs WHERE user_id = ? GROUP BY emotion ORDER BY total DESC";
      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
          $res = $stmt->get_result();
          while ($row = $res->fetch_assoc()) { $labels[] = $row['emotion']; $counts[] = (int)$row['total']; }
        }
        $stmt->close();
      }
    } else {
      $sql = "SELECT emotion, COUNT(*) AS total FROM mood_logs GROUP BY emotion ORDER BY total DESC";
      if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) { $labels[] = $row['emotion']; $counts[] = (int)$row['total']; }
        $res->free();
      }
    }
  }
} catch (Throwable $e) {
  // return empty arrays on error
}

echo json_encode(['labels' => $labels, 'counts' => $counts], JSON_UNESCAPED_UNICODE);
