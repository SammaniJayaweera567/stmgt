<?php
ob_start();
include '../../init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$db = dbConn();
$messages = [];
$students = [];
$attendance_records = [];

// Get filter values, default to today if not set
$selectedClassId = $_GET['class_id'] ?? null;
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// --- LOGIC PART 1: Handle SAVING attendance (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_id = dataClean($_POST['class_id']);
    $attendance_date = dataClean($_POST['attendance_date']);
    $attendance_data = $_POST['attendance'] ?? [];

    foreach ($attendance_data as $student_id => $status) {
        $student_id_clean = dataClean($student_id);
        $status_clean = dataClean($status);

        if (!empty($status_clean)) {
            // Check if a record already exists for this student on this date
            $checkSql = "SELECT id FROM attendance WHERE student_id = '$student_id_clean' AND attendance_date = '$attendance_date'";
            $checkResult = $db->query($checkSql);

            if ($checkResult->num_rows > 0) {
                // If exists, UPDATE it
                $record = $checkResult->fetch_assoc();
                $record_id = $record['id'];
                $updateSql = "UPDATE attendance SET status = '$status_clean' WHERE id = '$record_id'";
                $db->query($updateSql);
            } else {
                // If not, INSERT a new record
                $insertSql = "INSERT INTO attendance (class_id, student_id, attendance_date, status) VALUES ('$class_id', '$student_id_clean', '$attendance_date', '$status_clean')";
                $db->query($insertSql);
            }
        }
    }

    // Redirect back to the same page with filters and a success message
    header("Location: mark_attendance.php?class_id=$class_id&date=$attendance_date&status=saved");
    exit();
}


// --- LOGIC PART 2: Handle LOADING students (GET Request) ---
if (!empty($selectedClassId)) {
    // Fetch students for the selected class
    $studentsSql = "SELECT Id, registration_no, first_name, last_name 
                    FROM students 
                    WHERE class_id = '$selectedClassId' 
                    ORDER BY first_name ASC, last_name ASC";
    $studentsResult = $db->query($studentsSql);
    if ($studentsResult) {
        while ($row = $studentsResult->fetch_assoc()) {
            $students[] = $row;
        }
    }

    // Fetch existing attendance records for the loaded students on the selected date
    if (!empty($students)) {
        $student_ids = implode(',', array_column($students, 'Id'));
        $attendanceSql = "SELECT student_id, status FROM attendance 
                          WHERE student_id IN ($student_ids) AND attendance_date = '$selectedDate'";
        $attendanceResult = $db->query($attendanceSql);
        if ($attendanceResult) {
            while ($row = $attendanceResult->fetch_assoc()) {
                $attendance_records[$row['student_id']] = $row['status'];
            }
        }
    }
}

// Fetch all classes for the dropdown
$classes = [];
$classesResult = $db->query("SELECT Id, class_full_name FROM classes ORDER BY class_full_name ASC");
if ($classesResult) {
    while ($row = $classesResult->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Show success message from URL
if (isset($_GET['status']) && $_GET['status'] == 'saved') {
    echo "<script>
        window.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                position: 'top-end', icon: 'success', title: 'Attendance saved successfully!',
                showConfirmButton: false, timer: 2000
            });
        });
    </script>";
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-user-check mr-2 mt-1" style="font-size: 17px;"></i>
            <h5 class="mb-4 w-auto">Mark Student Attendance</h5>
        </div>
        <div class="col-12 mt-3">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Select Class and Date</h3></div>
                <div class="card-body">
                    <form method="GET" action="mark_attendance.php">
                        <div class="row">
                            <div class="form-group col-md-5">
                                <label>Class</label>
                                <select class="form-control" name="class_id" required>
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['Id'] ?>" <?= ($selectedClassId == $class['Id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($class['class_full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-5">
                                <label>Date</label>
                                <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($selectedDate) ?>" required>
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Load Students</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($students)): ?>
                <div class="card card-primary mt-4">
                    <div class="card-header" style="background: #4b545c"><h3 class="card-title">Mark Attendance</h3></div>
                    <div class="card-body">
                        <form method="POST" action="mark_attendance.php">
                            <input type="hidden" name="class_id" value="<?= htmlspecialchars($selectedClassId) ?>">
                            <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selectedDate) ?>">
                            <input type="hidden" name="action" value="save_attendance">
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Reg. No.</th>
                                            <th>Student Name</th>
                                            <th style="width: 200px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['registration_no']) ?></td>
                                                <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                                <td>
                                                    <select name="attendance[<?= $student['Id'] ?>]" class="form-control" required>
                                                        <option value="">-- Select --</option>
                                                        <option value="Present" <?= (@$attendance_records[$student['Id']] == 'Present') ? 'selected' : '' ?>>Present</option>
                                                        <option value="Absent" <?= (@$attendance_records[$student['Id']] == 'Absent') ? 'selected' : '' ?>>Absent</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-success px-4" style="background: #4b545c">Save Attendance</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif(!empty($selectedClassId)): ?>
                 <div class="alert alert-warning mt-4">No students found for the selected class.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>