<?php
ob_start(); // Start output buffering
include '../../init.php'; // Include init.php (assumes dbConn(), show_status_message(), display_status_badge() functions are here)

?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-link mt-1 me-2" style="font-size: 17px;"></i> <h5 class="w-auto">Manage Class Level Subjects</h5>
        </div>
    </div>

    <?php show_status_message(); // Calling the function to show status messages ?>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add_class_levels_subjects.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Class Level Subject</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Class Level Subject List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="classLevelsSubjectsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Class Level</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $db = dbConn();
                                // SQL query to select data from class_levels_subjects and join with class_levels and subjects tables
                                $sql = "SELECT 
                                            cls.id, 
                                            cl.level_name AS class_level_name, 
                                            s.subject_name, 
                                            cls.status 
                                        FROM 
                                            class_levels_subjects cls
                                        JOIN 
                                            class_levels cl ON cls.class_level_id = cl.id
                                        JOIN 
                                            subjects s ON cls.subject_id = s.id
                                        ORDER BY 
                                            cls.id DESC"; 
                                            
                                $result = $db->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['class_level_name']) ?></td>
                                            <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                            <td><?= display_status_badge($row['status']) ?></td>
                                            <td class="text-start">
                                                <!-- FIXED: Action points to the correct edit file -->
                                                <form action="edit_class_levels_subjects.php" method="post" style="display:inline-block;">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" title="Edit Relationship"><i class="fas fa-edit"></i></button>
                                                </form>
                                                
                                                <!-- FIXED: Action points to the correct delete file -->
                                                <form action="delete_class_levels_subjects.php" method="post" style="display:inline-block;" id="deleteForm<?= $row['id'] ?>">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-danger btn-sm" title="Delete Relationship"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php 
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center">No Class Level Subject relationships found.</td></tr>';
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
        $('#classLevelsSubjectsTable').DataTable(); 
    });

    // You should have a global confirmDelete function in your layout or a JS file
    function confirmDelete(id) {
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
        });
    }
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
