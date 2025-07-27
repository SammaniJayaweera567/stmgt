<?php
ob_start();
include '../../../init.php'; // Correct path from /system/assessments/assignments/
if (!hasPermission($_SESSION['user_id'], 'assignments_result')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// --- Security Check (REMOVED AS PER REQUEST for development) ---
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../../../login.php"); // Redirect to system login
//     exit();
// }

$db = dbConn();

// Get user's ID (still needed for 'evaluated_by_teacher_id')
$logged_in_user_id = (int)($_SESSION['user_id'] ?? 0); 

$messages = [];

$assignment_id = (int)($_REQUEST['assignment_id'] ?? 0); 

if ($assignment_id === 0) {
    header("Location: manage_assignments.php?status=notfound&message=Assignment ID not provided.");
    exit();
}

// --- Fetch Assignment Details ---
$sql_assignment = "SELECT 
                        a.id, a.title, a.max_marks, a.due_date, a.status,
                        cl.level_name, s.subject_name, ct.type_name,
                        c.id as class_id_for_assignment
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


// --- Fetch all Grades (A, B, C etc.) for auto-grading logic ---
$sql_grades = "SELECT id, grade_name, min_percentage, max_percentage FROM grades WHERE status='Active' ORDER BY min_percentage DESC";
$all_grades_result = $db->query($sql_grades);
$grades_map = []; // Grade ID => Grade Name
$grades_percentage_map = []; // Grade ID => {min, max} %
if ($all_grades_result) {
    while($grade = $all_grades_result->fetch_assoc()) {
        $grades_map[$grade['id']] = $grade['grade_name'];
        $grades_percentage_map[$grade['id']] = ['min' => $grade['min_percentage'], 'max' => $grade['max_percentage']];
    }
}


// --- Handle POST submission for entering results ---
// =================================================================================================
// START: REVISED LOGIC FOR SAVING RESULTS
// =================================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) { // Results form submission
    $db->begin_transaction();
    try {
        if (isset($_POST['results']) && is_array($_POST['results'])) {
            foreach ($_POST['results'] as $student_user_id => $data) {
                
                $marks_obtained_input = $data['marks_obtained'] ?? '';
                $remarks = dataClean($data['remarks'] ?? ''); 

                // --- Process only if marks are entered ---
                // If marks field is empty for a student, we will simply skip them.
                // This prevents deleting their existing marks if the teacher doesn't update them.
                if (is_numeric($marks_obtained_input) && $marks_obtained_input !== '') {
                    
                    $marks_obtained = (float)$marks_obtained_input;

                    // Validate marks are within the allowed range
                    if ($marks_obtained < 0 || $marks_obtained > (float)$assignment_details['max_marks']) {
                        // You can choose to show an error or just skip invalid entries.
                        // Here, we'll just skip to prevent breaking the whole transaction.
                        continue; 
                    }
                    
                    // --- Auto-determine Grade based on percentage ---
                    $percentage_obtained = ($assignment_details['max_marks'] > 0) ? ($marks_obtained / (float)$assignment_details['max_marks']) * 100 : 0;
                    $grade_id = 'NULL'; // Default to NULL
                    foreach ($grades_percentage_map as $g_id => $range) {
                        $min_val = (float)($range['min'] ?? -1); 
                        $max_val = (float)($range['max'] ?? 101); 
                        if ($percentage_obtained >= $min_val && $percentage_obtained <= $max_val) {
                            $grade_id = "'$g_id'"; // Enclose in quotes for SQL
                            break;
                        }
                    }

                    // --- UPSERT LOGIC (Update or Insert) ---
                    // This is the core fix. We check if a record exists first.
                    $sql_check = "SELECT id FROM assessment_results WHERE assessment_id = '$assignment_id' AND student_user_id = '$student_user_id'";
                    $result_check = $db->query($sql_check);

                    if ($result_check && $result_check->num_rows > 0) {
                        // --- UPDATE existing record ---
                        $sql_update_result = "UPDATE assessment_results 
                                              SET marks_obtained = '$marks_obtained', 
                                                  grade_id = $grade_id, 
                                                  remarks = '$remarks', 
                                                  evaluated_by_teacher_id = " . ($logged_in_user_id > 0 ? "'$logged_in_user_id'" : 'NULL') . ", 
                                                  evaluated_at = NOW()
                                              WHERE assessment_id = '$assignment_id' AND student_user_id = '$student_user_id'";
                        if (!$db->query($sql_update_result)) {
                            throw new Exception("SQL Update Error: " . $db->error);
                        }
                    } else {
                        // --- INSERT new record ---
                        $sql_insert_result = "INSERT INTO assessment_results (assessment_id, student_user_id, marks_obtained, grade_id, remarks, evaluated_by_teacher_id, evaluated_at)
                                              VALUES ('$assignment_id', '$student_user_id', '$marks_obtained', $grade_id, '$remarks', " . ($logged_in_user_id > 0 ? "'$logged_in_user_id'" : 'NULL') . ", NOW())";
                        if (!$db->query($sql_insert_result)) {
                            throw new Exception("SQL Insert Error: " . $db->error);
                        }
                    }
                }
            }
        }

        // Update assessment status to 'Graded' if not already
        if ($assignment_details['status'] !== 'Graded') { 
            $sql_update_assessment_status = "UPDATE assessments SET status = 'Graded' WHERE id = '$assignment_id'";
            $db->query($sql_update_assessment_status);
        }

        $db->commit();
        header("Location: enter_results.php?assignment_id=$assignment_id&status=updated&message=Assignment results saved successfully!");
        exit();

    } catch (Exception $e) {
        $db->rollback();
        $messages['main'] = "Database error: Could not enter results. " . $e->getMessage();
    }
}
// =================================================================================================
// END: REVISED LOGIC
// =================================================================================================


// --- Fetch Students for Results Entry ---
// This query now fetches ALL enrolled students for the class, not just those who submitted.
// This allows the teacher to see the full class list.
$sql_students_for_results = "SELECT 
                                u.Id as student_user_id, 
                                u.FirstName, 
                                u.LastName, 
                                sd.registration_no,
                                ss.id as submission_id,
                                ss.submission_status,
                                ss.file_name,
                                ss.file_path,
                                ss.submitted_at,
                                ar.marks_obtained,      -- Existing marks
                                ar.remarks,             -- Existing remarks
                                gr.grade_name           -- Existing grade name from grades table
                            FROM enrollments e
                            JOIN users u ON e.student_user_id = u.Id
                            LEFT JOIN student_details sd ON u.Id = sd.user_id
                            LEFT JOIN student_submissions ss ON u.Id = ss.student_user_id AND ss.assessment_id = '$assignment_id'
                            LEFT JOIN assessment_results ar ON u.Id = ar.student_user_id AND ar.assessment_id = '$assignment_id'
                            LEFT JOIN grades gr ON ar.grade_id = gr.id
                            WHERE e.class_id = '{$assignment_details['class_id_for_assignment']}'
                            AND e.status = 'active' -- Only active enrollments
                            ORDER BY u.FirstName";
$result_students = $db->query($sql_students_for_results);

?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-clipboard-check mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Enter Assignment Results</h5>
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
        <!-- IMPORTANT: Removed enctype="multipart/form-data" as we removed the file upload form -->
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?assignment_id=' . $assignment_id ?>">
            <div class="card-body">
                <?php if(!empty($messages['main'])): ?>
                    <div class="alert alert-danger"><?= $messages['main'] ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table id="resultsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reg. No</th>
                                <th>Student Name</th>
                                <th>Marks Obtained (Out of <?= htmlspecialchars(number_format($assignment_details['max_marks'], 2)) ?>)</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                                <th>Submission Status</th>
                                <!-- CHANGED: Removed the confusing upload form from this page -->
                                <th>Submitted File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_students && $result_students->num_rows > 0): ?>
                                <?php $i = 1; while ($student = $result_students->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($student['registration_no']) ?></td>
                                        <td><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></td>
                                        <td>
                                            <input type="number" step="0.01" name="results[<?= $student['student_user_id'] ?>][marks_obtained]" 
                                                   class="form-control marks-input" 
                                                   min="0" max="<?= htmlspecialchars($assignment_details['max_marks']) ?>"
                                                   value="<?= htmlspecialchars($student['marks_obtained'] ?? '') ?>"
                                                   data-student-id="<?= $student['student_user_id'] ?>">
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary grade-badge" 
                                                  id="grade_<?= $student['student_user_id'] ?>">
                                                <?= !empty($student['grade_name']) ? htmlspecialchars($student['grade_name']) : 'N/A' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <input type="text" name="results[<?= $student['student_user_id'] ?>][remarks]" 
                                                   class="form-control" 
                                                   value="<?= htmlspecialchars($student['remarks'] ?? '') ?>">
                                        </td>
                                        <td>
                                            <?php 
                                            // Logic to display submission status badge
                                            $submission_status_text = 'Not Submitted';
                                            $submission_status_class = 'badge-secondary';
                                            if (!empty($student['submission_status'])) {
                                                $submission_status_text = htmlspecialchars(ucfirst(str_replace('_', ' ', $student['submission_status'])));
                                                switch ($student['submission_status']) {
                                                    case 'Submitted': 
                                                        $submission_status_class = 'bg-success'; 
                                                        if (strtotime($student['submitted_at'] ?? 'now') > strtotime($assignment_details['due_date'])) {
                                                            $submission_status_class = 'bg-warning text-dark';
                                                            $submission_status_text = 'Late Submission';
                                                        }
                                                        break;
                                                    default: $submission_status_class = 'bg-secondary'; break;
                                                }
                                            } else {
                                                if (strtotime('now') > strtotime($assignment_details['due_date'])) {
                                                    $submission_status_text = 'Overdue';
                                                    $submission_status_class = 'bg-danger';
                                                }
                                            }
                                            ?>
                                            <span class="badge <?= $submission_status_class ?>"><?= $submission_status_text ?></span>
                                        </td>
                                        <td>
                                            <!-- SIMPLIFIED: Only show download link if file exists. Removed upload form. -->
                                            <?php if (!empty($student['file_path'])): ?>
                                                <a href="<?= SYS_URL ?>web/uploads/submissions/<?= htmlspecialchars($student['file_name']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Download Submission">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No File</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center">No students enrolled in this class.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Results</button>
                <a href="manage_assignments.php" class="btn btn-secondary ml-2">Back to Assignments</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const maxMarks = parseFloat(<?= json_encode($assignment_details['max_marks']) ?>);
    // Grade data passed from PHP
    const gradesPercentageMap = <?= json_encode($grades_percentage_map) ?>;
    const gradesMap = <?= json_encode($grades_map) ?>;

    // Function to find grade name from percentage
    function getGradeName(percentage) {
        if (isNaN(percentage)) return 'N/A';
        for (const g_id in gradesPercentageMap) {
            const range = gradesPercentageMap[g_id];
            if (percentage >= parseFloat(range.min) && percentage <= parseFloat(range.max)) {
                return gradesMap[g_id];
            }
        }
        return 'N/A'; // Return N/A if no grade range matches
    }

    // Attach event listener to all marks input fields for real-time grade update
    document.querySelectorAll('.marks-input').forEach(input => {
        input.addEventListener('input', function() {
            const marksObtained = parseFloat(this.value);
            const studentId = this.dataset.studentId;
            const gradeBadge = document.getElementById('grade_' + studentId);

            if (isNaN(marksObtained) || marksObtained < 0 || marksObtained > maxMarks) {
                gradeBadge.textContent = 'Invalid';
                return;
            }
            
            const percentage = (maxMarks > 0) ? (marksObtained / maxMarks) * 100 : 0;
            gradeBadge.textContent = getGradeName(percentage);
        });
    });

    // Initialize DataTable for the results table
    // Ensure you have jQuery and DataTables JS included in your layouts.php
    if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        $('#resultsTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php'; // Correct path to layouts.php
?>
