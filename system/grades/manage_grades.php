<?php
ob_start();
include '../../init.php'; // Correct path from /system/grades/
if (!hasPermission($_SESSION['user_id'], 'manage_assement_grade')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$db = dbConn();
?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-certificate mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Grades</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                 <?php if (hasPermission($_SESSION['user_id'], 'add_assement_grade')) { ?>
                <a href="add_grade.php" class="btn btn-primary">
                     
                    <i class="fas fa-plus-circle me-1"></i> Add New Grade
                </a>
                    <?php } ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Grade List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="gradesTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Grade Name</th>
                                    <th>Min Percentage (%)</th>
                                    <th>Max Percentage (%)</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM grades ORDER BY min_percentage DESC";
                                $result = $db->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['grade_name']) ?></td>
                                    <td><?= htmlspecialchars(rtrim(rtrim($row['min_percentage'], '0'), '.')) ?></td>
                                    <td><?= htmlspecialchars(rtrim(rtrim($row['max_percentage'], '0'), '.')) ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= display_status_badge($row['status']) ?></td>
                                    <td class="text-start">
                                        <div class="btn-group">
                                            <?php if (hasPermission($_SESSION['user_id'], 'edit_assement_grade')) { ?>
                                            <a href="edit_grade.php?id=<?= $row['id'] ?>"
                                                class="btn btn-primary btn-sm me-1 mr-1" title="Edit Grade">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php } ?>
                                            <?php if (hasPermission($_SESSION['user_id'], 'delete_assement_grade')) { ?>
                                            <form action="delete_grade.php" method="post" style="display:inline-block;"
                                                id="deleteForm<?= $row['id'] ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="button" onclick="confirmDeleteSweet(<?= $row['id'] ?>)"
                                                    class="btn btn-danger btn-sm" title="Delete Grade">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">No grades found.</td></tr>';
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
    $('#gradesTable').DataTable({
        "order": [[ 1, "desc" ]] // Order by Min Percentage descending by default
    });
});

// UPDATED: SweetAlert confirmation function
function confirmDeleteSweet(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteForm' + id).submit();
        }
    })
}
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>