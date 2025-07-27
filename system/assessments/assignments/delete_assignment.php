<?php
ob_start(); // Output buffering start
include '../../../init.php'; // Correct path from /system/assessments/assignments/
if (!hasPermission($_SESSION['user_id'], 'delete_assignment')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$db = dbConn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)($_POST['id']);

    // --- Check for usage in other tables (Foreign Key Constraints) before deleting ---
    // An assignment can have submissions and results.
    // 1. Check in 'student_submissions' table
    $sql_check_submissions = "SELECT COUNT(*) as usage_count FROM student_submissions WHERE assessment_id = '$id'";
    $result_check_submissions = $db->query($sql_check_submissions);
    $row_check_submissions = $result_check_submissions->fetch_assoc();

    // 2. Check in 'assessment_results' table
    $sql_check_results = "SELECT COUNT(*) as usage_count FROM assessment_results WHERE assessment_id = '$id'";
    $result_check_results = $db->query($sql_check_results);
    $row_check_results = $result_check_results->fetch_assoc();

    if ($row_check_submissions['usage_count'] > 0 || $row_check_results['usage_count'] > 0) {
        // If Assignment has submissions or results, redirect with a specific error message
        header("Location: manage_assignments.php?status=error_in_use&message=Deletion failed! This assignment has student submissions or results.");
        exit();
    } else {
        // If Assignment is NOT in use, proceed with deletion from 'assessments' table
        // Ensure it's an Assignment type we are deleting
        $sql_delete = "DELETE FROM assessments WHERE id = '$id' AND assessment_type = 'Assignment'";
        if ($db->query($sql_delete)) {
            header("Location: manage_assignments.php?status=deleted");
            exit();
        } else {
            // Database error during deletion
            header("Location: manage_assignments.php?status=error&message=Failed to delete the assignment from the database.");
            exit();
        }
    }
} else {
    // Redirect if not a POST request or no ID provided
    header("Location: manage_assignments.php");
    exit();
}

ob_end_flush(); // Output buffering end
?>