<?php
ob_start();
include '../../init.php'; // Adjust path if needed

// Assume teacher's ID is stored in session
$teacher_id = $_SESSION['user_id']; 
?>

<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-paper-plane"></i> Send Message to Parent</h3>
        </div>
        <form method="post" action="process_message.php">
            <div class="card-body">
                <div class="form-group">
                    <label>Select Student:</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Select a student you teach --</option>
                        <?php
                        $db = dbConn();
                        // Get only the students taught by this specific teacher
                        $sql_students = "SELECT DISTINCT u.Id, u.FirstName, u.LastName 
                                         FROM users u 
                                         JOIN enrollments e ON u.Id = e.student_user_id 
                                         JOIN classes c ON e.class_id = c.id 
                                         WHERE c.teacher_id = '$teacher_id' AND u.user_role_id = 4";
                        $result_students = $db->query($sql_students);
                        while ($student = $result_students->fetch_assoc()) {
                            echo "<option value='{$student['Id']}'>" . htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="message_subject" class="form-control" required placeholder="Enter message subject">
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message_body" class="form-control" rows="5" required placeholder="Enter your message to the parent"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Adjust path if needed
?>