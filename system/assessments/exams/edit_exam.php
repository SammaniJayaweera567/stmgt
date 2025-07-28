<?php
ob_start();
include '../../../init.php';
if (!hasPermission($_SESSION['user_id'], 'edit_exam')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// No user role check for now, will add later
// if (!isset($_SESSION['user_id'])) { header("Location:../login.php"); exit(); }

$db = dbConn();
$messages = [];
$id = (int)($_REQUEST['id'] ?? 0); // Get exam ID from GET or POST

if ($id === 0) {
    header("Location: manage_exams.php?status=notfound");
    exit();
}

// --- Handle form submission for UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST);

    // Clean data
    $title = dataClean($title);
    $class_id = dataClean($class_id);
    $academic_year_id = dataClean($academic_year_id);
    $subject_id = dataClean($subject_id);
    $teacher_id = dataClean($teacher_id);
    $assessment_date = dataClean($assessment_date);
    $start_time = dataClean($start_time);
    $end_time = dataClean($end_time);
    $class_room_id = dataClean($class_room_id);

    // --- Basic Validations ---
    if (empty($title)) { $messages['title'] = "Title is required."; }
    if (empty($class_id)) { $messages['class_id'] = "Class is required."; }
    if (empty($class_room_id)) { $messages['class_room_id'] = "Exam Hall is required."; }
    if (!empty($start_time) && !empty($end_time) && strtotime($start_time) >= strtotime($end_time)) {
        $messages['main'] = "End time must be after Start time.";
    }

    // --- Advanced Validations (excluding the current exam ID from checks) ---
    if (empty($messages)) {
        $day_of_week = date('l', strtotime($assessment_date));

        // 1. TEACHER CONFLICT
        $sql_teacher_exam = "SELECT id FROM assessments WHERE teacher_id = '$teacher_id' AND assessment_date = '$assessment_date' AND ('$start_time' < end_time AND '$end_time' > start_time) AND id != '$id'";
        if ($db->query($sql_teacher_exam)->num_rows > 0) { $messages['main'] = "Teacher Conflict: Teacher is scheduled for another exam."; }
        
        $sql_teacher_class = "SELECT id FROM classes WHERE teacher_id = '$teacher_id' AND day_of_week = '$day_of_week' AND ('$start_time' < end_time AND '$end_time' > start_time)";
        if ($db->query($sql_teacher_class)->num_rows > 0) { $messages['main'] = ($messages['main'] ?? '') . " Teacher Conflict: Teacher has a regular class at this time."; }

        // 2. HALL CONFLICT & CAPACITY
        $sql_hall_exam = "SELECT id FROM assessments WHERE class_room_id = '$class_room_id' AND assessment_date = '$assessment_date' AND ('$start_time' < end_time AND '$end_time' > start_time) AND id != '$id'";
        if ($db->query($sql_hall_exam)->num_rows > 0) { $messages['main'] = ($messages['main'] ?? '') . " Hall Conflict: Another exam is in this hall."; }

        $sql_hall_class = "SELECT id FROM classes WHERE class_room_id = '$class_room_id' AND day_of_week = '$day_of_week' AND ('$start_time' < end_time AND '$end_time' > start_time)";
        if ($db->query($sql_hall_class)->num_rows > 0) { $messages['main'] = ($messages['main'] ?? '') . " Hall Conflict: A regular class is in this hall."; }

        $sql_enroll_count = "SELECT COUNT(id) as student_count FROM enrollments WHERE class_id = '$class_id' AND status = 'active'";
        $student_count = $db->query($sql_enroll_count)->fetch_assoc()['student_count'];
        $sql_hall_capacity = "SELECT capacity FROM class_rooms WHERE id = '$class_room_id'";
        $hall_capacity = $db->query($sql_hall_capacity)->fetch_assoc()['capacity'];
        if ($student_count > $hall_capacity) {
            $messages['main'] = "Hall Capacity Exceeded: The hall has capacity for $hall_capacity, but $student_count students are enrolled.";
        }

        // 3. STUDENT CONFLICT
        $sql_student_conflict = "SELECT a.id FROM assessments a JOIN enrollments e ON a.class_id = e.class_id WHERE e.student_user_id IN (SELECT student_user_id FROM enrollments WHERE class_id = '$class_id') AND a.assessment_date = '$assessment_date' AND ('$start_time' < a.end_time AND '$end_time' > a.start_time) AND a.id != '$id'";
        if ($db->query($sql_student_conflict)->num_rows > 0) { $messages['main'] = ($messages['main'] ?? '') . " Student Conflict: Students have another exam at this time."; }

        // 4. LOGICAL CHECKS
        $sql_ay = "SELECT start_date, end_date FROM academic_years WHERE id = '$academic_year_id'";
        $ay_result = $db->query($sql_ay)->fetch_assoc();
        if ($assessment_date < $ay_result['start_date'] || $assessment_date > $ay_result['end_date']) {
            $messages['main'] = ($messages['main'] ?? '') . " Logical Error: Exam date is outside the selected academic year.";
        }
    }

    if (empty($messages)) {
        // Database update
        $assessment_type_id = 1; // 1 represents 'Exam'
        $sql = "UPDATE assessments SET 
                    title = '$title', 
                    description = '$description', 
                    class_id = '$class_id', 
                    academic_year_id = '$academic_year_id', 
                    subject_id = '$subject_id', 
                    teacher_id = '$teacher_id', 
                    class_room_id = '$class_room_id', 
                    max_marks = '$max_marks', 
                    assessment_date = '$assessment_date', 
                    start_time = '$start_time', 
                    end_time = '$end_time', 
                    pass_mark_percentage = '$pass_mark_percentage', 
                    status = '$status'
                WHERE id = '$id' AND assessment_type_id = '$assessment_type_id'";

        if ($db->query($sql)) {
            header("Location: manage_exams.php?status=updated");
            exit();
        } else {
            $messages['main'] = "Database error: Could not update exam. " . $db->error;
        }
    }
} else {
    // Fetch existing data for the form if it's not a POST request
    $assessment_type_id = 1; // 1 represents 'Exam' in the assessment_types table
    $sql_fetch = "SELECT * FROM assessments WHERE id = '$id' AND assessment_type_id = '$assessment_type_id'";
    $result_fetch = $db->query($sql_fetch);
    if ($result_fetch->num_rows === 0) {
        header("Location: manage_exams.php?status=notfound");
        exit();
    }
    $exam_data = $result_fetch->fetch_assoc();
    extract($exam_data); // This will populate all variables like $title, $class_id etc.
}

// Fetch data for dropdowns
$academic_years = $db->query("SELECT * FROM academic_years WHERE status='Active'");
$classes = $db->query("SELECT c.id, cl.level_name, s.subject_name, ct.type_name FROM classes c JOIN class_levels cl ON c.class_level_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN class_types ct ON c.class_type_id = ct.id WHERE c.status='Active' ORDER BY cl.level_name, s.subject_name");
$subjects = $db->query("SELECT * FROM subjects WHERE status='Active'");
$teachers = $db->query("SELECT u.Id, u.FirstName, u.LastName FROM users u JOIN user_roles ur ON u.user_role_id=ur.Id WHERE ur.RoleName='Teacher' AND u.Status='Active'");
$class_rooms = $db->query("SELECT * FROM class_rooms WHERE status='Active'");
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Edit Exam: <?= htmlspecialchars(@$title) ?></h3>
        </div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $id ?>">
            <div class="card-body">
                <?php if (!empty($messages['main'])): ?>
                <div class="alert alert-danger"><?= $messages['main'] ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars(@$title) ?>"
                            required>
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
                    <textarea name="description" class="form-control"
                        rows="3"><?= htmlspecialchars(@$description) ?></textarea>
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
                        <input type="number" step="0.01" name="max_marks" class="form-control"
                            value="<?= htmlspecialchars(@$max_marks) ?>" required min="0">
                        <span class="text-danger"><?= @$messages['max_marks'] ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Pass Mark Percentage (%)</label>
                        <input type="number" step="0.01" name="pass_mark_percentage" class="form-control"
                            value="<?= htmlspecialchars(@$pass_mark_percentage) ?>" min="0" max="100">
                        <span class="text-danger"><?= @$messages['pass_mark_percentage'] ?></span>
                    </div>
                </div>

                <hr class="my-4">
                <h4>Exam Schedule Details</h4>
                <div class="row">
                    <div class="form-group col-md-4"><label>Exam Date</label><input type="date" name="assessment_date"
                            class="form-control" value="<?= htmlspecialchars(@$assessment_date) ?>"></div>
                    <div class="form-group col-md-4"><label>Start Time</label><input type="time" name="start_time"
                            class="form-control" value="<?= htmlspecialchars(@$start_time) ?>"></div>
                    <div class="form-group col-md-4"><label>End Time</label><input type="time" name="end_time"
                            class="form-control" value="<?= htmlspecialchars(@$end_time) ?>"></div>
                </div>
                <div class="form-group mt-3">
                    <label>Exam Hall <span class="text-danger">*</span></label>
                    <select name="class_room_id" class="form-control" required>
                        <option value="">-- Select Exam Hall --</option>
                        <?php 
                        mysqli_data_seek($class_rooms, 0);
                        while($room = $class_rooms->fetch_assoc()) { 
                            $selected = (@$class_room_id == $room['id']) ? 'selected' : '';
                            echo "<option value='{$room['id']}' $selected>" . htmlspecialchars($room['room_name']) . " (Capacity: {$room['capacity']})</option>";
                        } ?>
                    </select>
                    <span class="text-danger"><?= @$messages['class_room_id'] ?></span>
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