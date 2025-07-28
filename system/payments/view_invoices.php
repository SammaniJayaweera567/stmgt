<?php
ob_start();
// Path from system/payments/
include '../../init.php'; 

$db = dbConn();

$messages = [];

// --- Get Filter Parameters from GET request ---
// Initialize variables first to avoid 'Undefined variable' warnings
$month_year_filter = $_GET['month_year'] ?? ''; 
$status_filter = $_GET['status'] ?? ''; 
$class_id_filter = (int)($_GET['class_id'] ?? 0); 

// For the month input field default value. If no filter is set, default to current month/year.
$filter_month_year_input_value = !empty($month_year_filter) ? $month_year_filter : date('Y-m'); 

// Parse month and year from the month_year_filter
$filter_year = '';
$filter_month = '';
if (!empty($month_year_filter)) {
    list($filter_year, $filter_month) = explode('-', $month_year_filter);
}

// --- Fetch all Active Classes for the Class Filter Dropdown ---
$sql_classes = "SELECT c.id, cl.level_name, s.subject_name, ct.type_name 
                FROM classes c 
                JOIN class_levels cl ON c.class_level_id = cl.id 
                JOIN subjects s ON c.subject_id = s.id 
                JOIN class_types ct ON c.class_type_id = ct.id 
                WHERE c.status='Active' 
                ORDER BY cl.level_name, s.subject_name";
$result_classes = $db->query($sql_classes);


// --- Main SQL Query for Fetching Invoices with Dynamic Filters ---
$sql_invoices_list = "SELECT 
                        i.id, 
                        CONCAT(u.FirstName, ' ', u.LastName) as student_name,
                        sd.registration_no,
                        CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name,
                        i.payable_amount,
                        i.due_date,
                        i.status,
                        i.invoice_month,
                        i.invoice_year
                    FROM invoices i
                    JOIN users u ON i.student_user_id = u.Id
                    LEFT JOIN student_details sd ON u.Id = sd.user_id
                    JOIN classes c ON i.class_id = c.id
                    JOIN class_levels cl ON c.class_level_id = cl.id
                    JOIN subjects s ON c.subject_id = s.id
                    JOIN class_types ct ON c.class_type_id = ct.id
                    WHERE 1=1"; // Start with 1=1 to easily append conditions

// Apply filters based on GET parameters
if (!empty($filter_month) && !empty($filter_year)) {
    $sql_invoices_list .= " AND i.invoice_month = '$filter_month' AND i.invoice_year = '$filter_year'";
}
if (!empty($status_filter)) { // Use $status_filter from GET
    $sql_invoices_list .= " AND i.status = '$status_filter'";
}
if ($class_id_filter > 0) { // Apply class filter if a class is selected
    $sql_invoices_list .= " AND i.class_id = '$class_id_filter'";
}

$sql_invoices_list .= " ORDER BY i.invoice_year DESC, i.invoice_month DESC, u.FirstName ASC"; // Final ordering

$result_invoices_list = $db->query($sql_invoices_list); // Execute the query

// Handle potential query errors
if (!$result_invoices_list) {
    // Log the error for debugging
    error_log("Database error in view_invoices.php: " . $db->error);
    $messages['main_error'] = "Failed to fetch invoices. Database error occurred.";
}

?>

<style>
/* Your custom CSS styles */
body {
    font-family: sans-serif;
    background-color: #f8f9fa;
}

.container-fluid {
    padding-top: 20px;
}

.content-header-text {
    margin-bottom: 20px;
}

.card {
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
}

.card-header {
    background-color: #007bff;
    color: white;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.table thead th {
    background-color: #f2f2f2;
}

.btn-action {
    margin: 2px;
}

/* Add more styles as needed for badges, etc. based on your display_status_badge function */
</style>

<div class="container-fluid">
    <?php // show_status_message(); // If you have a global status message function ?>
    <?php if(!empty($messages['main_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($messages['main_error']) ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-file-invoice mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">View Invoices</h5>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label for="monthFilter" class="form-label fw-bold">Filter by Month & Year</label>
                        <input type="month" id="monthFilter" name="month_year" class="form-control"
                            value="<?= htmlspecialchars($filter_month_year_input_value) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="statusFilter" class="form-label fw-bold">Filter by Status</label>
                        <select name="status" id="statusFilter" class="form-select form-control">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= ($status_filter == 'Pending') ? 'selected' : '' ?>>Pending
                            </option>
                            <option value="Paid" <?= ($status_filter == 'Paid') ? 'selected' : '' ?>>Paid</option>
                            <option value="Overdue" <?= ($status_filter == 'Overdue') ? 'selected' : '' ?>>Overdue
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="classFilter" class="form-label fw-bold">Filter by Class</label>
                        <select id="classFilter" name="class_id" class="form-select form-control">
                            <option value="">All Classes</option>
                            <?php 
                                // Reset pointer for classes result set if it was used before (though not likely here)
                                if ($result_classes) {
                                    mysqli_data_seek($result_classes, 0); 
                                    while($class_row = $result_classes->fetch_assoc()) {
                                        $full_class_name = htmlspecialchars($class_row['level_name'] . ' - ' . $class_row['subject_name'] . ' (' . $class_row['type_name'] . ')');
                                        $selected_class = ($class_id_filter == $class_row['id']) ? 'selected' : '';
                                        echo "<option value='{$class_row['id']}' {$selected_class}>{$full_class_name}</option>";
                                    }
                                }
                                ?>
                        </select>
                    </div>
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
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
                            if ($result_invoices_list && $result_invoices_list->num_rows > 0) { 
                                while ($row = $result_invoices_list->fetch_assoc()) { // Use $result_invoices_list here
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
                                <a href="<?= SYS_URL ?>payments/record_payment.php?invoice_id=<?= $row['id'] ?>"
                                    class="btn btn-success btn-sm" title="Record Payment">
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
        "order": [
            [4, "desc"]
        ] // Order by due date by default
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; 
?>