<?php
ob_start();
// Path from system/parents/
include '../../init.php'; 
if (!hasPermission($_SESSION['user_id'], 'show_parent')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$db = dbConn();

// Get the parent ID from the URL
$parent_id = (int)($_GET['parent_id'] ?? 0);
if ($parent_id === 0) {
    header("Location: manage_parents.php?status=error&message=Parent ID not provided.");
    exit();
}

// --- Fetch Parent's Details ---
$sql_parent = "SELECT FirstName, LastName FROM users WHERE Id = '$parent_id'";
$result_parent = $db->query($sql_parent);
if ($result_parent->num_rows === 0) {
    header("Location: manage_parents.php?status=notfound");
    exit();
}
$parent_data = $result_parent->fetch_assoc();
$parent_name = $parent_data['FirstName'] . ' ' . $parent_data['LastName'];

// --- Handle POST Actions (Link or Unlink) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // --- UNLINK a student ---
    if ($action == 'unlink') {
        $relationship_id = (int)($_POST['relationship_id'] ?? 0);
        if ($relationship_id > 0) {
            $sql_unlink = "DELETE FROM student_guardian_relationship WHERE id = '$relationship_id'";
            if ($db->query($sql_unlink)) {
                header("Location: link_student.php?parent_id=$parent_id&status=unlinked");
            } else {
                header("Location: link_student.php?parent_id=$parent_id&status=error");
            }
            exit();
        }
    }

    // --- LINK a new student ---
    if ($action == 'link') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $relationship_type = dataClean($_POST['relationship_type'] ?? '');

        if ($student_id > 0 && !empty($relationship_type)) {
            // Check if this link already exists to prevent duplicates
            $sql_check = "SELECT id FROM student_guardian_relationship WHERE student_user_id = '$student_id' AND guardian_user_id = '$parent_id'";
            if ($db->query($sql_check)->num_rows == 0) {
                $sql_link = "INSERT INTO student_guardian_relationship (student_user_id, guardian_user_id, relationship_type) 
                             VALUES ('$student_id', '$parent_id', '$relationship_type')";
                if ($db->query($sql_link)) {
                    header("Location: link_student.php?parent_id=$parent_id&status=linked");
                } else {
                    header("Location: link_student.php?parent_id=$parent_id&status=error");
                }
            } else {
                header("Location: link_student.php?parent_id=$parent_id&status=exists");
            }
            exit();
        } else {
            header("Location: link_student.php?parent_id=$parent_id&status=fields_missing");
            exit();
        }
    }
}

// --- Fetch currently linked students for this parent ---
$sql_linked_children = "SELECT sgr.id as relationship_id, u.FirstName, u.LastName, sd.registration_no, sgr.relationship_type
                        FROM student_guardian_relationship sgr
                        JOIN users u ON sgr.student_user_id = u.Id
                        JOIN student_details sd ON u.Id = sd.user_id
                        WHERE sgr.guardian_user_id = '$parent_id'";
$result_linked_children = $db->query($sql_linked_children);

// --- Fetch all students who are NOT YET LINKED to this parent ---
$sql_unlinked_students = "SELECT u.Id, u.FirstName, u.LastName, sd.registration_no 
                          FROM users u
                          JOIN user_roles ur ON u.user_role_id = ur.Id
                          JOIN student_details sd ON u.Id = sd.user_id
                          WHERE ur.RoleName = 'Student' 
                          AND u.Id NOT IN (SELECT student_user_id FROM student_guardian_relationship WHERE guardian_user_id = '$parent_id')";
$result_unlinked_students = $db->query($sql_unlinked_students);

?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-link mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Link Children to Parent: <strong><?= htmlspecialchars($parent_name) ?></strong></h5>
        </div>
    </div>

    <div class="row">
        <!-- Link New Student Card -->
        <div class="col-lg-5">
            <div class="card card-success h-100 shadow-sm">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle me-2"></i>Link a New Student</h3></div>
                <form method="POST" action="link_student.php?parent_id=<?= $parent_id ?>">
                    <input type="hidden" name="action" value="link">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                            <select class="form-select form-control" id="student_id" name="student_id" required>
                                <option value="">Select a Student to Link</option>
                                <?php if ($result_unlinked_students && $result_unlinked_students->num_rows > 0): ?>
                                    <?php while($student = $result_unlinked_students->fetch_assoc()): ?>
                                        <option value="<?= $student['Id'] ?>">
                                            <?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName'] . ' (' . $student['registration_no'] . ')') ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="relationship_type" class="form-label">Relationship to Child <span class="text-danger">*</span></label>
                            <select class="form-select form-control" id="relationship_type" name="relationship_type" required>
                                <option value="">Select Relationship</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Guardian">Guardian</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success"><i class="fas fa-link me-2"></i>Link Student</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Currently Linked Students Card -->
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-child me-2"></i>Currently Linked Children</h3></div>
                <div class="card-body">
                    <?php if ($result_linked_children && $result_linked_children->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($child = $result_linked_children->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($child['FirstName'] . ' ' . $child['LastName']) ?></h6>
                                        <small class="text-muted">Reg No: <?= htmlspecialchars($child['registration_no']) ?> | Relationship: <?= htmlspecialchars($child['relationship_type']) ?></small>
                                    </div>
                                    <form method="POST" action="link_student.php?parent_id=<?= $parent_id ?>" onsubmit="return confirm('Are you sure you want to unlink this child?');" class="d-inline">
                                        <input type="hidden" name="action" value="unlink">
                                        <input type="hidden" name="relationship_id" value="<?= $child['relationship_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Unlink Student">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </form>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fas fa-folder-open fa-3x text-muted"></i>
                            <p class="mt-3 text-muted">No children are currently linked to this parent.</p>
                        </div>
                    <?php endif; ?>
                </div>
                 <div class="card-footer">
                    <a href="manage_parents.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to All Parents
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php'; 
?>
