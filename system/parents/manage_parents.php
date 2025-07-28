<?php
ob_start();
// Path from system/parents/
include '../../init.php'; 

$db = dbConn();
?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-user-friends mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Parents</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Registered Parents List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="parentsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email & Contact</th>
                                    <th>Linked Children</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT u.Id, u.FirstName, u.LastName, u.Email, u.TelNo, u.Status 
                                        FROM users u 
                                        WHERE u.user_role_id = 5 
                                        ORDER BY u.FirstName ASC";
                                $result = $db->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($row['Email']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($row['TelNo']) ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $parent_id = $row['Id'];
                                                $sql_children = "SELECT u.FirstName, u.LastName 
                                                                 FROM users u 
                                                                 JOIN student_guardian_relationship sgr ON u.Id = sgr.student_user_id 
                                                                 WHERE sgr.guardian_user_id = '$parent_id'";
                                                $result_children = $db->query($sql_children);
                                                if ($result_children && $result_children->num_rows > 0) {
                                                    $children_names = [];
                                                    while($child = $result_children->fetch_assoc()){
                                                        $children_names[] = htmlspecialchars($child['FirstName']);
                                                    }
                                                    echo implode(', ', $children_names);
                                                } else {
                                                    echo '<span class="text-muted">No children linked</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?= display_status_badge($row['Status']) ?></td>
                                            <td>
                                                <form action="process_parent_status.php" method="post" class="d-flex">
                                                    <input type="hidden" name="parent_id" value="<?= $row['Id'] ?>">
                                                    <select name="new_status" class="form-select form-select-sm me-2">
                                                        <option value="Active" <?= ($row['Status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                                                        <option value="Inactive" <?= ($row['Status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center">No parents found.</td></tr>';
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
        $('#parentsTable').DataTable();
    });
</script>

<?php
$content = ob_get_clean();
// Assuming you are in 'system/parents/', the layout is in 'system/'
include '../layouts.php'; 
?>