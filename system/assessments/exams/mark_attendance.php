<?php
ob_start();
include '../../../init.php';

$db = dbConn();

// --- Security Check & Initial Setup ---
if (!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
    exit();
}
$logged_in_user_id = (int)$_SESSION['user_id'];
$user_role_name = strtolower($_SESSION['user_role_name'] ?? '');

// Allow access only if Admin or Teacher
if ($user_role_name !== 'admin' && $user_role_name !== 'teacher') { 
    die("Access Denied: You do not have permission to mark exam attendance.");
}

$assessment_id = (int)($_GET['assessment_id'] ?? 0);
if ($assessment_id === 0) {
    header("Location: manage_exams.php?status=notfound");
    exit();
}

// --- Fetch Exam Details ---
$sql_exam = "SELECT a.title, a.assessment_date, a.start_time, a.end_time, cl.level_name, s.subject_name, c.id as class_id_for_exam, a.status as exam_status
             FROM assessments a
             JOIN classes c ON a.class_id = c.id
             JOIN class_levels cl ON c.class_level_id = cl.id
             JOIN subjects s ON a.subject_id = s.id
             WHERE a.id = '$assessment_id' AND a.assessment_type_id = 1"; // <-- වෙනස් කළ කොටස
$result_exam = $db->query($sql_exam);

if ($result_exam->num_rows === 0) {
    header("Location: manage_exams.php?status=notfound");
    exit();
}
$exam_details = $result_exam->fetch_assoc();
$class_id_for_exam = $exam_details['class_id_for_exam'];

// --- NEW VALIDATION: Check if the exam is in a valid state to mark attendance ---
if ($exam_details['exam_status'] != 'Published') {
    die("Action Not Allowed: Attendance can only be marked for exams that are 'Published'. This exam's status is currently '{$exam_details['exam_status']}'.");
}


// --- Fetch Students for the list ---
$sql_students = "SELECT u.Id as student_user_id, u.FirstName, u.LastName, sd.registration_no, ea.attendance_status
                 FROM enrollments e
                 JOIN users u ON e.student_user_id = u.Id
                 JOIN student_details sd ON u.Id = sd.user_id
                 LEFT JOIN exam_attendance ea ON u.Id = ea.student_user_id AND ea.assessment_id = '$assessment_id'
                 WHERE e.class_id = '$class_id_for_exam' AND e.status = 'active'
                 ORDER BY ea.attendance_status DESC, u.FirstName ASC"; // Show present students first
$result_students = $db->query($sql_students);

?>

<style>
.scanner-section,
.attendance-list-section {
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    min-height: 550px;
    /* Adjust as needed for better visual balance */
}

#qr-reader {
    width: 100%;
    max-width: 400px;
    /* Control max width of scanner */
    margin: 0 auto;
    border: 2px solid #eee;
    border-radius: 8px;
    overflow: hidden;
}

#qr-reader__dashboard_section_csr button {
    background-color: #1cc1ba !important;
    color: white !important;
    border-radius: 5px !important;
    padding: 8px 12px !important;
    border: none !important;
}

.status-message {
    margin-top: 20px;
    padding: 15px;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 500;
    display: none;
    animation: fadeIn 0.5s;
    text-align: center;
}

.status-success {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.status-warning {
    background-color: #fff3cd;
    color: #664d03;
    border: 1px solid #ffecb5;
}

/* For Already Marked */
.status-error {
    background-color: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}

.status-processing {
    background-color: #cff4fc;
    color: #055160;
    border: 1px solid #b6effb;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.student-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    background-color: #f8f9fa;
    /* Light background for list items */
    margin-bottom: 5px;
    border-radius: 5px;
}

.student-list-item:last-child {
    border-bottom: none;
}

.student-list-item:hover {
    background-color: #e9ecef;
}

/* Hover effect */

.student-name {
    font-weight: 600;
    color: #343a40;
}

.reg-no {
    font-size: 0.85em;
    color: #6c757d;
}

.attendance-status-badge {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8em;
    font-weight: 700;
    min-width: 80px;
    /* Consistent width for badges */
    text-align: center;
}

.status-present {
    background-color: #28a745;
    color: white;
}

.status-absent {
    background-color: #dc3545;
    color: white;
}

.status-not-marked {
    background-color: #6c757d;
    color: white;
}

/* For scrollable student list */
#student_attendance_list_wrapper {
    max-height: 400px;
    /* Limit height */
    overflow-y: auto;
    /* Add scrollbar */
    padding-right: 15px;
    /* Space for scrollbar */
}
</style>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-qrcode mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Exam Attendance Scanner</h5>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                Exam: <?= htmlspecialchars($exam_details['title']) ?><br>
                <small class="text-white-50">
                    Class: <?= htmlspecialchars($exam_details['level_name']) ?> - <?= htmlspecialchars($exam_details['subject_name']) ?>
                    Date: <?= htmlspecialchars(date('Y-m-d', strtotime($exam_details['assessment_date']))) ?> |
                    Time: <?= htmlspecialchars(date('h:i A', strtotime($exam_details['start_time']))) ?> -
                    <?= htmlspecialchars(date('h:i A', strtotime($exam_details['end_time']))) ?>
                </small>
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="scanner-section">
                        <div class="text-center mb-4">
                            <i class="fas fa-camera" style="font-size: 36px; color: #1cc1ba;"></i>
                            <h4 class="mt-2">Scan Student QR Code</h4>
                        </div>
                        <div id="qr-reader"></div>
                        <div id="scan_result" class="status-message"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="attendance-list-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Student Attendance List</h4>
                            <a href="manage_exams.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to Exams
                            </a>
                        </div>
                        <div id="student_attendance_list_wrapper">
                            <?php if ($result_students && $result_students->num_rows > 0): ?>
                                <?php while ($student = $result_students->fetch_assoc()): ?>
                                    <div class="student-list-item" id="student_<?= $student['student_user_id'] ?>">
                                        <div>
                                            <span class="student-name"><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></span><br>
                                            <span class="reg-no"><?= htmlspecialchars($student['registration_no']) ?></span>
                                        </div>
                                        <div>
                                            <span class="attendance-status-badge 
                                                <?php 
                                                    if ($student['attendance_status'] == 'Present') echo 'status-present'; 
                                                    else echo 'status-not-marked';
                                                ?>">
                                                <?= htmlspecialchars($student['attendance_status'] ?? 'Not Marked') ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-muted mt-5">No students enrolled in this class.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const assessmentId = <?= $assessment_id ?>;
    const qrReaderDiv = document.getElementById('qr-reader');
    const resultContainer = document.getElementById('scan_result');
    const studentAttendanceListWrapper = document.getElementById('student_attendance_list_wrapper');

    let html5QrCode = new Html5Qrcode("qr-reader");
    let lastScannedResult = null;
    let scanTimeout = null;

    // Start scanner automatically when page loads
    startScanner();

    function startScanner() {
        const config = {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            }
        }; // Optimized for a tighter scan area
        html5QrCode.start({
                facingMode: "environment"
            }, config, onScanSuccess, onScanFailure)
            .catch(err => {
                console.error("Unable to start scanning.", err);
                displayMessage("ERROR: Could not start camera. Please grant permission and refresh.",
                    'error');
            });
    }

    function stopScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().catch(err => console.error("Failed to stop scanner.", err));
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Prevent continuous scanning of the same QR code immediately
        if (decodedText === lastScannedResult) {
            return;
        }
        lastScannedResult = decodedText;

        // Reset lastScannedResult after a delay to allow re-scanning the same QR if needed
        clearTimeout(scanTimeout);
        scanTimeout = setTimeout(() => {
            lastScannedResult = null;
        }, 3000); // 3 seconds debounce

        // Vibrate phone for feedback if supported
        if (navigator.vibrate) {
            navigator.vibrate(100);
        }

        processAttendance(decodedText);
    }

    function onScanFailure(error) {
        // This is called continuously when no QR code is found. Do nothing here.
    }

    function processAttendance(studentId) {
        displayMessage("Processing...", 'processing');

        fetch('process_exam_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `student_user_id=${encodeURIComponent(studentId)}&assessment_id=${encodeURIComponent(assessmentId)}`
            })
            .then(response => response.json())
            .then(data => {
                displayMessage(data.message, data.status);
                if (data.status === 'success' || data.status ===
                    'warning') { // Update list for success or already marked
                    updateStudentListUI(studentId,
                    'Present'); // Pass 'Present' status as it was successfully scanned
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayMessage("A network error occurred. Please try again.", 'error');
            });
    }

    function displayMessage(message, type) {
        resultContainer.textContent = message;
        resultContainer.className = 'status-message'; // Reset classes
        if (type === 'success') {
            resultContainer.classList.add('status-success');
        } else if (type === 'error') {
            resultContainer.classList.add('status-error');
        } else if (type === 'warning') { // For already marked
            resultContainer.classList.add('status-warning');
        } else { // processing
            resultContainer.classList.add('status-processing');
        }
        resultContainer.style.display = 'block';
    }

    // Function to update the student list UI after a scan
    function updateStudentListUI(scannedStudentId, status) {
        const studentItem = document.getElementById('student_' + scannedStudentId);
        if (studentItem) {
            const statusBadge = studentItem.querySelector('.attendance-status-badge');
            if (statusBadge) {
                statusBadge.textContent = status;
                statusBadge.className = 'attendance-status-badge'; // Reset classes
                if (status === 'Present') {
                    statusBadge.classList.add('status-present');
                    // Move the student to the top of the 'Present' section for immediate visibility
                    studentAttendanceListWrapper.prepend(studentItem);
                } else if (status === 'Absent') {
                    statusBadge.classList.add('status-absent');
                } else {
                    statusBadge.classList.add('status-not-marked');
                }
            }
        } else {
            console.warn(
                `Student with ID ${scannedStudentId} scanned, but UI element not found. List might not be up-to-date.`
                );
            // A more robust solution for missing UI elements:
            // Fetch student name/reg_no via AJAX (using studentId) and dynamically create/add a new list item.
            // For now, it logs a warning.
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php'; // Corrected path
?>