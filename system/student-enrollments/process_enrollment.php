<?php
include '../../init.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
    exit();
}

// --- STATUS UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    
    $enrollment_id = dataClean(@$_POST['enrollment_id']);
    $new_status = dataClean(@$_POST['new_status']);
    
    if (empty($enrollment_id) || empty($new_status)) {
        header("Location: manage_enrollments.php?status=error_data");
        exit();
    }
    
    $db = dbConn();
    $sql = "UPDATE enrollments SET status = '$new_status' WHERE id = '$enrollment_id'";
    
    if ($db->query($sql)) {
        header("Location: manage_enrollments.php?status=updated");
    } else {
        header("Location: manage_enrollments.php?status=error_update");
    }
    exit();
}

// --- DELETE ENROLLMENT LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_enrollment'])) {

    $enrollment_id = dataClean(@$_POST['enrollment_id']);

    if (empty($enrollment_id)) {
        header("Location: manage_enrollments.php?status=error_data");
        exit();
    }

    $db = dbConn();
    $sql = "DELETE FROM enrollments WHERE id = '$enrollment_id'";

    if ($db->query($sql)) {
        header("Location: manage_enrollments.php?status=deleted");
    } else {
        header("Location: manage_enrollments.php?status=error_delete");
    }
    exit();
}

// If accessed directly without a valid action
header("Location: manage_enrollments.php");
exit();
?>