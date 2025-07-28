<?php
ob_start();
include '../../../init.php'; // Correct path from /system/assessments/assignments/

$db = dbConn();
$messages = [];

$assignment_id = (int)($_GET['assignment_id'] ?? 0); // Get assignment ID from URL

if ($assignment_id === 0) {
    header("Location: manage_assignments.php?status=notfound&message=Assignment ID not provided.");
    exit();
}

// --- Fetch Assignment Details ---
$sql_assignment = "SELECT 
                        a.id, a.title, a.max_marks, a.due_date, a.status,
                        cl.level_name, s.subject_name, ct.type_name,
                        c.id as class_id_for_assignment -- Get class_id associated with this assignment
                    FROM assessments a
                    JOIN classes c ON a.class_id = c.id
                    JOIN class_levels cl ON c.class_level_id = cl.id
                    JOIN subjects s ON c.subject_id = s.id
                    JOIN class_types ct ON c.class_type_id = ct.id
                    WHERE a.id = '$assignment_id' AND a.assessment_type = 'Assignment'";
$result_assignment = $db->query($sql_assignment);

if ($result_assignment->num_rows === 0) {
    header("Location: manage_assignments.php?status=notfound&message=Assignment not found or is not an Assignment type.");
    exit();
}
$assignment_details = $result_assignment->fetch_assoc();
$class_id_for_assignment = $assignment_details['class_id_for_assignment'];
$page_title = "Submissions for " . htmlspecialchars($assignment_details['title']);


// --- Handle POST submission (for manual upload/status change by teacher in future) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = dataClean($_POST['action']);

    // Handle Mark as Submitted action by teacher
    if ($action == 'mark_submitted') {
        $submission_id = (int)($_POST['submission_id'] ?? 0);
        $student_user_id = (int)($_POST['student_user_id'] ?? 0);

        if ($submission_id > 0) {
            // Update existing submission if it exists
            $update_sql = "UPDATE student_submissions 
                           SET submission_status = 'Submitted', submitted_at = NOW() 
                           WHERE id = '$submission_id' AND assessment_id = '$assignment_id' AND student_user_id = '$student_user_id'";
            
            if ($db->query($update_sql)) {
                header("Location: view_submissions.php?assignment_id=$assignment_id&status=updated&message=Submission status marked as Submitted.");
                exit();
            } else {
                header("Location: view_submissions.php?assignment_id=$assignment_id&status=error&message=Failed to update submission status: " . $db->error);
                exit();
            }
        } else {
            // If submission_id is not provided (meaning no prior entry in student_submissions table),
            // CREATE a new 'Submitted' entry for the student. This handles cases where a physical submission happens first.
            // Ensure this student is enrolled in the class.
            $check_enrollment_sql = "SELECT id FROM enrollments WHERE student_user_id = '$student_user_id' AND class_id = '$class_id_for_assignment' AND status = 'active'";
            $enrollment_result = $db->query($check_enrollment_sql);

            if ($enrollment_result->num_rows > 0) {
                $insert_submission_sql = "INSERT INTO student_submissions (assessment_id, student_user_id, submitted_at, submission_status)
                                          VALUES ('$assignment_id', '$student_user_id', NOW(), 'Submitted')";
                if ($db->query($insert_submission_sql)) {
                    header("Location: view_submissions.php?assignment_id=$assignment_id&status=added&message=New submission record created as Submitted.");
                    exit();
                } else {
                    header("Location: view_submissions.php?assignment_id=$assignment_id&status=error&message=Failed to create new submission record: " . $db->error);
                    exit();
                }
            } else {
                 header("Location: view_submissions.php?assignment_id=$assignment_id&status=error&message=Student not enrolled in this class to create submission.");
                 exit();
            }
        }
    }
}

// Fetch all active students enrolled in this assignment's class,
// along with their submission status and results (if any)
$sql_students_with_submissions = "SELECT 
                                    u.Id as student_user_id, 
                                    u.FirstName, 
                                    u.LastName, 
                                    sd.registration_no,
                                    e.enrollment_date,
                                    ss.id as submission_id,
                                    ss.submission_status,
                                    ss.file_name,
                                    ss.file_path,
                                    ss.submitted_at,
                                    ar.marks_obtained,
                                    gr.grade_name
                                FROM enrollments e
                                JOIN users u ON e.student_user_id = u.Id
                                LEFT JOIN student_details sd ON u.Id = sd.user_id
                                LEFT JOIN student_submissions ss ON u.Id = ss.student_user_id AND ss.assessment_id = '$assignment_id'
                                LEFT JOIN assessment_results ar ON u.Id = ar.student_user_id AND ar.assessment_id = '$assignment_id'
                                LEFT JOIN grades gr ON ar.grade_id = gr.id
                                WHERE e.class_id = '$class_id_for_assignment' AND e.status = 'active'
                                ORDER BY u.FirstName";
$result_students = $db->query($sql_students_with_submissions);

?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-upload mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto"><?= $page_title ?></h5>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                Assignment: <?= htmlspecialchars($assignment_details['title']) ?> (Max Marks: <?= htmlspecialchars(number_format($assignment_details['max_marks'], 2)) ?>)
                <br>
                <small class="text-white-50">Due Date: <?= htmlspecialchars(date('Y-m-d H:i A', strtotime($assignment_details['due_date']))) ?></small>
            </h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="submissionsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Reg. No</th>
                            <th>Student Name</th>
                            <th>Submission Status</th>
                            <th>Submitted On</th>
                            <th>File</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_students->num_rows > 0): ?>
                            <?php $i = 1; while ($student = $result_students->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($student['registration_no']) ?></td>
                                    <td><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></td>
                                    <td>
                                        <?php 
                                            // Determine submission status badge
                                            $submission_status_text = 'Not Submitted';
                                            $submission_status_class = 'badge-secondary';
                                            if (!empty($student['submission_status'])) {
                                                $submission_status_text = htmlspecialchars(ucfirst(str_replace('_', ' ', $student['submission_status'])));
                                                switch ($student['submission_status']) {
                                                    case 'Submitted': 
                                                        $submission_status_class = 'bg-success'; 
                                                        // Check if past due date
                                                        if (strtotime($student['submitted_at']) > strtotime($assignment_details['due_date'])) {
                                                            $submission_status_class = 'bg-warning text-dark'; // Late submission warning color
                                                            $submission_status_text = 'Late Submission';
                                                        }
                                                        break;
                                                    case 'Graded': $submission_status_class = 'bg-info'; break; 
                                                    case 'Pending': $submission_status_class = 'bg-primary'; break;
                                                    case 'Late Submission': $submission_status_class = 'bg-warning text-dark'; break; // Explicitly handle if already late
                                                    default: $submission_status_class = 'bg-secondary'; break;
                                                }
                                            }
                                        ?>
                                        <span class="badge <?= $submission_status_class ?>"><?= $submission_status_text ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($student['submitted_at'])): ?>
                                            <?= htmlspecialchars(date('Y-m-d H:i A', strtotime($student['submitted_at']))) ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($student['file_path'])): ?>
                                            <a href="../../web/uploads/submissions/<?= htmlspecialchars($student['file_name']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Download Submission">
                                                <i class="fas fa-download"></i> <?= htmlspecialchars($student['file_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            No File
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!is_null($student['marks_obtained'])): ?>
                                            <strong><?= htmlspecialchars(number_format($student['marks_obtained'], 2)) ?></strong>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!is_null($student['grade_name'])): ?>
                                            <span class="badge bg-info"><?= htmlspecialchars($student['grade_name']) ?></span>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if (empty($student['submission_status']) || $student['submission_status'] == 'Pending'): ?>
                                                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?assignment_id=<?= $assignment_id ?>" method="post" style="display:inline-block;" onsubmit="return confirm('Mark this submission as submitted?');">
                                                    <input type="hidden" name="action" value="mark_submitted">
                                                    <input type="hidden" name="submission_id" value="<?= $student['submission_id'] ?>">
                                                    <input type="hidden" name="student_user_id" value="<?= $student['student_user_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning" title="Mark as Submitted">
                                                        <i class="fas fa-check-circle"></i> Mark
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="enter_results.php?assignment_id=<?= $assignment_id ?>&student_id=<?= $student['student_user_id'] ?>" class="btn btn-sm btn-success ml-1" title="Enter/Edit Result">
                                                <i class="fas fa-clipboard-check"></i> Results
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center">No students enrolled in this class.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <a href="manage_assignments.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() { 
        initializeDataTable('submissionsTable'); 
    });
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php'; // Correct path to layouts.php
?>