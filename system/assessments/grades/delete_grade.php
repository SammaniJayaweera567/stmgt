<?php
ob_start(); // Output buffering start
include '../../../init.php'; // Correct path from /system/grades/

$db = dbConn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)($_POST['id']);

    // --- Check for usage in 'assessment_results' table before deleting ---
    $sql_check_usage = "SELECT COUNT(*) as usage_count FROM assessment_results WHERE grade_id = '$id'";
    $result_check_usage = $db->query($sql_check_usage);
    $row_check_usage = $result_check_usage->fetch_assoc();

    if ($row_check_usage['usage_count'] > 0) {
        // If Grade is in use, redirect with a specific error message
        header("Location: manage_grades.php?status=error_in_use&message=Deletion failed! This grade is currently used in exam results.");
        exit();
    } else {
        // If Grade is NOT in use, proceed with deletion
        $sql_delete = "DELETE FROM grades WHERE id = '$id'";
        if ($db->query($sql_delete)) {
            header("Location: manage_grades.php?status=deleted");
            exit();
        } else {
            // Database error during deletion
            header("Location: manage_grades.php?status=error&message=Failed to delete the grade from the database.");
            exit();
        }
    }
} else {
    // Redirect if not a POST request or no ID provided
    header("Location: manage_grades.php");
    exit();
}

ob_end_flush(); // Output buffering end
?>