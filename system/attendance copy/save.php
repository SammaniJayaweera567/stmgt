<?php
ob_start();
include '../../init.php'; // Adjust path as necessary

// Ensure user is logged in
if (!isset($_SESSION['ID'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

$db = dbConn();
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_attendance') {
    $classId = dataClean($_POST['class_id_hidden'] ?? '');
    $attendanceDate = dataClean($_POST['attendance_date_hidden'] ?? '');
    $attendanceData = $_POST['attendance'] ?? []; // Array: student_id => status

    // Basic validation before processing
    if (empty($classId) || empty($attendanceDate) || !is_array($attendanceData) || empty($attendanceData)) {
        $response['message'] = "Invalid submission. Please select a class, date and mark attendance.";
        echo json_encode($response);
        exit();
    }

    $db->begin_transaction(); // Start transaction for atomicity

    try {
        // Delete existing attendance for this class and date to prevent duplicates/handle updates
        $deleteSql = "DELETE FROM attendance WHERE class_id = ? AND attendance_date = ?";
        $stmt_delete = $db->prepare($deleteSql);
        if (!$stmt_delete) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt_delete->bind_param("is", $classId, $attendanceDate);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Prepare for inserting new/updated attendance records
        $stmt_insert = $db->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status) VALUES (?, ?, ?, ?)");
        if (!$stmt_insert) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        // 'iiss' -> integer (student_id), integer (class_id), string (date), string (status)
        $stmt_insert->bind_param("iiss", $student_id, $class_id_param, $attendance_date_param, $status_param);

        foreach ($attendanceData as $studentId => $status) {
            $student_id = dataClean($studentId);
            $status_param = dataClean($status);
            $class_id_param = $classId; // Use the classId from the hidden input
            $attendance_date_param = $attendanceDate; // Use the attendanceDate from the hidden input

            // Ensure the status is either 'Present' or 'Absent'
            if (($status_param == 'Present' || $status_param == 'Absent')) {
                $stmt_insert->execute();
            }
        }
        $stmt_insert->close();
        $db->commit(); // Commit transaction

        $response['success'] = true;
        $response['message'] = "Attendance saved successfully!";

    } catch (Exception $e) {
        $db->rollback(); // Rollback on error
        $response['message'] = "Failed to save attendance: " . $e->getMessage();
        error_log("Error saving attendance data: " . $e->getMessage());
    }
} else {
    $response['message'] = "Invalid request.";
}

echo json_encode($response);
exit();
?>