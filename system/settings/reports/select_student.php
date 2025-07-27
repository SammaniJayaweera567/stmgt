<?php
ob_start();

include '../../../init.php';
if (!hasPermission($_SESSION['user_id'], 'student_report')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice"></i> Generate Student Progress Report</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="generate_progress_report.php" target="_blank">
                        <div class="form-group">
                            <label for="student_id">Select Student:</label>
                            <select name="student_id" id="student_id" class="form-control" required>
                                <option value="">-- Select a Student --</option>
                                <?php
                                $db = dbConn();
                                // 'Student' role එකේ සිටින සියලුම සිසුන්ව ලබාගැනීම
                                $sql_students = "SELECT Id, FirstName, LastName, NIC FROM users WHERE user_role_id = 4 AND Status = 'Active' ORDER BY FirstName";
                                $result_students = $db->query($sql_students);
                                while ($row_student = $result_students->fetch_assoc()) {
                                    echo "<option value='{$row_student['Id']}'>" . htmlspecialchars($row_student['FirstName'] . ' ' . $row_student['LastName'] . ' (' . $row_student['NIC'] . ')') . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../../layouts.php';
?>