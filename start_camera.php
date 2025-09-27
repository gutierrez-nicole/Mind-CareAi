<?php
// Path sa python at camera.py mo (i-adjust kung iba ang path!)
$python = "C:\\Users\\YourUsername\\AppData\\Local\\Programs\\Python\\Python311\\python.exe"; // <-- palitan mo ito sa tunay na path
$script = "C:\\xampp1\\htdocs\\MindCare-AI\\python\\camera.py";

// Run in background
pclose(popen("start /B \"\" \"$python\" \"$script\"", "r"));
?>