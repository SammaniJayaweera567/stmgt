<?php
include '../../../init.php';
$db = dbConn();

if (!hasPermission($_SESSION['user_id'], 'delete_permission')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to permissions page
    $backUrl = $_SERVER['HTTP_REFERER'] ?? 'permission.php';

    header("Location: $backUrl");
    exit;
}
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Prevent deletion of IDs between 1 and 123
    if ($id >= 1 && $id <= 123) {
        $_SESSION['error'] = "This permission is protected and cannot be deleted.";
        header("Location: permissions.php");
        exit();
    }

    // First delete related entries (if needed)
    $stmt = $db->prepare("DELETE FROM role_permissions WHERE permission_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    // Then delete the permission itself
    $stmt = $db->prepare("DELETE FROM permissions WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $_SESSION['success'] = "Permission deleted successfully.";
    header("Location: permissions.php");
    exit();
}

header("Location: permission.php");
exit();
?>
