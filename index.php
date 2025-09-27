<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MindCare AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      height: 100vh; min-height: 100svh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      font-family: 'Segoe UI', sans-serif;
      overflow: hidden;
      position: relative;
      color: #fff;
      padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
    }

    /* Background image with blur */
    body::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: url('assets/img/kev.jpg') no-repeat center center/cover;
      filter: blur(2px);
      z-index: -2;
    }

    /* Dark overlay */
    body::after {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(0, 0, 0, 0.6);
      z-index: -1;
    }

   

    /* Logo inside */
    .circle img {
      width: clamp(160px, 45vw, 300px);
      height: auto;
      z-index: 2;
    }

    /* Bubble animations */
    .bubble {
      position: absolute;
      bottom: -50px;
      width: 15px;
      height: 15px;
      background: rgba(0, 200, 255, 0.3);
      border-radius: 50%;
      animation: rise 6s infinite ease-in;
    }

    @keyframes rise {
      0% {
        transform: translateY(0) scale(1);
        opacity: 0.6;
      }
      100% {
        transform: translateY(-120vh) scale(0.5);
        opacity: 0;
      }
    }

    /* Spin animation */
    @keyframes spin {
      0% { transform: rotate(0); }
      100% { transform: rotate(360deg); }
    }

    /* Text styles */
    h1 {
      margin-top: 20px;
      font-size: clamp(22px, 5vw, 32px);
      color: #fff;
      font-weight: 700;
    }

    p {
      font-size: clamp(14px, 3.8vw, 16px);
      color: #ddd;
      margin-top: 8px;
    }

    /* Loading dots animation */
    .dots::after {
      content: "";
      animation: dots 1.5s steps(4, end) infinite;
    }

    @keyframes dots {
      0% { content: ""; }
      25% { content: "."; }
      50% { content: ".."; }
      75% { content: "..."; }
      100% { content: ""; }
    }

    /* Welcome box for non-logged users */
    .welcome-box {
      background-color: rgb(0 0 0 / 42%);
      border-radius: 18px;
      padding: 36px 30px;
      width: 100%;
      max-width: min(92vw, 420px);
      text-align: center;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.05);
      animation: fadeIn 1s ease-in-out;
      backdrop-filter: blur(8px);
      margin: 16px;
    }

    .welcome-box h2 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 12px;
      color: #fff;
    }

    .welcome-box p {
      color: #b0b0b0;
      font-size: 15px;
      margin-bottom: 30px;
    }

    .btn {
      border-radius: 25px;
      font-weight: 500;
      padding: 11px;
      transition: all 0.2s ease-in-out;
    }

    .btn-login {
      background-color: #fff;
      color: #000;
      box-shadow: 0 2px 8px rgba(255, 255, 255, 0.2);
    }

    .btn-login:hover {
      background-color: #e4e4e4;
      color: #000;
    }

    .btn-signup {
      background: transparent;
      border: 1px solid #ffffff;
      color: #fff;
    }

    .btn-signup:hover {
      background-color: #ffffff;
      color: #000;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Reduced motion preference */
    @media (prefers-reduced-motion: reduce) {
      .bubble { animation: none !important; }
      .dots::after { animation: none !important; content: '...'; }
    }

    @media (max-width: 576px) {
      .bubble { width: 10px; height: 10px; }
    }

    @media (max-width: 480px) {
      .welcome-box {
        padding: 28px 20px;
      }
      h1 { margin-top: 12px; }
    }
  </style>
</head>
<body>

  <!-- Loading screen -->
  <div class="circle">
    <img src="assets/img/brain2.png" alt="MindCare-AI Logo">
  </div>
  <h1>MindCare-AI</h1>
  <p class="dots">Loading</p>

  <!-- Floating bubbles -->
  <div class="bubble" style="left: 20%; animation-delay: 0s; animation-duration: 5s;"></div>
  <div class="bubble" style="left: 40%; animation-delay: 2s; animation-duration: 6s;"></div>
  <div class="bubble" style="left: 60%; animation-delay: 1s; animation-duration: 7s;"></div>
  <div class="bubble" style="left: 80%; animation-delay: 3s; animation-duration: 4s;"></div>
  <div class="bubble" style="left: 50%; animation-delay: 4s; animation-duration: 6s;"></div>

  <?php if (!isset($_SESSION['user_id'])): ?>
    <script>
      // Show welcome screen after loading animation
      setTimeout(function() {
        document.body.innerHTML = `
          <div class="welcome-box">
            <h2>Welcome Back!</h2>
            <p>You are not alone. Let’s talk..</p>
            <div class="d-grid gap-2">
              <a href="auth/login.php" class="btn btn-login mb-2">Log In</a>
              <a href="auth/register.php" class="btn btn-signup">Sign Up for Free</a>
            </div>
          </div>`;
      }, 4000);
    </script>
  <?php else: ?>
    <script>
      // Redirect logged-in users to dashboard after animation
      setTimeout(function() {
        window.location.href = "dashboard/user_dashboard.php";
      }, 4000);
    </script>
  <?php endif; ?>

</body>
</html>
