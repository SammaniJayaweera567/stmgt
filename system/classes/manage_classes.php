<?php
ob_start();
// [FIX 1] All paths corrected to '../'
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'manage_classes')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
show_status_message(); // Display status messages (e.g., added, updated, deleted)
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-chalkboard-teacher mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Classes</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                 <?php if (hasPermission($_SESSION['user_id'], 'add_classes')) { ?>
                <a href="add_classes.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New
                    Class</a>
                <?php } ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Class List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="classesTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Teacher</th>
                                    <th>Schedule</th>
                                    <th>Room</th>
                                    <th>Max Students</th>
                                    <th>Fee (Rs.)</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $db = dbConn();
                                // SQL query to fetch classes with joined data from related tables
                                // UPDATED: Select c.max_students explicitly for display in its own column
                                // UPDATED: Changed ORDER BY to day_of_week and start_time for better schedule view
                                $sql = "SELECT 
                                            c.id,
                                            ay.year_name,
                                            cl.level_name,
                                            s.subject_name,
                                            ct.type_name,
                                            CONCAT(u.FirstName, ' ', u.LastName) AS teacher_name,
                                            cr.room_name,
                                            cr.capacity AS room_capacity,
                                            c.max_students,
                                            c.class_fee,
                                            c.day_of_week,
                                            c.start_time,
                                            c.end_time,
                                            c.status
                                        FROM 
                                            classes c
                                        LEFT JOIN 
                                            academic_years ay ON c.academic_year_id = ay.id
                                        LEFT JOIN 
                                            class_levels cl ON c.class_level_id = cl.id
                                        LEFT JOIN 
                                            subjects s ON c.subject_id = s.id
                                        LEFT JOIN 
                                            class_types ct ON c.class_type_id = ct.id
                                        LEFT JOIN 
                                            users u ON c.teacher_id = u.id
                                        LEFT JOIN 
                                            class_rooms cr ON c.class_room_id = cr.id
                                        ORDER BY 
                                            FIELD(c.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), c.start_time ASC";
                                $result = $db->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        // Construct the Class Name (Level + Subject + Type)
                                        $class_name_display = htmlspecialchars($row['level_name']) . ' - ' . htmlspecialchars($row['subject_name']) . ' (' . htmlspecialchars($row['type_name']) . ')';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= $class_name_display ?></strong><br>
                                        <small class="text-muted">Year:
                                            <?= htmlspecialchars($row['year_name']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['day_of_week']) ?></strong><br>
                                        <small
                                            class="text-muted"><?= htmlspecialchars(date('h:i A', strtotime($row['start_time']))) ?>
                                            -
                                            <?= htmlspecialchars(date('h:i A', strtotime($row['end_time']))) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['room_name']) ?> (Cap:
                                        <?= htmlspecialchars($row['room_capacity']) ?>)</td>
                                    <td><?= htmlspecialchars($row['max_students']) ?></td>
                                    <td><?= htmlspecialchars(number_format($row['class_fee'], 2)) ?></td>
                                    <td><?= display_status_badge($row['status']) ?></td>
                                    <td class="text-start">
                                        <div class="btn-group">
                                             <?php if (hasPermission($_SESSION['user_id'], 'show_classes')) { ?>
                                            <a href="view.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm mr-1"
                                                title="View Class"><i class="fas fa-eye"></i></a>
                                                <?php } ?>

                                                 <?php if (hasPermission($_SESSION['user_id'], 'edit_classes')) { ?>
                                            <a href="edit_classes.php?id=<?= $row['id'] ?>"
                                                class="btn btn-primary btn-sm mr-1" title="Edit Class"><i
                                                    class="fas fa-edit"></i></a>
                                                <?php } ?>
                                                 <?php if (hasPermission($_SESSION['user_id'], 'manage_enrollment')) { ?>
                                            <a href="manage_enrollments.php?class_id=<?= $row['id'] ?>"
                                                class="btn btn-success btn-sm mr-1" title="Manage Enrollments"><i
                                                    class="fas fa-user-plus"></i></a>
                                                <?php } ?>
                                                 <?php if (hasPermission($_SESSION['user_id'], 'delete_classes')) { ?>
                                            <form action="delete_class.php" method="post" style="display:inline-block;"
                                                id="deleteForm<?= $row['id'] ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)"
                                                    class="btn btn-danger btn-sm" title="Delete Class">
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
                                    echo '<tr><td colspan="8" class="text-center">No classes found.</td></tr>'; // colspan changed to 8
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
// DataTable initialization is page-specific, so it remains here.
$(document).ready(function() {
    $('#classesTable').DataTable();
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Assuming layouts.php includes the HTML structure and echoes $content
?>