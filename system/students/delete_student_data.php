<?php
include '../../init.php'; // Adjust path if necessary

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in/authorized
// Assuming $_SESSION['user_id'] is set upon successful login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = "Unauthorized access. Please log in.";
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Sanitize the input using dataClean(), then explicitly cast to integer.
    // This is crucial for security and correct parameter binding.
    $studentId = intval(dataClean($_POST['id']));

    $db = dbConn(); // Get the database connection from init.php

    // Check if db connection was successful
    if ($db === false) {
        $response['message'] = "Database connection error from init.php.";
        echo json_encode($response);
        exit();
    }

    // Prepare a DELETE statement
    // Using 'Id' as the primary key column name for the students table, as per our discussion.
    $sql = "DELETE FROM students WHERE Id = ?";
    $stmt = $db->prepare($sql);

    if ($stmt === false) {
        $response['message'] = "Database prepare error: " . $db->error;
        echo json_encode($response);
        exit();
    }

    $stmt->bind_param("i", $studentId); // 'i' for integer

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = "Student deleted successfully.";
        } else {
            // This happens if the ID exists but no row was affected (e.g., already deleted or ID mismatch)
            $response['message'] = "Student not found or already deleted.";
        }
    } else {
        // Log the actual error for debugging, but provide a generic message to the user
        // error_log("Failed to delete student: " . $stmt->error); // Uncomment for server-side logging
        $response['message'] = "Failed to delete student: " . $stmt->error; // More specific error for debugging during development
    }

    $stmt->close();
    $db->close(); // Close the database connection
} else {
    $response['message'] = "Invalid request or missing student ID for deletion.";
}

echo json_encode($response);
?>