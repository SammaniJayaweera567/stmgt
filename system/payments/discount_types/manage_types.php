<?php
ob_start();
include '../../../init.php'; 

$db = dbConn();
?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-tags mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Discount Types</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add_type.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Discount Type</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Discount Types</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="typesTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Type Name</th>
                                    <th>Logic</th>
                                    <th>Default Value</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM discount_types ORDER BY id ASC";
                                $result = $db->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['type_name']) ?></td>
                                            <td><?= htmlspecialchars($row['value_logic']) ?></td>
                                            <td>
                                                <?php
                                                if ($row['value_logic'] == 'Percentage') {
                                                    echo htmlspecialchars($row['default_value']) . '%';
                                                } elseif ($row['value_logic'] == 'Fixed Amount') {
                                                    echo 'LKR ' . htmlspecialchars(number_format($row['default_value'], 2));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="edit_type.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="Edit Type">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="delete_type.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this type?');">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Type">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                    }
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
        $('#typesTable').DataTable();
    });
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php'; 
?>
