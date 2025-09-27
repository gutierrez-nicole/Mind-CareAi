<?php
session_start();
require_once '../config/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    // Camera service is now managed by the in-page floating widget for users; no server-side launch here.


    if ($user['role'] === 'admin') {
      header("Location: ../dashboard/admin_dashboard.php");
    } else {
      header("Location: ../dashboard/user_dashboard.php");
    }
    exit();
  } else {
    $error = "Invalid email or password.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | MindCare AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <style>
    * { box-sizing: border-box; }

    body, html {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Segoe UI', sans-serif;
    }

    .container-flex {
      display: flex;
      height: 100vh;
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

    .login-box {
      width: 100%;
      max-width: 400px;
    }

    .login-box .logo {
      display: flex;
      justify-content: center;
      margin-bottom: -80px;
    }

    .login-box .logo img {
      width: 200px;
      height: auto;
    }

    .login-box h2 {
      text-align: center;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .login-box p {
      text-align: center;
      font-size: 24px;
      color: #777;
      margin-bottom: 29px;
    }

    .tab-buttons {
      display: flex;
      margin-bottom: 30px;
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
      background: #6366f1;
      color: #fff;
    }

    .form-control {
      width: 85%;
      border-radius: 8px;
      padding: 12px;
      margin-top: 6px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .btn-login {
      width: 85%;
      padding: 12px;
      font-weight: bold;
      border-radius: 8px;
      background: linear-gradient(to right, #6366f1, #ec4899);
      color: white;
      border: none;
      transition: 0.3s ease;
    }

    .btn-login:hover {
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
      .container-flex {
        flex-direction: column;
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
    <div class="login-box">
      <div class="logo">
        <img src="../assets/img/brain1.png" alt="MindCare Logo">
      </div>
      <h2>Welcome to MindCare AI</h2>
      <p>Your journey to mental wellness begins here.</p>

      <div class="tab-buttons">
        <a href="login.php" class="active">Login</a>
        <a href="register.php">Register</a>
      </div>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-login">Login</button>
      </form>

      <div class="form-footer">
        <a href="register.php">Don't have an account? Register</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>
