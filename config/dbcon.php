<?php
require_once __DIR__ . '/session.php';
$host = "localhost";
$username = "root";
$password = "";
$database = "mindcare_ai";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
if (isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') !== 'admin')) {
  // Inject floating mood widget script for logged-in users only (not admins)
  // Only for HTML page responses (avoid breaking JSON APIs like load_questions.php)
  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  $isCli = (PHP_SAPI === 'cli');
  $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
  $isAjax = strtolower($xhr) === 'xmlhttprequest';
  $isHtml = (stripos($accept, 'text/html') !== false) && !$isAjax;
  if (!$isCli && $isHtml) {
    echo '<script src="/MindCare-AI/assets/js/floating_mood_widget.js?v=1" defer></script>';
  }
}
?>
