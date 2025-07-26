<?php
ob_start();
include '../../init.php';


// 1. Get and validate the class_id from the URL
$class_id = null;
if (isset($_GET['class_id']) && is_numeric($_GET['class_id'])) {
    $class_id = (int)$_GET['class_id'];
}
if ($class_id === null) {
    header("Location: manage_classes.php?status=notfound");
    exit();
}

$db = dbConn();
$messages = [];

// 2. Handle POST requests (enrolling or removing a student)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- Handle ENROLLING a new student ---
    if (isset($_POST['enroll_student'])) {
        $student_user_id = dataClean($_POST['student_user_id'] ?? null);
        
        // --- VALIDATION 1: Check for duplicates (Existing Code - Correct) ---
        $check_sql = "SELECT id FROM enrollments WHERE class_id = '$class_id' AND student_user_id = '$student_user_id' AND status = 'Active'";
        if ($db->query($check_sql)->num_rows > 0) {
            header("Location: manage_enrollments.php?class_id=$class_id&status=error_duplicate");
            exit();
        }

        // --- VALIDATION 2: Check Class Capacity (NEWLY ADDED) ---
        // Step A: Get max_students and current number of enrolled students
        $capacity_sql = "SELECT 
                            c.max_students, 
                            (SELECT COUNT(*) FROM enrollments WHERE class_id = c.id AND status = 'Active') as enrolled_count 
                         FROM classes c 
                         WHERE c.id = '$class_id'";
        $capacity_result = $db->query($capacity_sql)->fetch_assoc();
        
        $max_students = $capacity_result['max_students'];
        $enrolled_count = $capacity_result['enrolled_count'];

        // Step B: Compare and redirect if class is full
        // We check if max_students is not 0 or null to avoid blocking enrollment if no limit is set.
        if ($max_students > 0 && $enrolled_count >= $max_students) {
            header("Location: manage_enrollments.php?class_id=$class_id&status=error_capacity");
            exit();
        }
        
        // --- If all validations pass, proceed with enrollment ---
        $enrollment_date = date('Y-m-d');
        $status = 'Active';

        $insert_sql = "INSERT INTO enrollments (student_user_id, class_id, enrollment_date, status) VALUES ('$student_user_id', '$class_id', '$enrollment_date', '$status')";
        $db->query($insert_sql);
        
        header("Location: manage_enrollments.php?class_id=$class_id&status=added");
        exit();
    }
    
    // --- Handle REMOVING (deactivating) a student ---
    if (isset($_POST['remove_student'])) {
        $enrollment_id = (int)$_POST['enrollment_id'];
        $status = 'Inactive'; // Soft delete

        $update_sql = "UPDATE enrollments SET status = '$status' WHERE id = '$enrollment_id'";
        $db->query($update_sql);

        header("Location: manage_enrollments.php?class_id=$class_id&status=updated");
        exit();
    }
}


// 3. Fetch details of the current class for the page title
$class_sql = "SELECT s.subject_name, cl.level_name FROM classes c 
              JOIN subjects s ON c.subject_id = s.id 
              JOIN class_levels cl ON c.class_level_id = cl.id 
              WHERE c.id = '$class_id'";
$class_details = $db->query($class_sql)->fetch_assoc();
$page_title = "Enrollments for " . htmlspecialchars($class_details['level_name'] . ' - ' . $class_details['subject_name']);

// 4. Fetch students who are NOT YET enrolled in this class
$student_role_id_sql = "SELECT Id FROM user_roles WHERE RoleName = 'Student'";
$student_role_id = $db->query($student_role_id_sql)->fetch_assoc()['Id'];

$available_students_sql = "SELECT u.Id, u.FirstName, u.LastName, sd.registration_no FROM users u 
                           JOIN student_details sd ON u.Id = sd.user_id
                           WHERE u.user_role_id = '$student_role_id' AND u.status = 'Active' AND u.Id NOT IN 
                           (SELECT student_user_id FROM enrollments WHERE class_id = '$class_id' AND status = 'Active')";
$available_students = $db->query($available_students_sql);

// 5. Fetch students who are ALREADY enrolled in this class
$enrolled_students_sql = "SELECT e.id, u.FirstName, u.LastName, sd.registration_no, e.enrollment_date 
                          FROM enrollments e
                          JOIN users u ON e.student_user_id = u.Id
                          JOIN student_details sd ON u.Id = sd.user_id
                          WHERE e.class_id = '$class_id' AND e.status = 'Active' ORDER BY u.FirstName";
$enrolled_students = $db->query($enrolled_students_sql);
?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-user-plus mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto"><?= $page_title ?></h5>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Enroll Available Students</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <input type="text" id="studentSearch" class="form-control"
                            placeholder="Search by Name or Registration No...">
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover" id="availableStudentsTable">
                            <tbody>
                                <?php
                                if ($available_students && $available_students->num_rows > 0) {
                                    while ($student = $available_students->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?><br>
                                        <small
                                            class="text-muted"><?= htmlspecialchars($student['registration_no']) ?></small>
                                    </td>
                                    <td class="text-right" style="vertical-align: middle;">
                                        <form method="post"
                                            action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?class_id=<?= $class_id ?>">
                                            <input type="hidden" name="student_user_id" value="<?= $student['Id'] ?>">
                                            <button type="submit" name="enroll_student"
                                                class="btn btn-success btn-sm">Enroll</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="2" class="text-center">No new students available to enroll.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Currently Enrolled Students</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Reg. No</th>
                                <th>Student Name</th>
                                <th>Enrolled Date</th>
                                <th style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($enrolled_students->num_rows > 0) {
                                while ($student = $enrolled_students->fetch_assoc()) {
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($student['registration_no']) ?></td>
                                <td><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></td>
                                <td><?= htmlspecialchars($student['enrollment_date']) ?></td>
                                <td>
                                    <form method="post"
                                        action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?class_id=<?= $class_id ?>"
                                        onsubmit="return confirm('Are you sure you want to remove this student from the class? This will deactivate the enrollment.');">
                                        <input type="hidden" name="enrollment_id" value="<?= $student['id'] ?>">
                                        <button type="submit" name="remove_student" class="btn btn-danger btn-sm"
                                            title="Remove from class">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="4" class="text-center">No students are currently enrolled in this class.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <a href="manage_classes.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Class List</a>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#studentSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#availableStudentsTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>