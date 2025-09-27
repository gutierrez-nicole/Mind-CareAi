<?php
// scripts/test_notifications.php
require_once __DIR__ . '/../config/notifications.php';

$svc = new NotificationService();

// Simulate an assessment percentage that crosses the threshold
$assessmentPercent = (float)($argv[1] ?? 85);
$threshold         = (float)($argv[2] ?? 80);
$context           = $argv[3] ?? 'MindCare AI Assessment';

$result = $svc->notifyOnAssessment($assessmentPercent, $threshold, [
    // Optional override: 'to_email' => 'someone@example.com',
    'context' => $context,
]);

echo "Triggered: " . ($result['triggered'] ? 'yes' : 'no') . PHP_EOL;

if (!empty($result['email'])) {
    echo "Email: " . ($result['email']['success'] ? 'sent' : 'failed') . PHP_EOL;
    if (!$result['email']['success']) {
        echo "Email Error: " . ($result['email']['error'] ?? 'unknown') . PHP_EOL;
    }
}

if (!empty($result['facebook'])) {
    echo "Facebook: " . ($result['facebook']['success'] ? 'sent' : 'not sent (placeholder)') . PHP_EOL;
    if (!empty($result['facebook']['error'])) {
        echo "Facebook Info: " . $result['facebook']['error'] . PHP_EOL;
        if (!empty($result['facebook']['info'])) {
            echo "Target: " . $result['facebook']['info'] . PHP_EOL;
        }
    }
}