<?php


session_start();
require_once '../config/dbcon.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$emotion = trim($_POST['emotion'] ?? '');
$percentage = isset($_POST['percentage']) ? floatval($_POST['percentage']) : null;
$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;

if ($emotion === '' || $percentage === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing emotion or percentage']);
    exit;
}

try {
    // Use schema: (user_id, emotion, percentage, created_at)
    $stmt = $conn->prepare("INSERT INTO mood_logs (user_id, emotion, percentage, created_at) VALUES (?, ?, ?, NOW())");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param("isd", $user_id, $emotion, $percentage);
    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        // Also create an admin notification (admin-only UI already implemented)
        try {
            $userName = '';
            if ($st = $conn->prepare('SELECT name FROM users WHERE id = ? LIMIT 1')) {
                $st->bind_param('i', $user_id);
                $st->execute();
                $rs = $st->get_result();
                if ($r = $rs->fetch_assoc()) $userName = $r['name'] ?? '';
                $st->close();
            }
            $title = sprintf('Mood: %s — %s (%.2f%%)', ucfirst($emotion), $userName ?: 'User', (float)$percentage);
            $body  = sprintf("User: %s\nEmotion: %s\nShare: %.2f%%", $userName ?: 'User', $emotion, (float)$percentage);
            $link  = '/dashboard/admin_dashboard.php';
            if ($nn = $conn->prepare('INSERT INTO admin_notifications (title, body, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())')) {
                $nn->bind_param('sss', $title, $body, $link);
                $nn->execute();
                $nn->close();
            }
        } catch (Throwable $e) {
            // ignore notification errors silently
        }
        echo json_encode(['success' => true, 'id' => $insertId]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>