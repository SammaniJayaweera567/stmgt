<?php
ob_start();
include '../../init.php';

$db = dbConn();
?>
<div class="container-fluid">
    <?php show_status_message(); ?>
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-user-graduate mt-1 me-2"></i>
            <h5 class="w-auto">Manage Students</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Students List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="studentsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email & Contact</th>
                                    <th>Registration No.</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // UPDATED: ORDER BY u.Id ASC to show oldest students first
                                $sql = "SELECT u.Id, u.FirstName, u.LastName, u.Email, u.TelNo, u.ProfileImage, u.Status, sd.registration_no 
                                        FROM users u 
                                        LEFT JOIN student_details sd ON u.Id = sd.user_id
                                        WHERE u.user_role_id = 4 
                                        ORDER BY u.Id ASC";
                                $result = $db->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td>
                                        <img src="../../web/uploads/profile_images/<?= htmlspecialchars(!empty($row['ProfileImage']) ? $row['ProfileImage'] : 'default_avatar.png') ?>"
                                            class="rounded-circle me-2" width="40" height="40"
                                            style="object-fit:cover;">
                                        <?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['Email']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['TelNo']) ?></small>
                                    </td>
                                    <td><span
                                            class="badge bg-info text-dark"><?= htmlspecialchars($row['registration_no']) ?></span>
                                    </td>
                                    <td><?= display_status_badge($row['Status']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_student.php?id=<?= $row['Id'] ?>" class="btn btn-info btn-sm mr-1"
                                                title="View Details"><i class="fas fa-eye"></i></a>
                                            <a href="edit_student.php?id=<?= $row['Id'] ?>"
                                                class="btn btn-primary btn-sm mr-1" title="Edit Student"><i
                                                    class="fas fa-edit"></i></a>
                                            <form action="delete_student.php" method="post"
                                                style="display:inline-block;" id="deleteForm<?= $row['Id'] ?>">
                                                <input type="hidden" name="id" value="<?= $row['Id'] ?>">
                                                <button type="button" onclick="confirmDeleteSweet(<?= $row['Id'] ?>)"
                                                    class="btn btn-danger btn-sm" title="Delete Student"><i
                                                        class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
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
// This script requires jQuery and DataTables to be included in your layouts.php
$(document).ready(function() {
    $('#studentsTable').DataTable();
});
// This script requires a confirmDelete function, possibly using SweetAlert, in your layouts.php
// UPDATED: SweetAlert confirmation function
function confirmDeleteSweet(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! All related data will be lost.",
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