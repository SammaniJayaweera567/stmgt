<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'add_class_type')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$type_name = '';
$status = '';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type_name = dataClean($_POST['type_name']);
    $status = dataClean($_POST['status']);

    if (empty($type_name)) $messages['type_name'] = "Type Name is required.";
    if (empty($status)) $messages['status'] = "Status is required.";
    
    if (!empty($type_name)) {
        $db = dbConn();
        $sql_check = "SELECT id FROM class_types WHERE type_name = '$type_name'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['type_name'] = "This Class Type already exists.";
        }
    }

    if (empty($messages)) {
        $db = dbConn();
        $sql = "INSERT INTO class_types (type_name, status) VALUES ('$type_name', '$status')";
        $db->query($sql);
        header("Location: manage_class_types.php?status=added");
        exit();
    }
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Create New Class Type</h3></div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Type Name</label>
                            <input type="text" name="type_name" class="form-control" value="<?= htmlspecialchars(@$type_name) ?>">
                            <span class="text-danger"><?= @$messages['type_name'] ?></span>
                        </div>
                        <div class="form-group mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">-- Select Status --</option>
                                <option value="Active" <?= (@$status == 'Active') ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= (@$status == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <span class="text-danger"><?= @$messages['status'] ?></span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save Type</button>
                        <a href="manage_class_types.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layouts.php';
?>