<?php
session_start();
require_once '../config/dbcon.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? '';

// 🗑 Clear History
if (isset($_POST['clear'])) {
  $clear = $conn->prepare("DELETE FROM chat_logs WHERE user_id = ?");
  if ($clear) {
    $clear->bind_param("i", $user_id);
    $clear->execute();
    $clear->close();
    header("Location: chat_history.php");
    exit;
  }
}

// ✅ Fetch chat logs
$stmt = $conn->prepare("SELECT message, reply, mode, timestamp FROM chat_logs WHERE user_id = ? ORDER BY timestamp DESC");
if (!$stmt) {
  die("❌ SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Chat History - MindCare AI</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f6f9;
      font-family: 'Segoe UI', sans-serif;
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .table th, .table td {
      vertical-align: middle;
    }
    .badge-basic {
      background-color: #6C63FF;
    }
    .badge-pro {
      background-color: #ff6b6b;
    }
    .table-responsive {
      max-height: 600px;
      overflow-y: auto;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>Chat History - <?= htmlspecialchars($name) ?></h4>
      <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Chatbot</a>
        <form method="POST" onsubmit="return confirm('Clear all your chat history?');">
          <button type="submit" name="clear" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Clear History</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle m-0">
            <thead class="table-dark sticky-top">
              <tr>
                <th style="width: 25%">User Prompt</th>
                <th style="width: 35%">AI Response</th>
                <th style="width: 10%">Mode</th>
                <th style="width: 20%">Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['reply'])) ?></td>
                    <td>
                      <?php if ($row['mode'] === 'pro'): ?>
                        <span class="badge badge-pro text-white px-3 py-2">Pro</span>
                      <?php else: ?>
                        <span class="badge badge-basic text-white px-3 py-2">Basic</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= date("M d, Y - h:i A", strtotime($row['timestamp'])) ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">No chat history found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
