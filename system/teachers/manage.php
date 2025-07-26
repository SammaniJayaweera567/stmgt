<?php
ob_start();
include '../../init.php';
?>

<div class="container-fluid">
    <?php 
    // Call the common function to show toast notifications on success/error.
    show_status_message(); 
    ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-chalkboard-teacher mt-1 me-2 mr-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Teachers</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Teacher</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title" style="font-size: 1.05rem !important;">Teacher List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="teacherTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>NIC</th>
                                    <th>Designation</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $db = dbConn();
                                // UPDATED: Added TelNo and NIC to the SELECT statement
                                $sql = "SELECT u.Id, u.FirstName, u.LastName, u.Email, u.ProfileImage, u.Status, u.TelNo, u.NIC,
                                               td.designation, td.appointment_date
                                        FROM users u 
                                        LEFT JOIN teacher_details td ON u.Id = td.user_id
                                        LEFT JOIN user_roles r ON u.user_role_id = r.Id
                                        WHERE r.RoleName = 'Teacher'
                                        ORDER BY u.Id DESC";

                                $result = $db->query($sql);
                                while ($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td>
                                        <img src="../uploads/<?= htmlspecialchars(!empty($row['ProfileImage']) ? $row['ProfileImage'] : 'default_avatar.png') ?>"
                                            class="rounded-circle me-2" width="40" height="40" style="object-fit:cover;">
                                        <?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['Email']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['TelNo']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['NIC']) ?></td>
                                    <td><?= htmlspecialchars($row['designation']) ?></td>
                                    <td>
                                        <?= display_status_badge($row['Status']); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view.php?user_id=<?= $row['Id'] ?>" class="btn btn-info btn-sm mr-1"
                                                title="View Teacher">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="edit.php?user_id=<?= $row['Id'] ?>" class="btn btn-primary btn-sm mr-1"
                                                title="Edit Teacher">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <form action="delete.php" method="post" style="display:inline-block;"
                                                id="deleteForm<?= $row['Id'] ?>">
                                                <input type="hidden" name="user_id" value="<?= $row['Id'] ?>">
                                                <input type="hidden" name="ProfileImage"
                                                    value="<?= $row['ProfileImage'] ?>">
                                                <button type="button" class="btn btn-danger btn-sm" title="Delete Teacher"
                                                    onclick="confirmDelete(<?= $row['Id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
</div>

<script>
    $(document).ready(function() { 
        $('#teacherTable').DataTable(); 
    });
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
