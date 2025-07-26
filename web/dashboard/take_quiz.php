<?php
ob_start();
include '../../init.php'; // Path from web/dashboard/

// 1. Security Check: Ensure a student is logged in
if (!isset($_SESSION['ID']) || strtolower($_SESSION['user_role_name'] ?? '') != 'student') {
    header("Location: " . WEB_URL . "auth/login.php");
    exit();
}

$db = dbConn();
$logged_in_student_id = (int)$_SESSION['ID'];
$messages = [];

$quiz_id = (int)($_GET['quiz_id'] ?? 0);

if ($quiz_id === 0) {
    header("Location: student.php?status=error&message=Quiz ID not provided.");
    exit();
}

// --- 2. Fetch Quiz Details and Validate Access ---
$sql_quiz_details = "SELECT 
                        a.id, a.title, a.time_limit_minutes, a.pass_mark_percentage, s.subject_name
                    FROM assessments a
                    JOIN classes c ON a.class_id = c.id
                    JOIN subjects s ON a.subject_id = s.id
                    WHERE a.id = '$quiz_id' 
                    AND a.assessment_type = 'Quiz' 
                    AND a.status = 'Published'
                    AND c.id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$logged_in_student_id' AND status = 'active')";
$result_quiz_details = $db->query($sql_quiz_details);

if ($result_quiz_details->num_rows === 0) {
    header("Location: student.php?status=error&message=Quiz not found or you do not have access to it.");
    exit();
}
$quiz_data = $result_quiz_details->fetch_assoc();

// --- 3. Check if student has already attempted this quiz ---
$sql_check_attempt = "SELECT id FROM assessment_results WHERE assessment_id = '$quiz_id' AND student_user_id = '$logged_in_student_id'";
$result_check_attempt = $db->query($sql_check_attempt);
if ($result_check_attempt->num_rows > 0) {
    header("Location: view_quiz_result.php?quiz_id=$quiz_id&status=attempted");
    exit();
}

// --- 4. Handle Quiz Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $submitted_answers = $_POST['answers'] ?? [];
    $total_score = 0;

    // Fetch correct answers and marks for all questions in this quiz
    $sql_correct_answers = "SELECT id, correct_answer, marks FROM assessment_questions WHERE assessment_id = '$quiz_id'";
    $result_correct_answers = $db->query($sql_correct_answers);
    
    $correct_answers_map = [];
    while($row = $result_correct_answers->fetch_assoc()){
        $correct_answers_map[$row['id']] = ['answer' => $row['correct_answer'], 'marks' => $row['marks']];
    }

    // Calculate score
    foreach ($submitted_answers as $question_id => $submitted_answer) {
        $question_id = (int)$question_id;
        if (isset($correct_answers_map[$question_id])) {
            if ($correct_answers_map[$question_id]['answer'] == $submitted_answer) {
                $total_score += $correct_answers_map[$question_id]['marks'];
            }
        }
    }

    // --- Get Total Marks and Calculate Grade ---
    $sql_total_marks = "SELECT max_marks FROM assessments WHERE id = '$quiz_id'";
    $total_marks_result = $db->query($sql_total_marks);
    $total_marks = $total_marks_result->fetch_assoc()['max_marks'];
    $percentage = ($total_marks > 0) ? ($total_score / $total_marks) * 100 : 0;

    $grade_id = 'NULL';
    $sql_grades = "SELECT id FROM grades WHERE min_percentage <= '$percentage' AND max_percentage >= '$percentage' AND status='Active' LIMIT 1";
    $grade_result = $db->query($sql_grades);
    if($grade_result && $grade_result->num_rows > 0){
        $grade_id = "'" . $grade_result->fetch_assoc()['id'] . "'";
    }

    // --- Save results to database ---
    $db->begin_transaction();
    try {
        // Log the submission attempt
        // Encode the submitted answers array into a JSON string
$answers_json = json_encode($submitted_answers);

// Log the submission attempt WITH the answers
$sql_insert_submission = "INSERT INTO student_submissions (assessment_id, student_user_id, submitted_at, submission_status, submission_content) 
                          VALUES ('$quiz_id', '$logged_in_student_id', NOW(), 'Submitted', '$answers_json')";
$db->query($sql_insert_submission);

        // Save the final result
        $sql_insert_result = "INSERT INTO assessment_results (assessment_id, student_user_id, marks_obtained, grade_id, evaluated_at) 
                              VALUES ('$quiz_id', '$logged_in_student_id', '$total_score', $grade_id, NOW())";
        $db->query($sql_insert_result);

        $db->commit();
        header("Location: view_quiz_result.php?quiz_id=$quiz_id");
        exit();

    } catch (Exception $e) {
        $db->rollback();
        die("An error occurred while saving your results. Please try again.");
    }
}

// --- 5. Fetch Questions to display for the quiz (GET Request) ---
// NOTE: ORDER BY RAND() shuffles the questions so they appear in a different order for each student attempt. This is a feature.
$sql_questions = "SELECT id, question_text, option_a, option_b, option_c, option_d FROM assessment_questions WHERE assessment_id = '$quiz_id' ORDER BY RAND()";
$result_questions = $db->query($sql_questions);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz: <?= htmlspecialchars($quiz_data['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    body {
        background-color: #f0f2f5;
        font-family: sans-serif;
    }

    .quiz-container {
        max-width: 800px;
        margin: 40px auto;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .quiz-header {
        padding: 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        background-color: #fff;
        z-index: 10;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }

    .quiz-header h2 {
        margin: 0;
        font-size: 1.5rem;
        color: #343a40;
    }

    #timer {
        font-size: 1.2rem;
        font-weight: bold;
        color: #fff;
        background-color: #dc3545;
        padding: 8px 15px;
        border-radius: 50px;
    }

    .quiz-body {
        padding: 30px;
    }

    .question-card {
        margin-bottom: 25px;
        padding: 20px;
        border: 1px solid #dee2e6;
        border-radius: 10px;
    }

    .question-text {
        font-size: 1.1rem;
        font-weight: 500;
        margin-bottom: 15px;
    }

    .options-list .form-check {
        margin-bottom: 10px;
    }

    .options-list .form-check-label {
        font-size: 1rem;
    }

    .quiz-footer {
        padding: 20px;
        text-align: center;
        border-top: 1px solid #e9ecef;
    }

    .btn-submit-quiz {
        font-size: 1.2rem;
        padding: 12px 30px;
    }
    </style>
</head>

<body>
    <div class="quiz-container">
        <div class="quiz-header">
            <h2><?= htmlspecialchars($quiz_data['title']) ?></h2>
            <div id="timer"><i class="fas fa-clock me-2"></i>--:--</div>
        </div>
        <form id="quizForm" method="POST"
            action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?quiz_id=<?= $quiz_id ?>">
            <div class="quiz-body">
                <?php if ($result_questions && $result_questions->num_rows > 0): ?>
                <?php $q_num = 1; while($question = $result_questions->fetch_assoc()): ?>
                <div class="question-card">
                    <p class="question-text"><?= $q_num++ ?>. <?= htmlspecialchars($question['question_text']) ?></p>
                    <div class="options-list">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]"
                                id="q<?= $question['id'] ?>_a" value="A" required>
                            <label class="form-check-label"
                                for="q<?= $question['id'] ?>_a"><?= htmlspecialchars($question['option_a']) ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]"
                                id="q<?= $question['id'] ?>_b" value="B">
                            <label class="form-check-label"
                                for="q<?= $question['id'] ?>_b"><?= htmlspecialchars($question['option_b']) ?></label>
                        </div>
                        <?php if(!empty($question['option_c'])): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]"
                                id="q<?= $question['id'] ?>_c" value="C">
                            <label class="form-check-label"
                                for="q<?= $question['id'] ?>_c"><?= htmlspecialchars($question['option_c']) ?></label>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($question['option_d'])): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]"
                                id="q<?= $question['id'] ?>_d" value="D">
                            <label class="form-check-label"
                                for="q<?= $question['id'] ?>_d"><?= htmlspecialchars($question['option_d']) ?></label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p class="text-center">No questions found for this quiz.</p>
                <?php endif; ?>
            </div>
            <div class="quiz-footer">
                <button type="submit" class="btn btn-primary btn-submit-quiz">Submit Quiz</button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const timeLimitInMinutes = <?= (int)$quiz_data['time_limit_minutes'] ?>;
        let timeInSeconds = timeLimitInMinutes * 60;
        const timerElement = document.getElementById('timer');
        const quizForm = document.getElementById('quizForm');

        const timerInterval = setInterval(() => {
            const minutes = Math.floor(timeInSeconds / 60);
            let seconds = timeInSeconds % 60;

            seconds = seconds < 10 ? '0' + seconds : seconds;

            timerElement.innerHTML = `<i class="fas fa-clock me-2"></i>${minutes}:${seconds}`;

            if (timeInSeconds <= 0) {
                clearInterval(timerInterval);
                timerElement.textContent = 'Time Up!';
                alert('Time is up! Your quiz will be submitted automatically.');
                quizForm.submit();
            }

            if (timeInSeconds <= 60) {
                timerElement.style.backgroundColor = '#ffc107'; // Warning color
                timerElement.style.color = '#333';
            }

            timeInSeconds--;
        }, 1000);

        // --- CORRECTED SUBMISSION LOGIC ---
        quizForm.addEventListener('submit', function(e) {
            // Get all the question cards to find the groups
            const questionCards = document.querySelectorAll('.question-card');
            let allAnswered = true;

            questionCards.forEach(card => {
                // Find the radio buttons within this card
                const radioButtons = card.querySelectorAll('input[type="radio"]');
                if (radioButtons.length > 0) {
                    // Check if at least one radio button in this group is checked
                    const isAnswered = Array.from(radioButtons).some(radio => radio.checked);
                    if (!isAnswered) {
                        allAnswered = false;
                    }
                }
            });

            // If not all questions are answered, show a confirmation dialog
            if (!allAnswered) {
                if (!confirm(
                        'You have one or more unanswered questions. Are you sure you want to submit?'
                        )) {
                    e.preventDefault(); // Stop submission only if user clicks "Cancel"
                }
            }
        });
    });
    </script>
</body>

</html>
<?php
$content = ob_get_clean();
// This page should be standalone and not use the main layout
echo $content;
?>