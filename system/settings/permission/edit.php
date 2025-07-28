<?php
ob_start();
include '../../../init.php';
$db = dbConn();
if (!isset($_SESSION['user_id'])) {
    header("Location: " . SYS_URL . "login.php");
    exit();
}
if (!hasPermission($_SESSION['user_id'], 'edit_permission')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $permission = $db->query("SELECT * FROM permissions WHERE id = $id LIMIT 1")->fetch_assoc();
}

if (isset($_POST['update_permission'])) {
    $id = (int) $_POST['id'];
    $name = $_POST['name'];
    $slug = $_POST['slug'];

    if ($id >= 1 && $id <= 123) {
        // Only update name, not slug
        $stmt = $db->prepare("UPDATE permissions SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $_SESSION['success'] = "Permission name updated successfully.";
        $_SESSION['info'] = "Note: Slug cannot be updated for system-reserved permissions (ID 1–123).";
    } else {
        // Update both name and slug
        $stmt = $db->prepare("UPDATE permissions SET name = ?, slug = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $slug, $id);
        $_SESSION['success'] = "Permission updated successfully.";
    }

    $stmt->execute();
    header("Location: permission.php");
    exit();
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-users mt-1 me-2 mr-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Permission</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title" style="font-size: 1.05rem !important;">User List</h6>
                </div>
                <div class="card-body">
<form method="POST">
    <input type="hidden" name="id" value="<?= $permission['id'] ?>">
    <div class="mb-3">
        <label class="form-label">Permission Name</label>
        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($permission['name']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Slug</label>
        <input type="text" class="form-control" name="slug" value="<?= htmlspecialchars($permission['slug']) ?>" required>
    </div>
    <button type="submit" name="update_permission" class="btn btn-primary">Update</button>
</form>
                </div>
            </div>
        </div>
    </div>

<?php $content = ob_get_clean(); include '../../layouts.php'; ?>
