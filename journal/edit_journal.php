<?php
session_start();
require_once '../config/dbcon.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$entry_id = intval($_GET['id']);

// Get the entry
$stmt = $conn->prepare("SELECT * FROM journals WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();

if (!$entry) {
    die("Entry not found or access denied.");
}

// Update on submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title && $content) {
        $update = $conn->prepare("UPDATE journals SET title = ?, content = ? WHERE id = ? AND user_id = ?");
        $update->bind_param("ssii", $title, $content, $entry_id, $user_id);
        $update->execute();
        header("Location: journal.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Entry</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
  <h2><i class="fas fa-edit"></i> Edit Journal Entry</h2>
  <form method="post">
    <div class="mb-3">
      <label for="title" class="form-label">Title:</label>
      <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($entry['title']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="content" class="form-label">Content:</label>
      <textarea name="content" id="content" rows="6" class="form-control" required><?= htmlspecialchars($entry['content']) ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="journal.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>

</body>
</html>
