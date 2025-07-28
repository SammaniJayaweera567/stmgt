<?php
header('Content-Type: application/json');
include '../../init.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$db = dbConn();

$student_user_id = isset($_POST['student_user_id']) ? (int)$_POST['student_user_id'] : 0;
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$attendance_date = isset($_POST['attendance_date']) ? dataClean($_POST['attendance_date']) : date('Y-m-d');
$marked_by_user_id = (int)$_SESSION['user_id'];

if ($student_user_id <= 0 || $class_id <= 0) {
    $response['message'] = 'Invalid data received from scanner.';
    echo json_encode($response);
    exit();
}

// 1. Get Student's Name for messages
$sql_student_name = "SELECT FirstName FROM users WHERE Id = '$student_user_id'";
$name_result = $db->query($sql_student_name);
$student_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['FirstName'] : 'Student';

// 2. Check if student is enrolled
$sql_check_enrollment = "SELECT id FROM enrollments WHERE student_user_id = '$student_user_id' AND class_id = '$class_id' AND status = 'active'";
if ($db->query($sql_check_enrollment)->num_rows == 0) {
    $response['message'] = "Error: ".htmlspecialchars($student_name)." is not enrolled in this class.";
    echo json_encode($response);
    exit();
}

// 3. Insert or Update attendance using ON DUPLICATE KEY UPDATE
$marked_at_datetime = date('Y-m-d H:i:s');
$sql_insert = "INSERT INTO attendance (class_id, student_user_id, attendance_date, status, marked_by_user_id, marked_at)
               VALUES ('$class_id', '$student_user_id', '$attendance_date', 'Present', '$marked_by_user_id', '$marked_at_datetime')
               ON DUPLICATE KEY UPDATE 
               status = 'Present', marked_by_user_id = '$marked_by_user_id', marked_at = '$marked_at_datetime'";

if ($db->query($sql_insert)) {
    $response['status'] = 'success';
    $response['message'] = 'Success! Attendance marked for ' . htmlspecialchars($student_name) . '.';
} else {
    $response['message'] = 'Database Error: Could not record attendance.';
}

echo json_encode($response);
exit();
?>