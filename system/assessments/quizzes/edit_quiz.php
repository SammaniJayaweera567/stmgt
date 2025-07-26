<?php
ob_start();
include '../../../init.php'; // Path from /system/assessments/quizzes/

$db = dbConn();
$messages = [];
$id = (int)($_REQUEST['id'] ?? 0);

if ($id === 0) {
    header("Location: manage_quizzes.php?status=notfound");
    exit();
}

// --- Fetch existing quiz data ---
$sql_fetch = "SELECT * FROM assessments WHERE id = '$id' AND assessment_type = 'Quiz'";
$result_fetch = $db->query($sql_fetch);

if ($result_fetch->num_rows === 0) {
    header("Location: manage_quizzes.php?status=notfound");
    exit();
}
$quiz_data = $result_fetch->fetch_assoc();

// Initialize form variables with existing data
$title = $quiz_data['title'];
$class_id = $quiz_data['class_id'];
$academic_year_id = $quiz_data['academic_year_id'];
$subject_id = $quiz_data['subject_id'];
$teacher_id = $quiz_data['teacher_id'];
$time_limit_minutes = $quiz_data['time_limit_minutes'];
$pass_mark_percentage = $quiz_data['pass_mark_percentage'];
$status = $quiz_data['status'];


// --- Handle form submission for UPDATE ---
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
        // All validations pass, update the database
        $sql_update = "UPDATE assessments SET 
                        title = '$title', 
                        class_id = '$class_id', 
                        academic_year_id = '$academic_year_id', 
                        subject_id = '$subject_id', 
                        teacher_id = '$teacher_id', 
                        time_limit_minutes = '$time_limit_minutes', 
                        pass_mark_percentage = '$pass_mark_percentage', 
                        status = '$status'
                      WHERE id = '$id' AND assessment_type = 'Quiz'";
        
        if ($db->query($sql_update)) {
            header("Location: manage_quizzes.php?status=updated");
            exit();
        } else {
            $messages['main'] = "Database error: Could not update the quiz. " . $db->error;
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
        <div class="card-header"><h3 class="card-title">Edit Quiz: <?= htmlspecialchars($quiz_data['title']) ?></h3></div>
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
                            <?php mysqli_data_seek($classes, 0); while($row = $classes->fetch_assoc()) { 
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
                            <?php mysqli_data_seek($subjects, 0); while($row = $subjects->fetch_assoc()) { 
                                $selected = (@$subject_id == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>".htmlspecialchars($row['subject_name'])."</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Teacher</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">-- Select Teacher --</option>
                            <?php mysqli_data_seek($teachers, 0); while($row = $teachers->fetch_assoc()) { 
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
                        <option value="Archived" <?= (@$status == 'Archived') ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Quiz</button>
                <a href="manage_quizzes.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>
