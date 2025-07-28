<?php
ob_start();
// [FIX 1] All paths corrected to '../'
include '../../init.php';

if (!hasPermission($_SESSION['user_id'], 'manage_class_level')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// Show success message from URL status
if (isset($_GET['status'])) {
    $message = '';
    $icon = 'success';
    switch ($_GET['status']) {
        case 'added': $message = 'Class Level added successfully!'; break;
        case 'updated': $message = 'Class Level updated successfully!'; break;
        case 'deleted': $message = 'Class Level deleted successfully.'; $icon = 'info'; break;
    }
    if ($message) {
        echo "<script>
            window.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    position: 'top-end', icon: '$icon', title: '$message',
                    showConfirmButton: false, timer: 2500
                });
            });
        </script>";
    }
}
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-layer-group mt-1 mr-2" style="font-size: 17px;"></i>
            <h5 class="mb-5 w-auto">Manage Class Levels</h5>
        </div>
        <div class="col-12 mt-3">
            <div class="d-flex justify-content-start mb-4">
                <?php if (hasPermission($_SESSION['user_id'], 'add_class_level')) { ?>
                <a href="add_class_levels.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Class Level</a>
                <?php } ?>
            </div>
            <div class="card">
                <div class="card-header table-headers-bg">
                    <h3 class="card-title">Class Level List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="classLevelsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Level Name</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $db = dbConn();
                                $sql = "SELECT * FROM class_levels ORDER BY id DESC";
                                $result = $db->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['level_name']) ?></td>
                                            <td>
                                                <?php 
                                                    if($row['status'] == 'Active'){
                                                        echo '<span class="badge bg-success">Active</span>';
                                                    } else {
                                                        echo '<span class="badge bg-danger">Inactive</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="text-start">
                                                <?php if (hasPermission($_SESSION['user_id'], 'edit_class_level')) { ?>
                                                <form action="edit_class_levels.php" method="post" style="display:inline-block;">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" title="Edit Level">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                                <?php } ?>
                                                <?php if (hasPermission($_SESSION['user_id'], 'delete_class_level')) { ?>
                                                <form action="delete_class_level.php" method="post" style="display:inline-block;" id="frmdelete<?= $row['id'] ?>">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-danger btn-sm" title="Delete Level">
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
    $(document).ready(function() { $('#classLevelsTable').DataTable(); });
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?', text: "You won't be able to revert this!",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('frmdelete' + id).submit(); }
        });
    }
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>