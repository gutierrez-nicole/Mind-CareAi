<?php
// get_assessment_result.php — returns the current user's result for a specific assessment as JSON

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/dbcon.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$assessment_id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
if ($assessment_id <= 0) {
    echo json_encode(['error' => 'invalid assessment_id']);
    exit;
}

$data = null;
if ($stmt = $conn->prepare('SELECT ar.score, ar.category, a.title FROM assessment_results ar JOIN assessments a ON a.id = ar.assessment_id WHERE ar.user_id = ? AND ar.assessment_id = ? LIMIT 1')) {
    $stmt->bind_param('ii', $user_id, $assessment_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $data = [
                'score' => (int)($row['score'] ?? 0),
                'category' => (string)($row['category'] ?? ''),
                'title' => (string)($row['title'] ?? ''),
            ];
        }
    }
    $stmt->close();
}

if (!$data) {
    echo json_encode(['error' => 'no_result']);
    exit;
}

echo json_encode($data);
