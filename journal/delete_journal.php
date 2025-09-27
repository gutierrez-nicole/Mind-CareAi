<?php
session_start();
require_once '../config/dbcon.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$entry_id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM journals WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();

header("Location: journal.php");
exit();
