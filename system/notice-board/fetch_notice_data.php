<?php
include '../../init.php'; // Adjust path as necessary

$db = dbConn(); // Get database connection

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    if ($id <= 0) {
        $response['message'] = "Invalid Notice ID.";
        echo json_encode($response);
        exit();
    }

    $sql = "SELECT id, title, description, notice_type, published_date, expiry_date, is_active FROM notices WHERE id = ?";
    $stmt = $db->prepare($sql);

    if ($stmt === false) {
        error_log("Error preparing fetch notice statement: " . $db->error);
        $response['message'] = "Database error: Failed to prepare query.";
        echo json_encode($response);
        exit();
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        $response['message'] = "Notice not found.";
    }
    $stmt->close();
} else {
    $response['message'] = "Invalid request.";
}

echo json_encode($response);
exit();
?>