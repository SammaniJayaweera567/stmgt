<?php
include '../../init.php'; // Adjust path as necessary

$db = dbConn(); // Get database connection
if (!hasPermission($_SESSION['user_id'], 'delete_notice')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    if ($id <= 0) {
        $response['message'] = "Invalid Notice ID for deletion.";
        echo json_encode($response);
        exit();
    }

    $sql = "DELETE FROM notices WHERE id = ?";
    $stmt = $db->prepare($sql);

    if ($stmt === false) {
        error_log("Error preparing delete notice statement: " . $db->error);
        $response['message'] = "Database error: Failed to prepare delete query.";
        echo json_encode($response);
        exit();
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = "Notice deleted successfully!";
        } else {
            $response['message'] = "Notice not found or already deleted.";
        }
    } else {
        error_log("Error deleting notice data: " . $stmt->error);
        $response['message'] = "Failed to delete notice from the database.";
    }
    $stmt->close();
} else {
    $response['message'] = "Invalid request.";
}

echo json_encode($response);
exit();
?>