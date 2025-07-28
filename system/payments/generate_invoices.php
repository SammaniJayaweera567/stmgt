<?php
ob_start();
// Path from system/payments/
include '../../init.php'; 

$db = dbConn();
$messages = [];
$report = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $invoice_month_year = dataClean($_POST['invoice_month'] ?? '');
    
    if (!empty($invoice_month_year)) {
        $date_parts = explode('-', $invoice_month_year);
        $invoice_year = (int)$date_parts[0];
        $invoice_month = (int)$date_parts[1];

        // --- 1. Get all active enrollments for EXISTING students ---
        // FIXED: Added a JOIN to the 'users' table to ensure the student exists.
        $sql_enrollments = "SELECT e.student_user_id, e.class_id, c.class_fee 
                            FROM enrollments e
                            JOIN classes c ON e.class_id = c.id
                            JOIN users u ON e.student_user_id = u.Id 
                            WHERE e.status = 'active'";
        $result_enrollments = $db->query($sql_enrollments);

        $generated_count = 0;
        $existed_count = 0;
        $error_count = 0;

        if ($result_enrollments && $result_enrollments->num_rows > 0) {
            while ($enrollment = $result_enrollments->fetch_assoc()) {
                $student_id = $enrollment['student_user_id'];
                $class_id = $enrollment['class_id'];
                $base_fee = $enrollment['class_fee'];

                // --- 2. Check if an invoice already exists ---
                $sql_check = "SELECT id FROM invoices WHERE student_user_id = '$student_id' AND class_id = '$class_id' AND invoice_month = '$invoice_month' AND invoice_year = '$invoice_year'";
                if ($db->query($sql_check)->num_rows > 0) {
                    $existed_count++;
                    continue; // Skip to the next enrollment
                }

                // --- 3. Check for any active discounts ---
                $discount_applied = 0;
                $payable_amount = $base_fee;

                $sql_discount = "SELECT dt.value_logic, sd.discount_value 
                                 FROM student_discounts sd
                                 JOIN discount_types dt ON sd.discount_type_id = dt.id
                                 WHERE sd.student_user_id = '$student_id' AND sd.class_id = '$class_id' AND sd.status = 'Active'";
                $result_discount = $db->query($sql_discount);

                if ($result_discount && $result_discount->num_rows > 0) {
                    $discount = $result_discount->fetch_assoc();
                    $logic = $discount['value_logic'];
                    $value = $discount['discount_value'];

                    if ($logic == 'Percentage') {
                        $discount_applied = ($base_fee * $value) / 100;
                    } elseif ($logic == 'Fixed Amount') {
                        $discount_applied = $value;
                    }
                    $payable_amount = $base_fee - $discount_applied;
                }
                
                if ($payable_amount < 0) { $payable_amount = 0; }

                // --- 4. Create the invoice ---
                $due_date = date('Y-m-t', strtotime($invoice_month_year));
                $sql_insert = "INSERT INTO invoices (student_user_id, class_id, invoice_month, invoice_year, base_fee, discount_applied, payable_amount, due_date, status)
                               VALUES ('$student_id', '$class_id', '$invoice_month', '$invoice_year', '$base_fee', '$discount_applied', '$payable_amount', '$due_date', 'Pending')";
                
                if ($db->query($sql_insert)) {
                    $generated_count++;
                } else {
                    $error_count++;
                }
            }
        }
        $report = ['generated' => $generated_count, 'existed' => $existed_count, 'errors' => $error_count];
    } else {
        $messages['main_error'] = "Please select a month to generate invoices.";
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-file-invoice-dollar mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Generate Monthly Invoices</h5>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Invoice Generation Tool</h3></div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="card-body">
                <?php if(!empty($messages['main_error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($messages['main_error']) ?></div>
                <?php endif; ?>
                <p class="text-muted">Select a month and year to generate invoices for all actively enrolled students. The system will automatically apply any valid discounts and skip existing invoices.</p>
                <div class="row">
                    <div class="form-group col-md-6 mb-3">
                        <label for="invoice_month">Select Month & Year <span class="text-danger">*</span></label>
                        <input type="month" id="invoice_month" name="invoice_month" class="form-control" value="<?= date('Y-m') ?>">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-cogs me-2"></i>Generate Invoices</button>
            </div>
        </form>
    </div>

    <?php if (!empty($report)): ?>
    <div class="card mt-4">
        <div class="card-header"><h3 class="card-title">Generation Report for <?= date('F Y', strtotime($invoice_month_year)) ?></h3></div>
        <div class="card-body">
            <div class="alert alert-success">
                <h4 class="alert-heading">Process Complete!</h4>
                <p><strong><i class="fas fa-check-circle text-success"></i> New Invoices Generated:</strong> <?= $report['generated'] ?></p>
                <p><strong><i class="fas fa-info-circle text-info"></i> Previously Existing Invoices Skipped:</strong> <?= $report['existed'] ?></p>
                <?php if ($report['errors'] > 0): ?>
                    <p class="mb-0 text-danger"><strong><i class="fas fa-exclamation-triangle text-danger"></i> Errors Encountered:</strong> <?= $report['errors'] ?></p>
                <?php endif; ?>
            </div>
            <a href="view_invoices.php" class="btn btn-info">View All Invoices</a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include '../layouts.php'; 
?>