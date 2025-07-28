<?php
ob_start();
include '../../../init.php'; 

$db = dbConn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)($_POST['id']);

    // Check for usage in 'student_discounts' table before deleting
    $sql_check_usage = "SELECT COUNT(*) as usage_count FROM student_discounts WHERE discount_type_id = '$id'";
    $result_check_usage = $db->query($sql_check_usage);
    $row_check_usage = $result_check_usage->fetch_assoc();

    if ($row_check_usage['usage_count'] > 0) {
        $_SESSION['status_message'] = "Deletion failed! This discount type is currently in use.";
        header("Location: manage_types.php?status=error_in_use");
        exit();
    } else {
        $sql_delete = "DELETE FROM discount_types WHERE id = '$id'";
        if ($db->query($sql_delete)) {
            $_SESSION['status_message'] = "Discount type deleted successfully!";
            header("Location: manage_types.php?status=deleted");
            exit();
        } else {
            $_SESSION['status_message'] = "Error: Could not delete the discount type.";
            header("Location: manage_types.php?status=error");
            exit();
        }
    }
} else {
    header("Location: manage_types.php");
    exit();
}

ob_end_flush();
?>