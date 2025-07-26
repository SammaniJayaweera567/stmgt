<?php
ob_start();
include '../../init.php';

show_status_message(); 
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-users mt-1 me-2 mr-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Users</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New User</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title" style="font-size: 1.05rem !important;">User List</h6>
                </div>
                <div class="card-body">
                    <table id="userTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Telephone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $db = dbConn();
                            $sql = "SELECT u.Id, u.FirstName, u.LastName, u.Email, u.TelNo, u.ProfileImage, u.Status, r.RoleName 
                                    FROM users u 
                                    LEFT JOIN user_roles r ON u.user_role_id = r.Id 
                                    ORDER BY u.Id DESC";
                            $result = $db->query($sql);
                            while ($row = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td>
                                    <img src="../../web/uploads/profile_images/<?= htmlspecialchars(!empty($row['ProfileImage']) ? $row['ProfileImage'] : 'default_avatar.png') ?>"
                                        class="rounded-circle me-2" width="40" height="40" style="object-fit:cover;">
                                    <?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?>
                                </td>
                                <td><?= htmlspecialchars($row['Email']) ?></td>
                                <td><?= htmlspecialchars($row['TelNo']) ?></td>
                                <td><span
                                        class="badge bg-info text-dark"><?= htmlspecialchars($row['RoleName']) ?></span>
                                </td>
                                <td><?= display_status_badge($row['Status']) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <!-- VIEW BUTTON (NEW) -->
                                        <form action="view.php" method="post" class="mr-1"
                                            style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?= $row['Id'] ?>">
                                            <button type="submit" class="btn btn-info btn-sm" title="View User"><i
                                                    class="fas fa-eye"></i></button>
                                        </form>

                                        <!-- EDIT BUTTON -->
                                        <form action="edit.php" method="post" class="mr-1"
                                            style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?= $row['Id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm" title="Edit User"><i
                                                    class="fas fa-edit"></i></button>
                                        </form>

                                        <!-- DELETE BUTTON -->
                                        <form action="delete.php" method="post" style="display:inline-block;"
                                            id="deleteForm<?= $row['Id'] ?>">
                                            <input type="hidden" name="Id" value="<?= $row['Id'] ?>">
                                            <input type="hidden" name="ProfileImage"
                                                value="<?= $row['ProfileImage'] ?>">
                                            <button type="button" class="btn btn-danger btn-sm" title="Delete User"
                                                onclick="confirmDelete(<?= $row['Id'] ?>)"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $("#userTable")
        .DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
        })
        .buttons()
        .container()
        .appendTo("#userTable_wrapper .col-md-6:eq(0)");
});
</script>
<!-- The JavaScript part remains the same and should be in your layouts.php or a separate .js file -->
<?php
$content = ob_get_clean();
include '../layouts.php';
?>