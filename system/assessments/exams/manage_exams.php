<?php
ob_start();
include '../../../init.php';
if (!hasPermission($_SESSION['user_id'], 'manage_exam')) {
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
            <i class="fas fa-file-invoice mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Exams</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <?php if (hasPermission($_SESSION['user_id'], 'add_exam')) { ?>
                <a href="add_exam.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add New Exam
                </a>
                <?php } ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Exam List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="examsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Exam Date & Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // UPDATED: Changed 'a.assessment_type' to 'a.assessment_type_id'
                                $sql = "SELECT 
                                            a.id, a.title, a.assessment_date, a.start_time, a.end_time, a.status,
                                            cl.level_name, s.subject_name,
                                            CONCAT(u.FirstName, ' ', u.LastName) as teacher_name,
                                            ct.type_name
                                        FROM assessments a
                                        JOIN classes c ON a.class_id = c.id
                                        JOIN class_levels cl ON c.class_level_id = cl.id
                                        JOIN subjects s ON a.subject_id = s.id
                                        JOIN users u ON a.teacher_id = u.Id
                                        JOIN class_types ct ON c.class_type_id = ct.id
                                        WHERE a.assessment_type_id = 1
                                        ORDER BY a.assessment_date DESC, a.start_time DESC";
                                $result = $db->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $class_full_name = htmlspecialchars($row['level_name'] . ' - ' . $row['subject_name'] . ' (' . $row['type_name'] . ')');
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= $class_full_name ?></td>
                                            <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                                            <td>
                                                <?= htmlspecialchars(date('Y-m-d', strtotime($row['assessment_date']))) ?><br>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars(date('h:i A', strtotime($row['start_time']))) ?> - 
                                                    <?= htmlspecialchars(date('h:i A', strtotime($row['end_time']))) ?>
                                                </small>
                                            </td>
                                            <td><?= display_status_badge($row['status']) ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <?php if (hasPermission($_SESSION['user_id'], 'edit_exam')) { ?>
                                                    <a href="edit_exam.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm mr-1" title="Edit Exam">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php } ?>
                                                    <?php if (hasPermission($_SESSION['user_id'], 'delete_exam')) { ?>
                                                    <form action="delete_exam.php" method="post" style="display:inline-block;" id="deleteForm<?= $row['id'] ?>">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button type="button" onclick="confirmDeleteSweet(<?= $row['id'] ?>)" class="btn btn-danger btn-sm" title="Delete Exam"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                    <?php } ?>
                                                    <?php if (hasPermission($_SESSION['user_id'], 'show_exam')) { ?>
                                                    <a href="mark_attendance.php?assessment_id=<?= $row['id'] ?>" class="btn btn-info btn-sm ml-1" title="Mark Exam Attendance">
                                                        <i class="fas fa-user-check"></i>
                                                    </a>
                                                    <a href="enter_results.php?assessment_id=<?= $row['id'] ?>" class="btn btn-success btn-sm ml-1" title="Enter Exam Results">
                                                        <i class="fas fa-clipboard-check"></i>
                                                    </a>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#examsTable').DataTable({"order": []});
    });

    function confirmDeleteSweet(id) {
        Swal.fire({
            title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete it!'
        }).then((result) => { if (result.isConfirmed) { document.getElementById('deleteForm' + id).submit(); } })
    }
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>