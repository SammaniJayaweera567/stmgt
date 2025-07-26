<?php
include '../../init.php'; // Adjust path if init.php is located differently

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in (session 'ID' exists)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

// Check if the request method is POST and if 'id' is set
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Sanitize the student ID received from the AJAX request
    $studentId = dataClean($_POST['id']); // Assuming dataClean function exists in init.php

    $db = dbConn(); // Establish database connection using the dbConn() function from init.php

    // SQL query to fetch student data
    // We join with the 'classes' table to get the class name (Description)
    $sql = "SELECT
                s.*,
                c.Description AS ClassName
            FROM
                students s
            LEFT JOIN
                classes c ON s.class_id = c.Id
            WHERE
                s.Id = '$studentId'";

    $result = $db->query($sql); // Execute the query

    // Check if the query was successful and returned at least one row
    if ($result && $result->num_rows > 0) {
        $studentData = $result->fetch_assoc(); // Fetch the student data as an associative array
        echo json_encode(['success' => true, 'data' => $studentData]); // Return success with student data
    } else {
        // If no student found or query failed
        echo json_encode(['success' => false, 'message' => 'Student not found or database error.']);
    }
} else {
    // If the request is not a POST request or 'id' is missing
    echo json_encode(['success' => false, 'message' => 'Invalid request. Student ID not provided.']);
}
exit(); // Terminate the script
?>