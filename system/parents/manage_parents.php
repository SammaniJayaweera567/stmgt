<?php
ob_start();
// Path from system/parents/
include '../../init.php'; 
if (!hasPermission($_SESSION['user_id'], 'manage_parent')) {
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
                                    <th>Email</th>
                                    <th>Contact No.</th>
                                    <th>Status</th>
                                    <th>Linked Children</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // SQL to fetch all users with the 'Parent' role
                                $sql = "SELECT u.Id, u.FirstName, u.LastName, u.Email, u.TelNo, u.Status 
                                        FROM users u 
                                        JOIN user_roles ur ON u.user_role_id = ur.Id 
                                        WHERE ur.RoleName = 'Parent' 
                                        ORDER BY u.FirstName ASC";
                                $result = $db->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                                            <td><?= htmlspecialchars($row['Email']) ?></td>
                                            <td><?= htmlspecialchars($row['TelNo']) ?></td>
                                            <td><?= display_status_badge($row['Status']) ?></td>
                                            <td>
                                                <?php
                                                // Sub-query to get linked children for this parent
                                                $parent_id = $row['Id'];
                                                $sql_children = "SELECT u.FirstName, u.LastName 
                                                                 FROM users u 
                                                                 JOIN student_guardian_relationship sgr ON u.Id = sgr.student_user_id 
                                                                 WHERE sgr.guardian_user_id = '$parent_id'";
                                                $result_children = $db->query($sql_children);
                                                if ($result_children && $result_children->num_rows > 0) {
                                                    $children_names = [];
                                                    while($child = $result_children->fetch_assoc()){
                                                        $children_names[] = htmlspecialchars($child['FirstName'] . ' ' . $child['LastName']);
                                                    }
                                                    echo implode(', ', $children_names);
                                                } else {
                                                    echo '<span class="text-muted">No children linked</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (hasPermission($_SESSION['user_id'], 'show_parent')) { ?>
                                                <a href="link_student.php?parent_id=<?= $row['Id'] ?>" class="btn btn-info btn-sm" title="Manage Linked Children">
                                                    <i class="fas fa-link"></i> Manage Children
                                                </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">No parents found.</td></tr>';
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
include '../layouts.php'; 
?>
