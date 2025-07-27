<?php
// Set the content type to JSON as this file only returns data
header('Content-Type: application/json');
include '../../../init.php'; // Corrected path for nested folders
if (!hasPermission($_SESSION['user_id'], 'edit_exam')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// Prepare a default error response
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// --- Security Check 1: Ensure a user is logged in ---
// if (!isset($_SESSION['user_id'])) {
//     $response['message'] = 'Authentication failed. Please log in again.';
//     echo json_encode($response);
//     exit();
// }

// --- Security Check 2: Ensure the request is a POST request ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$db = dbConn();

// --- Data Validation ---
$student_user_id = isset($_POST['student_user_id']) ? (int)$_POST['student_user_id'] : 0;
$assessment_id = isset($_POST['assessment_id']) ? (int)$_POST['assessment_id'] : 0; // Changed from class_id to assessment_id
$marked_by_user_id = (int)$_SESSION['user_id']; // The logged-in user (Admin/Teacher/Card Checker)

if ($student_user_id <= 0 || $assessment_id <= 0) {
    $response['message'] = 'Invalid data received from the scanner (Student ID or Assessment ID missing).';
    echo json_encode($response);
    exit();
}

// --- Core Logic ---

// 1. Check if the assessment is actually an 'Exam'
$sql_check_exam_type = "SELECT id, class_id FROM assessments WHERE id = '$assessment_id' AND assessment_type = 'Exam'";
$result_exam_type = $db->query($sql_check_exam_type);

if (!$result_exam_type || $result_exam_type->num_rows == 0) {
    $response['message'] = 'Error: The provided ID does not belong to a valid Exam assessment.';
    echo json_encode($response);
    exit();
}
$exam_details_row = $result_exam_type->fetch_assoc();
$class_id_for_exam = $exam_details_row['class_id']; // Get the class_id associated with this exam

// 2. Check if the student is actually enrolled in the class associated with this exam and is active.
$sql_check_enrollment = "SELECT id FROM enrollments WHERE student_user_id = '$student_user_id' AND class_id = '$class_id_for_exam' AND status = 'active'";
$result_enrollment = $db->query($sql_check_enrollment);

if (!$result_enrollment || $result_enrollment->num_rows == 0) {
    // Get student name for more descriptive error
    $sql_student_name_error = "SELECT FirstName FROM users WHERE Id = '$student_user_id'";
    $name_result_error = $db->query($sql_student_name_error);
    $student_name_error = ($name_result_error->num_rows > 0) ? $name_result_error->fetch_assoc()['FirstName'] : 'Student';
    
    $response['message'] = 'Error: ' . htmlspecialchars($student_name_error) . ' is not enrolled in the class for this exam.';
    echo json_encode($response);
    exit();
}

// 3. Check if attendance has ALREADY been marked for this student, for this exam.
// Exam attendance is unique per assessment_id and student_user_id
$sql_check_existing_attendance = "SELECT id FROM exam_attendance WHERE student_user_id = '$student_user_id' AND assessment_id = '$assessment_id'";
$result_existing_attendance = $db->query($sql_check_existing_attendance);

if ($result_existing_attendance && $result_existing_attendance->num_rows > 0) {
    $sql_student_name = "SELECT FirstName FROM users WHERE Id = '$student_user_id'";
    $name_result = $db->query($sql_student_name);
    $student_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['FirstName'] : 'Student';
    
    $response['status'] = 'warning'; // Change status to 'warning' for already marked
    $response['message'] = 'Already Marked: ' . htmlspecialchars($student_name) . "'s attendance was already recorded for this exam.";
    echo json_encode($response);
    exit();
}

// --- All checks passed, record the attendance ---
$marked_at_datetime = date('Y-m-d H:i:s');
$sql_insert = "INSERT INTO exam_attendance (assessment_id, student_user_id, attendance_status, marked_by_user_id, marked_at)
               VALUES ('$assessment_id', '$student_user_id', 'Present', '$marked_by_user_id', '$marked_at_datetime')";

if ($db->query($sql_insert)) {
    $sql_student_name = "SELECT FirstName, LastName FROM users WHERE Id = '$student_user_id'";
    $name_result = $db->query($sql_student_name);
    $student_name = 'Student';
    if($name_result && $name_result->num_rows > 0){
        $name_row = $name_result->fetch_assoc();
        $student_name = $name_row['FirstName'] . ' ' . $name_row['LastName'];
    }

    $response['status'] = 'success';
    $response['message'] = 'Success! Attendance marked for ' . htmlspecialchars($student_name) . '.';
} else {
    // Check for duplicate entry error specifically (e.g., if somehow two scans happen simultaneously)
    if ($db->errno == 1062) { // 1062 is the MySQL error number for a duplicate entry.
        $response['message'] = 'Already Marked: Attendance was just recorded by another scan.';
    } else {
        $response['message'] = 'Database Error: Could not record attendance. ' . $db->error;
    }
}

// Return the final response as a JSON string.
echo json_encode($response);
exit();