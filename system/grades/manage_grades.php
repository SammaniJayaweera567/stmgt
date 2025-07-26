<?php
ob_start();
include '../../init.php'; // Correct path from /system/grades/

$db = dbConn();
?>

<div class="container-fluid">
    <?php show_status_message(); // Call the common function to show toast notifications ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-certificate mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Grades</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add_grade.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add New Grade
                </a>
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
                                            <a href="edit_grade.php?id=<?= $row['id'] ?>"
                                                class="btn btn-primary btn-sm mr-1" title="Edit Grade">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="delete_grade.php" method="post" style="display:inline-block;"
                                                id="deleteForm<?= $row['id'] ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)"
                                                    class="btn btn-danger btn-sm" title="Delete Grade">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
    // initializeDataTable and confirmDelete functions are expected to be in custom_scripts.js
    initializeDataTable('gradesTable');
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Correct path to layouts.php
?>