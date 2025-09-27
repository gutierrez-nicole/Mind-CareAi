<?php
session_start();
require_once '../config/dbcon.php'; // Database connection

$incomingPrompt = trim($_POST['user_prompt'] ?? ($_POST['prompt'] ?? ''));
$reason = trim($_POST['reason'] ?? '');

if (empty($incomingPrompt)) {
  echo "Please enter a message.";
  exit;
}

// Extract raw user message if frontend sent a composed prompt with a Message: marker
$userPrompt = $incomingPrompt;
if (stripos($incomingPrompt, 'Message:') !== false) {
  if (preg_match('/Message:\s*(.+)$/is', $incomingPrompt, $m)) {
    $userPrompt = trim($m[1]);
  }
}

// ❌ Block off-topic terms
$off_topic = ['nba', 'basketball', 'politics', 'movie', 'celebrity', 'tiktok', 'crypto'];
foreach ($off_topic as $term) {
  if (stripos($userPrompt, $term) !== false) {
    echo "⚠️ Sorry, I can only help with mental health topics.";
    exit;
  }
}

// 🚫 Guest message limit
if (!isset($_SESSION['user_id'])) {
  $_SESSION['guest_count'] = ($_SESSION['guest_count'] ?? 0) + 1;
  if ($_SESSION['guest_count'] > 5) {
    echo "⚠️ You’ve reached the guest message limit. Please log in.";
    exit;
  }
}

// ✅ Groq API call with language-matching behavior
function detect_language($text) {
  $t = mb_strtolower(' ' . $text . ' ', 'UTF-8');
  $markers = [' ang ', ' mga ', ' ng ', ' sa ', ' hindi', ' oo', ' po', ' opo', ' sige', ' kamusta', ' kumusta', ' salamat', ' sana ', ' kasi', ' naman', ' lang ', ' pala', ' wag', ' huwag', ' talaga', ' basta', ' pero', ' tsaka', ' ganyan', ' ganun', ' ganon', ' ako', ' ikaw', ' siya', ' natin', ' namin', ' ninyo'];
  $count = 0;
  foreach ($markers as $m) {
    if (mb_strpos($t, $m, 0, 'UTF-8') !== false) $count++;
  }
  return $count >= 2 ? 'tl' : 'en';
}
$lang = detect_language($userPrompt);

$secretKey = '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';
$endpoint = 'https://api.groq.com/openai/v1/chat/completions';

$messages = [
  [
    'role' => 'system',
    'content' => "You are a kind and professional mental health assistant.\n- Always reply in the same language as the user's message (language={$lang}).\n- If language=tl (Tagalog/Filipino), use natural, conversational Tagalog (hindi malalim, hindi sobrang pormal). Prefer simple everyday words.\n- If language=en, reply in clear, friendly English.\n- Keep responses supportive and focused only on mental wellness.\n- Do not translate the user's language; match it."
  ]
];
if (!empty($reason)) {
  $messages[] = [
    'role' => 'system',
    'content' => "Context to consider (do not change language): Reason/Referral: {$reason}"
  ];
}
$messages[] = [ 'role' => 'user', 'content' => $userPrompt ];

$data = [
  'messages' => $messages,
  'model' => 'llama-3.1-8b-instant'
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Authorization: Bearer ' . $secretKey,
  'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
if ($response === false) {
  echo "⚠️ Error: " . curl_error($ch);
  exit;
}
curl_close($ch);

// ✅ Decode and extract AI reply
$json = json_decode($response, true);
$reply = trim($json['choices'][0]['message']['content'] ?? '⚠️ Error occurred.');

echo "<strong>AI:</strong> " . htmlspecialchars($reply);

// ✅ Log to database if user is logged in
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, message, reply, mode) VALUES (?, ?, ?, 'basic')");
  if ($stmt) {
    $stmt->bind_param("iss", $user_id, $userPrompt, $reply);
    $stmt->execute();
    $stmt->close();
  }
}
