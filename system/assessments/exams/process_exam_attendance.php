<?php
// Set the content type to JSON as this file only returns data
header('Content-Type: application/json');
include '../../../init.php'; // Corrected path for nested folders

// Prepare a default error response
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// --- Security Check 1: Ensure a user is logged in ---
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication failed. Please log in again.';
    echo json_encode($response);
    exit();
}

// --- Security Check 2: Ensure the request is a POST request ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$db = dbConn();

// --- Data Validation ---
$student_user_id = isset($_POST['student_user_id']) ? (int)$_POST['student_user_id'] : 0;
$assessment_id = isset($_POST['assessment_id']) ? (int)$_POST['assessment_id'] : 0;
$marked_by_user_id = (int)$_SESSION['user_id'];

if ($student_user_id <= 0 || $assessment_id <= 0) {
    $response['message'] = 'Invalid data received from the scanner.';
    echo json_encode($response);
    exit();
}

// --- Core Logic ---

// 1. Check if the assessment is a valid 'Exam' and get its status
$sql_check_exam = "SELECT id, class_id, status FROM assessments WHERE id = '$assessment_id' AND assessment_type_id = 1"; // <-- වෙනස් කළ කොටස
$result_exam = $db->query($sql_check_exam);

if (!$result_exam || $result_exam->num_rows == 0) {
    $response['message'] = 'Error: This QR code does not belong to a valid Exam.';
    echo json_encode($response);
    exit();
}
$exam_details = $result_exam->fetch_assoc();
$class_id_for_exam = $exam_details['class_id'];

// --- NEW VALIDATION: Check if the exam status is 'Published' ---
if ($exam_details['status'] !== 'Published') {
    $response['message'] = "Action Not Allowed: Attendance can only be marked for 'Published' exams. This exam is currently '{$exam_details['status']}'.";
    echo json_encode($response);
    exit();
}

// 2. Check if the student is enrolled in the class for this exam
$sql_check_enrollment = "SELECT id FROM enrollments WHERE student_user_id = '$student_user_id' AND class_id = '$class_id_for_exam' AND status = 'active'";
$result_enrollment = $db->query($sql_check_enrollment);

if (!$result_enrollment || $result_enrollment->num_rows == 0) {
    $sql_student_name = "SELECT FirstName FROM users WHERE Id = '$student_user_id'";
    $student_name = $db->query($sql_student_name)->fetch_assoc()['FirstName'] ?? 'Student';
    $response['message'] = 'Error: ' . htmlspecialchars($student_name) . ' is not enrolled in this class.';
    echo json_encode($response);
    exit();
}

// 3. Check if attendance is already marked for this exam
$sql_check_existing = "SELECT id FROM exam_attendance WHERE student_user_id = '$student_user_id' AND assessment_id = '$assessment_id'";
if ($db->query($sql_check_existing)->num_rows > 0) {
    $sql_student_name = "SELECT FirstName FROM users WHERE Id = '$student_user_id'";
    $student_name = $db->query($sql_student_name)->fetch_assoc()['FirstName'] ?? 'Student';
    $response['status'] = 'warning';
    $response['message'] = 'Already Marked: ' . htmlspecialchars($student_name) . "'s attendance is already recorded.";
    echo json_encode($response);
    exit();
}

// --- All checks passed, record the attendance ---
$marked_at = date('Y-m-d H:i:s');
$sql_insert = "INSERT INTO exam_attendance (assessment_id, student_user_id, attendance_status, marked_by_user_id, marked_at)
               VALUES ('$assessment_id', '$student_user_id', 'Present', '$marked_by_user_id', '$marked_at')";

if ($db->query($sql_insert)) {
    $sql_student_name = "SELECT FirstName, LastName FROM users WHERE Id = '$student_user_id'";
    $name_row = $db->query($sql_student_name)->fetch_assoc();
    $student_name = $name_row['FirstName'] . ' ' . $name_row['LastName'];
    
    $response['status'] = 'success';
    $response['message'] = 'Success! Attendance marked for ' . htmlspecialchars($student_name) . '.';
} else {
    $response['message'] = 'Database Error: Could not record attendance.';
}

echo json_encode($response);
exit();
?>