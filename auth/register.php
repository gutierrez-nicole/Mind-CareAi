<?php
session_start();
require_once '../config/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = $_POST['role'];

  $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("ssss", $name, $email, $password, $role);

  if ($stmt->execute()) {
    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['name'] = $name;
    $_SESSION['role'] = $role;
    header("Location: ../dashboard/user_dashboard.php");
    exit();
  } else {
    $error = "Registration failed.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | MindCare AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <style>
    * { box-sizing: border-box; }
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Segoe UI', sans-serif;
      overflow: hidden; /* removes scrollbars */
    }
    .container-flex {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }
    .left-panel {
      flex: 1;
      background-image: url('../assets/img/123.png');
      background-size: cover;
      background-position: center;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .left-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.4);
    }
    .left-panel p {
      position: relative;
      color: white;
      font-size: 20px;
      text-align: center;
      max-width: 300px;
      padding: 20px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      z-index: 2;
    }
    .right-panel {
      flex: 1;
      background-color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px;
    }
    .register-box {
      width: 100%;
      max-width: 400px;
    }
    .register-box .logo {
      display: flex;
      justify-content: center;
      margin-bottom: -115px;
    }
    .register-box .logo img {
      width: 260px;
      height: auto;
    }
    .register-box h2 {
      text-align: center;
      font-weight: bold;
      margin-bottom: 10px;
    }
    .register-box p {
      text-align: center;
      font-size: 18px;
      color: #777;
      margin-bottom: 25px;
    }
    .tab-buttons {
      display: flex;
      margin-bottom: 25px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 0 4px rgba(0,0,0,0.1);
    }
    .tab-buttons a {
      flex: 1;
      text-align: center;
      padding: 10px 0;
      text-decoration: none;
      font-weight: 500;
      background: #f0f0f0;
      color: #444;
      transition: 0.3s;
    }
    .tab-buttons a.active {
      background: #ec4899;
      color: #fff;
    }
    .form-control, .form-select {
       width: 85%;
      border-radius: 8px;
      padding: 12px;
      margin-top: 6px;
    }
    .btn-register {
      width: 85%;
      padding: 12px;
      font-weight: bold;
      border-radius: 8px;
      background: linear-gradient(to right, #ec4899, #6366f1);
      color: white;
      border: none;
      transition: 0.3s ease;
      margin-top: 10px;
    }
    .btn-register:hover {
      opacity: 0.95;
    }
    .form-footer {
      text-align: center;
      margin-top: 20px;
    }
    .form-footer a {
      font-size: 14px;
      color: #6366f1;
      text-decoration: none;
    }
    .alert {
      font-size: 14px;
    }
    @media (max-width: 768px) {
      html, body { overflow-y: auto; } /* allow scroll on small screens */
      .container-flex {
        flex-direction: column;
        height: auto;
      }
      .left-panel {
        display: none;
      }
      .right-panel {
        flex: 1;
        padding: 60px 20px;
      }
    }
  </style>
</head>
<body>
<div class="container-flex">
  <div class="left-panel">
    <p>Find your calm. Unlock inner peace with MindCare AI.</p>
  </div>
  <div class="right-panel">
    <div class="register-box">
      <div class="logo">
        <img src="../assets/img/brain1.png" alt="MindCare Logo">
      </div>
      <h2>Join MindCare AI</h2>
      <p>Create your free account and begin your wellness journey.</p>
      <div class="tab-buttons">
        <a href="login.php">Login</a>
        <a href="register.php" class="active">Register</a>
      </div>
      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="mb-3">
          <label for="name">Full Name</label>
          <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
        </div>
        <div class="mb-3">
          <label for="email">Email address</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label for="password">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Create a password" required>
        </div>

        <button type="submit" class="btn btn-register">Register</button>
      </form>
      <div class="form-footer">
        <a href="login.php">Already have an account? Login</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
