<?php
ob_start();
include '../../init.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $db = dbConn();

    // Check if this classroom is being used in the 'classes' table
    $check_sql = "SELECT COUNT(*) as class_count FROM classes WHERE class_room_id = '$id'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['class_count'] > 0) {
        // If the room is in use, redirect with an error
        header("Location: manage_class_rooms.php?status=error_fk");
        exit();
    } else {
        // If the room is NOT in use, proceed with deletion
        $delete_sql = "DELETE FROM class_rooms WHERE id = '$id'";
        if ($db->query($delete_sql)) {
            header("Location: manage_class_rooms.php?status=deleted");
            exit();
        } else {
            header("Location: manage_class_rooms.php?status=error");
            exit();
        }
    }
} else {
    header("Location: manage_class_rooms.php");
    exit();
}
?>