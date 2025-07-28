<?php
ob_start();
// Path from system/payments/
include '../../init.php'; 

$db = dbConn();
?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-percent mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Student Discounts</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add_discount.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Discount</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Student Discounts & Cards</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="discountsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Discount Type</th>
                                    <th>Value</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // SQL to fetch all discounts with student, class, and discount type details
                                $sql = "SELECT 
                                            sd.id,
                                            sd.discount_value,
                                            sd.reason,
                                            sd.status,
                                            CONCAT(u.FirstName, ' ', u.LastName) as student_name,
                                            CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name,
                                            dt.type_name as discount_type_name,
                                            dt.value_logic
                                        FROM student_discounts sd
                                        JOIN users u ON sd.student_user_id = u.Id
                                        JOIN classes c ON sd.class_id = c.id
                                        JOIN class_levels cl ON c.class_level_id = cl.id 
                                        JOIN subjects s ON c.subject_id = s.id 
                                        JOIN class_types ct ON c.class_type_id = ct.id
                                        JOIN discount_types dt ON sd.discount_type_id = dt.id
                                        ORDER BY sd.created_at DESC";
                                $result = $db->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                                            <td><?= htmlspecialchars($row['class_full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['discount_type_name']) ?></td>
                                            <td>
                                                <?php
                                                // Display value differently based on type
                                                if ($row['value_logic'] == 'Percentage') {
                                                    echo htmlspecialchars($row['discount_value']) . '%';
                                                } elseif ($row['value_logic'] == 'Fixed Amount') {
                                                    echo 'LKR ' . htmlspecialchars(number_format($row['discount_value'], 2));
                                                } else {
                                                    // For Free Card / Half Card which has logic but value is stored in discount_types table
                                                    echo htmlspecialchars($row['discount_type_name']);
                                                }
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['reason']) ?></td>
                                            <td><?= display_status_badge($row['status']) ?></td>
                                            <td class="text-center">
                                                <a href="edit_discount.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="Edit Discount">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="delete_discount.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this discount?');">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Discount">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">No discounts found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() { 
        $('#discountsTable').DataTable();
    });
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; 
?>
