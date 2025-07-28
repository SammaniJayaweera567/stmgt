<?php
ob_start();
include '../../../init.php'; // Correct path from /system/grades/

$db = dbConn();
$messages = [];

// Initialize form variables to prevent undefined variable notices
$grade_name = '';
$min_percentage = '';
$max_percentage = '';
$description = '';
$status = 'Active'; // Default status

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST);

    // Clean data
    $grade_name = dataClean($grade_name ?? ''); // Use null coalescing for safety
    $min_percentage = dataClean($min_percentage ?? '');
    $max_percentage = dataClean($max_percentage ?? '');
    $description = dataClean($description ?? '');
    $status = dataClean($status ?? '');

    // Basic Validations
    if (empty($grade_name)) { $messages['grade_name'] = "Grade Name is required."; }
    if ($min_percentage === '') { $messages['min_percentage'] = "Min Percentage is required."; }
    if ($max_percentage === '') { $messages['max_percentage'] = "Max Percentage is required."; }
    
    if (!is_numeric($min_percentage) || $min_percentage < 0 || $min_percentage > 100) { $messages['min_percentage'] = "Min Percentage must be a number between 0 and 100."; }
    if (!is_numeric($max_percentage) || $max_percentage < 0 || $max_percentage > 100) { $messages['max_percentage'] = "Max Percentage must be a number between 0 and 100."; }
    
    // Convert to float for accurate comparison
    $min_percentage_float = (float)$min_percentage;
    $max_percentage_float = (float)$max_percentage;

    if ($min_percentage_float > $max_percentage_float) { $messages['main'] = "Min Percentage cannot be greater than Max Percentage."; }
    if (empty($status)) { $messages['status'] = "Status is required."; }

    // Overlap/Uniqueness Validations (only if no basic errors)
    if (empty($messages)) {
        // Check for duplicate grade name
        $escaped_grade_name = $db->real_escape_string($grade_name);
        $sql_check_name = "SELECT id FROM grades WHERE grade_name = '$escaped_grade_name'";
        if ($db->query($sql_check_name)->num_rows > 0) {
            $messages['grade_name'] = "This Grade Name already exists.";
        }

        // Check for overlapping percentage ranges
        // This query checks if the new range ($min_percentage_float - $max_percentage_float) overlaps with any existing range
        $sql_check_overlap = "SELECT id FROM grades 
                              WHERE (min_percentage <= '$max_percentage_float' AND max_percentage >= '$min_percentage_float')";
        if ($db->query($sql_check_overlap)->num_rows > 0) {
            $messages['main'] = ($messages['main'] ?? '') . " Percentage range overlaps with an existing grade."; // Append if main message already exists
        }
    }

    // If no validation errors, proceed with insertion
    if (empty($messages)) {
        $sql = "INSERT INTO grades (grade_name, min_percentage, max_percentage, description, status) 
                VALUES ('$escaped_grade_name', '$min_percentage_float', '$max_percentage_float', '$description', '$status')";
        
        if ($db->query($sql)) {
            header("Location: manage_grades.php?status=added");
            exit();
        } else {
            $messages['main'] = "Database error: Could not add the grade. " . $db->error;
        }
    }
}
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Add New Grade</h3></div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
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
                            <option value="">Select Status</option>
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
                <button type="submit" class="btn btn-primary">Save Grade</button>
                <a href="manage_grades.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../layouts.php'; // Correct path to layouts.php
?>