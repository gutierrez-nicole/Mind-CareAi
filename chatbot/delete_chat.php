<?php

session_start();
require_once '../config/dbcon.php';

header('Content-Type: application/json; charset=utf-8');

// Require POST and logged-in user
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
  echo json_encode(['success' => false, 'error' => 'Invalid id']);
  exit;
}

if (!isset($conn)) {
  echo json_encode(['success' => false, 'error' => 'Database unavailable']);
  exit;
}

$stmt = $conn->prepare("DELETE FROM chat_logs WHERE id = ? AND user_id = ?");
if (!$stmt) {
  echo json_encode(['success' => false, 'error' => 'Prepare failed']);
  exit;
}

$stmt->bind_param("ii", $id, $user_id);
$ok = $stmt->execute();
$err = $stmt->error;
$stmt->close();

if ($ok) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => $err ?: 'Delete failed']);
}
?>