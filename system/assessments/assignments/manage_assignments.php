<?php
ob_start();
include '../../../init.php'; // Correct path from /system/assessments/assignments/
if (!hasPermission($_SESSION['user_id'], 'manage_assignment')) {
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
            <i class="fas fa-file-alt mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Assignments</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <?php if (hasPermission($_SESSION['user_id'], 'add_assignment')) { ?>
                <a href="add_assignment.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add New Assignment
                </a>
                <?php } ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assignment List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="assignmentsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Max Marks</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT 
                                            a.id, 
                                            a.title, 
                                            a.max_marks,
                                            a.due_date,
                                            a.status,
                                            cl.level_name,
                                            s.subject_name,
                                            ct.type_name,
                                            CONCAT(u.FirstName, ' ', u.LastName) as teacher_name
                                        FROM assessments a
                                        JOIN classes c ON a.class_id = c.id
                                        JOIN class_levels cl ON c.class_level_id = cl.id
                                        JOIN subjects s ON c.subject_id = s.id
                                        JOIN class_types ct ON c.class_type_id = ct.id
                                        LEFT JOIN users u ON a.teacher_id = u.Id
                                        WHERE a.assessment_type_id = 2
                                        ORDER BY a.due_date DESC";
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
                                            <td><?= htmlspecialchars(number_format($row['max_marks'], 2)) ?></td>
                                            <td><?= htmlspecialchars(date('Y-m-d H:i A', strtotime($row['due_date']))) ?></td>
                                            <td><?= display_status_badge($row['status']) ?></td>
                                            <td class="text-start">
                                                <div class="btn-group">
                                                    <!-- VIEW BUTTON -->
                                                    <?php if (hasPermission($_SESSION['user_id'], 'edit_assignment')) { ?>
                                                    <a href="edit_assignment.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm mr-1" title="Edit Assignment">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php } ?>
                                                    <!-- DELETE BUTTON -->
                                                    <?php if (hasPermission($_SESSION['user_id'], 'delete_assignment')) { ?>
                                                    <form action="delete_assignment.php" method="post" style="display:inline-block;" id="deleteForm<?= $row['id'] ?>">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-danger btn-sm mr-1" title="Delete Assignment">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <?php } ?>
                                                    <!-- VIEW SUBMISSIONS and ENTER RESULTS -->
                                                    <?php if (hasPermission($_SESSION['user_id'], 'show_assignment')) { ?>
                                                    <a href="view_submissions.php?assignment_id=<?= $row['id'] ?>" class="btn btn-info btn-sm mr-1" title="View Submissions">
                                                        <i class="fas fa-upload"></i>
                                                    </a>
                                                     <?php } ?>
                                                    <!-- ENTER RESULTS -->
                                                    <?php if (hasPermission($_SESSION['user_id'], 'assignments_result')) { ?>
                                                    <a href="enter_results.php?assignment_id=<?= $row['id'] ?>" class="btn btn-success btn-sm" title="Enter Results">
                                                        <i class="fas fa-clipboard-check"></i>
                                                    </a>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center">No assignments found.</td></tr>';
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
        initializeDataTable('assignmentsTable'); 
    });
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php'; // Correct path to layouts.php
?>