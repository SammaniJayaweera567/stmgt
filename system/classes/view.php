<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'show_classes')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// --- FIX 1: Changed to accept ID from GET request ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = dataClean($_GET['id']);
    $db = dbConn();
    
    // --- FIX 2: Corrected SQL Query ---
    // - Joined 'users' table instead of non-existent 'teachers' table.
    // - Used CONCAT to get the teacher's full name.
    // - Removed 'c.class_full_name' as it does not exist in the 'classes' table.
    $sql = "SELECT 
                c.id, c.class_fee, c.status, c.max_students, c.day_of_week, c.start_time, c.end_time,
                ay.year_name, 
                cl.level_name, 
                s.subject_name, 
                ct.type_name, 
                cr.room_name,
                CONCAT(u.FirstName, ' ', u.LastName) AS teacher_name
            FROM classes c
            LEFT JOIN academic_years ay ON c.academic_year_id = ay.id
            LEFT JOIN class_levels cl ON c.class_level_id = cl.id
            LEFT JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN class_types ct ON c.class_type_id = ct.id
            LEFT JOIN class_rooms cr ON c.class_room_id = cr.id
            LEFT JOIN users u ON c.teacher_id = u.Id
            WHERE c.id = '$id'";
            
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $class_data = $result->fetch_assoc();
    } else {
        // If no class found with that ID, redirect with an error status
        header("Location: manage_classes.php?status=notfound");
        exit();
    }
} else {
    // If no ID is provided in the URL, redirect back
    header("Location: manage_classes.php");
    exit();
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Class Details</h3></div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Class Name</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($class_data['level_name'] . ' - ' . $class_data['subject_name'] . ' (' . $class_data['type_name'] . ')') ?></dd>
                        
                        <dt class="col-sm-3">Academic Year</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($class_data['year_name']) ?></dd>
                        
                        <hr class="my-2">

                        <dt class="col-sm-3">Teacher</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($class_data['teacher_name'] ?? 'Not Assigned') ?></dd>

                        <dt class="col-sm-3">Schedule</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($class_data['day_of_week']) ?> from <?= htmlspecialchars(date('h:i A', strtotime($class_data['start_time']))) ?> to <?= htmlspecialchars(date('h:i A', strtotime($class_data['end_time']))) ?></dd>

                        <dt class="col-sm-3">Classroom</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($class_data['room_name'] ?? 'Not Assigned') ?></dd>
                        
                        <hr class="my-2">
                        
                        <dt class="col-sm-3">Class Fee</dt>
                        <dd class="col-sm-9">: Rs. <?= htmlspecialchars(number_format($class_data['class_fee'], 2)) ?></dd>

                        <dt class="col-sm-3">Max Students</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($class_data['max_students']) ?></dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">: <?= ($class_data['status'] == 'Active') ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?></dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="manage_classes.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Class List</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layouts.php';
?>
