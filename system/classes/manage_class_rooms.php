<?php
ob_start();
include '../../init.php'; // Assuming init.php contains dbConn() and helper functions

show_status_message(); // Display status messages (e.g., added, updated, deleted)
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-door-open mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Classrooms</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add_class_rooms.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Classroom</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Classroom List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="classRoomsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Room Name</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $db = dbConn();
                                $sql = "SELECT * FROM class_rooms ORDER BY room_name ASC";
                                $result = $db->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['room_name']) ?></td>
                                            <td><?= htmlspecialchars($row['capacity']) ?></td>
                                            <td><?= display_status_badge($row['status']) ?></td>
                                            <td class="text-center">
                                                <form action="edit_class_rooms.php" method="post" style="display:inline-block;">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" title="Edit Classroom">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                                <form action="delete_class_rooms.php" method="post" style="display:inline-block;" id="deleteForm<?= $row['id'] ?>">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-danger btn-sm" title="Delete Classroom">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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
    $(document).ready(function() {
        $('#classRoomsTable').DataTable(); // Initialize DataTables
    });

    // This function should ideally be in a global JS file
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
