<?php
ob_start();
include '../../init.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $db = dbConn();

    // Check if the student is enrolled in any classes
    $check_sql = "SELECT COUNT(*) as count FROM enrollments WHERE student_user_id = '$id'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        // If enrolled, prevent deletion and show an error
        header("Location: manage_students.php?status=error_fk");
        exit();
    } else {
        // If not enrolled in any class, proceed with deletion
        // Deleting from 'users' table will also delete from 'student_details' due to CASCADE constraint
        $delete_sql = "DELETE FROM users WHERE Id = '$id'";
        if ($db->query($delete_sql)) {
            header("Location: manage_students.php?status=deleted");
            exit();
        } else {
            header("Location: manage_students.php?status=error");
            exit();
        }
    }
} else {
    header("Location: manage_students.php");
    exit();
}
?>