<?php
ob_start();
// The path needs to be from system/classes/materials/
include '../../../init.php'; 

$db = dbConn();
?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-book-open mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Class Materials</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add_material.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Material</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Class Materials</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="materialsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>File Name</th>
                                    <th>Uploaded On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // SQL to fetch all materials along with their class details
                                $sql = "SELECT 
                                            cm.id,
                                            cm.title,
                                            cm.file_name,
                                            cm.file_path,
                                            cm.created_at,
                                            CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name
                                        FROM class_materials cm
                                        JOIN classes c ON cm.class_id = c.id
                                        JOIN class_levels cl ON c.class_level_id = cl.id 
                                        JOIN subjects s ON c.subject_id = s.id 
                                        JOIN class_types ct ON c.class_type_id = ct.id 
                                        ORDER BY cm.created_at DESC";
                                $result = $db->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= htmlspecialchars($row['class_full_name']) ?></td>
                                            <td>
                                                <a href="../../../web/uploads/materials/<?= htmlspecialchars($row['file_path']) ?>" target="_blank">
                                                    <?= htmlspecialchars($row['file_name']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['created_at']))) ?></td>
                                            <td class="text-center">
                                                <a href="edit_material.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="Edit Material">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="delete_material.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Material">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center">No materials found.</td></tr>';
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
        $('#materialsTable').DataTable();
    });
</script>

<?php
$content = ob_get_clean();
// The path needs to be from system/classes/materials/
include '../../layouts.php'; 
?>
