<?php
header('Content-Type: application/json');
include '../../init.php'; // Adjust this path as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $db = dbConn();

    $sql = "SELECT s.*, c.class_full_name AS ClassName 
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.Id 
            WHERE s.Id = $id";

    $result = $db->query($sql);

    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $student]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
