<?php
ob_start();
// The path needs to be from system/classes/materials/
include '../../../init.php'; 
if (!hasPermission($_SESSION['user_id'], 'delete_class_material')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$db = dbConn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)($_POST['id']);

    // --- Before deleting the database record, we must delete the associated file from the server ---
    
    // 1. Get the file path from the database
    $sql_get_file = "SELECT file_path FROM class_materials WHERE id = '$id'";
    $result_file = $db->query($sql_get_file);

    if ($result_file && $result_file->num_rows > 0) {
        $file_data = $result_file->fetch_assoc();
        // Construct the full server path to the file
        $file_to_delete = '../../../web/uploads/materials/' . $file_data['file_path'];
        
        // 2. Check if the file exists on the server and delete it
        if (file_exists($file_to_delete)) {
            unlink($file_to_delete); // This deletes the file
        }
    }

    // 3. Now, proceed with deleting the record from the database
    $sql_delete = "DELETE FROM class_materials WHERE id = '$id'";
    if ($db->query($sql_delete)) {
        // Set a success message and redirect
        $_SESSION['status_message'] = "Material deleted successfully!";
        header("Location: manage_materials.php");
        exit();
    } else {
        // Database error during deletion
        $_SESSION['status_message'] = "Error: Could not delete the material from the database.";
        header("Location: manage_materials.php?status=error");
        exit();
    }
} else {
    // Redirect if not a POST request or no ID provided
    header("Location: manage_materials.php");
    exit();
}

ob_end_flush();
?>
