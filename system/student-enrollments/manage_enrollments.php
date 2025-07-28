<?php
ob_start();
include '../../init.php';

show_status_message(); 
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-user-check mt-1 me-2 mr-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Student Enrollments</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="card-title">Enrollment List</h6></div>
                <div class="card-body">
                    <table id="enrollmentTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Enrolled Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $db = dbConn();
                            $sql = "SELECT e.id AS enrollment_id, e.status AS enrollment_status, e.enrollment_date, u.FirstName, u.LastName, u.ProfileImage, s.subject_name, cl.level_name, ct.type_name AS class_type FROM enrollments AS e JOIN users AS u ON e.student_user_id = u.Id JOIN classes AS c ON e.class_id = c.id JOIN subjects AS s ON c.subject_id = s.id JOIN class_levels AS cl ON c.class_level_id = cl.id JOIN class_types AS ct ON c.class_type_id = ct.id WHERE u.user_role_id = 4 ORDER BY e.id DESC";
                            $result = $db->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td>
                                    <img src="../../web/uploads/profile_images/<?= htmlspecialchars(!empty($row['ProfileImage']) ? $row['ProfileImage'] : 'default_avatar.png') ?>" class="rounded-circle me-2" width="40" height="40" style="object-fit:cover;">
                                    <?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?>
                                </td>
                                <td><?= htmlspecialchars($row['level_name'] . ' | ' . $row['subject_name'] . ' (' . $row['class_type'] . ')') ?></td>
                                <td><?= htmlspecialchars($row['enrollment_date']) ?></td>
                                <td><?= display_status_badge($row['enrollment_status']) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <form action="process_enrollment_status.php" method="post" class="me-2 mr-1">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                            <select name="new_status" class="form-select-sm form-control" onchange="this.form.submit()">
                                                <option value="active" <?= $row['enrollment_status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="pending" <?= $row['enrollment_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="completed" <?= $row['enrollment_status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $row['enrollment_status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </form>

                                        <form action="delete_enrollment.php" method="post" id="deleteForm<?= $row['enrollment_id'] ?>">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                            <button type="button" class="btn btn-danger btn-sm mt-1" title="Delete Enrollment" onclick="confirmDeleteSweet(<?= $row['enrollment_id'] ?>)"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
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

<script>
function confirmDeleteSweet(id) {
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
    })
}
$(document).ready(function() { $('#enrollmentTable').DataTable(); });
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>