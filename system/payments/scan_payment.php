<?php
ob_start();
include '../../init.php';

// --- Security Check ---
// Ensure only authorized users (e.g., Admin, Card Checker) can access this page.
// if (!isset($_SESSION['ID'])) {
//     header("Location: ../login.php");
//     exit();
// }
?>

<!-- Page-specific CSS styles -->
<style>
    .scanner-container {
        max-width: 500px;
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
    .status-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .status-error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    .status-processing { background-color: #cff4fc; color: #055160; border: 1px solid #b6effb; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<div class="container-fluid">
    <div class="scanner-container">
        <div class="text-center mb-4">
            <i class="fas fa-qrcode" style="font-size: 48px; color: #1cc1ba;"></i>
            <h2 class="mt-2">Payment QR Scanner</h2>
        </div>
        
        <div id="scanner-wrapper" class="mt-4">
            <label class="form-label"><b>Scan Invoice QR Code</b></label>
            <div id="qr-reader"></div>
        </div>

        <div id="scan_result" class="status-message"></div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const resultContainer = document.getElementById('scan_result');
    let html5QrCode = new Html5Qrcode("qr-reader");
    let lastScannedResult = null;
    let scanTimeout = null;

    function onScanSuccess(decodedText, decodedResult) {
        // Prevent multiple submissions from a single long scan
        if (decodedText === lastScannedResult) {
            return;
        }
        lastScannedResult = decodedText;
        clearTimeout(scanTimeout);
        scanTimeout = setTimeout(() => { lastScannedResult = null; }, 3000); // Reset after 3 seconds

        if (navigator.vibrate) { navigator.vibrate(100); }

        processPayment(decodedText);
    }

    function onScanFailure(error) {
        // This is called continuously, so we don't show an error here.
    }

    html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 250 } }, onScanSuccess, onScanFailure)
        .catch(err => {
            console.error("Unable to start scanning.", err);
            displayMessage("ERROR: Could not start camera.", 'error');
        });

    function processPayment(qrData) {
        // We expect the QR data to be in the format "InvoiceID:123"
        if (!qrData.startsWith("InvoiceID:")) {
            displayMessage("Invalid QR Code. Please scan a valid invoice QR code.", 'error');
            return;
        }
        
        const invoiceId = qrData.split(':')[1];

        displayMessage("Processing...", 'processing');

        fetch('process_payment_scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `invoice_id=${encodeURIComponent(invoiceId)}`
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

<!-- =============================================================================================== -->

<!-- FILE: system/payments/process_payment_scan.php -->
<?php
header('Content-Type: application/json');
include '../../init.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// --- Security Check ---
if (!isset($_SESSION['ID'])) {
    $response['message'] = 'Authentication failed. Please log in again.';
    echo json_encode($response);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$db = dbConn();

// --- Data Validation ---
$invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
$recorded_by = (int)$_SESSION['ID'];

if ($invoice_id <= 0) {
    $response['message'] = 'Invalid Invoice ID received from scanner.';
    echo json_encode($response);
    exit();
}

// --- Core Logic ---

// 1. Fetch invoice details and check if it's pending or overdue
$sql_invoice = "SELECT payable_amount, status, student_user_id FROM invoices WHERE id = '$invoice_id'";
$result_invoice = $db->query($sql_invoice);

if (!$result_invoice || $result_invoice->num_rows == 0) {
    $response['message'] = 'Error: Invoice not found in the system.';
    echo json_encode($response);
    exit();
}

$invoice_data = $result_invoice->fetch_assoc();
$invoice_status = $invoice_data['status'];
$paid_amount = $invoice_data['payable_amount'];
$student_id = $invoice_data['student_user_id'];

if ($invoice_status == 'Paid') {
    $response['status'] = 'error';
    $response['message'] = 'Already Paid: This invoice has already been marked as paid.';
    echo json_encode($response);
    exit();
}

// --- All checks passed, record the payment ---
$db->begin_transaction();
try {
    // 1. Insert into payments table
    $sql_insert_payment = "INSERT INTO payments (invoice_id, paid_amount, payment_method, transaction_date, recorded_by)
                           VALUES ('$invoice_id', '$paid_amount', 'QR', NOW(), '$recorded_by')";
    if (!$db->query($sql_insert_payment)) {
        throw new Exception("Failed to record payment: " . $db->error);
    }

    // 2. Update invoice status to 'Paid'
    $sql_update_invoice = "UPDATE invoices SET status = 'Paid' WHERE id = '$invoice_id'";
    if (!$db->query($sql_update_invoice)) {
        throw new Exception("Failed to update invoice status: " . $db->error);
    }

    $db->commit();
    
    // Get student name for a friendly message
    $sql_student_name = "SELECT FirstName FROM users WHERE Id = '$student_id'";
    $name_result = $db->query($sql_student_name);
    $student_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['FirstName'] : 'Student';

    $response['status'] = 'success';
    $response['message'] = 'Success! Payment of LKR ' . number_format($paid_amount, 2) . ' recorded for ' . htmlspecialchars($student_name) . '.';

} catch (Exception $e) {
    $db->rollback();
    $response['message'] = 'Database Transaction Failed: ' . $e->getMessage();
}

// Return the final response.
echo json_encode($response);
exit();
?>
