<?php
ob_start();
include '../../init.php'; // Make sure the path to your init file is correct

// Check if the form was submitted via POST and an ID is present
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $db = dbConn();
    // Use dataClean to be consistent with your pattern, though (int) is also good for IDs
    $id = dataClean($_POST['id']);

    // --- Foreign Key Check ---
    // Check if this academic_year_id is being used in the 'classes' table
    $check_sql = "SELECT COUNT(*) as class_count FROM classes WHERE academic_year_id = '$id'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

   if ($row['class_count'] > 0) {
    // --- ADD THIS LINE ---
    $_SESSION['status_detail'] = 'This Academic Year cannot be deleted because it is used by one or more classes.';
    
    header("Location: manage.php?status=error_fk");
    exit();
} else {
        // If the academic year is NOT in use, proceed with deletion
        $delete_sql = "DELETE FROM academic_years WHERE id = '$id'";
        if ($db->query($delete_sql)) {
            // If deletion is successful, redirect with a success message
            $_SESSION['status_message'] = "Academic Year successfully deleted!";
            $_SESSION['status_type'] = "success";
            header("Location: manage.php");
            exit();
        } else {
            // If there was a database error during deletion
            $_SESSION['status_message'] = "Error: Could not delete the academic year.";
            $_SESSION['status_type'] = "danger";
            header("Location: manage.php");
            exit();
        }
    }
} else {
    // If the page is accessed without a POST request, just redirect back
    header("Location: manage.php");
    exit();
}
?>