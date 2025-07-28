<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'show_student_enrollment')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// Check if an ID is passed via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enrollment_id'])) {
    $id = dataClean($_POST['enrollment_id']);
    $db = dbConn();
    
    // SQL query to get all details of the specific enrollment
    $sql = "SELECT
                e.*,
                u.FirstName, u.LastName, u.username, u.Email AS StudentEmail, u.TelNo AS StudentTelNo, u.NIC AS StudentNIC,
                c.class_fee, c.day_of_week, c.start_time, c.end_time,
                teacher.FirstName AS TeacherFirstName, teacher.LastName AS TeacherLastName,
                s.subject_name,
                cl.level_name,
                ct.type_name AS class_type
            FROM enrollments AS e
            LEFT JOIN users AS u ON e.student_user_id = u.Id
            LEFT JOIN classes AS c ON e.class_id = c.id
            LEFT JOIN users AS teacher ON c.teacher_id = teacher.Id
            LEFT JOIN subjects AS s ON c.subject_id = s.id
            LEFT JOIN class_levels AS cl ON c.class_level_id = cl.id
            LEFT JOIN class_types AS ct ON c.class_type_id = ct.id
            WHERE e.id = '$id'";
            
    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        $enrollment = $result->fetch_assoc();
    } else {
        header("Location: manage_enrollments.php?status=notfound");
        exit();
    }
} else {
    header("Location: manage_enrollments.php");
    exit();
}
?>

<div class="container-fluid mb-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-check me-2 mr-1"></i>Enrollment Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 border-end">
                             <h5 class="mb-3 text-secondary" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Student Information</h5>
                            <dl class="row">
                                <dt class="col-sm-4">Full Name</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($enrollment['FirstName'] . ' ' . $enrollment['LastName']) ?></dd>
                                
                                <dt class="col-sm-4">Username</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($enrollment['username']) ?></dd>

                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($enrollment['StudentEmail']) ?></dd>

                                <dt class="col-sm-4">Telephone</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($enrollment['StudentTelNo']) ?></dd>
                                
                                <dt class="col-sm-4">NIC</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($enrollment['StudentNIC']) ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3 text-secondary" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Class & Enrollment Information</h5>
                            <dl class="row">
                                <dt class="col-sm-4">Class Description</dt>
                                <dd class="col-sm-8">: <strong><?= htmlspecialchars($enrollment['level_name'] . ' | ' . $enrollment['subject_name'] . ' (' . $enrollment['class_type'] . ')') ?></strong></dd>

                                <dt class="col-sm-4">Teacher</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($enrollment['TeacherFirstName'] . ' ' . $enrollment['TeacherLastName']) ?></dd>
                                
                                <dt class="col-sm-4">Schedule</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($enrollment['day_of_week'] . ' ' . date('h:i A', strtotime($enrollment['start_time'])) . ' - ' . date('h:i A', strtotime($enrollment['end_time']))) ?></dd>
                                
                                <dt class="col-sm-4">Enrollment Date</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars(date('F j, Y', strtotime($enrollment['enrollment_date']))) ?></dd>
                                
                                <dt class="col-sm-4">Enrollment Status</dt>
                                <dd class="col-sm-8">: <?= display_status_badge($enrollment['status']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="manage_enrollments.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Enrollment List</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>