<?php
ob_start();
include '../../init.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $db = dbConn();

    $check_sql = "SELECT COUNT(*) as class_count FROM classes WHERE subject_id = '$id'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['class_count'] > 0) {
        header("Location: manage_subjects.php?status=error_fk");
        exit();
    } else {
        $delete_sql = "DELETE FROM subjects WHERE id = '$id'";
        if ($db->query($delete_sql)) {
            header("Location: manage_subjects.php?status=deleted");
            exit();
        } else {
            header("Location: manage_subjects.php?status=error");
            exit();
        }
    }
} else {
    header("Location: manage_subjects.php");
    exit();
}
?>