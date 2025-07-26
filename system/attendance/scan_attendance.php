<?php
ob_start();
include '../../init.php';

// --- FINALIZED SECURITY CHECK ---
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. Check if the logged-in user has the correct role OR is the Super Admin (ID = 1).
$user_role = isset($_SESSION['user_role']) ? strtolower($_SESSION['user_role']) : '';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($user_id !== 1 && $user_role !== 'admin' && $user_role !== 'card checker') {
    // Display a clear access denied message if not Admin (by ID or Role) or Card Checker.
    die("Access Denied: You do not have permission to use the attendance scanner.");
}


$db = dbConn();
$today_day_name = date('l');
$sql_classes = "SELECT c.id, s.subject_name, cl.level_name, CONCAT(u.FirstName, ' ', u.LastName) AS teacher_name
                FROM classes c
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_levels cl ON c.class_level_id = cl.id
                JOIN users u ON c.teacher_id = u.Id
                WHERE c.status = 'Active' AND c.day_of_week = '$today_day_name'
                ORDER BY cl.level_name, s.subject_name";
$classes_result = $db->query($sql_classes);
?>

<!-- Page-specific CSS styles -->
<style>
    .scanner-container {
        max-width: 500px; /* Optimized for a single column view */
        margin: 20px auto;
        padding: 30px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    #qr-reader {
        width: 100%;
        border: 2px solid #eee;
        border-radius: 8px;
        overflow: hidden; /* Ensures the video fits within the rounded corners */
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
    .status-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .status-error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    .status-processing { background-color: #cff4fc; color: #055160; border: 1px solid #b6effb; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<div class="container-fluid">
    <div class="scanner-container">
        <div class="text-center mb-4">
            <i class="fas fa-qrcode" style="font-size: 48px; color: #1cc1ba;"></i>
            <h2 class="mt-2">Attendance Scanner</h2>
        </div>
        
        <div class="mb-3">
            <label for="class_id_selector" class="form-label"><b>1. Select Class for Today (<?= $today_day_name ?>)</b></label>
            <select id="class_id_selector" class="form-select form-select-lg form-control">
                <option value="">-- Please select a class --</option>
                <?php
                if ($classes_result && $classes_result->num_rows > 0) {
                    while ($class = $classes_result->fetch_assoc()) {
                        $display_text = htmlspecialchars($class['level_name'] . ' - ' . $class['subject_name'] . ' (' . $class['teacher_name'] . ')');
                        echo "<option value='{$class['id']}'>{$display_text}</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div id="scanner-wrapper" class="mt-4" style="display: none;">
            <label class="form-label"><b>2. Scan Student's QR Code</b></label>
            <div id="qr-reader"></div>
        </div>

        <div id="scan_result" class="status-message"></div>
    </div>
</div>

<!-- NEW, MORE RELIABLE QR SCANNER LIBRARY -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const classSelector = document.getElementById('class_id_selector');
    const scannerWrapper = document.getElementById('scanner-wrapper');
    const resultContainer = document.getElementById('scan_result');
    let html5QrCode = null;
    let lastScannedResult = null;
    let scanTimeout = null;

    classSelector.addEventListener('change', function() {
        const selectedClassId = this.value;
        resultContainer.style.display = 'none';

        if (selectedClassId) {
            scannerWrapper.style.display = 'block';
            startScanner();
        } else {
            scannerWrapper.style.display = 'none';
            stopScanner();
        }
    });

    function startScanner() {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qr-reader");
        }
        
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
            .catch(err => {
                console.error("Unable to start scanning.", err);
                displayMessage("ERROR: Could not start camera. Please grant permission and refresh.", 'error');
            });
    }

    function stopScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().catch(err => console.error("Failed to stop scanner.", err));
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (decodedText === lastScannedResult) {
            return;
        }
        lastScannedResult = decodedText;
        
        clearTimeout(scanTimeout);
        scanTimeout = setTimeout(() => { lastScannedResult = null; }, 3000);

        if (navigator.vibrate) { navigator.vibrate(100); }

        processAttendance(decodedText);
    }

    function onScanFailure(error) {
        // This is called continuously, so we don't show an error message here.
        // It just means no QR code was found in the frame.
    }

    function processAttendance(studentId) {
        const classId = classSelector.value;
        if (!classId) {
            displayMessage("Please select a class first.", 'error');
            return;
        }

        displayMessage("Processing...", 'processing');

        fetch('process_scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `student_user_id=${encodeURIComponent(studentId)}&class_id=${encodeURIComponent(classId)}`
        })
        .then(response => response.json())
        .then(data => {
            displayMessage(data.message, data.status);
        })
        .catch(error => {
            console.error('Error:', error);
            displayMessage("A network error occurred.", 'error');
        });
    }

    function displayMessage(message, type) {
        resultContainer.textContent = message;
        resultContainer.className = 'status-message';
        if (type === 'success') {
            resultContainer.classList.add('status-success');
        } else if (type === 'error') {
            resultContainer.classList.add('status-error');
        } else {
            resultContainer.classList.add('status-processing');
        }
        resultContainer.style.display = 'block';
    }
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
