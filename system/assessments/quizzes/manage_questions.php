<?php
ob_start();
include '../../../init.php'; // Path from /system/assessments/quizzes/

$db = dbConn();
$messages = [];

$quiz_id = (int)($_GET['quiz_id'] ?? 0);

if ($quiz_id === 0) {
    header("Location: manage_quizzes.php?status=notfound");
    exit();
}

// --- Fetch Quiz Details to display in header ---
$sql_quiz = "SELECT title, (SELECT SUM(marks) FROM assessment_questions WHERE assessment_id = a.id) as total_marks FROM assessments a WHERE a.id = '$quiz_id' AND a.assessment_type_id = 3"; // Changed to assessment_type_id = 3 (for Quiz)
$result_quiz = $db->query($sql_quiz);
if ($result_quiz->num_rows === 0) {
    header("Location: manage_quizzes.php?status=notfound&message=Quiz not found or is not a Quiz type."); // More specific message
    exit();
}
$quiz_data = $result_quiz->fetch_assoc(); // Fetch quiz data
$quiz_title = $quiz_data['title']; // Quiz title
$total_marks = $quiz_data['total_marks'] ?? 0; // Total marks

// --- Handle POST requests for ADDING, UPDATING, or DELETING questions ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // --- ADD a new question ---
    if ($action == 'add') {
        extract($_POST);
        $question_text = dataClean($question_text ?? '');
        $option_a = dataClean($option_a ?? '');
        $option_b = dataClean($option_b ?? '');
        $option_c = dataClean($option_c ?? '');
        $option_d = dataClean($option_d ?? '');
        $correct_answer = dataClean($correct_answer ?? '');
        $marks = dataClean($marks ?? '');

        if (empty($messages['error'])) { // Check if there are any validation errors
            // question_type is hardcoded to 'MCQ' as per table definition for now
            $sql_add = "INSERT INTO assessment_questions (assessment_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, marks)
                        VALUES ('$quiz_id', '$question_text', 'MCQ', '$option_a', '$option_b', " . (empty($option_c) ? "NULL" : "'$option_c'") . ", " . (empty($option_d) ? "NULL" : "'$option_d'") . ", '$correct_answer', '$marks')";
            if ($db->query($sql_add)) {
                // After adding a question, update the total marks in the assessments table
                $sql_update_marks = "UPDATE assessments SET max_marks = (SELECT SUM(marks) FROM assessment_questions WHERE assessment_id = '$quiz_id') WHERE id = '$quiz_id'";
                $db->query($sql_update_marks);
                header("Location: " . $_SERVER['PHP_SELF'] . "?quiz_id=$quiz_id&status=added");
                exit();
            } else {
                $messages['error'] = "Error adding question: " . $db->error;
            }
        }
    }

    // --- DELETE a question ---
    if ($action == 'delete') {
        $question_id = (int)($_POST['question_id'] ?? 0);
        if ($question_id > 0) {
            $sql_delete = "DELETE FROM assessment_questions WHERE id = '$question_id' AND assessment_id = '$quiz_id'";
            if ($db->query($sql_delete)) {
                 // After deleting a question, update the total marks in the assessments table
                $sql_update_marks = "UPDATE assessments SET max_marks = (SELECT SUM(marks) FROM assessment_questions WHERE assessment_id = '$quiz_id') WHERE id = '$quiz_id'";
                $db->query($sql_update_marks);
                header("Location: " . $_SERVER['PHP_SELF'] . "?quiz_id=$quiz_id&status=deleted");
                exit();
            } else {
                $messages['error'] = "Error deleting question: " . $db->error;
            }
        } else {
            $messages['error'] = "Invalid question ID for deletion.";
        }
    }
}

// --- Fetch all existing questions for this quiz ---
$sql_questions = "SELECT * FROM assessment_questions WHERE assessment_id = '$quiz_id' ORDER BY id ASC";
$result_questions = $db->query($sql_questions);
$question_count = $result_questions->num_rows; // Update count after any add/delete operations

?>

<style>
    .card-body-scrollable {
        max-height: 650px; 
        overflow-y: auto;
    }
    .form-label {
        font-weight: 500;
    }
    .question-display-card {
        background-color: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1.25rem;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .question-display-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .question-display-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f1f1;
    }
    .question-display-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #343a40;
    }
    .question-display-text {
        font-size: 1rem;
        color: #495057;
        margin-bottom: 1rem;
    }
    .question-options-list {
        list-style: none;
        padding-left: 0;
    }
    .question-options-list li {
        padding: 0.6rem 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        background-color: #f8f9fa;
    }
    .question-options-list li.correct {
        background-color: #d1e7dd;
        border-color: #badbcc;
        color: #0f5132;
        font-weight: 500;
    }
    .question-options-list li .option-label {
        font-weight: bold;
        margin-right: 0.75rem;
        color: #6c757d;
        width: 20px;
    }
    .question-options-list li.correct .option-label {
        color: #0f5132;
    }
    .question-display-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-top: 1rem;
    }
</style>

<div class="container-fluid">
    <?php show_status_message(); ?>
    <?php if (!empty($messages['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($messages['error']) ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="content-header-text d-flex align-items-center mb-2 mb-md-0">
                <i class="fas fa-list-ol me-2" style="font-size: 22px;"></i>
                <h4 class="w-auto mb-0">Manage Questions: <strong><?= htmlspecialchars($quiz_title) ?></strong></h4>
            </div>
            <div class="text-end">
                <span class="badge bg-primary fs-6 me-1"><i class="fas fa-question-circle me-1"></i> Total Questions: <?= $question_count ?></span>
                <span class="badge bg-success fs-6"><i class="fas fa-star me-1"></i> Total Marks: <?= number_format($total_marks, 2) ?></span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Column to Add New Question -->
        <div class="col-lg-5">
            <div class="card card-success h-100 shadow-sm">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle me-2"></i>Add New Question</h3></div>
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?quiz_id=<?= $quiz_id ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label class="form-label" for="question_text">Question Text <span class="text-danger">*</span></label>
                            <textarea id="question_text" name="question_text" class="form-control" rows="4" required placeholder="Enter the question here..."></textarea>
                        </div>
                        
                        <label class="form-label">Answer Options (Select the correct one) <span class="text-danger">*</span></label>
                        <div class="input-group mb-2">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="radio" value="A" name="correct_answer" required title="Select if this is the correct answer">
                            </div>
                            <span class="input-group-text fw-bold bg-light">A</span>
                            <input type="text" name="option_a" class="form-control" placeholder="Option A" required>
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="radio" value="B" name="correct_answer" title="Select if this is the correct answer">
                            </div>
                            <span class="input-group-text fw-bold bg-light">B</span>
                            <input type="text" name="option_b" class="form-control" placeholder="Option B" required>
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="radio" value="C" name="correct_answer" title="Select if this is the correct answer">
                            </div>
                            <span class="input-group-text fw-bold bg-light">C</span>
                            <input type="text" name="option_c" class="form-control" placeholder="Option C (Optional)">
                        </div>
                        <div class="input-group mb-3">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="radio" value="D" name="correct_answer" title="Select if this is the correct answer">
                            </div>
                            <span class="input-group-text fw-bold bg-light">D</span>
                            <input type="text" name="option_d" class="form-control" placeholder="Option D (Optional)">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="marks">Marks for this question <span class="text-danger">*</span></label>
                            <input id="marks" type="number" name="marks" class="form-control" required min="0.5" step="0.5" placeholder="e.g., 2.5">
                        </div>
                    </div>
                    <div class="card-footer text-end bg-light">
                        <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check-circle me-2"></i>Add Question</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Column to List Existing Questions -->
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-list me-2"></i>Existing Questions</h3></div>
                <div class="card-body card-body-scrollable">
                    <?php if ($result_questions && $result_questions->num_rows > 0): ?>
                        <?php $q_num = 1; mysqli_data_seek($result_questions, 0); while($question = $result_questions->fetch_assoc()): ?>
                            <div class="question-display-card">
                                <div class="question-display-header">
                                    <span class="question-display-title">Question <?= $q_num++ ?></span>
                                    <span class="badge bg-success rounded-pill"><?= htmlspecialchars($question['marks']) ?> Marks</span>
                                </div>
                                <p class="question-display-text"><?= nl2br(htmlspecialchars($question['question_text'])) ?></p>
                                <ul class="question-options-list">
                                    <li class="<?= ($question['correct_answer'] == 'A') ? 'correct' : '' ?>">
                                        <span class="option-label">A</span>
                                        <?= htmlspecialchars($question['option_a']) ?>
                                    </li>
                                    <li class="<?= ($question['correct_answer'] == 'B') ? 'correct' : '' ?>">
                                        <span class="option-label">B</span>
                                        <?= htmlspecialchars($question['option_b']) ?>
                                    </li>
                                    <?php if(!empty($question['option_c'])): ?>
                                        <li class="<?= ($question['correct_answer'] == 'C') ? 'correct' : '' ?>">
                                            <span class="option-label">C</span>
                                            <?= htmlspecialchars($question['option_c']) ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if(!empty($question['option_d'])): ?>
                                        <li class="<?= ($question['correct_answer'] == 'D') ? 'correct' : '' ?>">
                                            <span class="option-label">D</span>
                                            <?= htmlspecialchars($question['option_d']) ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                                <div class="question-display-footer">
                                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?quiz_id=<?= $quiz_id ?>" onsubmit="return confirm('Are you sure you want to delete this question?');" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i>Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center p-5 d-flex flex-column justify-content-center align-items-center h-100">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Questions Yet</h5>
                            <p class="text-muted">Use the form on the left to add the first question to this quiz.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <a href="manage_quizzes.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to All Quizzes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>
