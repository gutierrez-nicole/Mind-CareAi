<?php
session_start();
session_unset();  // Remove all session variables
session_destroy(); // Destroy the session

// Stop camera service via API only if the user was logged in (optional check for user role)
$ch = curl_init('http://localhost:5000/stop');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Short timeout
curl_exec($ch);
curl_close($ch);

// Redirect to homepage or login page
header("Location: ../index.php");
exit();
?>