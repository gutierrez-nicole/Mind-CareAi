<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../config/dbcon.php'; // adjust path if needed

// Fetch emergency numbers securely
$stmt = $conn->prepare("SELECT id, name, contact_number, logo FROM emergency_numbers ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();

$name = $_SESSION['name'] ?? 'User';
$initials = '';
if (!empty($name)) {
    $parts = explode(' ', $name);
    $initials = count($parts) > 1
        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
        : strtoupper(substr($parts[0], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Information</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
            flex-shrink: 0;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #495057;
        }
        /* Main content */
        .main {
            flex-grow: 1;
            padding: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-initials {
            background-color: #6c757d;
            border-radius: 50%;
            padding: 10px;
            color: white;
            font-weight: bold;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Search bar */
        .search-container {
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .search-container input {
            padding: 10px;
            width: 300px;
            max-width: 100%;
            border: 1px solid #ced4da;
            border-radius: 20px;
            padding-left: 35px;
        }
        .search-container i {
            position: absolute;
            margin-left: 12px;
            color: gray;
        }
        /* Cards */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #f0f0f0;
        }
        .contact-number {
            font-size: 1.2em;
            margin-top: 8px;
            color: #007bff;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Dashboard</h2>
        <a href="user_dashboard.php">🏠 Home</a>
        <a href="user_emergency_info.php" class="active">🚨 Emergency Info</a>
        <a href="../logout.php">🔓 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="top-bar">
            <h1>Emergency Information</h1>
            <div class="user-info">
                <div class="user-initials"><?php echo $initials; ?></div>
                <span><?php echo htmlspecialchars($name); ?></span>
            </div>
        </div>

        <!-- Search -->
        <div class="search-container">
            <i class="fa fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name or number...">
        </div>

        <!-- Cards -->
        <div class="card-grid" id="emergencyList">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <?php
                        $logoPath = "../uploads/logos/" . htmlspecialchars($row['logo']);
                        if (!file_exists($logoPath) || empty($row['logo'])) {
                            $logoPath = "../uploads/logos/default.png";
                        }
                    ?>
                    <img src="<?php echo $logoPath; ?>" alt="Logo" class="logo">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p class="contact-number"><?php echo htmlspecialchars($row['contact_number']); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
document.getElementById("searchInput").addEventListener("input", function() {
    const query = this.value.toLowerCase();
    const cards = document.querySelectorAll("#emergencyList .card");
    cards.forEach(card => {
        const name = card.querySelector("h3").textContent.toLowerCase();
        const number = card.querySelector(".contact-number").textContent.toLowerCase();
        card.style.display = (name.includes(query) || number.includes(query)) ? "" : "none";
    });
});
</script>

</body>
</html>
  