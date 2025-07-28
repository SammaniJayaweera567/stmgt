<?php
ob_start();
include '../../../init.php'; // Correct path from /system/assessments/assignments/

$db = dbConn(); // Database connection
$messages = []; // For displaying user messages

$id = (int)($_REQUEST['id'] ?? 0); // Get assignment ID from GET or POST

if ($id === 0) {
    header("Location: manage_assignments.php?status=notfound&message=Assignment ID not provided.");
    exit();
}

// --- Fetch existing assignment data ---
// UPDATED: Use assessment_type_id for filtering
$sql_fetch = "SELECT * FROM assessments WHERE id = '$id' AND assessment_type_id = 2";
$result_fetch = $db->query($sql_fetch);

if ($result_fetch->num_rows === 0) {
    header("Location: manage_assignments.php?status=notfound&message=Assignment not found or is not an Assignment type.");
    exit();
}
$assignment_data = $result_fetch->fetch_assoc();

// Initialize form variables with existing data
$title = $assignment_data['title'];
$description = $assignment_data['description'];
$class_id = $assignment_data['class_id'];
$academic_year_id = $assignment_data['academic_year_id'];
$subject_id = $assignment_data['subject_id'];
$teacher_id = $assignment_data['teacher_id'];
$max_marks = $assignment_data['max_marks'];
$due_date = $assignment_data['due_date']; // Datetime field
$pass_mark_percentage = $assignment_data['pass_mark_percentage']; // Fetch existing pass_mark_percentage
$status = $assignment_data['status'];


// --- Handle form submission for UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST); // Extract submitted data, overwriting fetched data

    // Clean submitted data (re-clean after extract)
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
    
    // --- Server-side Validations (Added from add_assignment.php) ---
    if (empty($title)) { $messages['main'] = "Assignment Title is required."; }
    if (empty($class_id)) { $messages['main'] = "Class is required."; }
    if (empty($academic_year_id)) { $messages['main'] = "Academic Year is required."; }
    if (empty($subject_id)) { $messages['main'] = "Subject is required."; }
    if (empty($teacher_id)) { $messages['main'] = "Teacher is required."; }
    if (empty($max_marks) || !is_numeric($max_marks) || $max_marks <= 0) { $messages['main'] = "Max Marks must be a positive number."; }
    if (!is_numeric($pass_mark_percentage) || $pass_mark_percentage < 0 || $pass_mark_percentage > 100) { $messages['main'] = "Pass Mark Percentage must be between 0 and 100."; }
    if (empty($due_date)) { $messages['main'] = "Due Date is required."; }
    if (empty($status)) { $messages['main'] = "Status is required."; }

    // Logical Validation: Due date within academic year
    if (empty($messages)) {
        $sql_ay = "SELECT start_date, end_date FROM academic_years WHERE id = '$academic_year_id'";
        $ay_result = $db->query($sql_ay)->fetch_assoc();
        if ($ay_result) {
            if (date('Y-m-d', strtotime($due_date)) < $ay_result['start_date'] || date('Y-m-d', strtotime($due_date)) > $ay_result['end_date']) {
                $messages['main'] = "Logical Error: The due date must be within the selected academic year (" . htmlspecialchars($ay_result['start_date']) . " to " . htmlspecialchars($ay_result['end_date']) . ").";
            }
        } else {
            $messages['main'] = "Error: Selected Academic Year not found or invalid.";
        }
    }

    // --- File Upload Handling and Validation (for update/replace existing file) ---
    // This logic allows replacing or adding a new file.
    $current_assignment_file_name = $assignment_data['assignment_file_name'];
    $current_assignment_file_path_for_db = $assignment_data['assignment_file_path'];

    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['assignment_file']['name'];
        $file_tmp = $_FILES['assignment_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'txt'];

        if (!in_array($file_ext, $allowed_ext)) {
            $messages['main'] = "Invalid file type. Only PDF, Word, Excel, Images, and Text files are allowed.";
        }
        if ($_FILES['assignment_file']['size'] > 5000000) { // 5MB limit
            $messages['main'] = "File is too large. Maximum size is 5MB.";
        }

        if (empty($messages)) {
            // Delete old file if exists
            if (!empty($current_assignment_file_name) && file_exists(__DIR__ . '/../../../' . $current_assignment_file_path_for_db)) {
                unlink(__DIR__ . '/../../../' . $current_assignment_file_path_for_db);
            }

            $new_assignment_file_name = "asgmt_" . uniqid() . '.' . $file_ext;
            $destination_folder = __DIR__ . '/../../../web/uploads/assignments/';
            
            if (!is_dir($destination_folder)) {
                mkdir($destination_folder, 0777, true);
            }

            $new_assignment_file_path_for_db = "web/uploads/assignments/" . $new_assignment_file_name;
            $full_destination_path = $destination_folder . $new_assignment_file_name;
            
            if (!move_uploaded_file($file_tmp, $full_destination_path)) {
                $messages['main'] = "Failed to upload the new file. Please check folder permissions. Error: " . error_get_last()['message'];
            } else {
                // Update file names/paths to the new ones if upload successful
                $assignment_file_name = $new_assignment_file_name;
                $assignment_file_path_for_db = $new_assignment_file_path_for_db;
            }
        }
    } else {
        // If no new file is uploaded, retain existing file information
        $assignment_file_name = $current_assignment_file_name;
        $assignment_file_path_for_db = $current_assignment_file_path_for_db;
    }

    if (empty($messages)) {
        // All validations pass, update database
        // Ensure to update only an 'Assignment' type assessment
        // UPDATED: Use assessment_type_id and handle file paths correctly
        $sql = "UPDATE assessments SET 
                    title = '$title', 
                    description = '$description', 
                    assignment_file_name = " . ($assignment_file_name ? "'$assignment_file_name'" : "NULL") . ", 
                    assignment_file_path = " . ($assignment_file_path_for_db ? "'$assignment_file_path_for_db'" : "NULL") . ", 
                    class_id = '$class_id', 
                    academic_year_id = '$academic_year_id', 
                    subject_id = '$subject_id', 
                    teacher_id = '$teacher_id', 
                    max_marks = '$max_marks', 
                    due_date = '$due_date', 
                    status = '$status',
                    pass_mark_percentage = '$pass_mark_percentage'
                WHERE id = '$id' AND assessment_type_id = 2";
        
        if ($db->query($sql)) {
            header("Location: manage_assignments.php?status=updated");
            exit();
        } else {
            $messages['main'] = "Database error: Could not update the assignment. " . $db->error;
        }
    }
}

// Fetch data for dropdowns (same as add_assignment.php, but re-fetching is fine)
$academic_years = $db->query("SELECT * FROM academic_years WHERE status='Active' ORDER BY year_name DESC"); // Added ordering
$classes = $db->query("SELECT c.id, cl.level_name, s.subject_name, ct.type_name 
                      FROM classes c
                      JOIN class_levels cl ON c.class_level_id = cl.id
                      JOIN subjects s ON c.subject_id = s.id
                      JOIN class_types ct ON c.class_type_id = ct.id
                      WHERE c.status='Active' ORDER BY cl.level_name, s.subject_name"); // Added ordering
$subjects = $db->query("SELECT * FROM subjects WHERE status='Active' ORDER BY subject_name"); // Added ordering
$teachers = $db->query("SELECT u.Id, u.FirstName, u.LastName FROM users u JOIN user_roles ur ON u.user_role_id=ur.Id WHERE ur.RoleName='Teacher' AND u.Status='Active' ORDER BY u.FirstName, u.LastName"); // Added ordering
?>

<div class="container-fluid">
    <?php show_status_message(); // Call the common function to show toast notifications ?>
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit Assignment: <?= htmlspecialchars($assignment_data['title']) ?></h3></div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $id ?>" enctype="multipart/form-data">
            <div class="card-body">
                <?php if(!empty($messages['main'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($messages['main']) ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars(@$title) ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            <?php 
                            // Reset pointer for dropdown data if it was already fetched
                            mysqli_data_seek($classes, 0); 
                            while($row = $classes->fetch_assoc()) { 
                                $class_full_name = htmlspecialchars($row['level_name'] . ' - ' . $row['subject_name'] . ' (' . $row['type_name'] . ')');
                                $selected = (@$class_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>{$class_full_name}</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars(@$description) ?></textarea>
                </div>
                
                <div class="form-group mb-3">
                    <label>Upload New Assignment File (Optional)</label>
                    <input type="file" name="assignment_file" class="form-control">
                    <small class="form-text text-muted">
                        <?php if (!empty($assignment_data['assignment_file_name'])): ?>
                            Current file: <a href="<?= WEB_URL ?>web/uploads/assignments/<?= htmlspecialchars($assignment_data['assignment_file_name']) ?>" target="_blank"><?= htmlspecialchars($assignment_data['assignment_file_name']) ?></a><br>
                        <?php endif; ?>
                        Allowed types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, TXT. Max 5MB.
                    </small>
                </div>


                <div class="row">
                    <div class="form-group col-md-4">
                        <label>Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-control" required>
                            <option value="">-- Select Academic Year --</option>
                            <?php 
                            mysqli_data_seek($academic_years, 0); 
                            while($row = $academic_years->fetch_assoc()) { 
                                $selected = (@$academic_year_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['year_name'])."</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">-- Select Subject --</option>
                            <?php 
                            mysqli_data_seek($subjects, 0); 
                            while($row = $subjects->fetch_assoc()) { 
                                $selected = (@$subject_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['subject_name'])."</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-control" required>
                            <option value="">-- Select Teacher --</option>
                            <?php 
                            mysqli_data_seek($teachers, 0); 
                            while($row = $teachers->fetch_assoc()) { 
                                $selected = (@$teacher_id == $row['Id']) ? 'selected' : '';
                                echo "<option value='{$row['Id']}' $selected>".htmlspecialchars($row['FirstName'].' '.$row['LastName'])."</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Max Marks <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="max_marks" class="form-control" value="<?= htmlspecialchars(@$max_marks) ?>" required min="0">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Due Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="due_date" class="form-control" value="<?= htmlspecialchars(@$due_date) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Pass Mark Percentage (%)</label>
                    <input type="number" step="0.01" name="pass_mark_percentage" class="form-control" value="<?= htmlspecialchars(@$pass_mark_percentage) ?>" min="0" max="100">
                </div>
                 <div class="form-group">
                    <label>Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="Draft" <?= (@$status == 'Draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="Published" <?= (@$status == 'Published') ? 'selected' : '' ?>>Published</option>
                        <option value="Graded" <?= (@$status == 'Graded') ? 'selected' : '' ?>>Graded</option>
                        <option value="Archived" <?= (@$status == 'Archived') ? 'selected' : '' ?>>Archived</option>
                        <option value="Cancelled" <?= (@$status == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Assignment</button>
                <a href="manage_assignments.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../layouts.php'; // Correct path to layouts.php
?>