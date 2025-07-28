<?php
ob_start();
include '../../init.php';

$db = dbConn();
$messages = []; // To store any validation errors

// Get filter values from the URL
$filter_class_id = dataClean($_GET['class_id'] ?? null);
$filter_date = dataClean($_GET['date'] ?? '');
$attendance_records = []; // Initialize an empty array to hold the results

// --- VALIDATION PART ---
// We only run validations if the user has submitted the form at least once
if (isset($_GET['class_id'])) {
    // 1. Validate Class ID
    if (!empty($filter_class_id)) {
        $sql_check_class = "SELECT id FROM classes WHERE id = '$filter_class_id' AND status = 'Active'";
        if ($db->query($sql_check_class)->num_rows == 0) {
            $messages[] = "The selected class is not valid or is currently inactive.";
            $filter_class_id = null; // Reset if invalid
        }
    } else {
        $messages[] = "Please select a class to view a report.";
    }

    // 2. Validate Date Format
    if (!empty($filter_date)) {
        $d = DateTime::createFromFormat('Y-m-d', $filter_date);
        if (!$d || $d->format('Y-m-d') !== $filter_date) {
            $messages[] = "The date format is invalid. Please use the date picker.";
            $filter_date = null; // Reset if invalid
        }
    } else {
        // Allowing an empty date might be a feature, but if it's required:
        $messages[] = "Please select a date.";
    }
}
// --- END OF VALIDATION PART ---


// Fetch Attendance Data only if there are no validation errors and filters are set
if (empty($messages) && !empty($filter_class_id) && !empty($filter_date)) {
    $sql = "SELECT
                a.status,
                a.marked_at,
                u_student.FirstName AS student_first_name,
                u_student.LastName AS student_last_name,
                sd.registration_no,
                u_marker.FirstName AS marker_first_name
            FROM attendance AS a
            JOIN users AS u_student ON a.student_user_id = u_student.Id
            JOIN student_details AS sd ON a.student_user_id = sd.user_id
            LEFT JOIN users AS u_marker ON a.marked_by_user_id = u_marker.Id
            WHERE a.class_id = '$filter_class_id' AND a.attendance_date = '$filter_date'
            ORDER BY u_student.FirstName ASC";
    
    $result = $db->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-file-alt mt-1 me-2"></i>
            <h5 class="w-auto">View Attendance Report</h5>
        </div>
    </div>

    <div class="card card-outline card-primary mb-4">
        <div class="card-header"><h3 class="card-title">Select Criteria</h3></div>
        <div class="card-body">
            <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-select form-control">
                            <option value="">-- Please Select --</option>
                            <?php
                            $sql_classes = "SELECT c.id, s.subject_name, cl.level_name FROM classes c JOIN subjects s ON c.subject_id=s.id JOIN class_levels cl ON c.class_level_id=cl.id WHERE c.status='Active' ORDER BY cl.id, s.subject_name";
                            $classes_result = $db->query($sql_classes);
                            if ($classes_result) {
                                while ($class = $classes_result->fetch_assoc()) {
                                    $selected = ($filter_class_id == $class['id']) ? 'selected' : '';
                                    echo "<option value='{$class['id']}' $selected>" . htmlspecialchars($class['level_name'] . ' - ' . $class['subject_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Select Date</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    if (!empty($messages)) {
        echo '<div class="alert alert-warning">';
        foreach($messages as $message) {
            echo htmlspecialchars($message) . '<br>';
        }
        echo '</div>';
    }
    ?>

    <?php if (empty($messages) && !empty($filter_class_id)): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Attendance Report for <?= htmlspecialchars($filter_date) ?></h3></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="reportTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Registration No</th>
                            <th>Status</th>
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
                                    <td>
                                        <span class="badge bg-<?= ($record['status'] == 'Present') ? 'success' : 'danger' ?>">
                                            <?= htmlspecialchars($record['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= !empty($record['marked_at']) ? htmlspecialchars(date('h:i:s A', strtotime($record['marked_at']))) : '' ?></td>
                                    <td><?= htmlspecialchars($record['marker_first_name'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No attendance records found for the selected class and date.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Initialize DataTable if the table is present
$(document).ready(function() {
    if($('#reportTable').length) {
        $('#reportTable').DataTable({
            "responsive": true,
            "buttons": ["copy", "csv", "excel", "pdf", "print"]
        }).buttons().container().appendTo('#reportTable_wrapper .col-md-6:eq(0)');
    }
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>