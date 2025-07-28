<?php
ob_start();
<<<<<<< HEAD
include '../../init.php';
=======
include '../../init.php'; // ඔබගේ init.php ගොනුවට නිවැරදි path එක දෙන්න
if (!hasPermission($_SESSION['user_id'], 'manage_student_enrollment')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";
>>>>>>> origin/geek

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
                                        <?php if (hasPermission($_SESSION['user_id'], 'show_student_enrollment')) { ?>
                                        <form action="view.php" method="post" style="display:inline-block;"
                                            class="mr-1">
                                            <input type="hidden" name="enrollment_id"
                                                value="<?= $row['enrollment_id'] ?>">
                                            <button type="submit" class="btn btn-info btn-sm" title="View Details"><i
                                                    class="fas fa-eye"></i></button>
                                        </form>
                                        <?php } ?>

                                        <?php if (hasPermission($_SESSION['user_id'], 'edit_student_enrollment')) { ?>
                                        <form action="process_enrollment.php" method="post"
                                            style="display:inline-block;" class="mr-1">
                                            <input type="hidden" name="enrollment_id"
                                                value="<?= $row['enrollment_id'] ?>">
                                            <select name="new_status" class="form-control-sm"
                                                style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                                <option value="active"
                                                    <?= $row['enrollment_status'] == 'active' ? 'selected' : '' ?>>
                                                    Active</option>
                                                <option value="pending"
                                                    <?= $row['enrollment_status'] == 'pending' ? 'selected' : '' ?>>
                                                    Pending</option>
                                                <option value="completed"
                                                    <?= $row['enrollment_status'] == 'completed' ? 'selected' : '' ?>>
                                                    Completed</option>
                                                <option value="cancelled"
                                                    <?= $row['enrollment_status'] == 'cancelled' ? 'selected' : '' ?>>
                                                    Cancelled</option>
                                            </select>
                                        </form>
                                        <?php } ?>
                                        <?php if (hasPermission($_SESSION['user_id'], 'delete_student_enrollment')) { ?>
                                        <form action="process_enrollment.php" method="post"
                                            style="display:inline-block;" id="deleteForm<?= $row['enrollment_id'] ?>">
                                            <input type="hidden" name="enrollment_id"
                                                value="<?= $row['enrollment_id'] ?>">
                                            <input type="hidden" name="delete_enrollment" value="1">
                                            <button type="button" class="btn btn-danger btn-sm"
                                                title="Delete Enrollment"
                                                onclick="confirmDelete(<?= $row['enrollment_id'] ?>)"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                        <?php } ?>
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