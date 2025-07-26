<?php
// Set the content type to JSON as this file only returns data
header('Content-Type: application/json');
include '../../init.php';

// Prepare a default error response
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// --- Security Check 1: Ensure a user is logged in ---
if (!isset($_SESSION['ID'])) {
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
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$marked_by_user_id = (int)$_SESSION['ID']; // The logged-in card checker
$today_date = date('Y-m-d');

if ($student_user_id <= 0 || $class_id <= 0) {
    $response['message'] = 'Invalid data received from the scanner.';
    echo json_encode($response);
    exit();
}

// --- Core Logic ---

// 1. Check if the student is actually enrolled in this class and is active.
$sql_check_enrollment = "SELECT id FROM enrollments WHERE student_user_id = '$student_user_id' AND class_id = '$class_id' AND status = 'active'";
$result_enrollment = $db->query($sql_check_enrollment);

if (!$result_enrollment || $result_enrollment->num_rows == 0) {
    $response['message'] = 'Error: This student is not enrolled in the selected class.';
    echo json_encode($response);
    exit();
}

// 2. Check if attendance has ALREADY been marked for this student, for this class, on this day.
$sql_check_today = "SELECT id FROM attendance WHERE student_user_id = '$student_user_id' AND class_id = '$class_id' AND attendance_date = '$today_date'";
$result_today = $db->query($sql_check_today);

if ($result_today && $result_today->num_rows > 0) {
    $sql_student_name = "SELECT FirstName FROM users WHERE Id = '$student_user_id'";
    $name_result = $db->query($sql_student_name);
    $student_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['FirstName'] : 'Student';
    
    $response['status'] = 'error';
    $response['message'] = 'Already Marked: ' . htmlspecialchars($student_name) . "'s attendance was already recorded today.";
    echo json_encode($response);
    exit();
}

// --- All checks passed, record the attendance ---
$marked_at_datetime = date('Y-m-d H:i:s');
$sql_insert = "INSERT INTO attendance (class_id, student_user_id, attendance_date, status, marked_by_user_id, marked_at)
               VALUES ('$class_id', '$student_user_id', '$today_date', 'Present', '$marked_by_user_id', '$marked_at_datetime')";

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
    if ($db->errno == 1062) { // 1062 is the MySQL error number for a duplicate entry.
         $response['message'] = 'Already Marked: Attendance was just recorded by another scan.';
    } else {
         $response['message'] = 'Database Error: Could not record attendance. ' . $db->error;
    }
}

// Return the final response as a JSON string.
echo json_encode($response);
exit();
?>
