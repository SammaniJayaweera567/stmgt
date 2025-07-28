<?php
ob_start();
include '../../init.php';

$db = dbConn();
$selected_class_id = $_GET['class_id'] ?? null;
$selected_date = $_GET['date'] ?? date('Y-m-d');
$students_to_mark = [];
$attendance_marked = [];

if ($selected_class_id) {
    // Get all enrolled students
    $sql_students = "SELECT u.Id, u.FirstName, u.LastName, sd.registration_no FROM users u JOIN student_details sd ON u.Id = sd.user_id JOIN enrollments e ON u.Id = e.student_user_id WHERE e.class_id = '$selected_class_id' AND e.status = 'active' ORDER BY u.FirstName";
    $result_students = $db->query($sql_students);
    if ($result_students) {
        while ($row = $result_students->fetch_assoc()) {
            $students_to_mark[] = $row;
        }
    }
    // Get already marked attendance
    $sql_marked = "SELECT student_user_id, status FROM attendance WHERE class_id = '$selected_class_id' AND attendance_date = '$selected_date'";
    $result_marked = $db->query($sql_marked);
    if($result_marked) {
        while($row = $result_marked->fetch_assoc()){
            $attendance_marked[$row['student_user_id']] = $row['status'];
        }
    }
}
?>
<div class="container-fluid">
    <?php show_status_message(); ?>
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-user-check"></i> Mark & View Attendance</h3></div>
        <div class="card-body">
            <form method="get" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label for="class_id">Select Class</label>
                        <select name="class_id" id="class_id" class="form-select form-control" required>
                            <option value="">-- Select a Class --</option>
                            <?php
                            $sql_classes = "SELECT c.id, s.subject_name, cl.level_name FROM classes c JOIN subjects s ON c.subject_id=s.id JOIN class_levels cl ON c.class_level_id=cl.id WHERE c.status='Active' ORDER BY cl.level_name, s.subject_name";
                            $result_classes = $db->query($sql_classes);
                            while ($class = $result_classes->fetch_assoc()) {
                                $selected = ($selected_class_id == $class['id']) ? 'selected' : '';
                                echo "<option value='{$class['id']}' $selected>" . htmlspecialchars($class['level_name'] . ' - ' . $class['subject_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-5"><label for="date">Select Date</label><input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" required></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">View</button></div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_class_id): ?>
    <div class="card mt-4">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-qrcode"></i> QR Code Scanner</h3></div>
        <div class="card-body text-center">
            <div id="scanner-wrapper" style="max-width: 400px; margin: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                <div id="qr-reader"></div>
            </div>
            <div id="scan_result" class="alert mt-3" style="display: none;"></div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header"><h3 class="card-title">Manual Attendance Sheet for <?= htmlspecialchars($selected_date) ?></h3></div>
        <div class="card-body">
            <form method="post" action="process_attendance.php">
                <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class_id) ?>">
                <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>#</th><th>Student Name</th><th>Registration No</th><th class="text-center">Status (Present / Absent)</th></tr></thead>
                    <tbody>
                        <?php if (!empty($students_to_mark)): 
                            $count = 1;
                            foreach ($students_to_mark as $student): 
                                $current_status = $attendance_marked[$student['Id']] ?? 'Absent';
                        ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></td>
                            <td><?= htmlspecialchars($student['registration_no']) ?></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="attendance[<?= $student['Id'] ?>]" id="present_<?= $student['Id'] ?>" value="Present" <?= $current_status == 'Present' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="present_<?= $student['Id'] ?>">Present</label>

                                    <input type="radio" class="btn-check" name="attendance[<?= $student['Id'] ?>]" id="absent_<?= $student['Id'] ?>" value="Absent" <?= $current_status == 'Absent' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-danger" for="absent_<?= $student['Id'] ?>">Absent</label>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center text-muted">No students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="text-end mt-3"><button type="submit" name="mark_attendance" class="btn btn-success">Save Manual Attendance</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="../dist/js/html5-qrcode.min.js" type="text/javascript"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const classSelector = document.getElementById('class_id');
    const resultContainer = document.getElementById('scan_result');
    
    // Only start the scanner if a class is already selected when the page loads
    if (classSelector.value) {
        const html5QrCode = new Html5Qrcode("qr-reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        const onScanSuccess = (decodedText, decodedResult) => {
            // Pause the scanner to prevent multiple quick scans of the same code
            html5QrCode.pause();
            
            // Call the function to process the scanned student ID
            processAttendance(decodedText);
            
            // Resume scanning after a 2-second delay
            setTimeout(() => { html5QrCode.resume(); }, 2000);
        };

        // Start the camera
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
            .catch(err => {
                console.error("Unable to start scanning.", err);
                // You can show a user-friendly message here if the camera fails
                document.getElementById('scanner-wrapper').innerHTML = '<div class="alert alert-danger">Could not start camera. Please grant permission and refresh the page.</div>';
            });
    }

    // This function sends the scanned data to the backend
    function processAttendance(studentId) {
        // Get the currently selected class and date from the filter
        const classId = classSelector.value;
        const attendanceDate = document.getElementById('date').value;
        
        // Show a "Processing..." message
        resultContainer.textContent = 'Processing...';
        resultContainer.className = 'alert alert-info';
        resultContainer.style.display = 'block';

        // Send the data to process_scan.php using fetch()
        fetch('process_scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `student_user_id=${studentId}&class_id=${classId}&attendance_date=${attendanceDate}`
        })
        .then(response => response.json())
        .then(data => {
            // Display the success or error message from the backend
            resultContainer.textContent = data.message;
            if (data.status === 'success') {
                resultContainer.className = 'alert alert-success';
                // Reload the page after 1.5 seconds to show the updated attendance
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                resultContainer.className = 'alert alert-danger';
            }
        })
        .catch(error => {
            resultContainer.textContent = "A network or system error occurred.";
            resultContainer.className = 'alert alert-danger';
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>