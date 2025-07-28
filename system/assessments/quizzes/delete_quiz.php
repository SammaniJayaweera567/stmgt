<?php
ob_start();
include '../../../init.php'; // Path from /system/assessments/quizzes/

$db = dbConn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)($_POST['id']); // Get ID from POST

    // A quiz can have questions, submissions, and results.
    // It's crucial to handle these related records before deleting the main quiz record.
    // The simplest approach for a quiz is to cascade delete.

    // Start a transaction to ensure all or nothing is deleted.
    $db->begin_transaction();

    try {
        // 1. Delete associated questions from 'assessment_questions'
        $sql_delete_questions = "DELETE FROM assessment_questions WHERE assessment_id = '$id'";
        if (!$db->query($sql_delete_questions)) {
            throw new Exception("Failed to delete quiz questions: " . $db->error);
        }

        // 2. Delete associated results from 'assessment_results'
        $sql_delete_results = "DELETE FROM assessment_results WHERE assessment_id = '$id'";
        if (!$db->query($sql_delete_results)) {
            throw new Exception("Failed to delete quiz results: " . $db->error);
        }
        
        // 3. Delete associated submissions from 'student_submissions'
        $sql_delete_submissions = "DELETE FROM student_submissions WHERE assessment_id = '$id'";
        if (!$db->query($sql_delete_submissions)) {
            throw new Exception("Failed to delete student submissions for the quiz: " . $db->error);
        }

        // 4. Finally, delete the quiz itself from the 'assessments' table
        // UPDATED: Use assessment_type_id for filtering
        $sql_delete_quiz = "DELETE FROM assessments WHERE id = '$id' AND assessment_type_id = 3"; // Changed to assessment_type_id = 3 (for Quiz)
        if (!$db->query($sql_delete_quiz)) {
            throw new Exception("Failed to delete the main quiz record: " . $db->error);
        }

        // If all queries were successful, commit the transaction
        $db->commit();
        header("Location: manage_quizzes.php?status=deleted");
        exit();

    } catch (Exception $e) {
        // If any query fails, roll back the entire transaction
        $db->rollback();
        // Redirect with a generic error message
        header("Location: manage_quizzes.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }

} else {
    // Redirect if not a POST request or no ID provided
    header("Location: manage_quizzes.php");
    exit();
}

ob_end_flush(); // Output buffering end
?>
