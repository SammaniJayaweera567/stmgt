<?php
include '../../init.php';

if (isset($_POST['send_message'])) {
    $db = dbConn();
    
    $teacher_id = $_SESSION['user_id']; // Logged-in teacher
    $student_id = dataClean($_POST['student_id']);
    $subject = dataClean($_POST['message_subject']);
    $body = dataClean($_POST['message_body']);

    // Find the parent ID linked to the student
    // This assumes you have a `student_guardian_relationship` table
    $parent_sql = "SELECT guardian_user_id FROM student_guardian_relationship WHERE student_user_id = '$student_id' LIMIT 1";
    $parent_result = $db->query($parent_sql);
    
    if ($parent_result && $parent_result->num_rows > 0) {
        $parent_data = $parent_result->fetch_assoc();
        $parent_id = $parent_data['guardian_user_id'];

        // Insert message into the new communications table
        $insert_sql = "INSERT INTO communications (teacher_user_id, student_user_id, parent_user_id, message_subject, message_body) 
                       VALUES ('$teacher_id', '$student_id', '$parent_id', '$subject', '$body')";
        
        if ($db->query($insert_sql)) {
            // Redirect with success message
            header("Location: send_message.php?status=success");
        } else {
            // Redirect with error message
            header("Location: send_message.php?status=error");
        }
    } else {
        // Redirect if parent is not found
        header("Location: send_message.php?status=parent_not_found");
    }
    exit();
}
?>