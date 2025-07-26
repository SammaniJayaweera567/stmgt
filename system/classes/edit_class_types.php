<?php
ob_start();
include '../../init.php';

$type_name = '';
$status = '';
$messages = [];

// Handle form submission for UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = dataClean($_POST['id']);
    $type_name = dataClean($_POST['type_name']);
    $status = dataClean($_POST['status']);

    if (empty($type_name)) $messages['type_name'] = "Type Name is required.";
    if (empty($status)) $messages['status'] = "Status is required.";

    if (!empty($type_name)) {
        $db = dbConn();
        $sql_check = "SELECT id FROM class_types WHERE type_name = '$type_name' AND id != '$id'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['type_name'] = "This Class Type already exists.";
        }
    }

    if (empty($messages)) {
        $db = dbConn();
        $sql = "UPDATE class_types SET type_name='$type_name', status='$status' WHERE id='$id'";
        $db->query($sql);
        header("Location: manage_class_types.php?status=updated");
        exit();
    }
}

// Fetch existing data when page loads via POST from manage_class_types.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $id = dataClean($_POST['id']);
    $db = dbConn();
    $sql = "SELECT * FROM class_types WHERE id='$id'";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $type_name = $row['type_name'];
        $status = $row['status'];
    }
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Edit Class Type</h3></div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <input type="hidden" name="id" value="<?= @$id ?>">
                    <input type="hidden" name="action" value="update">
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
                        <button type="submit" class="btn btn-primary">Update Type</button>
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