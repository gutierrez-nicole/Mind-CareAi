<?php
// /dashboard/notifications_stream.php
session_start();
require_once __DIR__ . '/../config/dbcon.php';

// SSE headers
header('Content-Type: text/event-stream; charset=UTF-8');
header('Cache-Control: no-cache, no-transform');
header('Connection: keep-alive');
// Disable buffering on proxies like Nginx
header('X-Accel-Buffering: no');

// Admin-only (adjust role check to your app if needed)
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    echo "event: count\n";
    echo 'data: {"count":0}' . "\n\n";
    @ob_flush(); @flush();
    exit;
}

// Release the session lock so other requests can proceed while this stream is open
session_write_close();

ignore_user_abort(true);
set_time_limit(0);

// Helper to get current unread count from your admin_notifications schema
function get_unread_count(mysqli $conn): int {
    if ($conn->connect_errno) return 0;

    // Assessment-only by default
    $sql = "SELECT COUNT(*) AS c FROM admin_notifications WHERE is_read = 0 AND (LOWER(title) LIKE '%assessment%' OR LOWER(body) LIKE '%assessment%' OR LOWER(title) LIKE '%mood%' OR LOWER(body) LIKE '%mood%')";
    $count = 0;
    if ($res = $conn->query($sql)) {
        $row = $res->fetch_assoc();
        $count = (int)($row['c'] ?? 0);
        $res->free();
    }
    return $count;
}

// Clean output buffers for streaming
while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(true);

// Hint the client to retry after disconnects (ms)
echo "retry: 5000\n\n";
@ob_flush(); @flush();

$iterations = 0;
$lastCount = -1;

// Stream for up to ~30 minutes; emit every 5s and send periodic keepalive
while (!connection_aborted() && $iterations < 360) {
    // If the DB connection dropped, stop cleanly
    if (!$conn || !$conn->ping()) {
        echo "event: count\n";
        echo 'data: {"count":0}' . "\n\n";
        @ob_flush(); @flush();
        break;
    }

    $count = get_unread_count($conn);

    // Send when changed or every ~30s to keep the connection alive
    if ($count !== $lastCount || ($iterations % 6) === 0) {
        echo "event: count\n";
        echo 'data: {"count":' . $count . "}\n\n";
        @ob_flush(); @flush();
        $lastCount = $count;
    } else {
        // Lightweight heartbeat comment to prevent idle timeouts on some proxies
        echo ": heartbeat\n\n";
        @ob_flush(); @flush();
    }

    $iterations++;
    sleep(5);
}