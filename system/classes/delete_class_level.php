<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'delete_class_level')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $db = dbConn();

    // --- VALIDATION STEP ---
    // Check if this class level is being used in the 'classes' table
    $check_sql = "SELECT COUNT(*) as class_count FROM classes WHERE class_level_id = '$id'";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['class_count'] > 0) {
        // If the level is in use, redirect with a specific error message
        header("Location: manage_class_levels.php?status=error_fk");
        exit();
    } else {
        // If the level is NOT in use, proceed with deletion
        $delete_sql = "DELETE FROM class_levels WHERE id = '$id'";
        if ($db->query($delete_sql)) {
            header("Location: manage_class_levels.php?status=deleted");
            exit();
        } else {
            // Handle other potential deletion errors
            header("Location: manage_class_levels.php?status=error");
            exit();
        }
    }
} else {
    // If accessed directly, redirect away
    header("Location: manage_class_levels.php");
    exit();
}
?>