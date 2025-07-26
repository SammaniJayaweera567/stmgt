<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../../init.php';

// Security: Only allow 'Card Checker' or 'Admin'
if (!isset($_SESSION['ID'])) {
    header("Location: ../../auth/login.php");
    exit();
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
        max-width: 600px;
        margin: 20px auto;
        padding: 30px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    #preview {
        width: 100%;
        border-radius: 8px;
        border: 2px solid #eee;
        background-color: #000;
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
            <select id="class_id_selector" class="form-select form-select-lg">
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
            <video id="preview" playsinline></video>
        </div>

        <div id="scan_result" class="status-message"></div>
    </div>
</div>

<!-- This page requires the instascan.min.js library -->
<script src="../../qr_scanner/instascan.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const classSelector = document.getElementById('class_id_selector');
    const scannerWrapper = document.getElementById('scanner-wrapper');
    const resultContainer = document.getElementById('scan_result');
    let scanner = null;
    let lastScannedResult = null;
    let scanTimeout = null;

    // Initialize the scanner object
    scanner = new Instascan.Scanner({ video: document.getElementById('preview'), scanPeriod: 5, mirror: false });

    scanner.addListener('scan', function (content) {
        // Prevent multiple submissions for the same scan within a short time
        if (content === lastScannedResult) {
            return;
        }
        lastScannedResult = content;
        
        // Reset the last scanned result after 3 seconds to allow re-scanning if needed
        clearTimeout(scanTimeout);
        scanTimeout = setTimeout(() => { lastScannedResult = null; }, 3000);

        // Give feedback to the user (e.g., phone vibration)
        if (navigator.vibrate) { navigator.vibrate(100); }

        processAttendance(content);
    });

    classSelector.addEventListener('change', function() {
        const selectedClassId = this.value;
        resultContainer.style.display = 'none'; // Hide previous messages

        if (selectedClassId) {
            scannerWrapper.style.display = 'block';
            startScanner();
        } else {
            scannerWrapper.style.display = 'none';
            stopScanner();
        }
    });

    function startScanner() {
        Instascan.Camera.getCameras().then(function (cameras) {
            if (cameras.length > 0) {
                // Use the back camera if available (usually the second camera, index 1)
                // If only one camera, it uses that (index 0)
                scanner.start(cameras.length > 1 ? cameras[1] : cameras[0]);
            } else {
                console.error('No cameras found.');
                displayMessage('Error: No cameras found on this device.', 'error');
            }
        }).catch(function (e) {
            console.error(e);
            displayMessage('Error: Could not access camera. Please grant permission.', 'error');
        });
    }

    function stopScanner() {
        if(scanner) {
            scanner.stop();
        }
    }

    function processAttendance(studentId) {
        const classId = classSelector.value;
        if (!classId) {
            displayMessage("Please select a class first.", 'error');
            return;
        }

        displayMessage("Processing...", 'processing');

        // Send the scanned data to the backend using AJAX (fetch API)
        fetch('process_scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `student_user_id=${encodeURIComponent(studentId)}&class_id=${encodeURIComponent(classId)}`
        })
        .then(response => response.json())
        .then(data => {
            displayMessage(data.message, data.status); // Display success or error from backend
        })
        .catch(error => {
            console.error('Error:', error);
            displayMessage("A network error occurred. Please check your connection.", 'error');
        });
    }

    function displayMessage(message, type) {
        resultContainer.textContent = message;
        resultContainer.className = 'status-message'; // Reset classes
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
