<?php
ob_start();
include '../../init.php'; // Corrected path to init.php

$db = dbConn();

// 1. Security Check: Ensure a parent is logged in
if (!isset($_SESSION['ID']) || strtolower($_SESSION['user_role_name'] ?? '') != 'parent') {
    header("Location: " . WEB_URL . "auth/login.php");
    exit();
}

$parent_user_id = (int)$_SESSION['ID'];

// --- Fetch logged-in parent's details ---
$parent_first_name = $_SESSION['first_name'] ?? 'Parent';
$parent_last_name = $_SESSION['last_name'] ?? '';

$sql_get_parent_details = "SELECT Email, TelNo FROM users WHERE Id = '$parent_user_id'";
$result_parent_details = $db->query($sql_get_parent_details);
$parent_contact_details = $result_parent_details->fetch_assoc();

$children = [];
$selected_child_id = null;
$selected_child_details = null;
$exam_results_for_display = [];
$assignment_results_for_display = [];
$quiz_results_for_display = [];
$class_materials_for_display = [];
$invoices_for_display = []; // Array for invoices

// --- 2. Fetch the children linked to this parent ---
$sql_get_children = "
    SELECT u.Id, u.FirstName, u.LastName, sd.registration_no
    FROM users u
    JOIN student_details sd ON u.Id = sd.user_id
    JOIN student_guardian_relationship sgr ON u.Id = sgr.student_user_id
    WHERE sgr.guardian_user_id = '$parent_user_id'
    ORDER BY u.FirstName";
$result_children = $db->query($sql_get_children);
if ($result_children && $result_children->num_rows > 0) {
    while ($child = $result_children->fetch_assoc()) {
        $children[] = $child;
    }
}

// --- 3. Determine which child's results to display ---
if (!empty($children)) {
    if (isset($_GET['child_id']) && is_numeric($_GET['child_id'])) {
        $requested_child_id = (int)$_GET['child_id'];
        $found = false;
        foreach ($children as $child) {
            if ($child['Id'] == $requested_child_id) {
                $selected_child_id = $requested_child_id;
                $selected_child_details = $child;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $selected_child_id = $children[0]['Id'];
            $selected_child_details = $children[0];
        }
    } else {
        $selected_child_id = $children[0]['Id'];
        $selected_child_details = $children[0];
    }
}

// --- 4. Fetch all data for the SELECTED CHILD ---
if ($selected_child_id > 0) {
    // Fetch Exam Results
    $sql_selected_student_exam_results = "SELECT a.title, a.assessment_date, a.max_marks, ar.marks_obtained, gr.grade_name FROM assessments a LEFT JOIN assessment_results ar ON a.id = ar.assessment_id AND ar.student_user_id = '$selected_child_id' JOIN classes c ON a.class_id = c.id LEFT JOIN grades gr ON ar.grade_id = gr.id WHERE a.assessment_type = 'Exam' AND a.status IN ('Published', 'Graded', 'Archived') AND c.id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$selected_child_id' AND status = 'active') ORDER BY a.assessment_date DESC";
    $result_selected_student_exam_results = $db->query($sql_selected_student_exam_results);
    if ($result_selected_student_exam_results) {
        while ($row = $result_selected_student_exam_results->fetch_assoc()) {
            $exam_results_for_display[] = $row;
        }
    }

    // Fetch Assignment Results
    $sql_assignment_results = "SELECT a.title, s.subject_name, a.due_date, a.max_marks, ar.marks_obtained, gr.grade_name FROM assessments a JOIN classes c ON a.class_id = c.id JOIN subjects s ON c.subject_id = s.id LEFT JOIN assessment_results ar ON a.id = ar.assessment_id AND ar.student_user_id = '$selected_child_id' LEFT JOIN grades gr ON ar.grade_id = gr.id WHERE a.assessment_type = 'Assignment' AND a.status IN ('Graded', 'Archived') AND c.id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$selected_child_id' AND status = 'active') ORDER BY a.due_date DESC";
    $result_assignment_results = $db->query($sql_assignment_results);
    if ($result_assignment_results) {
        while ($row = $result_assignment_results->fetch_assoc()) {
            $assignment_results_for_display[] = $row;
        }
    }

    // Fetch Quiz Results
    $sql_quiz_results = "SELECT a.title, a.max_marks, ar.marks_obtained, gr.grade_name, CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name FROM assessment_results ar JOIN assessments a ON ar.assessment_id = a.id JOIN classes c ON a.class_id = c.id JOIN class_levels cl ON c.class_level_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN class_types ct ON c.class_type_id = ct.id LEFT JOIN grades gr ON ar.grade_id = gr.id WHERE ar.student_user_id = '$selected_child_id' AND a.assessment_type = 'Quiz' ORDER BY a.created_at DESC";
    $result_quiz_results = $db->query($sql_quiz_results);
    if ($result_quiz_results) {
        while ($row = $result_quiz_results->fetch_assoc()) {
            $quiz_results_for_display[] = $row;
        }
    }
    
    // Fetch Class Materials
    $sql_materials = "SELECT cm.title, cm.file_name, cm.file_path, cm.created_at, CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name FROM class_materials cm JOIN classes c ON cm.class_id = c.id JOIN class_levels cl ON c.class_level_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN class_types ct ON c.class_type_id = ct.id WHERE cm.class_id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$selected_child_id' AND status = 'active') ORDER BY class_full_name, cm.created_at DESC";
    $result_materials = $db->query($sql_materials);
    if ($result_materials) {
        while ($row = $result_materials->fetch_assoc()) {
            $class_materials_for_display[$row['class_full_name']][] = $row;
        }
    }

    // Fetch Invoices for the selected child
    $sql_invoices = "SELECT i.id as invoice_id, i.invoice_month, i.invoice_year, i.payable_amount, i.status, CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name FROM invoices i JOIN classes c ON i.class_id = c.id JOIN class_levels cl ON c.class_level_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN class_types ct ON c.class_type_id = ct.id WHERE i.student_user_id = '$selected_child_id' ORDER BY i.invoice_year DESC, i.invoice_month DESC";
    $result_invoices = $db->query($sql_invoices);
    if ($result_invoices) {
        while ($row = $result_invoices->fetch_assoc()) {
            $invoices_for_display[] = $row;
        }
    }

    // PASTE THIS CODE BLOCK inside the if ($selected_child_id > 0) { ... } block

    // --- Fetch Messages from Teachers for the selected child ---
    $teacher_messages_for_display = [];
    $sql_messages = "SELECT 
                        comm.*,
                        teacher.FirstName AS TeacherFirstName,
                        teacher.LastName AS TeacherLastName
                     FROM communications comm
                     JOIN users teacher ON comm.teacher_user_id = teacher.Id
                     WHERE 
                        comm.parent_user_id = '$parent_user_id' AND 
                        comm.student_user_id = '$selected_child_id'
                     ORDER BY comm.sent_at DESC";
    $result_messages = $db->query($sql_messages);
    if ($result_messages) {
        while ($row = $result_messages->fetch_assoc()) {
            $teacher_messages_for_display[] = $row;
        }
    }
    
    // After fetching, mark these messages as 'Read'
    $sql_mark_as_read = "UPDATE communications SET read_status = 'Read' WHERE parent_user_id = '$parent_user_id' AND student_user_id = '$selected_child_id' AND read_status = 'Unread'";
    $db->query($sql_mark_as_read);

// END of the code block to paste
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    :root {
        --primary-color: #e0703c;
        --secondary-color: #1cc1ba;
        --light-grey: #f8f9fa;
        --dark-text: #343a40;
        --light-text: #6c757d;
    }

    .dashboard-background {
        background-color: var(--light-grey);
        padding: 40px 0;
        margin-top: 120px !important;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: auto;
    }

    .dashboard-header {
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .dashboard-card {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        padding: 25px;
        margin-bottom: 30px;
        height: 100%;
    }

    .card-title-icon {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .card-title-icon .icon {
        width: 40px;
        height: 40px;
        background-color: var(--secondary-color);
        color: #fff;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .card-title-icon .icon i,
    .card-title-icon .icon svg {
        width: 22px;
        height: 22px;
    }

    .card-title-icon h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dark-text);
        margin: 0;
    }

    .badge-graded {
        background-color: #17a2b8;
        color: #fff;
    }

    .badge-pending {
        background-color: #ffc107;
        color: #333;
    }

    .badge-na {
        background-color: #6c757d;
        color: #fff;
    }

    .accordion-button:not(.collapsed) {
        color: var(--bs-primary);
        background-color: var(--bs-primary-bg-subtle);
    }

    .list-group-item {
        border: none;
        padding: 15px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .list-group-item:last-child {
        border-bottom: none;
    }
    </style>
</head>

<body>
    <div class="dashboard-background">
        <div class="container dashboard-container">
            <div class="dashboard-header">
                <div>
                    <h1>Welcome, <?= htmlspecialchars($parent_first_name . ' ' . $parent_last_name) ?>!</h1>
                    <p class="text-muted">Parent Dashboard</p>
                </div>
                <?php if (!empty($children)): ?>
                <div class="child-selector">
                    <label for="childSelect">Viewing Details For:</label>
                    <select class="form-select" id="childSelect"
                        onchange="location = 'parent.php?child_id=' + this.value;" style="width: auto;">
                        <?php foreach ($children as $child): ?>
                        <option value="<?= $child['Id'] ?>"
                            <?= ($selected_child_id == $child['Id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($child['FirstName'] . ' ' . $child['LastName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($selected_child_id): ?>

            <!-- Payments & Invoices Section -->
            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-dollar-sign"></i></span>
                            <h3>Payments & Invoices for <?= htmlspecialchars($selected_child_details['FirstName']) ?>
                            </h3>
                        </div>
                        <?php if (!empty($invoices_for_display)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Invoice For</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices_for_display as $invoice): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($invoice['class_full_name']) ?></td>
                                        <td><?= date('F Y', mktime(0, 0, 0, $invoice['invoice_month'], 1, $invoice['invoice_year'])) ?>
                                        </td>
                                        <td>LKR <?= htmlspecialchars(number_format($invoice['payable_amount'], 2)) ?>
                                        </td>
                                        <td><?= display_status_badge($invoice['status']) ?></td>
                                        <td>
                                            <?php if ($invoice['status'] == 'Pending' || $invoice['status'] == 'Overdue'): ?>
                                            <a href="view_invoice.php?id=<?= $invoice['invoice_id'] ?>"
                                                class="btn btn-sm btn-primary">Pay Now / Upload Slip</a>
                                            <?php else: ?>
                                            <a href="view_invoice.php?id=<?= $invoice['invoice_id'] ?>"
                                                class="btn btn-sm btn-outline-secondary">View</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted">No invoices found for this child.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Parent Teacher Communication -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="card-title-icon">
                            <span class="icon" style="background-color: #6f42c1;"><i
                                    class="fas fa-envelope-open-text"></i></span>
                            <h3>Messages from Teachers</h3>
                        </div>
                        <?php if (!empty($teacher_messages_for_display)): ?>
                        <div class="accordion" id="messagesAccordion">
                            <?php $msg_index = 0; foreach ($teacher_messages_for_display as $message): $msg_index++; ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="msgHeading<?= $msg_index ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#msgCollapse<?= $msg_index ?>" aria-expanded="false">
                                        <strong><?= htmlspecialchars($message['message_subject']) ?></strong>
                                        <small class="ms-auto text-muted">
                                            From:
                                            <?= htmlspecialchars($message['TeacherFirstName'].' '.$message['TeacherLastName']) ?>
                                            (<?= date('Y-m-d', strtotime($message['sent_at'])) ?>)
                                        </small>
                                    </button>
                                </h2>
                                <div id="msgCollapse<?= $msg_index ?>" class="accordion-collapse collapse"
                                    data-bs-parent="#messagesAccordion">
                                    <div class="accordion-body">
                                        <?= nl2br(htmlspecialchars($message['message_body'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted">No messages found from teachers for this child.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Class Materials Section -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="card-title-icon">
                            <span class="icon"><i class="fas fa-book-open"></i></span>
                            <h3>Class Materials for <?= htmlspecialchars($selected_child_details['FirstName']) ?></h3>
                        </div>
                        <?php if (!empty($class_materials_for_display)): ?>
                        <div class="accordion" id="materialsAccordion">
                            <?php $i = 0; foreach ($class_materials_for_display as $class_name => $materials): $i++; ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?= $i ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse<?= $i ?>" aria-expanded="false"
                                        aria-controls="collapse<?= $i ?>">
                                        <strong><?= htmlspecialchars($class_name) ?></strong>
                                    </button>
                                </h2>
                                <div id="collapse<?= $i ?>" class="accordion-collapse collapse"
                                    aria-labelledby="heading<?= $i ?>" data-bs-parent="#materialsAccordion">
                                    <div class="accordion-body">
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($materials as $material): ?>
                                            <li class="list-group-item">
                                                <div>
                                                    <i class="fas fa-file-pdf text-danger me-2"></i>
                                                    <span><?= htmlspecialchars($material['title']) ?></span><br>
                                                    <small class="text-muted">Uploaded on:
                                                        <?= date('Y-m-d', strtotime($material['created_at'])) ?></small>
                                                </div>
                                                <a href="<?= WEB_URL ?>uploads/materials/<?= htmlspecialchars($material['file_path']) ?>"
                                                    target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download me-1"></i> Download
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted">No class materials available for this child.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <!-- Exam Results Card -->
                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-poll-h"></i></span>
                            <h3>Exam Results</h3>
                        </div>
                        <?php if (!empty($exam_results_for_display)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Date</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exam_results_for_display as $exam_result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($exam_result['title']) ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($exam_result['assessment_date']))) ?>
                                        </td>
                                        <td>
                                            <?php if (!is_null($exam_result['marks_obtained'])): ?>
                                            <strong><?= htmlspecialchars(number_format($exam_result['marks_obtained'], 2)) ?>
                                                /
                                                <?= htmlspecialchars(number_format($exam_result['max_marks'], 2)) ?></strong>
                                            <?php else: ?><span
                                                class="badge badge-pending">Pending</span><?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!is_null($exam_result['grade_name'])): ?>
                                            <span
                                                class="badge badge-graded"><?= htmlspecialchars($exam_result['grade_name']) ?></span>
                                            <?php else: ?><span class="badge badge-na">N/A</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?><p class="text-center text-muted">No exam results available for this child yet.
                        </p><?php endif; ?>
                    </div>
                </div>

                <!-- Assignment Results Card -->
                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-file-alt"></i></span>
                            <h3>Assignment Results</h3>
                        </div>
                        <?php if (!empty($assignment_results_for_display)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Assignment</th>
                                        <th>Due Date</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignment_results_for_display as $assignment_result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($assignment_result['title']) ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($assignment_result['due_date']))) ?>
                                        </td>
                                        <td>
                                            <?php if (!is_null($assignment_result['marks_obtained'])): ?>
                                            <strong><?= htmlspecialchars(number_format($assignment_result['marks_obtained'], 2)) ?>
                                                /
                                                <?= htmlspecialchars(number_format($assignment_result['max_marks'], 2)) ?></strong>
                                            <?php else: ?><span
                                                class="badge badge-pending">Pending</span><?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!is_null($assignment_result['grade_name'])): ?>
                                            <span
                                                class="badge badge-graded"><?= htmlspecialchars($assignment_result['grade_name']) ?></span>
                                            <?php else: ?><span class="badge badge-na">N/A</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?><p class="text-center text-muted">No assignment results available for this child
                            yet.</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quiz Results Card -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-question-circle"></i></span>
                            <h3>Quiz Results</h3>
                        </div>
                        <?php if (!empty($quiz_results_for_display)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Quiz Title</th>
                                        <th>Class</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quiz_results_for_display as $quiz_result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($quiz_result['title']) ?></td>
                                        <td><?= htmlspecialchars($quiz_result['class_full_name']) ?></td>
                                        <td>
                                            <?php if (!is_null($quiz_result['marks_obtained'])): ?>
                                            <strong><?= htmlspecialchars(number_format($quiz_result['marks_obtained'], 2)) ?>
                                                /
                                                <?= htmlspecialchars(number_format($quiz_result['max_marks'], 2)) ?></strong>
                                            <?php else: ?><span
                                                class="badge badge-pending">Pending</span><?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!is_null($quiz_result['grade_name'])): ?>
                                            <span
                                                class="badge badge-graded"><?= htmlspecialchars($quiz_result['grade_name']) ?></span>
                                            <?php else: ?><span class="badge badge-na">N/A</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?><p class="text-center text-muted">No quiz results available for this child yet.
                        </p><?php endif; ?>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card text-center">
                        <h3>No Children Found</h3>
                        <p>There are no students linked to your parent account. Please contact the administration if you
                            believe this is an error.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
<?php
$content = ob_get_clean();
include '../layouts.php';
?>