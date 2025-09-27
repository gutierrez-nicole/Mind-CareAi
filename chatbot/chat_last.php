<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/dbcon.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

if (!isset($conn)) {
  echo json_encode(['success' => false, 'error' => 'Database unavailable']);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, mode FROM chat_logs WHERE user_id = ? ORDER BY timestamp DESC, id DESC LIMIT 1");
if (!$stmt) {
  echo json_encode(['success' => false, 'error' => 'Prepare failed']);
  exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if ($row) {
  echo json_encode([
    'success' => true,
    'data' => [
      'id' => (int)$row['id'],
      'mode' => $row['mode'] ?? 'basic'
    ]
  ]);
} else {
  echo json_encode(['success' => false, 'error' => 'No logs found']);
}
