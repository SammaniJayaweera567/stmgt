<?php
ob_start();
include '../../init.php';

if (!hasPermission($_SESSION['user_id'], 'academic-years-manage')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
show_status_message(); 
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-calendar-alt mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Academic Years</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <?php if (hasPermission($_SESSION['user_id'], 'academic-years-add')) { ?>
            <div class="d-flex justify-content-start mb-4">
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Academic Year</a>
            </div>
            <?php } ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Academic Years List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="academicYearsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Year Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $db = dbConn();
                                // Changed SQL to fetch from the 'academic_years' table
                                $sql = "SELECT * FROM academic_years ORDER BY year_name DESC";
                                $result = $db->query($sql);
                                while ($row = $result->fetch_assoc()) {
                                ?>
                                    <tr>
                                        <td class="text-left"><?= htmlspecialchars($row['year_name']) ?></td>
                                        <td class="text-left"><?= htmlspecialchars($row['start_date']) ?></td>
                                        <td class="text-left"><?= htmlspecialchars($row['end_date']) ?></td>
                                        
                                        <td class="text-left"><?= display_status_badge($row['status']) ?></td> 
                                        
                                        <td class="text-left">
                                             <?php if (hasPermission($_SESSION['user_id'], 'academic-years-edit')) { ?>
                                            <form action="edit.php" method="post" style="display:inline-block;">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" title="Edit Academic Year">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </form>
                                                <?php } ?>
                                             <?php if (hasPermission($_SESSION['user_id'], 'academic-years-delete')) { ?>
                                            <form action="delete.php" method="post" style="display:inline-block;" id="deleteForm<?= $row['id'] ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-danger btn-sm" title="Delete Academic Year">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                                <?php } ?>
                                        </td>
                                    </tr>
                                <?php
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
    // Initialize DataTable on the correct table ID
    $(document).ready(function() { 
        $('#academicYearsTable').DataTable();
    });

</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Make sure the path to the layout file is correct
?>