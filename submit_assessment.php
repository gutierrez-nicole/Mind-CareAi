<?php
// submit_assessment.php
// If your project uses save_assessment.php instead, copy this logic there.
// Calculates severity percentage (0% = no symptoms, 100% = severe) and stores it.

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/dbcon.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$assessment_id = isset($payload['assessment_id']) ? (int)$payload['assessment_id'] : 0;
$answers = isset($payload['answers']) && is_array($payload['answers']) ? $payload['answers'] : [];

if ($assessment_id <= 0) {
    echo json_encode(['error' => 'Invalid assessment_id']);
    exit;
}
if (empty($answers)) {
    echo json_encode(['error' => 'No answers provided']);
    exit;
}

// TODO: Adjust table name to your schema.
// Assuming a table 'assessment_questions' with columns (id, assessment_id, question_text)
$qids = [];
if ($stmt = $conn->prepare('SELECT id FROM assessment_questions WHERE assessment_id = ?')) {
    $stmt->bind_param('i', $assessment_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $qids[(int)$row['id']] = true;
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'Failed to prepare questions query']);
    exit;
}

if (empty($qids)) {
    echo json_encode(['error' => 'No questions found for this assessment']);
    exit;
}

// Normalize and validate answers: only accept questions belonging to this assessment and values 0..3
$validAnswers = [];
foreach ($answers as $qid => $val) {
    $qid = (int)$qid;
    $val = (int)$val;
    if (!isset($qids[$qid])) continue;
    if ($val < 0 || $val > 3) continue;
    $validAnswers[$qid] = $val;
}

$totalQuestions = count($qids);
$answeredCount = count($validAnswers);
if ($answeredCount !== $totalQuestions) {
    echo json_encode(['error' => 'Please answer all questions before submitting.']);
    exit;
}

// Sum severity points (0..3 each)
$sum = 0;
foreach ($validAnswers as $v) { $sum += $v; }

$max = $totalQuestions * 3;
$severityPct = $max > 0 ? (int)round(($sum / $max) * 100) : 0;
$severityPct = max(0, min(100, $severityPct));

$category = derive_category($severityPct);

// Check if result already exists (prevent duplicates)
$existing = null;
if ($stmt = $conn->prepare('SELECT id, score, category FROM assessment_results WHERE user_id = ? AND assessment_id = ? LIMIT 1')) {
    $stmt->bind_param('ii', $user_id, $assessment_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();
}

$shouldNotify = false;
$notif_result = null;

if ($existing) {
    // Optionally, update category if thresholds changed
    if ($existing['category'] !== $category || (int)$existing['score'] !== $severityPct) {
        if ($stmt = $conn->prepare('UPDATE assessment_results SET score = ?, category = ?, updated_at = NOW() WHERE id = ?')) {
            $stmt->bind_param('isi', $severityPct, $category, $existing['id']);
            if (!$stmt->execute()) {
                // continue but warn
            }
            $stmt->close();
            $shouldNotify = true;
        }
    } else {
        echo json_encode([
            'already_completed' => true,
            'score' => $severityPct,
            'category' => $category
        ]);
        exit;
    }
} else {
    // Insert new result
    if ($stmt = $conn->prepare('INSERT INTO assessment_results (user_id, assessment_id, score, category, created_at) VALUES (?, ?, ?, ?, NOW())')) {
        $stmt->bind_param('iiis', $user_id, $assessment_id, $severityPct, $category);
        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Failed to save result']);
            $stmt->close();
            exit;
        }
        $stmt->close();
        $shouldNotify = true;
    } else {
        echo json_encode(['error' => 'Failed to prepare save statement']);
        exit;
    }
}

// If we inserted/updated, create admin notification row and send email
if ($shouldNotify) {
    // Fetch friendly user name and assessment title (best-effort)
    $userName = '';
    if ($stmt = $conn->prepare('SELECT name FROM users WHERE id = ? LIMIT 1')) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $r = $res->fetch_assoc();
        if ($r) $userName = $r['name'];
        $stmt->close();
    }
    $assessmentTitle = '';
    if ($stmt = $conn->prepare('SELECT title FROM assessments WHERE id = ? LIMIT 1')) {
        $stmt->bind_param('i', $assessment_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $r = $res->fetch_assoc();
        if ($r) $assessmentTitle = $r['title'];
        $stmt->close();
    }

    $notifTitle = sprintf('%s — %s: %d%%', $assessmentTitle ?: 'Assessment', $userName ?: 'User', $severityPct);
    $notifBody = sprintf('User: %s\nAssessment: %s\nScore: %d%%\nCategory: %s', $userName ?: 'User', $assessmentTitle ?: 'Assessment', $severityPct, $category);
    $notifLink = '/dashboard/admin_dashboard.php'; // relative link admin can follow

    if ($stmt = $conn->prepare('INSERT INTO admin_notifications (title, body, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())')) {
        $stmt->bind_param('sss', $notifTitle, $notifBody, $notifLink);
        $stmt->execute();
        $stmt->close();
    }

    // Send email to admin using NotificationService (best-effort)
    // service is optional; ignore failures but return status
    try {
        require_once __DIR__ . '/config/notifications.php';
        $svc = new NotificationService();
        $subject = "New assessment result: " . ($assessmentTitle ?: 'Assessment');
        $html = "<h3>New assessment result</h3>"
              . "<p><b>User:</b> " . htmlspecialchars($userName) . "</p>"
              . "<p><b>Assessment:</b> " . htmlspecialchars($assessmentTitle) . "</p>"
              . "<p><b>Score:</b> {$severityPct}%</p>"
              . "<p><b>Category:</b> {$category}</p>"
              . "<p>Open admin dashboard: <a href='" . htmlspecialchars($notifLink) . "'>Dashboard</a></p>";
        $emailResult = $svc->sendEmail($subject, $html);
        $notif_result = $emailResult;
    } catch (Throwable $e) {
        $notif_result = ['success' => false, 'error' => $e->getMessage()];
    }
}

echo json_encode(array_filter([
    'score' => $severityPct,
    'category' => $category,
    'email' => $notif_result
], function($v){ return $v !== null; }));

function derive_category(int $severityPct): string {
    if ($severityPct <= 20) return 'Good';
    if ($severityPct <= 50) return 'Fair';
    return 'Bad';
}