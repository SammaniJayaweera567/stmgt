<?php
ob_start();
include '../../../init.php';

$db = dbConn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    // --- Check for usage in other tables before deleting ---

    // 1. Check in 'exam_attendance' table
    $sql_check_attendance = "SELECT COUNT(*) as usage_count FROM exam_attendance WHERE assessment_id = '$id'";
    $attendance_count = $db->query($sql_check_attendance)->fetch_assoc()['usage_count'];

    // 2. Check in 'assessment_results' table
    $sql_check_results = "SELECT COUNT(*) as usage_count FROM assessment_results WHERE assessment_id = '$id'";
    $results_count = $db->query($sql_check_results)->fetch_assoc()['usage_count'];

    if ($attendance_count > 0 || $results_count > 0) {
        // If the exam is in use, set a session-based error message and redirect
        $_SESSION['status_message'] = "Deletion Failed!";
        $_SESSION['status_type'] = "danger";
        $_SESSION['status_detail'] = "This exam cannot be deleted because attendance or results have already been recorded.";
        
        header("Location: manage_exams.php");
        exit();
    } else {
        // If not in use, proceed with deletion
        $sql_delete = "DELETE FROM assessments WHERE id = '$id' AND assessment_type = 'Exam'";
        
        if ($db->query($sql_delete)) {
            $_SESSION['status_message'] = "Exam successfully deleted!";
            $_SESSION['status_type'] = "success";
        } else {
            $_SESSION['status_message'] = "Error: Could not delete the exam.";
            $_SESSION['status_type'] = "danger";
        }
        
        header("Location: manage_exams.php");
        exit();
    }
} else {
    // If not a POST request or no ID provided, redirect
    header("Location: manage_exams.php");
    exit();
}
?>