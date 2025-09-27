<?php
// load_questions.php — returns assessment questions as a plain JSON array (matching assessment.php expectations)

// Force JSON and suppress HTML/warnings in output
header('Content-Type: application/json; charset=utf-8');
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');

// Buffer any incidental output from includes (e.g., scripts injected elsewhere)
ob_start();
require_once __DIR__ . '/config/dbcon.php';
ob_end_clean();

$assessment_id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
if ($assessment_id <= 0) {
    echo json_encode([]);
    exit;
}

$rows = [];

// Preferred schema: assessment_questions(id, assessment_id, question_text)
if ($stmt = $conn->prepare('SELECT id, question_text FROM assessment_questions WHERE assessment_id = ? ORDER BY id ASC')) {
    $stmt->bind_param('i', $assessment_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)($r['id'] ?? 0),
                'question_text' => (string)($r['question_text'] ?? ''),
            ];
        }
    }
    $stmt->close();
}

// Fallback to legacy schema: questions(id, assessment_id, question)
if (empty($rows)) {
    if ($stmt = $conn->prepare('SELECT id, question FROM questions WHERE assessment_id = ? ORDER BY id ASC')) {
        $stmt->bind_param('i', $assessment_id);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'id' => (int)($r['id'] ?? 0),
                    'question_text' => (string)($r['question'] ?? ''),
                ];
            }
        }
        $stmt->close();
    }
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
