<?php
ob_start();
// Path from system/payments/
include '../../init.php'; 

$db = dbConn();

// --- Handle Approve/Reject Actions ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $slip_id = (int)($_POST['slip_id'] ?? 0);
    $invoice_id = (int)($_POST['invoice_id'] ?? 0);
    $reviewed_by = $_SESSION['user_id'] ?? null;

    if ($slip_id > 0 && $invoice_id > 0) {
        // --- APPROVE ACTION ---
        if ($_POST['action'] == 'approve') {
            $db->begin_transaction();
            try {
                // 1. Update payment_slips table
                $sql_update_slip = "UPDATE payment_slips SET status = 'Approved', reviewed_by_user_id = '$reviewed_by', reviewed_at = NOW() WHERE id = '$slip_id'";
                if (!$db->query($sql_update_slip)) throw new Exception($db->error);

                // 2. Update invoices table
                $sql_update_invoice = "UPDATE invoices SET status = 'Paid' WHERE id = '$invoice_id'";
                if (!$db->query($sql_update_invoice)) throw new Exception($db->error);

                // 3. Create a record in the payments table
                $sql_invoice_amount = "SELECT payable_amount FROM invoices WHERE id = '$invoice_id'";
                $amount_result = $db->query($sql_invoice_amount);
                $paid_amount = $amount_result->fetch_assoc()['payable_amount'];

                $sql_insert_payment = "INSERT INTO payments (invoice_id, paid_amount, payment_method, transaction_date, recorded_by)
                                       VALUES ('$invoice_id', '$paid_amount', 'Bank Transfer', NOW(), '$reviewed_by')";
                if (!$db->query($sql_insert_payment)) throw new Exception($db->error);
                
                $db->commit();
                $_SESSION['status_message'] = "Payment slip approved and payment recorded successfully!";

            } catch (Exception $e) {
                $db->rollback();
                $_SESSION['status_message'] = "Transaction failed: " . $e->getMessage();
            }
        }
        // --- REJECT ACTION ---
        elseif ($_POST['action'] == 'reject') {
            $rejection_reason = dataClean($_POST['rejection_reason'] ?? 'No reason provided.');
            $sql_reject_slip = "UPDATE payment_slips SET status = 'Rejected', rejection_reason = '$rejection_reason', reviewed_by_user_id = '$reviewed_by', reviewed_at = NOW() WHERE id = '$slip_id'";
            
            if ($db->query($sql_reject_slip)) {
                $_SESSION['status_message'] = "Payment slip has been rejected.";
            } else {
                $_SESSION['status_message'] = "Error: Could not reject the slip.";
            }
        }
    }
    header("Location: review_slips.php");
    exit();
}

// --- Fetch Slips to Display ---
$filter_status = $_GET['status'] ?? 'Pending'; // Default to show pending slips

$sql_slips = "SELECT 
                ps.id as slip_id,
                ps.invoice_id,
                ps.file_path,
                ps.uploaded_at,
                ps.rejection_reason,
                CONCAT(u_student.FirstName, ' ', u_student.LastName) as student_name,
                CONCAT(u_parent.FirstName, ' ', u_parent.LastName) as parent_name,
                CONCAT(cl.level_name, ' - ', s.subject_name) as class_name,
                i.payable_amount,
                i.invoice_month,
                i.invoice_year
            FROM payment_slips ps
            JOIN invoices i ON ps.invoice_id = i.id
            JOIN users u_student ON i.student_user_id = u_student.Id
            JOIN users u_parent ON ps.uploaded_by_parent_id = u_parent.Id
            JOIN classes c ON i.class_id = c.id
            JOIN class_levels cl ON c.class_level_id = cl.id
            JOIN subjects s ON c.subject_id = s.id
            WHERE ps.status = '$filter_status'
            ORDER BY ps.uploaded_at ASC";

$result_slips = $db->query($sql_slips);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-receipt mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Review Payment Slips</h5>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div class="mb-3">
        <a href="review_slips.php?status=Pending" class="btn <?= $filter_status == 'Pending' ? 'btn-primary' : 'btn-outline-primary' ?>">Pending</a>
        <a href="review_slips.php?status=Approved" class="btn <?= $filter_status == 'Approved' ? 'btn-success' : 'btn-outline-success' ?>">Approved</a>
        <a href="review_slips.php?status=Rejected" class="btn <?= $filter_status == 'Rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Rejected</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= htmlspecialchars($filter_status) ?> Slips</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="slipsTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Parent</th>
                            <th>Class</th>
                            <th>Invoice For</th>
                            <th>Amount</th>
                            <th>Uploaded At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_slips && $result_slips->num_rows > 0): ?>
                            <?php while($slip = $result_slips->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($slip['student_name']) ?></td>
                                    <td><?= htmlspecialchars($slip['parent_name']) ?></td>
                                    <td><?= htmlspecialchars($slip['class_name']) ?></td>
                                    <td><?= date('F Y', mktime(0,0,0,$slip['invoice_month'],1,$slip['invoice_year'])) ?></td>
                                    <td><?= htmlspecialchars(number_format($slip['payable_amount'], 2)) ?></td>
                                    <td><?= htmlspecialchars($slip['uploaded_at']) ?></td>
                                    <td>
                                        <a href="<?= WEB_URL ?>uploads/slips/<?= htmlspecialchars($slip['file_path']) ?>" target="_blank" class="btn btn-info btn-sm mb-1">View Slip</a>
                                        <?php if ($filter_status == 'Pending'): ?>
                                            <form action="review_slips.php" method="POST" class="d-inline">
                                                <input type="hidden" name="slip_id" value="<?= $slip['slip_id'] ?>">
                                                <input type="hidden" name="invoice_id" value="<?= $slip['invoice_id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm mb-1">Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $slip['slip_id'] ?>">Reject</button>
                                        <?php elseif ($filter_status == 'Rejected'): ?>
                                            <p class="text-danger small mt-2"><strong>Reason:</strong> <?= htmlspecialchars($slip['rejection_reason']) ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?= $slip['slip_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="review_slips.php" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Payment Slip</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Please provide a reason for rejecting this payment slip.</p>
                                                    <input type="hidden" name="slip_id" value="<?= $slip['slip_id'] ?>">
                                                    <input type="hidden" name="invoice_id" value="<?= $slip['invoice_id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No slips found with '<?= htmlspecialchars($filter_status) ?>' status.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() { 
        $('#slipsTable').DataTable();
    });
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; 
?>
