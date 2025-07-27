<?php
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'delete_user')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Id'])) {
    $id = $_POST['Id'];
    $profile_image = $_POST['ProfileImage'];
    
    $db = dbConn();

    // --- VALIDATION STEP ---
    // Check if the user is assigned as a teacher in the 'classes' table
    $check_sql = "SELECT COUNT(*) as class_count FROM classes WHERE teacher_id = '$id'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['class_count'] > 0) {
        // If the user is a teacher for one or more classes, do not delete.
        // Redirect back with a foreign key error status.
        header("Location: manage.php?status=error_fk");
        exit();
    } else {
        // If the user is not a teacher, proceed with deletion.
        $delete_sql = "DELETE FROM users WHERE Id = '$id'";
        
        if ($db->query($delete_sql)) {
            // If deletion is successful, delete the profile image
            if (!empty($profile_image) && $profile_image != 'default_avatar.png') {
                $image_path = '../../web/uploads/profile_images/' . $profile_image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            // Redirect with a success message
            header("Location: manage.php?status=deleted");
            exit();
        } else {
            // If deletion fails for some other reason
            header("Location: manage.php?status=error_delete");
            exit();
        }
    }
} else {
    // If accessed directly without POST method, redirect to manage page
    header("Location: manage.php");
    exit();
}
?>
