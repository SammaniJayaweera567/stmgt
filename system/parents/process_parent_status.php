<?php
include '../../init.php';

// Check if the form was submitted
if (isset($_POST['update_status'])) {
    $db = dbConn();

    // Sanitize the input
    $parent_id = dataClean($_POST['parent_id']);
    $new_status = dataClean($_POST['new_status']);

    // Validate the status value to be safe
    if ($new_status == 'Active' || $new_status == 'Inactive') {
        
        // Prepare the SQL statement to update the user's status
        $sql = "UPDATE users SET Status = '$new_status' WHERE Id = '$parent_id' AND user_role_id = 5";

        if ($db->query($sql)) {
            // If update is successful, set a success message and redirect back
            $_SESSION['status_message'] = "Parent status successfully updated!";
            $_SESSION['status_type'] = "success";
        } else {
            // If update fails, set an error message
            $_SESSION['status_message'] = "Error: Could not update parent status.";
            $_SESSION['status_type'] = "danger";
        }
    } else {
        // If an invalid status was submitted
        $_SESSION['status_message'] = "Invalid status value provided.";
        $_SESSION['status_type'] = "warning";
    }

    // Redirect back to the manage parents page
    header("Location: manage_parents.php");
    exit();

} else {
    // If someone accesses this page directly, redirect them
    header("Location: manage_parents.php");
    exit();
}
?>