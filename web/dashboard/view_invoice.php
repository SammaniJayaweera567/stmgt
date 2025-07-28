<?php
ob_start();
include '../../init.php'; 

// --- 1. Security & Initial Setup ---
if (!isset($_SESSION['user_id'])) {
    header("Location: " . WEB_URL . "auth/login.php");
    exit();
}
$db = dbConn();
$logged_in_user_id = (int)$_SESSION['user_id'];
$user_role = strtolower($_SESSION['user_role_name'] ?? '');
$messages = [];
$invoice_id = (int)($_GET['id'] ?? 0);

if ($invoice_id === 0) {
    die("Error: Invalid Invoice ID provided.");
}

// --- 2. Fetch Invoice Details from Database ---
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
    die("Error: The requested invoice was not found.");
}
$invoice_data = $result_invoice->fetch_assoc();
$student_id_from_invoice = $invoice_data['student_user_id'];

// --- 3. Authorization Check: Is the logged-in user allowed to see this? ---
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
    die("Access Denied: You are not authorized to view this invoice.");
}

// --- 4. Handle Bank Slip Upload (Only for Parents) ---
// --- 4. Handle Bank Slip Upload (Only for Parents) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_role == 'parent') {
    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/slips/";
        $file_name_raw = basename($_FILES['payment_slip']['name']);
        $file_ext = strtolower(pathinfo($file_name_raw, PATHINFO_EXTENSION));
        // Using $invoice_id as part of the unique file name for easier lookup and uniqueness
        $unique_file_name = "slip_" . $invoice_id . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $unique_file_name;
        
        // Ensure the directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_file)) {
            // Path to save in DB (relative to WEB_URL)
            $file_path_for_db = "uploads/slips/" . $unique_file_name; 

            $sql_insert_slip = "INSERT INTO payment_slips (invoice_id, student_user_id, uploaded_by_parent_id, file_name, file_path, status)
                                VALUES ('$invoice_id', '$student_id_from_invoice', '$logged_in_user_id', '$file_name_raw', '$file_path_for_db', 'Pending')";
            if ($db->query($sql_insert_slip)) {
                // --- NEW CODE: Update the invoice status to 'Pending' ---
                $sql_update_invoice_status = "UPDATE invoices SET status = 'Pending' WHERE id = '$invoice_id'";
                if ($db->query($sql_update_invoice_status)) {
                    // Redirect to parent dashboard to show success message
                    $_SESSION['status_message'] = "Payment slip uploaded and invoice status updated to Pending successfully!";
                    header("Location: " . WEB_URL . "dashboard/parent.php?child_id=" . $student_id_from_invoice . "&status=slip_success");
                    exit();
                } else {
                    // Handle error if invoice status update fails (and log it)
                    error_log("Database error updating invoice status for invoice_id: $invoice_id - " . $db->error);
                    $messages['main_error'] = "Payment slip uploaded, but failed to update invoice status. Please contact support.";
                }
            } else {
                // Handle error if payment slip insertion fails (and log it)
                error_log("Database error inserting payment slip for invoice_id: $invoice_id - " . $db->error);
                $messages['main_error'] = "Database error: Could not save the slip record.";
            }
        } else {
            $messages['main_error'] = "Error: Could not upload the file. Please check folder permissions.";
        }
    } else if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] !== UPLOAD_ERR_NO_FILE) {
         $messages['main_error'] = "File upload error: " . $_FILES['payment_slip']['error'];
    } else {
        // No file was selected, or other generic POST issues
        $messages['main_error'] = "Please select a file to upload.";
    }
}

// --- 5. Generate QR Code for the Invoice ID ---
include '../../qr_gen/qrlib.php';
$qr_path = '../../qr_codes/invoices/';
if (!file_exists($qr_path)) { mkdir($qr_path, 0777, true); }
$qr_data = "InvoiceID:" . $invoice_id;
$filename = $qr_path . 'invoice_' . $invoice_id . '.png';
QRcode::png($qr_data, $filename, 'L', 4, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= htmlspecialchars($invoice_id) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: sans-serif; }
        .invoice-container { max-width: 800px; margin: 40px auto; background: #fff; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .invoice-header { background-color: #0d6efd; color: white; padding: 20px; border-top-left-radius: 15px; border-top-right-radius: 15px; }
        .invoice-body { padding: 30px; }
        .invoice-details-table { width: 100%; margin-bottom: 20px; }
        .invoice-details-table td { padding: 8px 0; }
        .payment-instructions { border: 1px dashed #ced4da; padding: 20px; border-radius: 10px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header"><h2>Invoice Details</h2></div>
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
                <div class="col-md-7">
                    <div class="payment-instructions">
                        <h5>Payment Instructions</h5>
                        <p><strong>Bank:</strong> Sampath Bank<br><strong>Account Name:</strong> Your Institute Name<br><strong>Account No:</strong> 1234 5678 9012<br><strong>Reference:</strong> <?= htmlspecialchars($invoice_data['registration_no']) ?></p>
                    </div>
                </div>
                <div class="col-md-5 text-center">
                    <h5>Scan for Office Payment</h5>
                    <img src="<?= WEB_URL ?>qr_codes/invoices/<?= 'invoice_' . $invoice_id . '.png' ?>" alt="Invoice QR Code" class="img-fluid">
                </div>
            </div>

            <?php if ($user_role == 'parent' && ($invoice_data['status'] == 'Pending' || $invoice_data['status'] == 'Overdue')): ?>
            <div class="card mt-4">
                <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload Bank Transfer Slip</h5></div>
                <div class="card-body">
                    <p>If you paid via bank transfer, please upload a clear image of the slip for confirmation.</p>
                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $invoice_id ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="payment_slip" class="form-label">Payment Slip (JPG, PNG, PDF) <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" id="payment_slip" name="payment_slip" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit for Review</button>
                    </form>
                </div>
            </div>
            <?php elseif ($user_role == 'student' && ($invoice_data['status'] == 'Pending' || $invoice_data['status'] == 'Overdue')): ?>
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i>Please ask your parent/guardian to log in to upload the payment slip.
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="javascript:history.back()" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Back</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$content = ob_get_clean();
// This is a standalone page for students/parents, so it doesn't use the admin layout
echo $content;
?>