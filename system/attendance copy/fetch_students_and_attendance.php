<?php
ob_start();
include '../../init.php'; // Adjust path as necessary

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

$db = dbConn();
header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => []];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['class_id']) && isset($_GET['date'])) {
    $selectedClassId = dataClean($_GET['class_id']);
    $selectedDate = dataClean($_GET['date']);

    if (empty($selectedClassId) || !filter_var($selectedClassId, FILTER_VALIDATE_INT) || empty($selectedDate) || !strtotime($selectedDate)) {
        $response['message'] = "Invalid Class or Date provided.";
        echo json_encode($response);
        exit();
    }

    $students = [];
    $attendance_status_records = [];
    $presentStudentsCount = 0;

    // Fetch students for the selected class
    $studentsSql = "SELECT Id, registration_no, first_name, last_name 
                    FROM students 
                    WHERE class_id = '$selectedClassId' 
                    ORDER BY first_name ASC, last_name ASC";
    $studentsResult = $db->query($studentsSql);
    if ($studentsResult && $studentsResult->num_rows > 0) {
        while ($row = $studentsResult->fetch_assoc()) {
            $students[] = $row;
        }
    }

    // Fetch existing attendance for these students on the selected date
    if (!empty($students)) {
        $student_ids = implode(',', array_column($students, 'Id'));
        $attendanceSql = "SELECT student_id, status FROM attendance 
                          WHERE student_id IN ($student_ids) AND class_id = '$selectedClassId' AND attendance_date = '$selectedDate'";
        $attendanceResult = $db->query($attendanceSql);
        if ($attendanceResult && $attendanceResult->num_rows > 0) {
            while ($row = $attendanceResult->fetch_assoc()) {
                $attendance_status_records[$row['student_id']] = $row['status'];
                if ($row['status'] == 'Present') {
                    $presentStudentsCount++;
                }
            }
        }
    }

    $response['success'] = true;
    $response['data'] = [
        'students' => $students,
        'attendance_status_records' => $attendance_status_records,
        'presentStudentsCount' => $presentStudentsCount,
        'totalStudentsCount' => count($students) // Added for convenience in JS
    ];

} else {
    $response['message'] = "Missing class_id or date parameter.";
}

echo json_encode($response);
exit();
?>