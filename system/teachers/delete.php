<?php
ob_start();
include '../../init.php';

// Check for login session and valid POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    
    $user_id = (int)$_POST['user_id'];
    $profile_image = dataClean($_POST['ProfileImage'] ?? '');
    
    $db = dbConn();

    // 1. Check if this teacher is assigned to any classes
    $check_sql = "SELECT COUNT(*) as class_count FROM classes WHERE teacher_id = '$user_id'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

    // 2. If the teacher is in use, redirect with an error
    if ($row['class_count'] > 0) {
        header("Location: manage.php?status=error_in_use");
        exit();
    } 
    // 3. If the teacher is NOT in use, proceed with deletion
    else {
        // Step A: Delete from the child table `teacher_details` first
        $delete_details_sql = "DELETE FROM teacher_details WHERE user_id = '$user_id'";
        $db->query($delete_details_sql);

        // Step B: Delete from the parent table `users`
        $delete_user_sql = "DELETE FROM users WHERE Id = '$user_id'";
        if ($db->query($delete_user_sql)) {
            // If deletion from `users` is successful, delete the image
            if (!empty($profile_image) && file_exists('../uploads/' . $profile_image)) {
                unlink('../uploads/' . $profile_image);
            }
            // Redirect with success message
            header("Location: manage.php?status=deleted");
            exit();
        } else {
            // If deletion fails for some reason
            header("Location: manage.php?status=error");
            exit();
        }
    }
} 
// If the request is not valid, redirect back
else {
    header("Location: manage.php");
    exit();
}
?>