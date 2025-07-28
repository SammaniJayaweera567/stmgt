<?php
include '../../init.php';

// Check if the form was submitted with the 'mark_attendance' button
if (isset($_POST['mark_attendance'])) {
    $db = dbConn();
    
    // Get the data from the form
    $class_id = dataClean($_POST['class_id']);
    $attendance_date = dataClean($_POST['attendance_date']);
    $attendance_data = $_POST['attendance']; // This is an array like [student_id => status]
    $marked_by = $_SESSION['user_id'];
    $marked_at = date('Y-m-d H:i:s');
    
    // Loop through each student's attendance data
    foreach ($attendance_data as $student_id => $status) {
        $student_id = (int)$student_id;
        $status = dataClean($status);

        // This powerful query will INSERT a new record, 
        // OR UPDATE the status if a record for that student/class/date already exists.
        $sql = "INSERT INTO attendance (class_id, student_user_id, attendance_date, status, marked_by_user_id, marked_at) 
                VALUES ('$class_id', '$student_id', '$attendance_date', '$status', '$marked_by', '$marked_at')
                ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                marked_by_user_id = VALUES(marked_by_user_id), 
                marked_at = VALUES(marked_at)";
        
        // Execute the query for each student
        $db->query($sql);
    }

    // Set a success message and redirect back to the same filtered page
    $_SESSION['status_message'] = "Attendance for " . $attendance_date . " has been successfully saved!";
    $_SESSION['status_type'] = "success";
    header("Location: mark_attendance.php?class_id=$class_id&date=$attendance_date");
    exit();

} else {
    // If someone accesses this page directly without submitting the form, redirect them
    header("Location: mark_attendance.php");
    exit();
}
?>