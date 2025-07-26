<?php
ob_start();
include '../../init.php';

// Initialize form variables to prevent errors on initial page load
$level_name = '';
$status = '';

$messages = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Use null coalescing operator to prevent deprecated warnings
    $level_name = dataClean($_POST['level_name'] ?? '');
    $status = dataClean($_POST['status'] ?? '');

    if (empty($level_name)) {
        $messages['level_name'] = "Level Name is required.";
    } else {
        $db = dbConn();
        $sql_check = "SELECT id FROM class_levels WHERE level_name = '$level_name'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['level_name'] = "This Level Name already exists.";
        }
    }
    
    if (empty($status)) {
        $messages['status'] = "Status is required.";
    }

    if (empty($messages)) {
        $db = dbConn();
        
        // --- FIX: Added the 'created_at' field to the query ---
        $created_at = date('Y-m-d H:i:s');
        $sql = "INSERT INTO class_levels (level_name, status, created_at) VALUES ('$level_name', '$status', '$created_at')";
        
        $db->query($sql);
        header("Location: manage_class_levels.php?status=added");
        exit();
    }
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Create New Class Level</h3>
                </div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Level Name</label>
                            <input type="text" name="level_name" class="form-control" value="<?= htmlspecialchars(@$level_name) ?>">
                            <span class="text-danger"><?= @$messages['level_name'] ?></span>
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
                        <button type="submit" class="btn btn-primary">Save Level</button>
                        <a href="manage_class_levels.php" class="btn btn-outline-secondary ms-2">Cancel</a>
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