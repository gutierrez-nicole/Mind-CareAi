<?php
session_start();
require_once '../config/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Insert assessment
    $sql = "INSERT INTO assessments (title, description) VALUES ('$title', '$description')";
    if (mysqli_query($conn, $sql)) {
        $assessment_id = mysqli_insert_id($conn);

        // Insert questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q) {
                $q = mysqli_real_escape_string($conn, $q);
                if (!empty($q)) {
                    $sql_q = "INSERT INTO questions (assessment_id, question) VALUES ('$assessment_id', '$q')";
                    mysqli_query($conn, $sql_q);
                }
            }
        }

        // ✅ Redirect to admin dashboard after saving
        header("Location: ../dashboard/admin_dashboard.php?success=1");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
