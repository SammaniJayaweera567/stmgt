<?php
ob_start();
include '../../init.php'; // Correct path
if (!hasPermission($_SESSION['user_id'], 'manage_subject')) {
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
    <?php show_status_message(); // Call the common function to show toast notifications ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-book mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Subjects</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <?php if (hasPermission($_SESSION['user_id'], 'add_subject')) { ?>
                <a href="add_subject.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add New Subject
                </a>
                <?php } ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Subject List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="subjectsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject Name</th>
                                    <th>Subject Code</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // FIXED SQL Query: Select data from the 'subjects' table
                                $sql = "SELECT id, subject_name, subject_code, status 
                                        FROM subjects 
                                        ORDER BY id ASC"; // Order by ID descending for latest subjects first
                                $result = $db->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['id']) ?></td>
                                            <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($row['subject_code']) ?></td>
                                            <td><?= display_status_badge($row['status']) ?></td>
                                            <td class="text-start">
                                                <div class="btn-group">
                                                    <?php if (hasPermission($_SESSION['user_id'], 'edit_subject')) { ?>
                                                    <a href="edit_subject.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm mr-1" title="Edit Subject">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php } ?>
                                                    <?php if (hasPermission($_SESSION['user_id'], 'delete_subject')) { ?>
                                                    <form action="delete_subject.php" method="post" style="display:inline-block;" id="deleteForm<?= $row['id'] ?>">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-danger btn-sm" title="Delete Subject">
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
                                    echo '<tr><td colspan="5" class="text-center">No subjects found.</td></tr>';
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
        // Use the common function to initialize the DataTable
        initializeDataTable('subjectsTable'); 
    });
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Correct path to layouts.php
?>