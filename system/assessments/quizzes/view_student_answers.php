<?php
ob_start();
include '../../../init.php'; // Path from /system/assessments/quizzes/
if (!hasPermission($_SESSION['user_id'], 'quizz_result')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$db = dbConn();
$submission_id = (int)($_GET['submission_id'] ?? 0);

if ($submission_id === 0) {
    die("Submission ID not provided.");
}

// --- 1. Fetch Submission Details (Quiz Title, Student Name, Score, Answers) ---
$sql_submission = "SELECT 
                        ss.submission_content,
                        a.title as quiz_title,
                        a.max_marks,
                        ar.marks_obtained,
                        CONCAT(u.FirstName, ' ', u.LastName) as student_name,
                        a.id as assessment_id
                   FROM student_submissions ss
                   JOIN assessments a ON ss.assessment_id = a.id
                   JOIN users u ON ss.student_user_id = u.Id
                   LEFT JOIN assessment_results ar ON ss.assessment_id = ar.assessment_id AND ss.student_user_id = ar.student_user_id
                   WHERE ss.id = '$submission_id'";
$result_submission = $db->query($sql_submission);
if ($result_submission->num_rows === 0) {
    die("Submission not found.");
}
$submission_data = $result_submission->fetch_assoc();

// FIX: Check if submission_content is not empty before decoding to prevent deprecated warning.
$student_answers = !empty($submission_data['submission_content']) ? json_decode($submission_data['submission_content'], true) : [];

$quiz_id = $submission_data['assessment_id'];

// --- 2. Fetch All Questions and Correct Answers for this Quiz ---
$sql_questions = "SELECT * FROM assessment_questions WHERE assessment_id = '$quiz_id' ORDER BY id ASC";
$result_questions = $db->query($sql_questions);
$all_questions = [];
if ($result_questions->num_rows > 0) {
    while($row = $result_questions->fetch_assoc()){
        $all_questions[] = $row;
    }
}

?>

<style>
    .answer-card {
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .answer-card-header {
        padding: 1rem 1.25rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }
    .answer-card-header h5 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }
    .answer-card-body {
        padding: 1.25rem;
    }
    .answer-options li {
        padding: 0.6rem 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .answer-options li.student-answer-correct {
        background-color: #d1e7dd; /* Light green for correct student answer */
        border-color: #badbcc;
    }
    .answer-options li.student-answer-incorrect {
        background-color: #f8d7da; /* Light red for incorrect student answer */
        border-color: #f5c2c7;
    }
    .answer-options li.correct-answer {
        border-left: 5px solid #198754; /* Green border for the actual correct answer */
    }
    .answer-options li .option-label {
        font-weight: bold;
        margin-right: 0.75rem;
        width: 20px;
    }
</style>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title mb-1">Reviewing: <strong><?= htmlspecialchars($submission_data['quiz_title']) ?></strong></h4>
                    <p class="card-text text-muted mb-0">Student: <strong><?= htmlspecialchars($submission_data['student_name']) ?></strong></p>
                </div>
                <div class="text-end mt-2 mt-md-0">
                    <h5 class="mb-1 text-muted">Final Score</h5>
                    <span class="badge bg-primary fs-5">
                        <?= htmlspecialchars(number_format($submission_data['marks_obtained'] ?? 0, 2)) ?> / <?= htmlspecialchars(number_format($submission_data['max_marks'] ?? 0, 2)) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>


    <?php if (!empty($all_questions)): ?>
        <?php $q_num = 1; foreach ($all_questions as $question): 
            $question_id = $question['id'];
            $student_choice = $student_answers[$question_id] ?? null; // Get student's choice for this question
            $correct_answer = $question['correct_answer'];
        ?>
            <div class="card answer-card">
                <div class="answer-card-header">
                    <h5>Question <?= $q_num++ ?></h5>
                </div>
                <div class="answer-card-body">
                    <p class="fs-5"><?= nl2br(htmlspecialchars($question['question_text'])) ?></p>
                    <ul class="list-unstyled answer-options">
                        <?php foreach (['A', 'B', 'C', 'D'] as $option): 
                            $option_text = $question['option_' . strtolower($option)];
                            if (empty($option_text)) continue;

                            $li_class = '';
                            if ($option == $correct_answer) {
                                $li_class .= ' correct-answer';
                            }
                            if ($option == $student_choice) {
                                $li_class .= ($student_choice == $correct_answer) ? ' student-answer-correct' : ' student-answer-incorrect';
                            }
                        ?>
                            <li class="<?= trim($li_class) ?>">
                                <span class="option-label"><?= $option ?></span>
                                <?= htmlspecialchars($option_text) ?>
                                <?php if ($option == $student_choice && $student_choice != $correct_answer): ?>
                                    <span class="badge bg-danger ms-auto">Your Answer</span>
                                <?php elseif ($option == $correct_answer): ?>
                                     <span class="badge bg-success ms-auto">Correct Answer</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No questions found for this quiz.</p>
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="javascript:history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Results
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>
