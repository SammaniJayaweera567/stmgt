<?php
ob_start();
include '../../init.php'; // Correct path from /system/grades/
if (!hasPermission($_SESSION['user_id'], 'edit_assement_grade')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}

$db = dbConn();
$messages = [];
$id = (int)($_REQUEST['id'] ?? 0); // Get grade ID from GET or POST

if ($id === 0) {
    header("Location: manage_grades.php?status=notfound&message=Grade ID not provided.");
    exit();
}

// --- Fetch existing data for the form ---
$sql_fetch = "SELECT * FROM grades WHERE id='$id'";
$result_fetch = $db->query($sql_fetch);

if ($result_fetch->num_rows === 0) {
    header("Location: manage_grades.php?status=notfound&message=Grade not found.");
    exit();
}
$grade_data = $result_fetch->fetch_assoc();

// Initialize form variables with existing data
$grade_name = $grade_data['grade_name'];
$min_percentage = $grade_data['min_percentage'];
$max_percentage = $grade_data['max_percentage'];
$description = $grade_data['description'];
$status = $grade_data['status'];

// --- Handle form submission for UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST); // Extract submitted data, overwriting fetched data

    // Clean submitted data (re-clean after extract)
    $grade_name = dataClean($grade_name ?? '');
    $min_percentage = dataClean($min_percentage ?? '');
    $max_percentage = dataClean($max_percentage ?? '');
    $description = dataClean($description ?? '');
    $status = dataClean($status ?? '');

    // Basic Validations (similar to add_grade.php)
    if (empty($grade_name)) { $messages['grade_name'] = "Grade Name is required."; }
    if ($min_percentage === '') { $messages['min_percentage'] = "Min Percentage is required."; }
    if ($max_percentage === '') { $messages['max_percentage'] = "Max Percentage is required."; }
    
    if (!is_numeric($min_percentage) || $min_percentage < 0 || $min_percentage > 100) { $messages['min_percentage'] = "Min Percentage must be a number between 0 and 100."; }
    if (!is_numeric($max_percentage) || $max_percentage < 0 || $max_percentage > 100) { $messages['max_percentage'] = "Max Percentage must be a number between 0 and 100."; }
    
    $min_percentage_float = (float)$min_percentage;
    $max_percentage_float = (float)$max_percentage;

    if ($min_percentage_float > $max_percentage_float) { $messages['main'] = "Min Percentage cannot be greater than Max Percentage."; }
    if (empty($status)) { $messages['status'] = "Status is required."; }

    // Overlap/Uniqueness Validations (Exclude current record for uniqueness)
    if (empty($messages)) {
        // Check for duplicate grade name (excluding current ID)
        $escaped_grade_name = $db->real_escape_string($grade_name);
        $sql_check_name = "SELECT id FROM grades WHERE grade_name = '$escaped_grade_name' AND id != '$id'";
        if ($db->query($sql_check_name)->num_rows > 0) {
            $messages['grade_name'] = "This Grade Name already exists.";
        }

        // Check for overlapping percentage ranges (excluding current ID)
        $sql_check_overlap = "SELECT id FROM grades 
                              WHERE (min_percentage <= '$max_percentage_float' AND max_percentage >= '$min_percentage_float') 
                              AND id != '$id'";
        if ($db->query($sql_check_overlap)->num_rows > 0) {
            $messages['main'] = ($messages['main'] ?? '') . " Percentage range overlaps with an existing grade.";
        }
    }

    // If no validation errors, proceed with update
    if (empty($messages)) {
        $sql = "UPDATE grades SET 
                    grade_name='$escaped_grade_name', 
                    min_percentage='$min_percentage_float', 
                    max_percentage='$max_percentage_float', 
                    description='$description', 
                    status='$status' 
                WHERE id='$id'";
        
        if ($db->query($sql)) {
            header("Location: manage_grades.php?status=updated");
            exit();
        } else {
            $messages['main'] = "Database error: Could not update the grade. " . $db->error;
        }
    }
}
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit Grade: <?= htmlspecialchars($grade_data['grade_name']) ?></h3></div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $id ?>">
            <div class="card-body">
                <?php if(!empty($messages['main'])): ?>
                    <div class="alert alert-danger"><?= $messages['main'] ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Grade Name <span class="text-danger">*</span></label>
                        <input type="text" name="grade_name" class="form-control" value="<?= htmlspecialchars(@$grade_name) ?>" required>
                        <span class="text-danger"><?= @$messages['grade_name'] ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Min Percentage (%) <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="min_percentage" class="form-control" value="<?= htmlspecialchars($min_percentage) ?>" required min="0" max="100">
                        <span class="text-danger"><?= @$messages['min_percentage'] ?></span>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Max Percentage (%) <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="max_percentage" class="form-control" value="<?= htmlspecialchars($max_percentage) ?>" required min="0" max="100">
                        <span class="text-danger"><?= @$messages['max_percentage'] ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="">-- Select Status --</option>
                            <option value="Active" <?= (@$status == 'Active') ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= (@$status == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <span class="text-danger"><?= @$messages['status'] ?></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars(@$description) ?></textarea>
                </div>

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Grade</button>
                <a href="manage_grades.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Correct path to layouts.php
?>