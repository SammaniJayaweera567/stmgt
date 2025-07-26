<?php
ob_start();
include '../../../init.php'; // Path from /system/assessments/quizzes/

$db = dbConn();
$messages = [];

// Initialize form variables
$title = '';
$class_id = '';
$academic_year_id = '';
$subject_id = '';
$teacher_id = '';
$time_limit_minutes = '';
$pass_mark_percentage = '';
$status = 'Draft'; // Default status

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST);

    $title = dataClean($title ?? '');
    $class_id = dataClean($class_id ?? '');
    $academic_year_id = dataClean($academic_year_id ?? '');
    $subject_id = dataClean($subject_id ?? '');
    $teacher_id = dataClean($teacher_id ?? '');
    $time_limit_minutes = dataClean($time_limit_minutes ?? '');
    $pass_mark_percentage = dataClean($pass_mark_percentage ?? '');
    $status = dataClean($status ?? '');
    
    // Server-side Validation
    if (empty($title)) { $messages['main'] = "Quiz Title is required."; }
    if (empty($class_id)) { $messages['main'] = "Class is required."; }
    if (empty($subject_id)) { $messages['main'] = "Subject is required."; }
    if (empty($time_limit_minutes) || !is_numeric($time_limit_minutes) || $time_limit_minutes <= 0) { $messages['main'] = "Time Limit must be a positive number."; }
    if (!is_numeric($pass_mark_percentage) || $pass_mark_percentage < 0 || $pass_mark_percentage > 100) { $messages['main'] = "Pass Mark Percentage must be between 0 and 100."; }
    if (empty($status)) { $messages['main'] = "Status is required."; }

    if (empty($messages)) {
        // All validations pass, insert into database
        // =================================================================================
        // FIX: Added 'max_marks' with a default value of 0 to the INSERT statement.
        // =================================================================================
        $sql = "INSERT INTO assessments (title, assessment_type, class_id, academic_year_id, subject_id, teacher_id, time_limit_minutes, pass_mark_percentage, status, max_marks) 
                VALUES ('$title', 'Quiz', '$class_id', '$academic_year_id', '$subject_id', '$teacher_id', '$time_limit_minutes', '$pass_mark_percentage', '$status', 0)";
        
        if ($db->query($sql)) {
            $new_quiz_id = $db->insert_id;
            header("Location: manage_questions.php?quiz_id=$new_quiz_id&status=quiz_added");
            exit();
        } else {
            $messages['main'] = "Database error: Could not create the quiz. " . $db->error;
        }
    }
}

// Fetch data for dropdowns
$academic_years = $db->query("SELECT * FROM academic_years WHERE status='Active'");
$classes = $db->query("SELECT c.id, cl.level_name, s.subject_name, ct.type_name FROM classes c JOIN class_levels cl ON c.class_level_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN class_types ct ON c.class_type_id = ct.id WHERE c.status='Active' ORDER BY cl.level_name, s.subject_name");
$subjects = $db->query("SELECT * FROM subjects WHERE status='Active'");
$teachers = $db->query("SELECT u.Id, u.FirstName, u.LastName FROM users u JOIN user_roles ur ON u.user_role_id=ur.Id WHERE ur.RoleName='Teacher' AND u.Status='Active'");
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Create New Quiz</h3></div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
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
                            <?php while($row = $classes->fetch_assoc()) { 
                                $class_full_name = htmlspecialchars($row['level_name'] . ' - ' . $row['subject_name'] . ' (' . $row['type_name'] . ')');
                                $selected = (@$class_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>{$class_full_name}</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-4">
                        <label>Academic Year</label>
                        <select name="academic_year_id" class="form-control">
                            <option value="">-- Select Academic Year --</option>
                            <?php mysqli_data_seek($academic_years, 0); while($row = $academic_years->fetch_assoc()) { 
                                $selected = (@$academic_year_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['year_name'])."</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">-- Select Subject --</option>
                            <?php while($row = $subjects->fetch_assoc()) { 
                                $selected = (@$subject_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['subject_name'])."</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Teacher</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">-- Select Teacher --</option>
                            <?php while($row = $teachers->fetch_assoc()) { 
                                $selected = (@$teacher_id == $row['Id']) ? 'selected' : '';
                                echo "<option value='{$row['Id']}' $selected>".htmlspecialchars($row['FirstName'].' '.$row['LastName'])."</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Time Limit (Minutes) <span class="text-danger">*</span></label>
                        <input type="number" name="time_limit_minutes" class="form-control" value="<?= htmlspecialchars(@$time_limit_minutes) ?>" required min="1">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Pass Mark Percentage (%)</label>
                        <input type="number" step="0.01" name="pass_mark_percentage" class="form-control" value="<?= htmlspecialchars(@$pass_mark_percentage) ?>" min="0" max="100">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="Draft" <?= (@$status == 'Draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="Published" <?= (@$status == 'Published') ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create Quiz and Add Questions</button>
                <a href="manage_quizzes.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>
