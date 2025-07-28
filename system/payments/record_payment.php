<?php
ob_start();
// Path from system/payments/
include '../../init.php'; 

$db = dbConn();
$messages = [];

// --- Get Invoice ID from URL ---
$invoice_id = (int)($_GET['invoice_id'] ?? 0);
if ($invoice_id === 0) {
    header("Location: view_invoices.php?status=error&message=Invoice ID not provided.");
    exit();
}

// --- Fetch Invoice Details ---
$sql_invoice = "SELECT i.id as invoice_id, i.payable_amount, CONCAT(u.FirstName, ' ', u.LastName) as student_name, sd.registration_no, 
                       CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_name -- UPDATED
                FROM invoices i
                JOIN users u ON i.student_user_id = u.Id
                JOIN student_details sd ON i.student_user_id = sd.user_id
                JOIN classes c ON i.class_id = c.id
                JOIN class_levels cl ON c.class_level_id = cl.id 
                JOIN subjects s ON c.subject_id = s.id 
                JOIN class_types ct ON c.class_type_id = ct.id -- NEW: Join class_types table
                WHERE i.id = '$invoice_id' AND i.status IN ('Pending', 'Overdue')";
$result_invoice = $db->query($sql_invoice);
if ($result_invoice->num_rows === 0) {
    header("Location: view_invoices.php?status=error&message=Invoice not found or already paid.");
    exit();
}
$invoice_data = $result_invoice->fetch_assoc();

// --- Handle Payment Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $paid_amount = dataClean($_POST['paid_amount'] ?? 0);
    $payment_method = dataClean($_POST['payment_method'] ?? '');
    $transaction_date = dataClean($_POST['transaction_date'] ?? '');
    $recorded_by = $_SESSION['user_id'] ?? null;

    if (empty($paid_amount) || empty($payment_method) || empty($transaction_date)) {
        $messages['main_error'] = "Please fill all required fields.";
    } else {
        // 1. Insert into payments table
        $transaction_datetime = $transaction_date . ' ' . date('H:i:s');
        $sql_insert_payment = "INSERT INTO payments (invoice_id, paid_amount, payment_method, transaction_date, recorded_by) VALUES ('$invoice_id', '$paid_amount', '$payment_method', '$transaction_datetime', '$recorded_by')";
        
        if ($db->query($sql_insert_payment)) {
            // 2. If payment is recorded, update invoice status
            $sql_update_invoice = "UPDATE invoices SET status = 'Paid' WHERE id = '$invoice_id'";
            if ($db->query($sql_update_invoice)) {
                $_SESSION['status_message'] = "Payment recorded successfully!";
                header("Location: view_invoices.php");
                exit();
            } else {
                 $messages['main_error'] = "Payment recorded, but failed to update invoice status.";
            }
        } else {
            $messages['main_error'] = "Failed to record payment.";
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-cash-register mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Record Payment</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
             <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Invoice Details</h3></div>
                <div class="card-body">
                    <p><strong>Student:</strong> <?= htmlspecialchars($invoice_data['student_name']) ?> (<?= htmlspecialchars($invoice_data['registration_no']) ?>)</p>
                    <p><strong>Class:</strong> <?= htmlspecialchars($invoice_data['class_name']) ?></p>
                    <p><strong>Amount to Pay:</strong> <strong class="text-danger fs-5">LKR <?= htmlspecialchars(number_format($invoice_data['payable_amount'], 2)) ?></strong></p>
                </div>
            </div>

            <div class="card card-success mt-4">
                <div class="card-header"><h3 class="card-title">Payment Information</h3></div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?invoice_id=<?= $invoice_id ?>">
                    <div class="card-body">
                        <?php if(!empty($messages['main_error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($messages['main_error']) ?></div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="form-group col-md-6 mb-3"><label>Paid Amount (LKR)</label><input type="number" step="0.01" name="paid_amount" class="form-control" value="<?= htmlspecialchars($invoice_data['payable_amount']) ?>" required></div>
                            <div class="form-group col-md-6 mb-3"><label>Payment Method</label><select name="payment_method" class="form-select form-control" required><option value="">Select</option><option value="Cash">Cash</option><option value="Card">Bank Trasfer</option></select></div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 mb-3"><label>Transaction Date</label><input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Confirm & Record Payment</button>
                        <a href="view_invoices.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php'; 
?>