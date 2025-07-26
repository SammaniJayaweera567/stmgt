<?php
ob_start();
include '../../init.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = dataClean($_POST['id']);
    $db = dbConn();

    // --- NEW: Foreign Key Check ---
    // Check if any students are enrolled in this class
    $check_sql = "SELECT COUNT(*) as enrollment_count FROM enrollments WHERE class_id = '$id' AND status = 'Active'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['enrollment_count'] > 0) {
        // If students are enrolled, prevent deletion
        header("Location: manage_classes.php?status=error_fk");
        exit();
    } else {
        // If no active enrollments, proceed with deletion
        $sql = "DELETE FROM classes WHERE id='$id'";
        $db->query($sql);
        header("Location: manage_classes.php?status=deleted");
        exit();
    }
} else {
    header("Location: manage_classes.php");
    exit();
}
?>
