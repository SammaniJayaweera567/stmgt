<?php
ob_start();
include '../../init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-arrow-alt-circle-right" style="font-size: 20px;"></i>
            <h5 class="mb-5 w-auto">Manage Expenses</h5>
        </div>
        <div class="col-12 mt-3">
            <ul class="nav nav-tabs" id="expenseTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list"
                        type="button" role="tab">
                        <i class="fas fa-list me-1"></i> Expense List
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button"
                        role="tab">
                        <i class="fas fa-plus-circle me-1"></i> Add Expense
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="expenseTabsContent">
                <!-- Expense List Tab -->
                <div class="tab-pane fade show active mt-3" id="list" role="tabpanel">
                    <div class="card mt-5">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="expenseTable" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>EXP001</td>
                                            <td>Office Supplies</td>
                                            <td>Office Expenses</td>
                                            <td>15,250.00</td>
                                            <td>Cash</td>
                                            <td>2023-10-15</td>
                                            <td><span class="badge bg-success">Paid</span></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit_expense.php?id=1" class="btn btn-sm btn-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(1)" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>EXP002</td>
                                            <td>Software Subscription</td>
                                            <td>Technology</td>
                                            <td>42,500.00</td>
                                            <td>Credit Card</td>
                                            <td>2023-10-12</td>
                                            <td><span class="badge bg-warning">Pending</span></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit_expense.php?id=2" class="btn btn-sm btn-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(2)" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Expense Tab -->
                <div class="tab-pane fade" id="add" role="tabpanel">
                    <div class="col-md-12 mt-5">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add New Expense</h3>
                            </div>
                            <form method="post" action="save_expense.php" class="p-4">
                                <div class="card-body">

                                    <!-- ========== EXPENSE DETAILS SECTION ========== -->
                                    <div class="card mb-4 border-primary">
                                        <div class="card-header bg-light">
                                            <h5 class="detail-header mb-0" style="color: #037e7d; font-size: 16px; font-weight: 600;">
                                                <i class="fas fa-file-invoice-dollar me-2 mr-2" style="color: #037e7d;"></i>Expense Details
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="title" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                                        <select class="form-control" name="category" required>
                                                            <option value="">-- Select Category --</option>
                                                            <option value="Office Expenses">Office Expenses</option>
                                                            <option value="Technology">Technology</option>
                                                            <option value="Travel">Travel</option>
                                                            <option value="Utilities">Utilities</option>
                                                            <option value="Salaries">Salaries</option>
                                                            <option value="Maintenance">Maintenance</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Amount (LKR) <span class="text-danger">*</span></label>
                                                        <input type="number" step="0.01" class="form-control" name="amount" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                                        <select class="form-control" name="payment_method" required>
                                                            <option value="">-- Select Method --</option>
                                                            <option value="Cash">Cash</option>
                                                            <option value="Credit Card">Credit Card</option>
                                                            <option value="Bank Transfer">Bank Transfer</option>
                                                            <option value="Cheque">Cheque</option>
                                                            <option value="Online Payment">Online Payment</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" name="date" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                                        <select class="form-control" name="status" required>
                                                            <option value="">-- Select Status --</option>
                                                            <option value="Paid">Paid</option>
                                                            <option value="Pending">Pending</option>
                                                            <option value="Rejected">Rejected</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ========== ADDITIONAL DETAILS SECTION ========== -->
                                    <div class="card mb-4 border-success">
                                        <div class="card-header bg-light">
                                            <h5 class="detail-header mb-0" style="color: #037e7d; font-size: 16px; font-weight: 600;">
                                                <i class="fas fa-info-circle me-2 mr-2" style="color: #037e7d;"></i>Additional Details
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="3"></textarea>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Receipt Number</label>
                                                        <input type="text" class="form-control" name="receipt_number">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Attach Receipt</label>
                                                        <input type="file" class="form-control" name="receipt_file">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Form Footer with Submit Button -->
                                <div class="card-footer bg-light">
                                    <button type="submit" class="btn btn-primary px-4 py-2">
                                       Save Expense
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary px-4 py-2 ms-2">
                                        Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables Init -->
<script>
$(document).ready(function() {
    $('#expenseTable').DataTable({
        responsive: true,
        lengthMenu: [5, 10, 25, 50, 100],
        pageLength: 10,
        language: {
            searchPlaceholder: "Search expenses...",
            search: "",
            lengthMenu: "Show _MENU_ entries"
        },
        order: [[5, 'desc']] // Sort by date descending
    });
});

function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this expense?")) {
        window.location = 'delete_expense.php?id=' + id;
    }
}
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>