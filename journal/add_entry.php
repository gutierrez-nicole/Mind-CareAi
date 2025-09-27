<?php
session_start();
require_once '../config/dbcon.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title && $content) {
        $stmt = $conn->prepare("INSERT INTO journals (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $user_id, $title, $content);
        $stmt->execute();
    }
    header("Location: journal.php");
    exit();
}
?>
