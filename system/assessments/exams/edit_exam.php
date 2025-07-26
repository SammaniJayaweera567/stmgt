<?php
ob_start();
include '../../../init.php';

// No user role check for now, will add later
// if (!isset($_SESSION['user_id'])) { header("Location:../login.php"); exit(); }

$db = dbConn();
$messages = [];
$id = (int)($_REQUEST['id'] ?? 0); // Get exam ID from GET or POST

if ($id === 0) {
    header("Location: manage_exams.php?status=notfound&message=Exam ID not provided.");
    exit();
}

// --- Fetch existing exam data ---
$sql_fetch = "SELECT * FROM assessments WHERE id = '$id' AND assessment_type = 'Exam'";
$result_fetch = $db->query($sql_fetch);

if ($result_fetch->num_rows === 0) {
    header("Location: manage_exams.php?status=notfound&message=Exam not found or is not an Exam type.");
    exit();
}
$exam_data = $result_fetch->fetch_assoc();

// Initialize form variables with existing data
$title = $exam_data['title'];
$description = $exam_data['description'];
$class_id = $exam_data['class_id'];
$academic_year_id = $exam_data['academic_year_id'];
$subject_id = $exam_data['subject_id'];
$teacher_id = $exam_data['teacher_id'];
$max_marks = $exam_data['max_marks'];
$assessment_date = $exam_data['assessment_date'];
$start_time = $exam_data['start_time'];
$end_time = $exam_data['end_time'];
$pass_mark_percentage = $exam_data['pass_mark_percentage'];
$status = $exam_data['status'];


// --- Handle form submission for UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST); // Extract submitted data, overwriting fetched data

    // Clean submitted data (re-clean after extract)
    $title = dataClean($title);
    $description = dataClean($description);
    $class_id = dataClean($class_id);
    $academic_year_id = dataClean($academic_year_id);
    $subject_id = dataClean($subject_id);
    $teacher_id = dataClean($teacher_id);
    $max_marks = dataClean($max_marks);
    $assessment_date = dataClean($assessment_date);
    $start_time = dataClean($start_time);
    $end_time = dataClean($end_time);
    $pass_mark_percentage = dataClean($pass_mark_percentage);
    $status = dataClean($status);

    // Validations (same as add_exam.php)
    if (empty($title)) { $messages['title'] = "Title is required."; }
    if (empty($class_id)) { $messages['class_id'] = "Class is required."; }
    if (empty($academic_year_id)) { $messages['academic_year_id'] = "Academic Year is required."; }
    if (empty($subject_id)) { $messages['subject_id'] = "Subject is required."; }
    if (empty($teacher_id)) { $messages['teacher_id'] = "Teacher is required."; }
    if (empty($max_marks) || !is_numeric($max_marks) || $max_marks <= 0) { $messages['max_marks'] = "Max Marks must be a positive number."; }
    if (empty($assessment_date)) { $messages['assessment_date'] = "Exam Date is required."; }
    if (empty($start_time)) { $messages['start_time'] = "Start Time is required."; }
    if (empty($end_time)) { $messages['end_time'] = "End Time is required."; }
    if (!is_numeric($pass_mark_percentage) || $pass_mark_percentage < 0 || $pass_mark_percentage > 100) { $messages['pass_mark_percentage'] = "Pass Mark Percentage must be between 0 and 100."; }
    if (empty($status)) { $messages['status'] = "Status is required."; }

    if (!empty($start_time) && !empty($end_time) && strtotime($start_time) >= strtotime($end_time)) {
        $messages['main'] = "End time must be after Start time.";
    }

    // Check for general errors before proceeding to DB update
    if (!empty($messages)) {
        goto end_post_processing; // Use goto for simple jumps to avoid nested ifs
    }

    // Database update
    $sql = "UPDATE assessments SET 
                title = '$title', 
                description = '$description', 
                class_id = '$class_id', 
                academic_year_id = '$academic_year_id', 
                subject_id = '$subject_id', 
                teacher_id = '$teacher_id', 
                max_marks = '$max_marks', 
                assessment_date = '$assessment_date', 
                start_time = '$start_time', 
                end_time = '$end_time', 
                pass_mark_percentage = '$pass_mark_percentage', 
                status = '$status'
            WHERE id = '$id' AND assessment_type = 'Exam'"; // Ensure only Exam type is updated

    if ($db->query($sql)) {
        header("Location: manage_exams.php?status=updated");
        exit();
    } else {
        $messages['main'] = "Database error: Could not update exam. " . $db->error;
    }

    end_post_processing:;
}

// Fetch data for dropdowns (same as add_exam.php)
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
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit Exam: <?= htmlspecialchars($exam_data['title']) ?></h3></div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $id ?>">
            <div class="card-body">
                <?php if (!empty($messages['main'])): ?>
                    <div class="alert alert-danger"><?= $messages['main'] ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars(@$title) ?>" required>
                        <span class="text-danger"><?= @$messages['title'] ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            <?php 
                            mysqli_data_seek($classes, 0); // Reset pointer
                            while($row = $classes->fetch_assoc()) { 
                                $class_full_name = htmlspecialchars($row['level_name'] . ' - ' . $row['subject_name'] . ' (' . $row['type_name'] . ')');
                                $selected = (@$class_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>{$class_full_name}</option>";
                            } ?>
                        </select>
                        <span class="text-danger"><?= @$messages['class_id'] ?></span>
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
                            mysqli_data_seek($academic_years, 0); // Reset pointer
                            while($row = $academic_years->fetch_assoc()) { 
                                $selected = (@$academic_year_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['year_name'])."</option>";
                            } ?>
                        </select>
                        <span class="text-danger"><?= @$messages['academic_year_id'] ?></span>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">-- Select Subject --</option>
                            <?php 
                            mysqli_data_seek($subjects, 0); // Reset pointer
                            while($row = $subjects->fetch_assoc()) { 
                                $selected = (@$subject_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['subject_name'])."</option>";
                            } ?>
                        </select>
                        <span class="text-danger"><?= @$messages['subject_id'] ?></span>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-control" required>
                            <option value="">-- Select Teacher --</option>
                            <?php 
                            mysqli_data_seek($teachers, 0); // Reset pointer
                            while($row = $teachers->fetch_assoc()) { 
                                $selected = (@$teacher_id == $row['Id']) ? 'selected' : '';
                                echo "<option value='{$row['Id']}' $selected>".htmlspecialchars($row['FirstName'].' '.$row['LastName'])."</option>";
                            } ?>
                        </select>
                        <span class="text-danger"><?= @$messages['teacher_id'] ?></span>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Max Marks <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="max_marks" class="form-control" value="<?= htmlspecialchars(@$max_marks) ?>" required min="0">
                        <span class="text-danger"><?= @$messages['max_marks'] ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Pass Mark Percentage (%)</label>
                        <input type="number" step="0.01" name="pass_mark_percentage" class="form-control" value="<?= htmlspecialchars(@$pass_mark_percentage) ?>" min="0" max="100">
                        <span class="text-danger"><?= @$messages['pass_mark_percentage'] ?></span>
                    </div>
                </div>

                <hr class="my-4">
                <h4>Exam Schedule Details</h4>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Exam Date <span class="text-danger">*</span></label>
                        <input type="date" name="assessment_date" class="form-control" value="<?= htmlspecialchars(@$assessment_date) ?>" required>
                        <span class="text-danger"><?= @$messages['assessment_date'] ?></span>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars(@$start_time) ?>" required>
                        <span class="text-danger"><?= @$messages['start_time'] ?></span>
                    </div>
                    <div class="form-group col-md-3">
                        <label>End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars(@$end_time) ?>" required>
                        <span class="text-danger"><?= @$messages['end_time'] ?></span>
                    </div>
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
                    <span class="text-danger"><?= @$messages['status'] ?></span>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Exam</button>
                <a href="manage_exams.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>