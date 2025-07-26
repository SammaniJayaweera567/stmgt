<?php
ob_start();
include '../../init.php'; // Path from web/dashboard/

// 1. Security Check: Ensure a user (student or parent) is logged in
if (!isset($_SESSION['ID'])) {
    header("Location: " . WEB_URL . "auth/login.php");
    exit();
}

$db = dbConn();
$logged_in_user_id = (int)$_SESSION['ID'];
$user_role = strtolower($_SESSION['user_role_name'] ?? '');
$messages = [];

$invoice_id = (int)($_GET['id'] ?? 0);
if ($invoice_id === 0) {
    die("Invalid Invoice ID.");
}

// --- Fetch Invoice Details ---
$sql_invoice = "SELECT i.*, 
                    CONCAT(u.FirstName, ' ', u.LastName) as student_name,
                    sd.registration_no,
                    CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name
                FROM invoices i
                JOIN users u ON i.student_user_id = u.Id
                JOIN student_details sd ON i.student_user_id = sd.user_id
                JOIN classes c ON i.class_id = c.id
                JOIN class_levels cl ON c.class_level_id = cl.id 
                JOIN subjects s ON c.subject_id = s.id 
                JOIN class_types ct ON c.class_type_id = ct.id
                WHERE i.id = '$invoice_id'";
$result_invoice = $db->query($sql_invoice);

if ($result_invoice->num_rows === 0) {
    die("Invoice not found.");
}
$invoice_data = $result_invoice->fetch_assoc();
$student_id_from_invoice = $invoice_data['student_user_id'];

// --- Authorization Check ---
$is_authorized = false;
if ($user_role == 'student' && $logged_in_user_id == $student_id_from_invoice) {
    $is_authorized = true;
} elseif ($user_role == 'parent') {
    $sql_check_child = "SELECT id FROM student_guardian_relationship WHERE student_user_id = '$student_id_from_invoice' AND guardian_user_id = '$logged_in_user_id'";
    if ($db->query($sql_check_child)->num_rows > 0) {
        $is_authorized = true;
    }
}

if (!$is_authorized) {
    die("You are not authorized to view this invoice.");
}

// --- Handle Bank Slip Upload (Only for Parents) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_role == 'parent') {
    
    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
        
        $target_dir = "../uploads/slips/";
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                $messages['main_error'] = "Critical Error: Failed to create the upload directory. Please contact admin.";
            }
        }

        if (empty($messages)) {
            $file_name_raw = basename($_FILES['payment_slip']['name']);
            $file_ext = strtolower(pathinfo($file_name_raw, PATHINFO_EXTENSION));
            $unique_file_name = "slip_" . $invoice_id . '_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $unique_file_name;

            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($file_ext, $allowed_types)) {
                $messages['main_error'] = "Invalid file type. Only JPG, PNG, or PDF are allowed.";
            } elseif ($_FILES['payment_slip']['size'] > 5 * 1024 * 1024) { // Max 5MB
                $messages['main_error'] = "File size exceeds 5MB limit.";
            }

            if (empty($messages)) {
                if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_file)) {
                    
                    $sql_insert_slip = "INSERT INTO payment_slips (invoice_id, student_user_id, uploaded_by_parent_id, file_name, file_path, status)
                                        VALUES ('$invoice_id', '$student_id_from_invoice', '$logged_in_user_id', '$file_name_raw', '$unique_file_name', 'Pending')";
                    
                    if ($db->query($sql_insert_slip)) {
                        $_SESSION['status_message'] = "Payment slip submitted for review successfully!";
                        
                        // **FIX:** Use the full URL for a more reliable redirect.
                        $redirect_url = WEB_URL . "web/dashboard/parent.php?child_id=" . $student_id_from_invoice;
                        header("Location: " . $redirect_url);
                        exit(); // Crucial to stop script execution after redirect
                    } else {
                        unlink($target_file); 
                        $messages['main_error'] = "Database error. Could not save the slip record.";
                    }
                } else {
                    $messages['main_error'] = "Critical Error: Could not move the uploaded file. Check server folder permissions for 'web/uploads/slips/'.";
                }
            }
        } elseif ($_FILES['payment_slip']['error'] === UPLOAD_ERR_NO_FILE) {
            $messages['main_error'] = "Please select a file to upload.";
        } else {
            $messages['main_error'] = "An unexpected error occurred during file upload.";
        }
    } else {
        $messages['main_error'] = "No file was received. The form might be incorrect.";
    }
}


// --- Generate QR Code for the Invoice ID ---
include '../../qr_gen/qrlib.php';
$qr_path = '../../qr_codes/invoices/';
if (!file_exists($qr_path)) { mkdir($qr_path, 0777, true); }
$qr_data = "InvoiceID:" . $invoice_id; // Data to be encoded
$filename = $qr_path . 'invoice_' . $invoice_id . '.png';
QRcode::png($qr_data, $filename, 'L', 4, 2);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= htmlspecialchars($invoice_id) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: sans-serif; }
        .invoice-container { max-width: 800px; margin: 40px auto; background: #fff; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .invoice-header { background-color: #343a40; color: white; padding: 20px; border-top-left-radius: 15px; border-top-right-radius: 15px; }
        .invoice-body { padding: 30px; }
        .invoice-details-table { width: 100%; margin-bottom: 20px; }
        .invoice-details-table td { padding: 5px 0; }
        .payment-instructions { border: 1px dashed #ced4da; padding: 20px; border-radius: 10px; margin-top: 30px; }
        .qr-code-section { text-align: center; }
        .qr-code-section img { border: 5px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h2>Invoice Details</h2>
        </div>
        <div class="invoice-body">
            <?php if(!empty($messages['main_error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($messages['main_error']) ?></div>
            <?php endif; ?>

            <h4>Invoice for: <?= htmlspecialchars($invoice_data['student_name']) ?></h4>
            <table class="invoice-details-table">
                <tr><td><strong>Class:</strong></td><td><?= htmlspecialchars($invoice_data['class_full_name']) ?></td></tr>
                <tr><td><strong>Month:</strong></td><td><?= date('F Y', mktime(0, 0, 0, $invoice_data['invoice_month'], 1, $invoice_data['invoice_year'])) ?></td></tr>
                <tr><td><strong>Status:</strong></td><td><?= display_status_badge($invoice_data['status']) ?></td></tr>
                <tr style="font-size: 1.2rem; font-weight: bold;"><td><strong>Amount Due:</strong></td><td>LKR <?= htmlspecialchars(number_format($invoice_data['payable_amount'], 2)) ?></td></tr>
            </table>
            
            <hr>

            <div class="row">
                <div class="col-md-6">
                    <div class="payment-instructions">
                        <h5>Payment Instructions</h5>
                        <p>Please use one of the following methods to pay the class fee.</p>
                        <h6>1. Physical Payment (at office)</h6>
                        <p>Show this invoice or the QR code at the office counter to make a cash payment.</p>
                        <h6>2. Bank Transfer</h6>
                        <p>
                            <strong>Bank Name:</strong> Sampath Bank<br>
                            <strong>Account Name:</strong> TKSALAVA Institute<br>
                            <strong>Account No:</strong> 1234 5678 9012<br>
                            <strong>Reference:</strong> <?= htmlspecialchars($invoice_data['registration_no']) ?>-<?= date('My', mktime(0, 0, 0, $invoice_data['invoice_month'], 1)) ?>
                        </p>
                    </div>
                </div>
                <div class="col-md-6 qr-code-section">
                    <h5>Scan for Physical Payment</h5>
                    <img src="<?= WEB_URL ?>qr_codes/invoices/<?= 'invoice_' . $invoice_id . '.png' ?>" alt="Invoice QR Code">
                </div>
            </div>

            <?php if ($user_role == 'parent' && ($invoice_data['status'] == 'Pending' || $invoice_data['status'] == 'Overdue')): ?>
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload Bank Transfer Slip</h5>
                </div>
                <div class="card-body">
                    <p>If you have paid via bank transfer, please upload a clear image of the payment slip here for confirmation.</p>
                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $invoice_id ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="payment_slip" class="form-label">Payment Slip Image <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" id="payment_slip" name="payment_slip" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit for Review</button>
                    </form>
                </div>
            </div>
            <?php elseif ($user_role == 'student' && ($invoice_data['status'] == 'Pending' || $invoice_data['status'] == 'Overdue')): ?>
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i>
                Please ask your parent/guardian to log in to their dashboard to upload the payment slip after a bank transfer.
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="javascript:history.back()" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$content = ob_get_clean();
// This page is standalone
echo $content;
?>
