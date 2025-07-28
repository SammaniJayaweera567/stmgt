<?php
ob_start();
include '../../../init.php'; // Path from /system/assessments/quizzes/

$db = dbConn();
$quiz_id = (int)($_GET['quiz_id'] ?? 0);

if ($quiz_id === 0) {
    header("Location: manage_quizzes.php?status=notfound");
    exit();
}

// --- Fetch Quiz Details ---
$sql_quiz = "SELECT title, max_marks, pass_mark_percentage FROM assessments WHERE id = '$quiz_id' AND assessment_type_id = 3"; // Changed to assessment_type_id = 3 (for Quiz)
$result_quiz = $db->query($sql_quiz);
if ($result_quiz->num_rows === 0) {
    header("Location: manage_quizzes.php?status=notfound&message=Quiz not found or is not a Quiz type.");
    exit();
}
$quiz_data = $result_quiz->fetch_assoc(); // Fetch quiz data
$quiz_title = $quiz_data['title']; // Quiz title
$max_marks_quiz = $quiz_data['max_marks']; // Use a distinct variable name for quiz's max marks
$pass_mark_percentage = $quiz_data['pass_mark_percentage']; // Pass mark percentage

// --- Fetch Student Results for this Quiz ---
// UPDATED: Joined student_submissions table to get the submission ID for the "View Answers" link
$sql_results = "SELECT 
                    u.FirstName, 
                    u.LastName, 
                    sd.registration_no,
                    ar.marks_obtained,
                    gr.grade_name,
                    ss.id as submission_id
                FROM assessment_results ar
                JOIN users u ON ar.student_user_id = u.Id
                LEFT JOIN student_details sd ON u.Id = sd.user_id
                LEFT JOIN grades gr ON ar.grade_id = gr.id
                LEFT JOIN student_submissions ss ON ar.assessment_id = ss.assessment_id AND ar.student_user_id = ss.student_user_id
                WHERE ar.assessment_id = '$quiz_id'
                ORDER BY ar.marks_obtained DESC, u.FirstName ASC";

$result_students = $db->query($sql_results); // Fetch student results
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-poll mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Quiz Results: <strong><?= htmlspecialchars($quiz_title) ?></strong></h5>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Student Performance</h3>
            <div>
                <span class="badge bg-info">Total Marks:
                    <?= htmlspecialchars(number_format($max_marks_quiz, 2)) ?></span>
                <span class="badge bg-warning text-dark">Pass Mark:
                    <?= htmlspecialchars($pass_mark_percentage) ?>%</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="resultsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Reg. No</th>
                            <th>Student Name</th>
                            <th>Marks Obtained</th>
                            <th>Percentage (%)</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_students && $result_students->num_rows > 0) {
                            $rank = 1;
                            while ($row = $result_students->fetch_assoc()) {
                                // Use $max_marks_quiz for percentage calculation
                                $percentage = ($max_marks_quiz > 0) ? ($row['marks_obtained'] / $max_marks_quiz) * 100 : 0;
                                $status_text_pass_fail = ($percentage >= $pass_mark_percentage) ? 'Pass' : 'Fail';
                                $status_class_pass_fail = ($percentage >= $pass_mark_percentage) ? 'bg-success' : 'bg-danger';
                        ?>
                        <tr>
                            <td><?= $rank++ ?></td>
                            <td><?= htmlspecialchars($row['registration_no']) ?></td>
                            <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                            <td><?= htmlspecialchars(number_format($row['marks_obtained'], 2)) ?> /
                                <?= htmlspecialchars(number_format($max_marks_quiz, 2)) ?></td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($row['grade_name'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $status_class_pass_fail ?>"><?= $status_text_pass_fail ?></span>
                            </td>
                            <td>
                                <?php if (!empty($row['submission_id'])): ?>
                                <a href="view_student_answers.php?submission_id=<?= $row['submission_id'] ?>"
                                    class="btn btn-info btn-sm" title="View Answers">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                            // Colspan changed from 7 to 8 (already correct in your provided code)
                            echo '<tr><td colspan="8" class="text-center">No results found for this quiz yet.</td></tr>';
                        }
                        ?>
                    </tbody>
            </div>
        </div>
        <div class="card-footer">
            <a href="manage_quizzes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to All Quizzes
            </a>
        </div>

    </div>
</div>

<script>
$(document).ready(function() {
    $('#resultsTable').DataTable({
        "order": [
            [0, "asc"]
        ] // Order by the first column (Rank)
    });
});
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>