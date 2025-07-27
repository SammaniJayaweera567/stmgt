<?php
ob_start();
include '../../../init.php'; // Correct path from /system/assessments/assignments/
if (!hasPermission($_SESSION['user_id'], 'edit_assignment')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$db = dbConn();
$messages = [];
$id = (int)($_REQUEST['id'] ?? 0); // Get assignment ID from GET or POST

if ($id === 0) {
    header("Location: manage_assignments.php?status=notfound&message=Assignment ID not provided.");
    exit();
}

// --- Fetch existing assignment data ---
$sql_fetch = "SELECT * FROM assessments WHERE id = '$id' AND assessment_type = 'Assignment'";
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
    $pass_mark_percentage = dataClean($pass_mark_percentage ?? ''); // Clean pass_mark_percentage
    $status = dataClean($status ?? '');
    
    // Server-side Validation
    if (empty($title)) { $messages['main'] = "Assignment Title is required."; }
    if (empty($class_id)) { $messages['main'] = "Class is required."; }
    if (empty($academic_year_id)) { $messages['main'] = "Academic Year is required."; }
    if (empty($subject_id)) { $messages['main'] = "Subject is required."; }
    if (empty($teacher_id)) { $messages['main'] = "Teacher is required."; }
    if (empty($max_marks) || !is_numeric($max_marks) || $max_marks <= 0) { $messages['main'] = "Max Marks must be a positive number."; }
    // Pass mark percentage validation
    if (!is_numeric($pass_mark_percentage) || $pass_mark_percentage < 0 || $pass_mark_percentage > 100) { $messages['main'] = "Pass Mark Percentage must be between 0 and 100."; }
    if (empty($due_date)) { $messages['main'] = "Due Date is required."; }
    if (empty($status)) { $messages['main'] = "Status is required."; }

    if (empty($messages)) {
        // All validations pass, update database
        // Ensure to update only an 'Assignment' type assessment
        $sql = "UPDATE assessments SET 
                    title = '$title', 
                    description = '$description', 
                    class_id = '$class_id', 
                    academic_year_id = '$academic_year_id', 
                    subject_id = '$subject_id', 
                    teacher_id = '$teacher_id', 
                    max_marks = '$max_marks', 
                    due_date = '$due_date', 
                    status = '$status',
                    pass_mark_percentage = '$pass_mark_percentage'
                WHERE id = '$id' AND assessment_type = 'Assignment'";
        
        if ($db->query($sql)) {
            header("Location: manage_assignments.php?status=updated");
            exit();
        } else {
            $messages['main'] = "Database error: Could not update the assignment. " . $db->error;
        }
    }
}

// Fetch data for dropdowns (same as add_assignment.php)
$academic_years = $db->query("SELECT * FROM academic_years WHERE status='Active'");
$classes = $db->query("SELECT c.id, cl.level_name, s.subject_name, ct.type_name 
                      FROM classes c
                      JOIN class_levels cl ON c.class_level_id = cl.id
                      JOIN subjects s ON c.subject_id = s.id
                      JOIN class_types ct ON c.class_type_id = ct.id
                      WHERE c.status='Active' ORDER BY cl.level_name, s.subject_name");
$subjects = $db->query("SELECT * FROM subjects WHERE status='Active'");
$teachers = $db->query("SELECT u.Id, u.FirstName, u.LastName FROM users u JOIN user_roles ur ON u.user_role_id=ur.Id WHERE ur.RoleName='Teacher' AND u.Status='Active'");
?>

<div class="container-fluid">
    <?php show_status_message(); ?>
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit Assignment: <?= htmlspecialchars($assignment_data['title']) ?></h3></div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $id ?>">
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