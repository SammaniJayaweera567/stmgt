<?php
ob_start();
include '../../init.php'; // Path from web/dashboard/

// 1. Security Check: Ensure a student is logged in
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role_name'] ?? '') != 'student') {
    header("Location: " . WEB_URL . "auth/login.php");
    exit();
}

$db = dbConn();
$logged_in_student_id = (int)$_SESSION['user_id'];

$quiz_id = (int)($_GET['quiz_id'] ?? 0);

if ($quiz_id === 0) {
    header("Location: student.php?status=error&message=Quiz ID not provided.");
    exit();
}

// --- 2. Fetch the student's result for this quiz ---
$sql_result = "SELECT 
                    a.title as quiz_title,
                    a.max_marks,
                    a.pass_mark_percentage,
                    s.subject_name,
                    ar.marks_obtained,
                    gr.grade_name
                FROM assessment_results ar
                JOIN assessments a ON ar.assessment_id = a.id
                JOIN subjects s ON a.subject_id = s.id
                LEFT JOIN grades gr ON ar.grade_id = gr.id
                WHERE ar.assessment_id = '$quiz_id' 
                AND ar.student_user_id = '$logged_in_student_id'
                AND a.assessment_type_id = 3";

$result_data = $db->query($sql_result);

if ($result_data->num_rows === 0) {
    // This can happen if the student tries to access the result page without attempting the quiz
    header("Location: student.php?status=error&message=Result not found for this quiz.");
    exit();
}
$result = $result_data->fetch_assoc();

// --- 3. Calculate Percentage and Status ---
$percentage = ($result['max_marks'] > 0) ? ($result['marks_obtained'] / $result['max_marks']) * 100 : 0;
$status = ($percentage >= $result['pass_mark_percentage']) ? 'Pass' : 'Fail';
$status_color_class = ($status == 'Pass') ? 'text-success' : 'text-danger';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result: <?= htmlspecialchars($result['quiz_title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .result-container { max-width: 600px; width: 100%; background: #fff; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; padding: 40px 30px; }
        .result-icon { font-size: 5rem; }
        .result-icon.pass { color: #28a745; }
        .result-icon.fail { color: #dc3545; }
        .result-container h1 { font-size: 2rem; font-weight: bold; margin-top: 20px; }
        .result-container h2 { font-size: 1.5rem; color: #6c757d; margin-bottom: 30px; }
        .score-display { font-size: 3rem; font-weight: bold; color: #343a40; }
        .score-display small { font-size: 1.5rem; color: #6c757d; }
        .result-details { list-style: none; padding: 0; margin: 30px 0; }
        .result-details li { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef; font-size: 1.1rem; }
        .result-details li:last-child { border-bottom: none; }
        .result-details li strong { color: #343a40; }
        .btn-back { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="result-container">
        <?php if ($status == 'Pass'): ?>
            <i class="fas fa-check-circle result-icon pass"></i>
            <h1 class="text-success">Congratulations! You Passed!</h1>
        <?php else: ?>
            <i class="fas fa-times-circle result-icon fail"></i>
            <h1 class="text-danger">Better Luck Next Time!</h1>
        <?php endif; ?>

        <h2><?= htmlspecialchars($result['quiz_title']) ?></h2>

        <p class="text-muted">You Scored</p>
        <div class="score-display">
            <?= htmlspecialchars(number_format($result['marks_obtained'], 2)) ?>
            <small>/ <?= htmlspecialchars(number_format($result['max_marks'], 2)) ?></small>
        </div>

        <ul class="result-details">
            <li>
                <span>Percentage</span>
                <strong><?= htmlspecialchars(number_format($percentage, 2)) ?>%</strong>
            </li>
            <li>
                <span>Grade</span>
                <strong><?= htmlspecialchars($result['grade_name'] ?? 'N/A') ?></strong>
            </li>
            <li>
                <span>Status</span>
                <strong class="<?= $status_color_class ?>"><?= $status ?></strong>
            </li>
        </ul>

        <a href="student.php" class="btn btn-primary btn-lg btn-back">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
<?php
$content = ob_get_clean();
// This page is also standalone
echo $content;
?>
