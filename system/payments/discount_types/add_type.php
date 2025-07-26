<?php
ob_start();
include '../../../init.php'; 

$type_name = '';
$value_logic = 'None';
$default_value = 0;
$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type_name = dataClean($_POST['type_name'] ?? '');
    $value_logic = dataClean($_POST['value_logic'] ?? 'None');
    $default_value = dataClean($_POST['default_value'] ?? 0);

    if (empty($type_name)) {
        $messages['type_name'] = "Type Name is required.";
    } else {
        $db = dbConn();
        $sql_check = "SELECT id FROM discount_types WHERE type_name = '$type_name'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['type_name'] = "This discount type already exists.";
        }
    }

    if (empty($messages)) {
        $sql = "INSERT INTO discount_types (type_name, value_logic, default_value) VALUES ('$type_name', '$value_logic', '$default_value')";
        if ($db->query($sql)) {
            $_SESSION['status_message'] = "Discount Type added successfully!";
            header("Location: manage_types.php");
            exit();
        } else {
            $messages['main_error'] = "Database error.";
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Create New Discount Type</h3>
                </div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Type Name</label>
                            <input type="text" name="type_name" class="form-control" placeholder="E.g., Sibling Discount" value="<?= htmlspecialchars(@$type_name) ?>">
                            <span class="text-danger"><?= @$messages['type_name'] ?></span>
                        </div>
                        <div class="form-group mb-3">
                            <label>Value Logic</label>
                            <select name="value_logic" id="value_logic" class="form-control">
                                <option value="None" <?= (@$value_logic == 'None') ? 'selected' : '' ?>>None (e.g., for manual entry later)</option>
                                <option value="Percentage" <?= (@$value_logic == 'Percentage') ? 'selected' : '' ?>>Percentage</option>
                                <option value="Fixed Amount" <?= (@$value_logic == 'Fixed Amount') ? 'selected' : '' ?>>Fixed Amount</option>
                            </select>
                        </div>
                        <div class="form-group mb-3" id="default_value_wrapper" style="display: none;">
                            <label>Default Value</label>
                            <input type="number" step="0.01" name="default_value" class="form-control" value="<?= htmlspecialchars(@$default_value) ?>">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create Type</button>
                        <a href="manage_types.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const logicSelect = document.getElementById('value_logic');
    const valueWrapper = document.getElementById('default_value_wrapper');
    function toggleValueField() {
        if (logicSelect.value === 'Percentage' || logicSelect.value === 'Fixed Amount') {
            valueWrapper.style.display = 'block';
        } else {
            valueWrapper.style.display = 'none';
        }
    }
    logicSelect.addEventListener('change', toggleValueField);
    toggleValueField(); // Run on page load
});
</script>
<?php
$content = ob_get_clean();
include '../../layouts.php';
?>