<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'manage_class_type')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// REPLACED: The entire 'if (isset($_GET['status']))' block is now handled by this single function.
show_status_message(); 
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-tags mt-1 mr-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Class Types</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <?php if (hasPermission($_SESSION['user_id'], 'add_class_type')) { ?>
                <a href="add_class_types.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Class Type</a>
                <?php } ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Class Type List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="classTypesTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Type Name</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $db = dbConn();
                                $sql = "SELECT * FROM class_types ORDER BY id DESC";
                                $result = $db->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['type_name']) ?></td>
                                        <td>
                                            <?= display_status_badge($row['status']) ?>
                                        </td>
                                        <td class="text-center">
                                             <?php if (hasPermission($_SESSION['user_id'], 'edit_class_type')) { ?>
                                            <form action="edit_class_types.php" method="post" style="display:inline-block;">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" title="Edit Type">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </form>
                                            <?php } ?>
                                             <?php if (hasPermission($_SESSION['user_id'], 'delete_class_type')) { ?>
                                            <form action="delete_class_types.php" method="post" style="display:inline-block;" id="deleteForm<?= $row['id'] ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-danger btn-sm" title="Delete Type">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php } ?>
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
    // This script initializes the DataTable
    $(document).ready(function() { 
        $('#classTypesTable').DataTable(); 
    });

    // This function handles the delete confirmation. It's better to keep this in a global JS file.
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Corrected the form ID to match the pattern
                document.getElementById('deleteForm' + id).submit();
            }
        });
    }
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>