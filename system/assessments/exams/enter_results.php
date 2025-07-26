<?php
ob_start();
include '../../../init.php'; // Corrected path

// Check if the logged-in user has the correct role OR is the Super Admin (ID = 1).
// $logged_in_user_id = (int)$_SESSION['user_id'];
// $user_role_name = ''; 

$db = dbConn();

// Fetch user's role name from the database
if ($logged_in_user_id > 0) {
    $sql_user_role = "SELECT ur.RoleName FROM users u JOIN user_roles ur ON u.user_role_id = ur.Id WHERE u.Id = '$logged_in_user_id'";
    $result_user_role = $db->query($sql_user_role);
    if ($result_user_role && $result_user_role->num_rows > 0) {
        $user_role_name = strtolower($result_user_role->fetch_assoc()['RoleName']);
    }
}

// Allow access only if the user ID is 1 (Super Admin) OR the role is 'admin' or 'teacher'.
if ($logged_in_user_id !== 1 && $user_role_name !== 'admin' && $user_role_name !== 'teacher') { 
    die("Access Denied: You do not have permission to enter exam results.");
}

$assessment_id = (int)($_REQUEST['assessment_id'] ?? 0); 

if ($assessment_id === 0) {
    header("Location: manage_exams.php?status=notfound&message=Exam ID not provided.");
    exit();
}

// --- Fetch Exam Details ---
$sql_exam = "SELECT 
                a.title, 
                a.max_marks, 
                a.assessment_date, 
                cl.level_name, 
                s.subject_name,
                a.status as exam_status -- To check if exam is already graded
            FROM assessments a
            JOIN classes c ON a.class_id = c.id
            JOIN class_levels cl ON c.class_level_id = cl.id
            JOIN subjects s ON a.subject_id = s.id
            WHERE a.id = '$assessment_id' AND a.assessment_type = 'Exam'";
$result_exam = $db->query($sql_exam);

if ($result_exam->num_rows === 0) {
    header("Location: manage_exams.php?status=notfound&message=Exam not found or is not an Exam type.");
    exit();
}
$exam_details = $result_exam->fetch_assoc();

// --- Fetch all Grades (A, B, C etc.) for dropdown and auto-grading logic ---
$sql_grades = "SELECT id, grade_name, min_percentage, max_percentage FROM grades WHERE status='Active' ORDER BY min_percentage DESC";
$all_grades_result = $db->query($sql_grades);
$grades_map = []; // For easy lookup: Grade_ID => Grade_Name
$grades_percentage_map = []; // For auto-grading: Min, Max percentage
if ($all_grades_result) {
    while($grade = $all_grades_result->fetch_assoc()) {
        $grades_map[$grade['id']] = $grade['grade_name'];
        $grades_percentage_map[$grade['id']] = ['min' => $grade['min_percentage'], 'max' => $grade['max_percentage']];
    }
}


// --- Handle POST submission for entering results ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db->begin_transaction();
    try {
        // Delete existing results for this assessment to avoid conflicts on re-submission
        // This makes it easier to update results by just inserting new records
        $sql_delete_existing_results = "DELETE FROM assessment_results WHERE assessment_id = '$assessment_id'";
        $db->query($sql_delete_existing_results);

        if (isset($_POST['results']) && is_array($_POST['results'])) {
            foreach ($_POST['results'] as $student_user_id => $data) {
                $marks_obtained = dataClean($data['marks_obtained']);
                $remarks = dataClean($data['remarks']);
                
                // Only insert if marks are provided and are valid numbers
                if (is_numeric($marks_obtained) && (float)$marks_obtained >= 0 && (float)$marks_obtained <= (float)$exam_details['max_marks']) {
                    $marks_obtained = (float)$marks_obtained;
                    $grade_id = 'NULL'; // Default to NULL if grade cannot be determined

                    // Auto-determine Grade based on percentage
                    // Ensure max_marks is not zero to avoid division by zero
                    $percentage_obtained = ($exam_details['max_marks'] > 0) ? ($marks_obtained / (float)$exam_details['max_marks']) * 100 : 0;
                    
                    foreach ($grades_percentage_map as $g_id => $range) {
                        if ($percentage_obtained >= (float)$range['min'] && $percentage_obtained <= (float)$range['max']) {
                            $grade_id = $g_id; // Store the actual grade_id from the database
                            break;
                        }
                    }

                    $sql_insert_result = "INSERT INTO assessment_results (assessment_id, student_user_id, marks_obtained, grade_id, remarks, evaluated_by_teacher_id, evaluated_at)
                                          VALUES ('$assessment_id', '$student_user_id', '$marks_obtained', " . ($grade_id === 'NULL' ? 'NULL' : "'$grade_id'") . ", '$remarks', '$logged_in_user_id', NOW())";
                    $db->query($sql_insert_result);
                }
            }
        }

        // Update assessment status to 'Graded' if not already
        if ($exam_details['exam_status'] !== 'Graded') {
            $sql_update_assessment_status = "UPDATE assessments SET status = 'Graded' WHERE id = '$assessment_id'";
            $db->query($sql_update_assessment_status);
        }

        $db->commit();
        header("Location: enter_results.php?assessment_id=$assessment_id&status=updated&message=Exam results entered successfully!");
        exit();

    } catch (Exception $e) {
        $db->rollback();
        $messages['main'] = "Database error: Could not enter results. " . $e->getMessage();
    }
}

// --- Fetch Students who were Present for the Exam ---
// Also fetch their existing results if already entered
$sql_students_for_results = "SELECT 
                                u.Id as student_user_id, 
                                u.FirstName, 
                                u.LastName, 
                                sd.registration_no,
                                ea.attendance_status, -- Should be 'Present'
                                ar.marks_obtained,    -- Existing marks
                                ar.remarks,           -- Existing remarks
                                ar.grade_id           -- Existing grade (Grade ID from grades table)
                             FROM enrollments e
                             JOIN users u ON e.student_user_id = u.Id
                             JOIN student_details sd ON u.Id = sd.user_id
                             LEFT JOIN exam_attendance ea ON u.Id = ea.student_user_id AND ea.assessment_id = '$assessment_id'
                             LEFT JOIN assessment_results ar ON u.Id = ar.student_user_id AND ar.assessment_id = '$assessment_id'
                             WHERE e.class_id = (SELECT class_id FROM assessments WHERE id = '$assessment_id') 
                             AND e.status = 'active' -- Only active enrollments
                             AND ea.attendance_status = 'Present' -- Only students who were marked present
                             ORDER BY u.FirstName";
$result_students_for_results = $db->query($sql_students_for_results);

?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-clipboard-check mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Enter Exam Results</h5>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                Exam: <?= htmlspecialchars($exam_details['title']) ?><br>
                <small class="text-white-50">
                    Class: <?= htmlspecialchars($exam_details['level_name']) ?> -
                    <?= htmlspecialchars($exam_details['subject_name']) ?> |
                    Max Marks: <?= htmlspecialchars(number_format($exam_details['max_marks'])) ?>
                </small>
            </h3>
        </div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?assessment_id=' . $assessment_id ?>">
            <div class="card-body">
                <?php if(!empty($messages['main'])): ?>
                <div class="alert alert-danger"><?= $messages['main'] ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reg. No</th>
                                <th>Student Name</th>
                                <th>Marks Obtained (Out of
                                    <?= htmlspecialchars(number_format($exam_details['max_marks'])) ?>)</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_students_for_results->num_rows > 0): ?>
                            <?php $i = 1; while ($student = $result_students_for_results->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($student['registration_no']) ?></td>
                                <td><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></td>
                                <td>
                                    <input type="number" step="0.01"
                                        name="results[<?= $student['student_user_id'] ?>][marks_obtained]"
                                        class="form-control marks-input" min="0"
                                        max="<?= htmlspecialchars($exam_details['max_marks']) ?>"
                                        value="<?= htmlspecialchars($student['marks_obtained']) ?>">
                                </td>
                                <td>
                                    <span class="badge bg-secondary grade-badge"
                                        id="grade_<?= $student['student_user_id'] ?>">
                                        <?= !empty($student['grade_id']) ? htmlspecialchars($grades_map[$student['grade_id']]) : 'N/A' ?>
                                    </span>
                                </td>
                                <td>
                                    <input type="text" name="results[<?= $student['student_user_id'] ?>][remarks]"
                                        class="form-control" value="<?= htmlspecialchars($student['remarks'] ?? '') ?>">
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No students marked 'Present' for this exam, or no students found.
                                    <br>
                                    <small>Please ensure students' attendance is marked as 'Present' for this
                                        exam.</small>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Results</button>
                <a href="manage_exams.php" class="btn btn-secondary ml-2">Back to Exams</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const maxMarks = parseFloat(<?= json_encode($exam_details['max_marks']) ?>);
    const gradesMap = <?= json_encode($grades_map) ?>; // Grade ID -> Grade Name
    const gradesPercentageMap = <?= json_encode($grades_percentage_map) ?>; // Grade ID -> {min, max} %

    // Function to calculate grade based on marks obtained
    function calculateGrade(marks) {
        // Ensure marks is a valid number and within bounds
        if (isNaN(marks) || marks < 0 || marks > maxMarks) {
            return {
                id: null,
                name: 'Invalid Marks'
            };
        }

        // Handle maxMarks being 0 to avoid division by zero
        const percentage = (maxMarks > 0) ? (marks / maxMarks) * 100 : 0;
        let gradeId = null;
        let gradeName = 'N/A';

        // Find the corresponding grade
        for (const g_id in gradesPercentageMap) {
            if (gradesPercentageMap.hasOwnProperty(g_id)) {
                const range = gradesPercentageMap[g_id];
                // Convert range values to float for accurate comparison
                if (percentage >= parseFloat(range.min) && percentage <= parseFloat(range.max)) {
                    gradeId = g_id; // Use the actual Grade ID
                    gradeName = gradesMap[g_id]; // Get the Grade Name
                    break; // Found the grade, exit loop
                }
            }
        }
        return {
            id: gradeId,
            name: gradeName
        };
    }

    // Attach event listener to all marks input fields
    document.querySelectorAll('.marks-input').forEach(input => {
        input.addEventListener('input', function() {
            const marksObtained = parseFloat(this.value);
            const studentId = this.name.match(/\[(\d+)\]/)[
            1]; // Extract student ID from input name
            const gradeBadge = document.getElementById('grade_' + studentId);

            const {
                id: gradeId,
                name: gradeName
            } = calculateGrade(marksObtained);

            gradeBadge.textContent = gradeName;
            // Optionally add/remove CSS classes for badge colors based on grade (e.g., pass/fail)
            // For example:
            // if (gradeName === 'A') gradeBadge.classList.add('bg-success'); else ...
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>