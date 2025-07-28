<?php
header('Content-Type: application/json');
include '../../init.php';

$db = dbConn();
$class_id = (int)($_GET['class_id'] ?? 0);
$students = [];

if ($class_id > 0) {
    // Fetch students enrolled in the selected class
    $sql = "SELECT u.Id, u.FirstName, u.LastName, sd.registration_no 
            FROM users u
            JOIN enrollments e ON u.Id = e.student_user_id
            JOIN student_details sd ON u.Id = sd.user_id
            WHERE e.class_id = '$class_id' AND e.status = 'active'
            ORDER BY u.FirstName ASC";
    $result = $db->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

echo json_encode($students);
?>
