<?php
ob_start();
include '../../init.php';

// Check if form was submitted to update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_status'])) {
    $db = dbConn();

    $enrollment_id = dataClean($_POST['enrollment_id']);
    $new_status = dataClean($_POST['new_status']);

    // Validate the status value
    $allowed_statuses = ['active', 'pending', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        
        $sql = "UPDATE enrollments SET status = '$new_status' WHERE id = '$enrollment_id'";
        
        if ($db->query($sql)) {
            $_SESSION['status_message'] = "Enrollment status successfully updated!";
            $_SESSION['status_type'] = "success";
        } else {
            $_SESSION['status_message'] = "Error: Could not update status.";
            $_SESSION['status_type'] = "danger";
        }

    } else {
        $_SESSION['status_message'] = "Invalid status value provided.";
        $_SESSION['status_type'] = "warning";
    }

    header("Location: manage_enrollments.php");
    exit();

} else {
    // If accessed directly, redirect back
    header("Location: manage_enrollments.php");
    exit();
}
?>