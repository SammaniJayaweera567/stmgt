<?php
ob_start();
include '../../init.php';

$messages = [];
$id = null; // Initialize id variable

// Handle form submission for UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = dataClean($_POST['id']);
    $level_name = dataClean($_POST['level_name']);
    $status = dataClean($_POST['status']);

    if (empty($level_name)) $messages['level_name'] = "Level Name is required.";
    if (empty($status)) $messages['status'] = "Status is required.";

    $db = dbConn();
    if (!empty($level_name)) {
        $sql_check = "SELECT id FROM class_levels WHERE level_name = '$level_name' AND id != '$id'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['level_name'] = "This Level Name already exists.";
        }
    }

    if (empty($messages)) {
        // --- FIX: Added the 'updated_at' field to the query ---
        $updated_at = date('Y-m-d H:i:s');
        $sql = "UPDATE class_levels SET level_name='$level_name', status='$status', updated_at='$updated_at' WHERE id='$id'";
        
        $db->query($sql);
        header("Location: manage_class_levels.php?status=updated");
        exit();
    }
}

// Fetch existing data when page loads via POST from manage.php
// This logic runs when the 'Edit' button is clicked
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $id = dataClean($_POST['id']);
    $db = dbConn();
    $sql = "SELECT * FROM class_levels WHERE id='$id'";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $level_name = $row['level_name'];
        $status = $row['status'];
    }
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Edit Class Level</h3></div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <input type="hidden" name="id" value="<?= @$id ?>">
                    <input type="hidden" name="action" value="update">
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
                        <button type="submit" class="btn btn-primary">Update Level</button>
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