<?php
session_start();
require_once '../config/dbcon.php';

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

// ✅ Static Responses (English + Tagalog)
$data = [
  "i feel anxious" => "I'm here for you. It's okay to feel anxious. Would you like some grounding techniques?",
  "i need comfort" => "You're not alone. Take a deep breath — you're doing the best you can.",
  "i am stressed" => "Try pausing for a moment. Would you like to try a 5-minute breathing exercise?",
  "i want to cry" => "Letting emotions out is healthy. I can suggest journaling prompts or breathing tips.",
  "i feel lonely" => "You are not alone. I'm here to listen. Want to talk about what’s bothering you?",
  "naiiyak ako" => "Okay lang yan. Ang pag-iyak ay nakakatulong. Gusto mo bang mag-journal o mag-relax muna?",
  "nalulungkot ako" => "Nararamdaman ko ang lungkot mo. Huminga tayo ng malalim. Gusto mo bang mag-reflect o mag-music?",
  "i'm tired" => "Rest is important. Would you like to take a mindful pause together?",
  "ayoko na" => "Huminga ka muna. Huwag kang mag-isa sa laban mo. Nandito ako para tumulong."
];

// ✅ Static match (case-insensitive)
$response = null;
$hay = mb_strtolower($userPrompt, 'UTF-8');
foreach ($data as $key => $value) {
  if (mb_strpos($hay, mb_strtolower($key, 'UTF-8')) !== false) {
    $response = $value;
    break;
  }
}

// ✅ If no match → Ask Groq with language-matching behavior
if (!$response) {
  // Language detection
  function detect_language($text) {
    $t = mb_strtolower(' ' . $text . ' ', 'UTF-8');
    $markers = [' ang ', ' mga ', ' ng ', ' sa ', ' hindi', ' oo', ' po', ' opo', ' sige', ' kamusta', ' kumusta', ' salamat', ' sana ', ' kasi', ' naman', ' lang ', ' pala', ' wag', ' huwag', ' talaga', ' basta', ' pero', ' tsaka', ' ganyan', ' ganun', ' ganon', ' ako', ' ikaw', ' siya', ' natin', ' namin', ' ninyo'];
    $count = 0;
    foreach ($markers as $m) { if (mb_strpos($t, $m, 0, 'UTF-8') !== false) $count++; }
    return $count >= 2 ? 'tl' : 'en';
  }
  $lang = detect_language($userPrompt);

  $secretKey = '';
  $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

  $messages = [
    [
      'role' => 'system',
      'content' => "You are a mental health professional.\n- Always reply in the same language as the user's message (language={$lang}).\n- If language=tl (Tagalog/Filipino), use natural, conversational Tagalog (hindi malalim, hindi sobrang pormal). Prefer simple everyday words.\n- If language=en, reply in clear, friendly English.\n- Keep responses supportive and focused only on mental wellness.\n- Do not translate the user's language; match it."
    ]
  ];
  if (!empty($reason)) {
    $messages[] = [ 'role' => 'system', 'content' => "Context to consider (do not change language): Reason/Referral: {$reason}" ];
  }
  $messages[] = [ 'role' => 'user', 'content' => $userPrompt ];

  $data = [
    'messages' => $messages,
    'model' => 'llama3-8b-8192'
  ];

  $ch = curl_init($endpoint);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secretKey,
    'Content-Type: application/json'
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

  $res = curl_exec($ch);
  if ($res === false) {
    echo "⚠️ Error: " . curl_error($ch);
    exit;
  }
  curl_close($ch);

  $json = json_decode($res, true);
  $response = trim($json['choices'][0]['message']['content'] ?? '⚠️ AI Error: No response.');
}

// ✅ Log to chat_logs if logged in
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, message, reply, mode) VALUES (?, ?, ?, 'professional')");
  if ($stmt) {
    $stmt->bind_param("iss", $_SESSION['user_id'], $userPrompt, $response);
    $stmt->execute();
    $stmt->close();
  }
}

// ✅ Show output
echo "<strong>AI:</strong> " . htmlspecialchars($response);
