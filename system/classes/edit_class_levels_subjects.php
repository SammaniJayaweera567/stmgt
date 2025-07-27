<?php
ob_start(); 
include '../../init.php'; 
if (!hasPermission($_SESSION['user_id'], 'edit_class_subject')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$messages = [];
$id = null; 
$class_level_id = '';
$subject_id = '';
$status = '';

// Handle form submission for UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = dataClean($_POST['id'] ?? ''); 
    $class_level_id = dataClean($_POST['class_level_id'] ?? '');
    $subject_id = dataClean($_POST['subject_id'] ?? '');
    $status = dataClean($_POST['status'] ?? '');

    // --- Validation ---
    if (empty($class_level_id)) {
        $messages['class_level_id'] = "Class Level selection is required...!";
    }
    if (empty($subject_id)) {
        $messages['subject_id'] = "Subject selection is required...!";
    }
    if (empty($status)) {
        $messages['status'] = "Status is required...!";
    }

    $db = dbConn();

    // Check for duplicate combination, ignoring the current record
    if (empty($messages)) {
        $sql_check_duplicate = "SELECT id FROM class_levels_subjects WHERE class_level_id = '$class_level_id' AND subject_id = '$subject_id' AND id != '$id'";
        if ($db->query($sql_check_duplicate)->num_rows > 0) {
            $messages['duplicate_entry'] = "This Subject is already assigned to this Class Level for another record...!";
        }
    }

    // If no validation errors, proceed with update
    if (empty($messages)) {
        $updated_at = date('Y-m-d H:i:s');
        $sql = "UPDATE class_levels_subjects SET 
                    class_level_id='$class_level_id', 
                    subject_id='$subject_id', 
                    status='$status', 
                    updated_at='$updated_at' 
                WHERE id='$id'";
        
        if ($db->query($sql) === TRUE) {
            $_SESSION['status_message'] = "Class Level Subject updated successfully!";
            header("Location: manage_class_levels_subjects.php"); 
            exit();
        }
    }
}

// This logic runs for the initial page load with an ID from either POST or GET
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $id = dataClean($_POST['id']);
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = dataClean($_GET['id']);
}

// Fetch data if an ID is available
if ($id) {
    $db = dbConn();
    $sql = "SELECT * FROM class_levels_subjects WHERE id='$id'";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $class_level_id = $row['class_level_id'];
        $subject_id = $row['subject_id'];
        $status = $row['status'];
    } else {
        // If no record found, redirect
        header("Location: manage_class_levels_subjects.php");
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] != 'POST') { // Redirect if no ID and not a failed POST submission
    header("Location: manage_class_levels_subjects.php");
    exit();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit Class Level Subject Relationship</h3>
                </div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" novalidate>
                    <input type="hidden" name="id" value="<?= htmlspecialchars(@$id) ?>">
                    <input type="hidden" name="action" value="update">

                    <div class="card-body">
                        <?php 
                        if (!empty($messages['duplicate_entry'])) { ?>
                        <div class="alert alert-warning" role="alert">
                            <?= @$messages['duplicate_entry'] ?>
                        </div>
                        <?php } ?>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label for="classLevelSelect">Select Class Level (Grade)</label>
                                <select name="class_level_id" id="classLevelSelect" class="form-control">
                                    <option value="">-- Select Class Level --</option>
                                    <?php
    $db = dbConn(); 
    // දැනට Active ඇති සියල්ල සහ, මෙම record එකට අදාළ level එක Inactive වුවත් එයද ගෙන එන්න.
    $sql_class_levels = "
        SELECT id, level_name FROM class_levels WHERE status = 'Active'
        UNION
        SELECT id, level_name FROM class_levels WHERE id = '$class_level_id'
    ";
    $result_class_levels = $db->query($sql_class_levels);
    
    while($row_class_level = $result_class_levels->fetch_assoc()){
        $selected = ($class_level_id == $row_class_level['id']) ? 'selected' : '';
        echo "<option value='{$row_class_level['id']}' $selected>{$row_class_level['level_name']}</option>";
    }
    ?>
                                </select>
                                <span class="text-danger"><?= @$messages['class_level_id'] ?></span>
                            </div>

                            <div class="form-group col-md-6 mb-3">
                                <label for="subjectSelect">Select Subject</label>
                                <select name="subject_id" id="subjectSelect" class="form-control">
                                    <option value="">-- Select Subject --</option>
                                    <?php
                                    $sql_subjects = "SELECT id, subject_name FROM subjects WHERE status='Active'";
                                    $result_subjects = $db->query($sql_subjects);
                                    while($row_subject = $result_subjects->fetch_assoc()){
                                        $selected = (@$subject_id == $row_subject['id']) ? 'selected' : '';
                                        echo "<option value='{$row_subject['id']}' $selected>{$row_subject['subject_name']}</option>";
                                    }
                                    ?>
                                </select>
                                <span class="text-danger"><?= @$messages['subject_id'] ?></span>
                            </div>
                        </div>

                        <div class="form-group col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">-- Select Status --</option>
                                <option value="Active" <?= (@$status == 'Active') ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= (@$status == 'Inactive') ? 'selected' : '' ?>>Inactive
                                </option>
                            </select>
                            <span class="text-danger"><?= @$messages['status'] ?></span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Relationship</button>
                        <a href="manage_class_levels_subjects.php" class="btn btn-secondary ms-2">Cancel</a>
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