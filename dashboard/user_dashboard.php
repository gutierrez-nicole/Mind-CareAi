<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? 'user@example.com';
$userId = (int)($_SESSION['user_id'] ?? 0);

// Get initials
$initials = '';
if (!empty($name)) {
    $parts = explode(' ', $name);
    $initials = count($parts) > 1
        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1))
        : strtoupper(substr($name, 0, 2));
}

require_once '../config/dbcon.php';

// Fetch emergency numbers
$emergencyNumbers = $conn->query("SELECT * FROM emergency_numbers");

// Fetch facebook pages
$facebookPages = $conn->query("SELECT * FROM facebook_pages");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard | MindCare AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <style>
    /* Loader + bubbles */
    #loadingOverlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(8px);
      z-index: 2000;
      color: #fff;
      animation: fadeInOverlay 0.5s ease-in;
    }
    #loadingOverlay .circle img {
      width: 250px;
      height: auto;
      animation: pulse 2s infinite ease-in-out;
    }
    #loadingOverlay h1 { margin-top: 20px; font-size: 28px; font-weight: 700; }
    #loadingOverlay p { font-size: 16px; color: #ddd; margin-top: 8px; }
    .dots::after {
      content: "";
      animation: dots 1.5s steps(4, end) infinite;
    }
    @keyframes fadeInOverlay {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    @keyframes dots {
      0% { content: ""; }
      25% { content: "."; }
      50% { content: ".."; }
      75% { content: "..."; }
      100% { content: ""; }
    }
    .bubble {
      position: absolute;
      bottom: -50px;
      width: 15px; height: 15px;
      background: rgba(0, 200, 255, 0.4);
      border-radius: 50%;
      animation: rise 6s infinite ease-in;
    }
    @keyframes rise {
      0% { transform: translateY(0) scale(1); opacity: 0.6; }
      100% { transform: translateY(-120vh) scale(0.3); opacity: 0; }
    }

    /* New Color Palette and Layout */
    :root {
      --primary: #007bff; /* A vibrant blue */
      --secondary: #6c757d; /* Gray for subtle elements */
      --accent: #17a2b8; /* A secondary blue */
      --hover-bg: #e2f1ff; /* Light blue hover effect */
      --active-color: #0056b3; /* Darker blue for active links */
      --text-color: #212529; /* Black for text */
      --card-text-color: #495057; /* Dark gray for card text */
      --bg: #f8f9fa; /* Light gray background */
      --card-bg: #ffffff; /* White card background */
      --border-color: #dee2e6; /* Light border color */
    }

    body { 
      margin: 0; 
      font-family: 'Segoe UI', sans-serif; 
      background: var(--bg); 
      color: var(--text-color); 
      display: flex; 
      transition: background 0.5s ease; 
      height: 100vh; 
      overflow: hidden; 
    }

    /* Dashboard Banner */
    .dashboard-banner {
      position: relative;
      background: linear-gradient(rgba(0, 123, 255, 0.7), rgba(0, 123, 255, 0.7)), url('../assets/img/000.png') no-repeat center center/cover;
      backdrop-filter: blur(8px);
      border-radius: 12px;
      padding: 30px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      animation: fadeInHero 0.8s ease-out;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      overflow: hidden;
    }
    .dashboard-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: inherit;
      filter: blur(10px);
      z-index: -1;
    }
    .dashboard-banner .banner-content {
      flex: 1;
      text-align: center;
      min-width: 200px;
      z-index: 1;
    }
    .dashboard-banner h2 {
      font-size: 1.8em;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 10px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
      opacity: 0;
      animation: fadeInText 0.6s ease forwards;
      animation-delay: 0.2s;
    }
    .dashboard-banner p {
      font-size: 1em;
      color: #f8f9fa;
      max-width: 600px;
      margin: 0 auto 15px;
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
      opacity: 0;
      animation: fadeInText 0.6s ease forwards;
      animation-delay: 0.4s;
    }
    .dashboard-banner .btn {
      padding: 10px 20px;
      font-size: 0.95em;
      font-weight: 500;
      border-radius: 25px;
      background: var(--primary);
      color: white;
      border: none;
      transition: transform 0.2s ease, background-color 0.3s ease, box-shadow 0.3s ease;
      opacity: 0;
      animation: fadeInText 0.6s ease forwards;
      animation-delay: 0.6s;
    }
    .dashboard-banner .btn:hover {
      transform: scale(1.05);
      background-color: var(--active-color);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    .dashboard-banner img {
      display: none; /* Hide the image */
    }
    
    @keyframes fadeInHero {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Sidebar */
    .sidebar { 
      position: fixed; 
      top: 0; 
      left: 0; 
      height: 100vh; 
      width: 250px; 
      background: #ffffff; 
      transition: all 0.3s ease; 
      box-shadow: 2px 0 20px rgba(0,0,0,0.05); 
      z-index: 99; 
      animation: slideInSidebar 0.5s ease;
    }
    .sidebar.collapsed { width: 78px; }
    .sidebar .logo-details { 
      height: 60px; 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      padding: 0 15px; 
      border-bottom: 1px solid #eee; 
    }
    .sidebar .logo_name { 
      font-size: 20px; 
      font-weight: 600; 
      color: #333; 
      transition: opacity 0.3s ease; 
    }
    .sidebar .toggle-btn { 
      font-size: 24px; 
      background: none; 
      border: none; 
      color: #333; 
      cursor: pointer; 
    }
    .sidebar .initials { 
      width: 40px; 
      height: 40px; 
      background-color: var(--primary); 
      color: #fff; 
      border-radius: 50%; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      font-weight: bold; 
      margin: 15px auto; 
      transition: transform 0.3s ease; 
    }
    .sidebar .initials:hover { transform: scale(1.1); }
    .nav-links { 
      list-style: none; 
      margin-top: 15px; 
      padding-left: 0; 
    }
    .nav-links li { 
      margin: 10px 0; 
      opacity: 0; 
      animation: slideInNav 0.5s ease forwards; 
      animation-delay: calc(var(--i) * 0.1s); 
    }
    .nav-links li:nth-child(1) { --i: 1; }
    .nav-links li:nth-child(2) { --i: 2; }
    .nav-links li:nth-child(3) { --i: 3; }
    .nav-links li:nth-child(4) { --i: 4; }
    .nav-links li:nth-child(5) { --i: 5; }
    .nav-links li:nth-child(6) { --i: 6; }
    .nav-links li a { 
      display: flex; 
      align-items: center; 
      padding: 12px 20px; 
      color: var(--text-color); 
      text-decoration: none; 
      font-size: 16px; 
      border-radius: 8px; 
      transition: all 0.3s ease; 
    }
    .nav-links li a i { 
      font-size: 18px; 
      margin-right: 12px; 
    }
    .nav-links li a:hover, .nav-links li a.active { 
      background-color: var(--hover-bg); 
      color: var(--active-color); 
      transform: translateX(5px); 
    }
    .sidebar.collapsed .logo_name, .sidebar.collapsed .sidebar-text { display: none; }

    @keyframes slideInSidebar {
      from { transform: translateX(-100%); }
      to { transform: translateX(0); }
    }
    @keyframes slideInNav {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Main */
    .main { 
      margin-left: 250px; 
      padding: 20px; 
      flex: 1; 
      transition: margin-left 0.3s ease; 
      background: var(--bg); 
      border-radius: 12px; 
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05); 
      animation: fadeInMain 0.7s ease; 
      height: 100vh; 
      overflow: hidden; 
      display: flex;
      flex-direction: column;
    }
    .sidebar.collapsed ~ .main { margin-left: 78px; }
    .dashboard-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
      gap: 15px; 
      padding: 15px; 
      flex: 1; 
      overflow-y: auto; 
    }
    .card { 
      border-radius: 12px; 
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); 
      border: 1px solid var(--border-color); 
      transition: transform 0.3s ease, box-shadow 0.3s ease; 
      color: var(--card-text-color); 
      background: var(--card-bg); 
      overflow: hidden; 
      position: relative; 
      opacity: 0; 
      animation: cardFadeIn 0.5s ease forwards; 
      animation-delay: calc(var(--i) * 0.2s); 
    }
    .card:nth-child(1) { --i: 1; }
    .card:nth-child(2) { --i: 2; }
    .card:nth-child(3) { --i: 3; }
    .card:nth-child(4) { --i: 4; }
    .card:nth-child(5) { --i: 5; }
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: var(--primary);
      transition: height 0.3s ease;
    }
    .card:hover { 
      transform: translateY(-6px); 
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); 
    }
    .card:hover::before { height: 6px; }
    .card-body { 
      padding: 20px; 
      text-align: center; 
      transition: transform 0.3s ease; 
    }
    .card-title { 
      font-size: 1.3em; 
      font-weight: 700; 
      margin-bottom: 10px; 
      display: flex; 
      align-items: center; 
      gap: 10px; 
      color: var(--primary); 
      justify-content: center;
    }
    .card-text {
      font-size: 0.95em;
      margin-bottom: 15px;
      color: var(--card-text-color);
      opacity: 0;
      animation: fadeInText 0.6s ease forwards;
      animation-delay: calc(var(--i) * 0.3s);
    }
    .btn {
      border: none;
      padding: 10px 20px;
      font-size: 0.95em;
      font-weight: 500;
      border-radius: 25px;
      transition: transform 0.2s ease, background-color 0.3s ease, box-shadow 0.3s ease;
      background: var(--primary);
      color: white;
      position: relative;
      overflow: hidden;
    }
    .btn::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.4s ease;
    }
    .btn:hover::after {
      left: 100%;
    }
    .btn:hover {
      transform: scale(1.05);
      background-color: var(--active-color);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    .mood-tracker-card { 
      background-color: #f1f1f1; 
      grid-column: span 2; 
    }
    .mood-tracker-card .card-header { 
      background: #fff; 
      padding: 10px 15px; 
      font-weight: 600; 
      border-bottom: 1px solid #ddd; 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      gap: 8px; 
      flex-wrap: wrap; 
    }
    #moodContainer { 
      display: none; 
      flex-direction: column; 
      align-items: center; 
      padding: 15px; 
    }
    #cameraFeed { 
      max-width: 100%; 
      border-radius: 8px; 
      margin-bottom: 10px; 
      border: 2px solid #ddd; 
      transition: transform 0.3s ease; 
    }
    #cameraFeed:hover { transform: scale(1.02); }

    /* Mood meta */
    .mood-meta { 
      display: flex; 
      gap: 8px; 
      align-items: center; 
      flex-wrap: wrap; 
    }
    .badge-pill { 
      border-radius: 50px; 
      padding: 5px 10px; 
      font-size: 0.85rem; 
      transition: transform 0.2s ease; 
    }
    .badge-pill:hover { transform: scale(1.1); }

    /* Emergency btn + modal */
    .floating-emergency-btn { 
      position: fixed; 
      bottom: 20px; 
      right: 20px; 
      background: var(--primary); 
      color: white; 
      border: none; 
      border-radius: 50%; 
      width: 56px; 
      height: 56px; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      font-size: 24px; 
      cursor: pointer; 
      box-shadow: 0 4px 12px rgba(0,0,0,0.3); 
      z-index: 1000; 
      transition: transform 0.3s ease, background-color 0.3s ease; 
    }
    .floating-emergency-btn:hover { 
      background-color: var(--active-color); 
      transform: scale(1.15) rotate(5deg); 
    }
    .modal-overlay { 
      display: none; 
      position: fixed; 
      z-index: 999; 
      left: 0; 
      top: 0; 
      width: 100%; 
      height: 100%; 
      background-color: rgba(0,0,0,0.6); 
      animation: fadeInOverlay 0.3s ease; 
    }
    .modal-content { 
      background-color: #fff; 
      margin: 40px auto; 
      padding: 15px 20px; 
      border-radius: 8px; 
      width: 90%; 
      max-width: 500px; 
      max-height: 80vh; 
      overflow-y: auto; 
      position: relative; 
      text-align: left; 
      animation: slideInModal 0.4s ease-out; 
    }
    .close-btn { 
      position: absolute; 
      top: 10px; 
      right: 15px; 
      font-size: 20px; 
      font-weight: bold; 
      color: #333; 
      cursor: pointer; 
      transition: transform 0.2s ease; 
    }
    .close-btn:hover { transform: scale(1.2); }
    .info-list { 
      display: flex; 
      flex-direction: column; 
      gap: 10px; 
    }
    .info-item { 
      display: flex; 
      align-items: center; 
      gap: 12px; 
      padding: 6px 0; 
      border-bottom: 1px solid #eee; 
      opacity: 0; 
      animation: fadeInText 0.5s ease forwards; 
      animation-delay: calc(var(--i) * 0.1s); 
    }
    .info-item:nth-child(1) { --i: 1; }
    .info-item:nth-child(2) { --i: 2; }
    .info-item:nth-child(3) { --i: 3; }
    .info-item img { 
      width: 36px; 
      height: 36px; 
      object-fit: cover; 
      border-radius: 50%; 
      transition: transform 0.3s ease; 
    }
    .info-item img:hover { transform: scale(1.1); }

    @keyframes fadeInMain {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes cardFadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInText {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideInModal {
      from { opacity: 0; transform: translateY(-50px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Mobile/off-canvas sidebar and responsive tweaks */
    .sidebar-backdrop { 
      position: fixed; 
      inset: 0; 
      background: rgba(0,0,0,0.25); 
      backdrop-filter: blur(2px); 
      z-index: 98; 
      display: none; 
      transition: opacity 0.3s ease; 
    }
    .sidebar-backdrop.show { 
      display: block; 
      opacity: 1; 
    }
    body.no-scroll { 
      overflow: hidden; 
    }

    a {
      text-decoration: none; /* removes underline */
    }

    a:hover {
      text-decoration: none; /* makes sure it doesn’t come back on hover */
    }

    @media (max-width: 992px) {
      .dashboard-banner { 
        padding: 15px; 
        flex-direction: column; 
        align-items: center; 
        text-align: center; 
      }
      .dashboard-banner .banner-content { text-align: center; }
      .dashboard-banner img { display: none; }
      .dashboard-banner h2 { font-size: 1.5em; }
      .dashboard-banner p { font-size: 0.9em; max-width: 100%; }
      .sidebar { 
        transform: translateX(-100%); 
        width: 260px; 
        left: 0; 
        top: 0; 
        height: 100vh;
        z-index: 99;
      }
      .sidebar.open { transform: translateX(0); }
      .sidebar.collapsed { width: 260px; }
      .main { 
        margin-left: 0; 
        padding: 15px; 
        height: 100vh; 
        display: flex; 
        flex-direction: column; 
      }
      .dashboard-grid { 
        grid-template-columns: 1fr; 
        gap: 12px; 
        padding: 10px; 
      }
      .mood-tracker-card { grid-column: auto; }
    }
    @media (max-width: 576px) {
      .dashboard-banner { padding: 10px; }
      .dashboard-banner img { display: none; }
      .dashboard-banner h2 { font-size: 1.3em; }
      .dashboard-banner p { font-size: 0.85em; }
      #loadingOverlay .circle img { width: clamp(140px, 50vw, 200px); }
      #loadingOverlay h1 { font-size: clamp(18px, 5vw, 24px); }
      #loadingOverlay p { font-size: clamp(12px, 3vw, 14px); }
      .floating-emergency-btn { 
        width: 50px; 
        height: 50px; 
        bottom: calc(12px + env(safe-area-inset-bottom)); 
        right: calc(12px + env(safe-area-inset-right)); 
        font-size: 22px; 
      }
      .dashboard-grid { padding: 8px; }
      .card-body { padding: 15px; }
      .card-title { font-size: 1.2em; }
      .btn { padding: 8px 16px; font-size: 0.9em; }
    }
  </style>
</head>
<body>

<div id="loadingOverlay">
  <div class="circle"><img src="../assets/img/brain2.png" alt="MindCare AI Logo"></div>
  <h1>MindCare-AI</h1>
  <p class="dots">Loading</p>
  <div class="bubble" style="left: 20%; animation-delay: 0s; animation-duration: 5s;"></div>
  <div class="bubble" style="left: 40%; animation-delay: 2s; animation-duration: 6s;"></div>
  <div class="bubble" style="left: 60%; animation-delay: 1s; animation-duration: 7s;"></div>
  <div class="bubble" style="left: 80%; animation-delay: 3s; animation-duration: 4s;"></div>
  <div class="bubble" style="left: 50%; animation-delay: 4s; animation-duration: 6s;"></div>
</div>

<div class="sidebar" id="sidebar">
  <div class="logo-details">
    <i class="bi bi-list toggle-btn" onclick="toggleSidebar()"></i>
    <span class="logo_name">MindCare</span>
  </div>
  <div class="initials" title="<?= htmlspecialchars($name) ?>"><?= $initials ?></div>
  <ul class="nav-links">
    <li><a href="#" class="active"><i class="bi bi-house"></i> <span class="sidebar-text">Dashboard</span></a></li>
    <li><a href="../chatbot/index.php"><i class="bi bi-chat-dots"></i> <span class="sidebar-text">Chatbot</span></a></li>
    <li><a href="../journal/journal.php"><i class="bi bi-journal-text"></i> <span class="sidebar-text">Journal</span></a></li>
    <li><a href="../resources/resources.php"><i class="bi bi-lightbulb"></i> <span class="sidebar-text">Resources</span></a></li>
    <li><a href="../moodtracker/emotional_analysis.php"><i class="bi bi-activity"></i> <span class="sidebar-text">Emotional Analysis</span></a></li>
    <li><a href="../auth/logout.php" id="logoutLink"><i class="bi bi-box-arrow-right"></i> <span class="sidebar-text">Logout</span></a></li>
    <li><a href="#" onclick="toggleDarkMode()"><i class="bi bi-moon-stars"></i> <span class="sidebar-text">Dark Mode</span></a></li>
  </ul>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="main">
  <div class="dashboard-banner">
    <div class="banner-content">
      <h2 id="greeting"><?= htmlspecialchars($name) ?>!</h2>
      <p>Take a moment to nurture your mind and find your inner peace.</p>
    </div>
  </div>

  <div class="dashboard-grid">
    <div class="card chat-card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-chat-dots"></i> Chat with Aura</h5>
        <p class="card-text">Your AI mental health assistant</p>
        <a href="../chatbot/index.php" class="btn">Start Chat</a>
      </div>
    </div>
    <div class="card journal-card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-journal-text"></i> Journal</h5>
        <p class="card-text">Write and reflect your thoughts</p>
        <a href="../journal/journal.php" class="btn">Add Entry</a>
      </div>
    </div>
    <div class="card resources-card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-lightbulb"></i> Resources</h5>
        <p class="card-text">Explore mental health guides</p>
        <a href="../resources/resources.php" class="btn">Get Started</a>
      </div>
    </div>
    <div class="card resources-card">
      <div class="card-body">
        <h5 class="card-title"><i class="fa-solid fa-brain"></i> Test Assessment</h5>
        <p class="card-text">Take a psychological test and view your results.</p>
        <a href="../assessment.php" class="btn">Take Assessment</a>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-activity"></i> Emotional Analysis</h5>
        <p class="card-text">View your recent emotions and distribution from the mood tracker.</p>
        <a href="../moodtracker/emotional_analysis.php" class="btn">Open Analysis</a>
      </div>
    </div>
  </div>
</div>

<button class="floating-emergency-btn" id="emergencyBtn" title="Emergency Info">
  <i class="bi bi-telephone-fill"></i>
</button>

<div id="emergencyModal" class="modal-overlay">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>🚨 Emergency Information</h2>
    <h4>📞 Emergency Contacts</h4>
    <div class="info-list">
      <?php while($row = $emergencyNumbers->fetch_assoc()): ?>
        <div class="info-item">
          <img src="../<?= htmlspecialchars($row['logo']) ?>" alt="Logo">
          <div>
            <strong><?= htmlspecialchars($row['name']) ?></strong><br>
            <?= htmlspecialchars($row['contact_number']) ?>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <hr>
    <h4>📘 MindCare Facebook Pages</h4>
    <div class="info-list">
      <?php while($page = $facebookPages->fetch_assoc()): ?>
        <div class="info-item">
          <img src="../<?= htmlspecialchars($page['logo']) ?>" alt="Logo">
          <div>
            <strong><?= htmlspecialchars($page['name']) ?></strong><br>
            <a href="<?= htmlspecialchars($page['page_link']) ?>" target="_blank">Visit Page</a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<script>
const PY_HOST = 'http://localhost:5000';
const userId = <?= json_encode($userId) ?>;
const sessionKey = <?= json_encode(session_id()) ?>;

// Dynamic greeting based on local time
function updateGreeting() {
  const hour = new Date().getHours();
  const greetingText = hour < 12 ? 'Good Morning' : (hour < 17 ? 'Good Afternoon' : 'Good Evening');
  const name = <?= json_encode(htmlspecialchars($name)) ?>;
  document.getElementById('greeting').innerText = `${greetingText}, ${name}!`;
}
window.addEventListener('DOMContentLoaded', updateGreeting);

function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const isMobile = window.matchMedia('(max-width: 992px)').matches;
  if (isMobile) {
    const willOpen = !sb.classList.contains('open');
    sb.classList.toggle('open', willOpen);
    document.body.classList.toggle('no-scroll', willOpen);
    if (backdrop) backdrop.classList.toggle('show', willOpen);
  } else {
    sb.classList.toggle('collapsed');
  }
}
// Backdrop interactions and responsive reset on resize
(function(){
  const backdrop = document.getElementById('sidebarBackdrop');
  if (backdrop) {
    backdrop.addEventListener('click', () => {
      const sb = document.getElementById('sidebar');
      sb.classList.remove('open');
      document.body.classList.remove('no-scroll');
      backdrop.classList.remove('show');
    });
  }
  window.addEventListener('resize', () => {
    const sb = document.getElementById('sidebar');
    const isMobile = window.matchMedia('(max-width: 992px)').matches;
    if (!isMobile) {
      sb.classList.remove('open');
      document.body.classList.remove('no-scroll');
      if (backdrop) backdrop.classList.remove('show');
    }
  });
})();

function toggleDarkMode() {
  document.body.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}
window.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
  }
});

// Loader show/hide
window.addEventListener('load', () => {
  setTimeout(() => {
    document.getElementById('loadingOverlay').style.display = 'none';
  }, 1500);
});

// ---- Mood tracker state ----
let sessionStart = sessionStorage.getItem('mc_session_start') ? parseInt(sessionStorage.getItem('mc_session_start')) : Date.now();
sessionStorage.setItem('mc_session_start', sessionStart.toString());
let emotionLog = []; // {emotion: string, t: seconds since session start}
let lastEmotion = null;

// Start camera on page ready
async function startCamera() {
  try {
    await fetch(`${PY_HOST}/start?user_id=${encodeURIComponent(userId)}&session_key=${encodeURIComponent(sessionKey)}`, { method: 'GET', mode: 'cors' });
  } catch (e) {
    console.warn('Could not start camera service:', e);
  }
}

// Stop camera explicitly on logout
async function stopCamera() {
  try {
    await fetch(`${PY_HOST}/stop?user_id=${encodeURIComponent(userId)}&session_key=${encodeURIComponent(sessionKey)}`, { method: 'GET', mode: 'cors' });
  } catch (e) {
    console.warn('Could not stop camera service:', e);
  }
}

// Update session timer display
function fmtTime(sec) {
  const h = Math.floor(sec / 3600).toString().padStart(2, '0');
  const m = Math.floor((sec % 3600) / 60).toString().padStart(2, '0');
  const s = Math.floor(sec % 60).toString().padStart(2, '0');
  return `${h}:${m}:${s}`;
}
function updateTimer() {
  const seconds = (Date.now() - sessionStart) / 1000;
  const st = document.getElementById('sessionTime');
  if (st) st.innerText = 'Session: ' + fmtTime(seconds);
}
setInterval(updateTimer, 1000);
updateTimer();

// Poll current emotion every 2s and log changes
async function pollEmotion() {
  try {
    const res = await fetch(`${PY_HOST}/current_emotion`, { mode: 'cors' });
    if (!res.ok) return;
    const data = await res.json();
    const current = data.emotion || 'neutral';
    const display = current.charAt(0).toUpperCase() + current.slice(1);
    const de = document.getElementById('detectedEmotion');
    if (de) de.innerText = display;
    if (current !== lastEmotion) {
      lastEmotion = current;
      const t = Math.floor((Date.now() - sessionStart) / 1000);
      emotionLog.push({ emotion: current, t });
    }
  } catch (e) { /* ignore polling failures silently */ }
}
setInterval(pollEmotion, 2000);
pollEmotion();

// Mood tracker UI: camera is always visible in floating widget. Optional clear button support if present.
(function(){
  const btn = document.getElementById('clearSession');
  if (!btn) return;
  btn.addEventListener('click', function () {
    emotionLog = [];
    sessionStart = Date.now();
    sessionStorage.setItem('mc_session_start', sessionStart.toString());
    lastEmotion = null;
    const de = document.getElementById('detectedEmotion');
    if (de) de.innerText = 'Analyzing...';
  });
})();

// Emergency modal
document.getElementById('emergencyBtn').onclick = () => {
  document.getElementById('emergencyModal').style.display = 'block';
};
document.querySelector('.close-btn').onclick = () => {
  document.getElementById('emergencyModal').style.display = 'none';
};
window.onclick = (event) => {
  if (event.target == document.getElementById('emergencyModal')) {
    document.getElementById('emergencyModal').style.display = 'none';
  }
};

// Logout: stop camera then proceed
document.getElementById('logoutLink').addEventListener('click', async (e) => {
  e.preventDefault();
  const href = e.currentTarget.getAttribute('href');
  await stopCamera();
  sessionStorage.removeItem('mc_session_start');
  window.location.href = href;
});

// Start camera service when dashboard is active
startCamera();

// Inject global floating mood widget for users (also injected via dbcon.php safety-net)
(function injectWidget(){
  if (!document.querySelector('script[src*="floating_mood_widget.js"]')) {
    const s = document.createElement('script');
    s.src = '../assets/js/floating_mood_widget.js?v=1';
    s.defer = true;
    document.head.appendChild(s);
  }
})();
</script>
</body>
</html>