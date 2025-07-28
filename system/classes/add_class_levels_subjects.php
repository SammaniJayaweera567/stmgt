<?php
ob_start(); // Start output buffering
include '../../init.php';

if (!hasPermission($_SESSION['user_id'], 'add_class_subject')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// Initialize form variables to prevent undefined variable notices
$class_level_id = '';
$subject_id = '';
$status = ''; // Initialize status
$messages = []; // Array to hold validation messages

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST);

    // Clean the input data (using your dataClean() function)
    $class_level_id = dataClean(@$class_level_id);
    $subject_id = dataClean(@$subject_id);
    $status = dataClean(@$status); // Clean the status

    // --- Validation ---
    if (empty($class_level_id)) {
        $messages['class_level_id'] = "Class Level selection is required...!";
    }
    if (empty($subject_id)) {
        $messages['subject_id'] = "Subject selection is required...!";
    }
    // NEW: Validate status
    if (empty($status)) {
        $messages['status'] = "Status is required...!";
    }

    // Get database connection (for validations, as per user add.php pattern)
    $db = dbConn();

    // Unique combination validation (Class Level + Subject must be unique)
    if (empty($messages)) { 
        $check_duplicate_sql = "SELECT id FROM class_levels_subjects WHERE class_level_id='$class_level_id' AND subject_id='$subject_id'";
        $result_duplicate = $db->query($check_duplicate_sql);
        if ($result_duplicate->num_rows > 0) {
            $messages['duplicate_entry'] = "This Subject is already assigned to this Class Level...!";
        }
    }

    // If there are no validation errors, insert into database
    if (empty($messages)) {
        // INSERT SQL statement
        $sql = "INSERT INTO class_levels_subjects(class_level_id, subject_id, status) 
                VALUES('$class_level_id', '$subject_id', '$status')";
        
        // Execute the SQL query
        if ($db->query($sql) === TRUE) {
            header("Location: manage_class_levels_subjects.php?status=added"); 
            exit(); 
        }
    }
}
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Add New Class Level Subject Relationship</h3>
        </div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" novalidate>
            <div class="card-body">
                <?php 
                if (!empty($messages['duplicate_entry'])) { ?>
                    <div class="alert alert-warning" role="alert">
                        <?= @$messages['duplicate_entry'] ?>
                    </div>
                <?php } ?>

                <div class="row">
                    <div class="form-group col-md-6"> <label for="classLevelSelect">Select Class Level (Grade)</label>
                        <select name="class_level_id" id="classLevelSelect" class="form-control">
                            <option value="">-- Select Class Level --</option>
                            <?php
                            $db = dbConn(); 
                            $sql_class_levels = "SELECT id, level_name FROM class_levels WHERE status='Active'";
                            $result_class_levels = $db->query($sql_class_levels);
                            
                            while($row_class_level = $result_class_levels->fetch_assoc()){
                                $selected = (@$class_level_id == $row_class_level['id']) ? 'selected' : '';
                                echo "<option value='{$row_class_level['id']}' $selected>{$row_class_level['level_name']}</option>";
                            }
                            ?>
                        </select>
                        <span class="text-danger"><?= @$messages['class_level_id'] ?></span>
                    </div>

                    <div class="form-group col-md-6"> <label for="subjectSelect">Select Subject</label>
                        <select name="subject_id" id="subjectSelect" class="form-control">
                            <option value="">-- Select Subject --</option>
                            <?php
                            $db = dbConn(); 
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

                <!-- NEW: Status Field Added -->
                <div class="form-group col-md-6 mt-3">
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
                <button type="submit" class="btn btn-primary">Add Relationship</button>
                <a href="manage_class_levels_subjects.php" class="btn btn-secondary ms-2">Cancel</a> 
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
