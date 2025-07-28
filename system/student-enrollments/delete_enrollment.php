<?php
ob_start();
include '../../init.php';

// Check if the form was submitted correctly with an enrollment_id
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enrollment_id'])) {
    $db = dbConn();
    $enrollment_id = dataClean($_POST['enrollment_id']);

    $can_delete = true; // Assume we can delete, until proven otherwise

    // --- Step 1: Get student and class IDs ---
    $sql_get_ids = "SELECT student_user_id, class_id FROM enrollments WHERE id = '$enrollment_id'";
    $result_ids = $db->query($sql_get_ids);

    if ($result_ids && $result_ids->num_rows > 0) {
        $data = $result_ids->fetch_assoc();
        $student_id = $data['student_user_id'];
        $class_id = $data['class_id'];

        // --- Step 2: Check for related records (invoices, results) ---
        $sql_check_invoices = "SELECT COUNT(*) as count FROM invoices WHERE student_user_id = '$student_id' AND class_id = '$class_id'";
        $invoice_count = $db->query($sql_check_invoices)->fetch_assoc()['count'];

        $sql_check_results = "SELECT COUNT(ar.id) as count FROM assessment_results ar JOIN assessments a ON ar.assessment_id = a.id WHERE ar.student_user_id = '$student_id' AND a.class_id = '$class_id'";
        $results_count = $db->query($sql_check_results)->fetch_assoc()['count'];
        
        if ($invoice_count > 0 || $results_count > 0) {
            // If related records exist, do not allow deletion
            $can_delete = false;
            $_SESSION['status_message'] = "Deletion Failed!";
            $_SESSION['status_type'] = "danger";
            $_SESSION['status_detail'] = 'This enrollment has related invoices or results and cannot be deleted.';
        }
    } else {
        // If the enrollment record itself doesn't exist
        $can_delete = false;
        $_SESSION['status_message'] = "Error!";
        $_SESSION['status_type'] = "danger";
        $_SESSION['status_detail'] = 'The enrollment record was not found.';
    }
    
    // --- Step 3: If all checks passed, delete the record ---
    if ($can_delete) {
        $sql_delete = "DELETE FROM enrollments WHERE id = '$enrollment_id'";
        
        if ($db->query($sql_delete)) {
            $_SESSION['status_message'] = "Enrollment was successfully deleted!";
            $_SESSION['status_type'] = "success";
        } else {
            $_SESSION['status_message'] = "Error!";
            $_SESSION['status_type'] = "danger";
            $_SESSION['status_detail'] = 'Could not delete the enrollment record from the database.';
        }
    }
    
    // --- Step 4: Redirect back to the list page ---
    header("Location: manage_enrollments.php");
    exit();

} else {
    // If accessed directly, redirect back
    header("Location: manage_enrollments.php");
    exit();
}
?>