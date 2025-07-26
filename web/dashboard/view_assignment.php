<?php
ob_start();
include '../../init.php'; // Correct path from /web/dashboard/

// Session check: Ensure a student is logged in
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role_name'] ?? '') != 'student') {
    header("Location: " . WEB_URL . "auth/login.php");
    exit();
}

$db = dbConn();
$logged_in_student_id = (int)$_SESSION['user_id'];
$messages = [];

$assignment_id = (int)($_GET['assignment_id'] ?? 0);

if ($assignment_id === 0) {
    header("Location: student.php?status=error&message=Assignment ID not provided.");
    exit();
}

// --- Fetch Assignment Details for the student ---
$sql_assignment_details = "
    SELECT 
        a.id as assessment_id,
        a.title,
        a.description,
        a.max_marks,
        a.due_date,
        a.pass_mark_percentage,
        a.status as assignment_status, -- Status of the assignment itself (Published, Graded)
        s.subject_name,
        cl.level_name,
        CONCAT(t.FirstName, ' ', t.LastName) as teacher_name,
        ss.id as submission_id,
        ss.submission_status,         -- Student's submission status (Submitted, Pending, Late Submission)
        ss.submitted_at,              -- When student submitted
        ss.file_name,                 -- Submitted file name
        ss.file_path,                 -- Submitted file path
        ar.marks_obtained,            -- Student's marks for this assignment
        ar.remarks,                   -- Teacher's remarks for this assignment
        gr.grade_name                 -- Student's grade for this assignment
    FROM assessments a
    JOIN classes c ON a.class_id = c.id
    JOIN subjects s ON c.subject_id = s.id
    JOIN class_levels cl ON c.class_level_id = cl.id
    LEFT JOIN users t ON a.teacher_id = t.Id
    LEFT JOIN student_submissions ss ON a.id = ss.assessment_id AND ss.student_user_id = '$logged_in_student_id'
    LEFT JOIN assessment_results ar ON a.id = ar.assessment_id AND ar.student_user_id = '$logged_in_student_id'
    LEFT JOIN grades gr ON ar.grade_id = gr.id
    WHERE a.id = '$assignment_id' AND a.assessment_type = 'Assignment'
    AND c.id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$logged_in_student_id' AND status = 'active')
";
$result_assignment = $db->query($sql_assignment_details);

if ($result_assignment->num_rows === 0) {
    header("Location: student.php?status=error&message=Assignment not found or not accessible.");
    exit();
}
$assignment_data = $result_assignment->fetch_assoc();

// Check if assignment is overdue
$current_datetime = date('Y-m-d H:i:s');
$is_overdue = strtotime($current_datetime) > strtotime($assignment_data['due_date']) && empty($assignment_data['submission_status']);

// --- Handle File Upload POST request ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_submission') {
    if ($is_overdue) {
        $messages['main'] = "Assignment is overdue. Submissions are no longer accepted.";
    } elseif (isset($assignment_data['submission_status']) && ($assignment_data['submission_status'] == 'Submitted' || $assignment_data['submission_status'] == 'Late Submission')) {
        $messages['main'] = "You have already submitted this assignment. You cannot resubmit.";
    } elseif (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../../web/uploads/submissions/"; // Correct path from /web/dashboard/
        // Create directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name_raw = basename($_FILES['assignment_file']['name']);
        $file_ext = strtolower(pathinfo($file_name_raw, PATHINFO_EXTENSION));
        // Generate a unique file name using student_id, assignment_id, and timestamp
        $unique_file_name = $logged_in_student_id . '_' . $assignment_id . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $unique_file_name;

        // Basic file validation
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_types)) {
            $messages['main'] = "Invalid file type. Only PDF, DOC, DOCX, JPG, JPEG, PNG are allowed.";
        }
        if ($_FILES['assignment_file']['size'] > 10 * 1024 * 1024) { // Max 10MB
            $messages['main'] = "File size exceeds 10MB limit.";
        }

        if (empty($messages['main'])) {
            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)) {
                // Update or Insert into student_submissions
                if ($assignment_data['submission_id'] > 0) { // If a previous entry exists, update it
                    // Get old file path to delete it
                    $old_file_sql = "SELECT file_path, file_name FROM student_submissions WHERE id='{$assignment_data['submission_id']}'";
                    $old_file_result = $db->query($old_file_sql);
                    if ($old_file_result->num_rows > 0) {
                        $old_file_data = $old_file_result->fetch_assoc();
                        $old_full_path = $target_dir . $old_file_data['file_name'];
                        if (file_exists($old_full_path) && !is_dir($old_full_path)) { // Ensure it's a file
                            unlink($old_full_path);
                        }
                    }
                    $update_sql = "UPDATE student_submissions SET file_name='$unique_file_name', file_path='$unique_file_name', submitted_at=NOW(), submission_status='Submitted' WHERE id='{$assignment_data['submission_id']}'";
                    $db->query($update_sql);
                } else {
                    // Create new submission entry
                    $insert_sql = "INSERT INTO student_submissions (assessment_id, student_user_id, file_name, file_path, submitted_at, submission_status)
                                   VALUES ('$assignment_id', '$logged_in_student_id', '$unique_file_name', '$unique_file_name', NOW(), 'Submitted')";
                    $db->query($insert_sql);
                }
                $_SESSION['status_message'] = "Assignment submitted successfully!";
                header("Location: view_assignment.php?assignment_id=$assignment_id&status=submitted");
                exit();
            } else {
                $messages['main'] = "Failed to upload file to server.";
            }
        }
    } else if (isset($_POST['action']) && $_POST['action'] == 'upload_submission' && $_FILES['assignment_file']['error'] != UPLOAD_ERR_NO_FILE) {
         $messages['main'] = "File upload error: " . $_FILES['assignment_file']['error'];
    }
}

// Re-fetch assignment data after potential submission (for displaying updated status)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_submission' && empty($messages['main'])) {
    $result_assignment = $db->query($sql_assignment_details);
    $assignment_data = $result_assignment->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assignment - <?= htmlspecialchars($assignment_data['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .assignment-card { background-color: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07); padding: 30px; margin-top: 50px; margin-bottom: 30px;}
        .assignment-header { border-bottom: 2px solid #e0703c; padding-bottom: 15px; margin-bottom: 25px; text-align: center;}
        .assignment-header h2 { color: #e0703c; font-weight: 700; margin-bottom: 5px;}
        .assignment-details p { margin-bottom: 10px; color: #6c757d;}
        .assignment-details strong { color: #343a40;}
        .assignment-description { background-color: #e9ecef; border-left: 5px solid #1cc1ba; padding: 15px; border-radius: 8px; margin-bottom: 25px;}
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; color: #fff; }
        .bg-submitted { background-color: #28a745; } /* Green */
        .bg-pending { background-color: #007bff; } /* Blue */
        .bg-overdue { background-color: #dc3545; } /* Red */
        .bg-graded { background-color: #17a2b8; } /* Info */
        .bg-late-submitted { background-color: #ffc107; color: #333; } /* Warning */
        .upload-section { border: 1px dashed #ced4da; padding: 25px; border-radius: 10px; text-align: center; margin-top: 30px; background-color: #f0f8ff;}
        .upload-section input[type="file"] { margin: 15px auto; display: block; width: fit-content; }
        .btn-upload { background-color: #1cc1ba; color: white; }
        .submission-info-box { background-color: #d1e7dd; border: 1px solid #badbcc; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .alert-danger { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="assignment-card">
            <div class="assignment-header">
                <h2><?= htmlspecialchars($assignment_data['title']) ?></h2>
                <p class="text-muted">Subject: <?= htmlspecialchars($assignment_data['subject_name']) ?> (<?= htmlspecialchars($assignment_data['level_name']) ?>)</p>
            </div>

            <?php if(!empty($messages['main'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($messages['main']) ?></div>
            <?php endif; ?>
            <?php if(isset($_SESSION['status_message'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['status_message']) ?></div>
                <?php unset($_SESSION['status_message']); ?>
            <?php endif; ?>

            <div class="row assignment-details">
                <div class="col-md-6">
                    <p><strong>Due Date:</strong> <?= htmlspecialchars(date('Y-m-d H:i A', strtotime($assignment_data['due_date']))) ?></p>
                    <p><strong>Max Marks:</strong> <?= htmlspecialchars(number_format($assignment_data['max_marks'], 2)) ?></p>
                    <p><strong>Teacher:</strong> <?= htmlspecialchars($assignment_data['teacher_name']) ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>Your Status:</strong> 
                        <?php 
                        $status_class = 'bg-secondary';
                        $status_text = 'Not Submitted';
                        if (!empty($assignment_data['submission_status'])) {
                            $status_text = htmlspecialchars(ucfirst(str_replace('_', ' ', $assignment_data['submission_status'])));
                            switch ($assignment_data['submission_status']) {
                                case 'Submitted': $status_class = 'bg-success'; break;
                                case 'Late Submission': $status_class = 'bg-warning text-dark'; break;
                                case 'Pending': $status_class = 'bg-primary'; break;
                                default: $status_class = 'bg-secondary'; break;
                            }
                        } elseif ($is_overdue) {
                            $status_class = 'bg-overdue';
                            $status_text = 'Overdue';
                        }
                        echo '<span class="status-badge ' . $status_class . '">' . $status_text . '</span>';
                        ?>
                    </p>
                    <?php if ($assignment_data['marks_obtained'] !== null): ?>
                        <p><strong>Your Marks:</strong> <?= htmlspecialchars(number_format($assignment_data['marks_obtained'], 2)) ?> / <?= htmlspecialchars(number_format($assignment_data['max_marks'], 2)) ?></p>
                        <p><strong>Your Grade:</strong> <span class="status-badge bg-graded"><?= htmlspecialchars($assignment_data['grade_name'] ?? 'N/A') ?></span></p>
                        <p><strong>Teacher Remarks:</strong> <?= htmlspecialchars($assignment_data['remarks'] ?? 'No remarks.') ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <h4 class="mt-4">Assignment Description</h4>
            <div class="assignment-description">
                <p><?= nl2br(htmlspecialchars($assignment_data['description'])) ?></p>
            </div>

            <?php if (!empty($assignment_data['file_name'])): ?>
            <h4 class="mt-4">Your Submitted File</h4>
            <div class="submission-info-box text-center">
                <p>File: <strong><?= htmlspecialchars($assignment_data['file_name']) ?></strong></p>
                <p>Submitted On: <strong><?= htmlspecialchars(date('Y-m-d H:i A', strtotime($assignment_data['submitted_at']))) ?></strong></p>
                <a href="<?= SYS_URL ?>web/uploads/submissions/<?= htmlspecialchars($assignment_data['file_name']) ?>" target="_blank" class="btn btn-info mt-2"><i class="fas fa-download"></i> Download Submitted File</a>
            </div>
            <?php endif; ?>

            <?php 
            // Display upload section based on submission status and due date
            if ($assignment_data['submission_status'] == null || $assignment_data['submission_status'] == 'Pending'): // Not submitted yet
            ?>
                <?php if (!$is_overdue): ?>
                <div class="upload-section">
                    <h4>Upload Your Assignment File</h4>
                    <p class="text-muted">Please upload your assignment file before the due date.</p>
                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?assignment_id=' . $assignment_id ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_submission">
                        <input type="file" name="assignment_file" required>
                        <button type="submit" class="btn btn-upload"><i class="fas fa-upload"></i> Upload Assignment</button>
                    </form>
                </div>
                <?php else: // Not submitted and overdue ?>
                <div class="alert alert-danger text-center mt-4">
                    This assignment is overdue. Submissions are no longer accepted.
                </div>
                <?php endif; ?>
            <?php elseif ($assignment_data['submission_status'] == 'Submitted' || $assignment_data['submission_status'] == 'Late Submission'): // Already submitted ?>
            <div class="alert alert-success text-center mt-4">
                You have successfully submitted this assignment.
            </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="student.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$content = ob_get_clean();
// Assuming layouts.php wraps the entire content for the web folder
// If this file is directly included in layouts.php, then remove this line.
include '../layouts.php'; 
?>