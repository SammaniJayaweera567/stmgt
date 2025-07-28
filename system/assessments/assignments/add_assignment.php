<?php
ob_start();
include '../../../init.php'; // Correct path from /system/assessments/assignments/
if (!hasPermission($_SESSION['user_id'], 'add_assignment')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$db = dbConn();
$messages = [];

// Initialize form variables with default values or empty strings
$title = ''; $description = ''; $class_id = ''; $academic_year_id = '';
$subject_id = ''; $teacher_id = ''; $max_marks = ''; $due_date = '';
$pass_mark_percentage = '40.00'; $status = 'Published'; // Default pass mark percentage

// Check if form is submitted via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST); // Extract POST data into variables

    // Clean and sanitize input data
    $title = dataClean($title ?? '');
    $description = dataClean($description ?? '');
    $class_id = dataClean($class_id ?? '');
    $academic_year_id = dataClean($academic_year_id ?? '');
    $subject_id = dataClean($subject_id ?? '');
    $teacher_id = dataClean($teacher_id ?? '');
    $max_marks = dataClean($max_marks ?? '');
    $due_date = dataClean($due_date ?? '');
    $pass_mark_percentage = dataClean($pass_mark_percentage ?? '');
    $status = dataClean($status ?? '');
    
    // --- Server-side Validations ---
    if (empty($title)) { $messages['main'] = "Assignment Title is required."; }
    if (empty($class_id)) { $messages['main'] = "Class is required."; }
    if (empty($academic_year_id)) { $messages['main'] = "Academic Year is required."; } // Added validation
    if (empty($subject_id)) { $messages['main'] = "Subject is required."; } // Added validation
    if (empty($teacher_id)) { $messages['main'] = "Teacher is required."; } // Added validation
    if (empty($max_marks) || !is_numeric($max_marks) || $max_marks <= 0) { $messages['main'] = "Max Marks must be a positive number."; } // Added more robust validation
    if (empty($pass_mark_percentage) || !is_numeric($pass_mark_percentage) || $pass_mark_percentage < 0 || $pass_mark_percentage > 100) { $messages['main'] = "Pass Mark Percentage must be between 0 and 100."; } // Added validation
    if (empty($due_date)) { $messages['main'] = "Due Date is required."; }
    // Ensure either description or file is provided
    if (empty($description) && (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] != UPLOAD_ERR_OK)) {
        $messages['main'] = "You must provide a text description OR upload an assignment file.";
    }

    // --- Logical Validations (e.g., due date within academic year) ---
    if (empty($messages)) {
        $sql_ay = "SELECT start_date, end_date FROM academic_years WHERE id = '$academic_year_id'";
        $ay_result = $db->query($sql_ay)->fetch_assoc();
        // Check if $ay_result is not empty before accessing its elements
        if ($ay_result) {
            if (date('Y-m-d', strtotime($due_date)) < $ay_result['start_date'] || date('Y-m-d', strtotime($due_date)) > $ay_result['end_date']) {
                $messages['main'] = "Logical Error: The due date must be within the selected academic year (" . htmlspecialchars($ay_result['start_date']) . " to " . htmlspecialchars($ay_result['end_date']) . ").";
            }
        } else {
            $messages['main'] = "Error: Selected Academic Year not found or invalid.";
        }
    }
    
    // --- File Upload Handling and Validation ---
    $assignment_file_name = null;
    $assignment_file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['assignment_file']['name'];
        $file_tmp = $_FILES['assignment_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'txt']; // Allowed file extensions

        if (!in_array($file_ext, $allowed_ext)) {
            $messages['main'] = "Invalid file type. Only PDF, Word, Excel, Images, and Text files are allowed.";
        }
        if ($_FILES['assignment_file']['size'] > 5000000) { // 5MB limit
            $messages['main'] = "File is too large. Maximum size is 5MB.";
        }

        if (empty($messages)) {
            $assignment_file_name = "asgmt_" . uniqid() . '.' . $file_ext;
            // Corrected file path relative to the project root (assuming WEB_URL is defined from init.php)
            // You confirmed that web and system are in the same directory under stmgt
            // So, from current file: ../../../web/uploads/assignments/
            $destination_folder = __DIR__ . '/../../../web/uploads/assignments/'; 
            
            // Ensure the destination directory exists
            if (!is_dir($destination_folder)) {
                mkdir($destination_folder, 0777, true); // Create directory if not exists, recursive, with full permissions (for local dev)
            }

            $assignment_file_path_for_db = "web/uploads/assignments/" . $assignment_file_name; // Path to save in DB
            $full_destination_path = $destination_folder . $assignment_file_name;
            
            if (!move_uploaded_file($file_tmp, $full_destination_path)) {
                $messages['main'] = "Failed to upload the file. Please check folder permissions. Error: " . error_get_last()['message']; // More specific error
                $assignment_file_name = null; // Reset to null if upload fails
                $assignment_file_path_for_db = null; // Reset path to null if upload fails
            }
        }
    }

    if (empty($messages)) {
        // Database Insertion - UPDATED with assessment_type_id
        $sql = "INSERT INTO assessments (title, description, assignment_file_name, assignment_file_path, assessment_type_id, class_id, academic_year_id, subject_id, teacher_id, max_marks, due_date, status, pass_mark_percentage) 
                VALUES ('$title', '$description', " . ($assignment_file_name ? "'$assignment_file_name'" : "NULL") . ", " . ($assignment_file_path_for_db ? "'$assignment_file_path_for_db'" : "NULL") . ", 2, '$class_id', '$academic_year_id', '$subject_id', '$teacher_id', '$max_marks', '$due_date', '$status', '$pass_mark_percentage')";
        
        if ($db->query($sql)) {
            header("Location: manage_assignments.php?status=added");
            exit();
        } else {
            $messages['main'] = "Database error: " . $db->error; // Display detailed database error
        }
    }
}

// Fetch data for dropdowns
$academic_years = $db->query("SELECT * FROM academic_years WHERE status='Active'");
$classes = $db->query("SELECT c.id, cl.level_name, s.subject_name, ct.type_name FROM classes c JOIN class_levels cl ON c.class_level_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN class_types ct ON c.class_type_id = ct.id WHERE c.status='Active' ORDER BY cl.level_name, s.subject_name"); // Added ordering
$subjects = $db->query("SELECT * FROM subjects WHERE status='Active' ORDER BY subject_name"); // Added ordering
$teachers = $db->query("SELECT u.Id, u.FirstName, u.LastName FROM users u JOIN user_roles ur ON u.user_role_id=ur.Id WHERE ur.RoleName='Teacher' AND u.Status='Active' ORDER BY u.FirstName, u.LastName"); // Added join to user_roles and ordering
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Create New Assignment</h3>
        </div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
            <div class="card-body">
                <?php if(!empty($messages['main'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($messages['main']) ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="form-group col-md-6 mb-3"><label>Title <span class="text-danger">*</span></label><input
                            type="text" name="title" class="form-control" value="<?= htmlspecialchars(@$title) ?>" required>
                    </div>
                    <div class="form-group col-md-6 mb-3"><label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            <?php 
                            mysqli_data_seek($classes, 0); // Reset pointer for dropdown data
                            while($row = $classes->fetch_assoc()) { 
                                $selected = (@$class_id == $row['id']) ? 'selected' : ''; 
                                echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['level_name'] . ' - ' . $row['subject_name'] . ' (' . $row['type_name'] . ')')."</option>"; 
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label>Description (Type details here OR upload a file below)</label>
                    <textarea name="description" class="form-control"
                        rows="4"><?= htmlspecialchars(@$description) ?></textarea>
                </div>
                <div class="form-group mb-3">
                    <label>Upload Assignment File (Optional)</label>
                    <input type="file" name="assignment_file" class="form-control">
                    <small class="form-text text-muted">Allowed types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, TXT. Max 5MB.</small>
                </div>
                <div class="row">
                    <div class="form-group col-md-4 mb-3"><label>Academic Year <span
                                class="text-danger">*</span></label><select name="academic_year_id"
                            class="form-control" required>
                            <option value="">-- Select --</option>
                            <?php mysqli_data_seek($academic_years, 0); while($row = $academic_years->fetch_assoc()) { $selected = (@$academic_year_id == $row['id']) ? 'selected' : ''; echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['year_name'])."</option>"; } ?></select>
                    </div>
                    <div class="form-group col-md-4 mb-3"><label>Subject <span
                                class="text-danger">*</span></label><select name="subject_id"
                            class="form-control" required>
                            <option value="">-- Select --</option>
                            <?php mysqli_data_seek($subjects, 0); while($row = $subjects->fetch_assoc()) { $selected = (@$subject_id == $row['id']) ? 'selected' : ''; echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['subject_name'])."</option>"; } ?></select>
                    </div>
                    <div class="form-group col-md-4 mb-3"><label>Teacher <span
                                class="text-danger">*</span></label><select name="teacher_id"
                            class="form-control" required>
                            <option value="">-- Select --</option>
                            <?php mysqli_data_seek($teachers, 0); while($row = $teachers->fetch_assoc()) { $selected = (@$teacher_id == $row['Id']) ? 'selected' : ''; echo "<option value='{$row['Id']}' $selected>".htmlspecialchars($row['FirstName'].' '.$row['LastName'])."</option>"; } ?></select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6 mb-3"><label>Max Marks <span
                                class="text-danger">*</span></label><input type="number" step="0.01" name="max_marks"
                            class="form-control" value="<?= htmlspecialchars(@$max_marks) ?>" required min="0"></div>
                    <div class="form-group col-md-6 mb-3"><label>Due Date <span
                                class="text-danger">*</span></label><input type="datetime-local" name="due_date"
                            class="form-control" value="<?= htmlspecialchars(@$due_date) ?>" required></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6 mb-3"><label>Pass Mark Percentage (%)</label><input type="number"
                            step="0.01" name="pass_mark_percentage" class="form-control"
                            value="<?= htmlspecialchars(@$pass_mark_percentage) ?>" min="0" max="100"></div>
                    <div class="form-group col-md-6 mb-3"><label>Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="Draft" <?= (@$status == 'Draft') ? 'selected' : '' ?>>Draft</option>
                            <option value="Published" <?= (@$status == 'Published') ? 'selected' : '' ?>>Published
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create Assignment</button>
                <a href="manage_assignments.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../../layouts.php';
?>