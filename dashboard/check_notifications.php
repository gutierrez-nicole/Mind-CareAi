<?php
// /dashboard/check_notifications.php
session_start();
require_once __DIR__ . '/../config/dbcon.php';

// Only admins should access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo '0';
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$type = strtolower($_GET['type'] ?? '');
$where = "is_read = 0";
if ($type === 'assessment') {
    $where .= " AND (LOWER(title) LIKE '%assessment%' OR LOWER(body) LIKE '%assessment%' OR LOWER(title) LIKE '%mood%' OR LOWER(body) LIKE '%mood%')";
}

$count = 0;
if ($conn && !$conn->connect_errno) {
    $sql = "SELECT COUNT(*) AS c FROM admin_notifications WHERE $where";
    if ($res = $conn->query($sql)) {
        $row = $res->fetch_assoc();
        $count = (int)($row['c'] ?? 0);
        $res->free();
    }
}

echo (string)$count;