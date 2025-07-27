<?php
ob_start();
include '../../init.php'; // Make sure the path to your init file is correct
if (!hasPermission($_SESSION['user_id'], 'delete_class_type')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// Check if the request is a POST and an ID is provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $db = dbConn();
    $id = dataClean($_POST['id']);

    // --- Foreign Key Check ---
    // Check if this class_type_id is being used in the 'classes' table
    $check_sql = "SELECT COUNT(*) as class_count FROM classes WHERE class_type_id = '$id'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['class_count'] > 0) {
        // If the class type is in use, it cannot be deleted.
        // Set a specific session message for our show_status_message() function
        $_SESSION['status_detail'] = 'This class type cannot be deleted because it is currently assigned to one or more classes.';
        
        // Redirect with a specific error status
        header("Location: manage_class_types.php?status=error_fk");
        exit();
    } else {
        // If the class type is NOT in use, proceed with deletion
        $delete_sql = "DELETE FROM class_types WHERE id = '$id'";
        
        if ($db->query($delete_sql)) {
            // Redirect with a success status if deletion is successful
            $_SESSION['status_message'] = "Class Type deleted successfully!";
            header("Location: manage_class_types.php?status=deleted");
            exit();
        } else {
            // Redirect with a generic error status if deletion fails
            $_SESSION['status_detail'] = "Database error: " . $db->error;
            header("Location: manage_class_types.php?status=error");
            exit();
        }
    }
} else {
    // If the page is accessed directly or without an ID, redirect back
    header("Location: manage_class_types.php");
    exit();
}
?>