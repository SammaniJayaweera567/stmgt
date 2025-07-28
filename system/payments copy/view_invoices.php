<?php
ob_start();
// Path from system/payments/
include '../../init.php'; 

$db = dbConn();

// --- Step 1: Handle Filters ---
$filter_month_year = isset($_GET['month']) ? dataClean($_GET['month']) : date('Y-m');
$filter_status = isset($_GET['status']) ? dataClean($_GET['status']) : '';

// --- Step 2: Build SQL Query based on Filters ---
$sql = "SELECT 
            i.id as invoice_id,
            i.payable_amount,
            i.due_date,
            i.status,
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
        WHERE 1=1"; // Start WHERE clause

if (!empty($filter_month_year)) {
    $date_parts = explode('-', $filter_month_year);
    $filter_year = (int)$date_parts[0];
    $filter_month = (int)$date_parts[1];
    $sql .= " AND i.invoice_year = '$filter_year' AND i.invoice_month = '$filter_month'";
}

if (!empty($filter_status)) {
    $sql .= " AND i.status = '$filter_status'";
}

$sql .= " ORDER BY i.due_date DESC, student_name ASC";

$result_invoices = $db->query($sql);

?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-file-invoice mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">View Invoices</h5>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label for="month" class="form-label fw-bold">Filter by Month & Year</label>
                        <input type="month" id="month" name="month" class="form-control" value="<?= htmlspecialchars($filter_month_year) ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="status" class="form-label fw-bold">Filter by Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">-- All Statuses --</option>
                            <option value="Pending" <?= ($filter_status == 'Pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="Paid" <?= ($filter_status == 'Paid') ? 'selected' : '' ?>>Paid</option>
                            <option value="Overdue" <?= ($filter_status == 'Overdue') ? 'selected' : '' ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Invoices List</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoicesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Reg. No</th>
                            <th>Class</th>
                            <th>Payable Amount (LKR)</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_invoices && $result_invoices->num_rows > 0) {
                            while ($row = $result_invoices->fetch_assoc()) {
                        ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><?= htmlspecialchars($row['registration_no']) ?></td>
                                    <td><?= htmlspecialchars($row['class_full_name']) ?></td>
                                    <td class="text-end"><?= htmlspecialchars(number_format($row['payable_amount'], 2)) ?></td>
                                    <td><?= htmlspecialchars($row['due_date']) ?></td>
                                    <td><?= display_status_badge($row['status']) ?></td>
                                    <td class="text-center">
                                        <?php if ($row['status'] == 'Pending' || $row['status'] == 'Overdue'): ?>
                                            <a href="record_payment.php?invoice_id=<?= $row['invoice_id'] ?>" class="btn btn-success btn-sm" title="Record Payment">
                                                <i class="fas fa-dollar-sign"></i> Record Payment
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">No invoices found for the selected filters.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() { 
        $('#invoicesTable').DataTable({
            "order": [[ 4, "desc" ]] // Order by due date by default
        });
    });
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; 
?>
