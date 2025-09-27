<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/dbcon.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

if (!isset($conn)) {
  echo json_encode(['success' => false, 'error' => 'Database unavailable']);
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$message = trim($_POST['message'] ?? '');
$mode = trim($_POST['mode'] ?? 'basic');
$reason = trim($_POST['reason'] ?? '');

if ($id <= 0 || $message === '') {
  echo json_encode(['success' => false, 'error' => 'Invalid payload']);
  exit;
}

// Verify row ownership and get previous row (optional)
$stmt = $conn->prepare("SELECT id, user_id, mode FROM chat_logs WHERE id = ? AND user_id = ?");
if (!$stmt) {
  echo json_encode(['success' => false, 'error' => 'Prepare failed']);
  exit;
}
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$log = $res->fetch_assoc();
$stmt->close();

if (!$log) {
  echo json_encode(['success' => false, 'error' => 'Not found']);
  exit;
}

// Off-topic guard (same as basic.php)
$off_topic = ['nba', 'basketball', 'politics', 'movie', 'celebrity', 'tiktok', 'crypto'];
foreach ($off_topic as $term) {
  if (stripos($message, $term) !== false) {
    echo json_encode(['success' => false, 'error' => 'Off-topic content not allowed.']);
    exit;
  }
}

// Language detection helper (same as endpoints)
function detect_language($text) {
  $t = mb_strtolower(' ' . $text . ' ', 'UTF-8');
  $markers = [' ang ', ' mga ', ' ng ', ' sa ', ' hindi', ' oo', ' po', ' opo', ' sige', ' kamusta', ' kumusta', ' salamat', ' sana ', ' kasi', ' naman', ' lang ', ' pala', ' wag', ' huwag', ' talaga', ' basta', ' pero', ' tsaka', ' ganyan', ' ganun', ' ganon', ' ako', ' ikaw', ' siya', ' natin', ' namin', ' ninyo'];
  $count = 0;
  foreach ($markers as $m) { if (mb_strpos($t, $m, 0, 'UTF-8') !== false) $count++; }
  return $count >= 2 ? 'tl' : 'en';
}

$lang = detect_language($message);

// Decide model and system prompt by mode
$secretKey = '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';
$endpoint = 'https://api.groq.com/openai/v1/chat/completions';

if ($mode === 'professional' || $mode === 'pro') {
  $sys = "You are a mental health professional.\n- Always reply in the same language as the user's message (language={$lang}).\n- If language=tl (Tagalog/Filipino), use natural, conversational Tagalog (hindi malalim, hindi sobrang pormal). Prefer simple everyday words.\n- If language=en, reply in clear, friendly English.\n- Keep responses supportive and focused only on mental wellness.\n- Do not translate the user's language; match it.";
  $model = 'llama3-8b-8192';
} else {
  $sys = "You are a kind and professional mental health assistant.\n- Always reply in the same language as the user's message (language={$lang}).\n- If language=tl (Tagalog/Filipino), use natural, conversational Tagalog (hindi malalim, hindi sobrang pormal). Prefer simple everyday words.\n- If language=en, reply in clear, friendly English.\n- Keep responses supportive and focused only on mental wellness.\n- Do not translate the user's language; match it.";
  $model = 'llama-3.1-8b-instant';
}

$messages = [ [ 'role' => 'system', 'content' => $sys ] ];
if (!empty($reason)) {
  $messages[] = [ 'role' => 'system', 'content' => "Context to consider (do not change language): Reason/Referral: {$reason}" ];
}
$messages[] = [ 'role' => 'user', 'content' => $message ];

$payload = [ 'messages' => $messages, 'model' => $model ];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Authorization: Bearer ' . $secretKey,
  'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
if ($response === false) {
  echo json_encode(['success' => false, 'error' => 'AI service error: ' . curl_error($ch)]);
  exit;
}
curl_close($ch);

$json = json_decode($response, true);
$reply = trim($json['choices'][0]['message']['content'] ?? 'AI Error');

// Update the existing row (keep original timestamp)
$mode_to_save = ($mode === 'professional' || $mode === 'pro') ? 'professional' : 'basic';
$upd = $conn->prepare("UPDATE chat_logs SET message = ?, reply = ?, mode = ? WHERE id = ? AND user_id = ?");
if (!$upd) {
  echo json_encode(['success' => false, 'error' => 'Update prepare failed']);
  exit;
}
$upd->bind_param("sssii", $message, $reply, $mode_to_save, $id, $user_id);
$ok = $upd->execute();
$err = $upd->error;
$upd->close();

if (!$ok) {
  echo json_encode(['success' => false, 'error' => $err ?: 'Update failed']);
  exit;
}

echo json_encode(['success' => true, 'reply' => $reply]);
