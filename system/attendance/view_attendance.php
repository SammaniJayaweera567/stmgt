<?php
ob_start();
// [FIX 1] All paths corrected to '../'
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'manage_attendance')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$db = dbConn();

// --- Step 1: Handle Filters ---
$filter_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$filter_date = isset($_GET['date']) ? dataClean($_GET['date']) : date('Y-m-d'); // Default to today

// --- Step 2: Fetch Data for Filter Dropdowns ---
$sql_classes = "SELECT c.id, s.subject_name, cl.level_name 
                FROM classes c
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_levels cl ON c.class_level_id = cl.id
                WHERE c.status = 'Active'
                ORDER BY cl.level_name, s.subject_name";
$classes_result = $db->query($sql_classes);

// --- Step 3: Fetch Attendance Data Based on Filters ---
$attendance_records = [];
if ($filter_class_id > 0) {
    $sql_attendance = "SELECT
                            a.attendance_date,
                            a.marked_at,
                            u_student.FirstName AS student_first_name,
                            u_student.LastName AS student_last_name,
                            sd.registration_no,
                            u_marker.FirstName AS marker_first_name,
                            u_marker.LastName AS marker_last_name
                        FROM attendance AS a
                        JOIN users AS u_student ON a.student_user_id = u_student.Id
                        JOIN student_details AS sd ON a.student_user_id = sd.user_id
                        JOIN users AS u_marker ON a.marked_by_user_id = u_marker.Id
                        WHERE a.class_id = '$filter_class_id' AND a.attendance_date = '$filter_date'
                        ORDER BY a.marked_at DESC";
    
    $attendance_result = $db->query($sql_attendance);
    if ($attendance_result) {
        while ($row = $attendance_result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
    }
}
?>

<!-- Page-specific CSS -->
<style>
    .filter-card {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .table-wrapper {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .table thead th {
        background-color: #f8f9fa;
        border-bottom-width: 2px;
        font-weight: 600;
    }
    .table tbody tr:hover {
        background-color: #f1f1f1;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Attendance Report</h1>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label for="class_id" class="form-label"><b>Select Class</b></label>
                    <select name="class_id" id="class_id" class="form-select">
                        <option value="">-- All Classes --</option>
                        <?php
                        if ($classes_result && $classes_result->num_rows > 0) {
                            mysqli_data_seek($classes_result, 0); // Reset pointer
                            while ($class = $classes_result->fetch_assoc()) {
                                $display_text = htmlspecialchars($class['level_name'] . ' - ' . $class['subject_name']);
                                $selected = ($filter_class_id == $class['id']) ? 'selected' : '';
                                echo "<option value='{$class['id']}' $selected>{$display_text}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="date" class="form-label"><b>Select Date</b></label>
                    <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">View Report</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Attendance Report Table -->
    <div class="table-wrapper">
        <?php if ($filter_class_id > 0): ?>
            <h4 class="mb-3">Report for <?= htmlspecialchars($filter_date) ?></h4>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Registration No</th>
                            <th>Marked At</th>
                            <th>Marked By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance_records)): ?>
                            <?php $i = 1; foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($record['student_first_name'] . ' ' . $record['student_last_name']) ?></td>
                                    <td><?= htmlspecialchars($record['registration_no']) ?></td>
                                    <td><?= htmlspecialchars(date('h:i:s A', strtotime($record['marked_at']))) ?></td>
                                    <td><?= htmlspecialchars($record['marker_first_name'] . ' ' . $record['marker_last_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No attendance records found for the selected class and date.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                Please select a class and a date to view the attendance report.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
