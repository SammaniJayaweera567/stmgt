<?php
ob_start();
include '../../init.php';

if (!isset($_SESSION['ID'])) {
    header("Location:../login.php");
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-arrow-alt-circle-right" style="font-size: 20px;"></i>
            <h5 class="w-auto">Income List</h5>
        </div>

        <div class="col-12 mt-3">
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-left">
                    <button onclick="printIncomeTable()" class="print-button btn btn-sm" style="background: #f1c94d;">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="incomeTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Payment Method</th>
                                    <th>Amount (LKR)</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Service Payment</td>
                                    <td>Haircut appointment</td>
                                    <td><span class="badge bg-success">Cash</span></td>
                                    <td>2,500.00</td>
                                    <td>2025-06-25</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Product Sale</td>
                                    <td>Shampoo bottle</td>
                                    <td><span class="badge bg-primary">Card</span></td>
                                    <td>1,200.00</td>
                                    <td>2025-06-24</td>
                                </tr>
                                <!-- More dynamic rows -->
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables Init and Print Script -->
<script>
$(document).ready(function() {
    $('#incomeTable').DataTable({
        responsive: true,
        lengthMenu: [5, 10, 25, 50, 100],
        pageLength: 10,
        language: {
            searchPlaceholder: "Search income records...",
            search: "",
            lengthMenu: "Show _MENU_ entries"
        }
    });
});

// Print functionality
function printIncomeTable() {
    let divToPrint = document.getElementById("incomeTable");
    let newWin = window.open("");
    newWin.document.write(`<html><head><title>Income List</title>`);
    newWin.document.write(
        `<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">`);
    newWin.document.write(`</head><body>`);
    newWin.document.write("<h4 class='text-center mb-4'>Income List</h4>");
    newWin.document.write(divToPrint.outerHTML);
    newWin.document.write(`</body></html>`);
    newWin.print();
    newWin.close();
}
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>