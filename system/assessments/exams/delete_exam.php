<?php
ob_start(); // Output buffering start
include '../../../init.php'; // Corrected path

// No user role check for now, will add later
// if (!isset($_SESSION['user_id'])) { header("Location:../../../login.php"); exit(); }

$db = dbConn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)($_POST['id']);

    // --- Check for usage in other tables before deleting an Exam ---

    // 1. Check in 'exam_attendance' table
    $sql_check_attendance = "SELECT COUNT(*) as usage_count FROM exam_attendance WHERE assessment_id = '$id'";
    $result_check_attendance = $db->query($sql_check_attendance);
    $row_check_attendance = $result_check_attendance->fetch_assoc();
    if ($row_check_attendance['usage_count'] > 0) {
        header("Location: manage_exams.php?status=error_in_use&message=This exam has recorded attendance and cannot be deleted.");
        exit();
    }
    
    // 2. Check in 'assessment_results' table
    $sql_check_results = "SELECT COUNT(*) as usage_count FROM assessment_results WHERE assessment_id = '$id'";
    $result_check_results = $db->query($sql_check_results);
    $row_check_results = $result_check_results->fetch_assoc();
    if ($row_check_results['usage_count'] > 0) {
        header("Location: manage_exams.php?status=error_in_use&message=This exam has recorded results and cannot be deleted.");
        exit();
    }
    
    // If NO usage found in related tables, proceed with deletion from 'assessments' table
    // Ensure that only Exams are deleted through this file by checking assessment_type
    $sql_delete = "DELETE FROM assessments WHERE id = '$id' AND assessment_type = 'Exam'";
    if ($db->query($sql_delete)) {
        header("Location: manage_exams.php?status=deleted");
        exit();
    } else {
        // Database error during deletion from main table
        header("Location: manage_exams.php?status=error&message=Failed to delete exam from database.");
        exit();
    }
} else {
    // Redirect if not a POST request or no ID provided
    header("Location: manage_exams.php");
    exit();
}

ob_end_flush(); // Output buffering end
?>