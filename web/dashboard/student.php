<?php
ob_start();
include '../../init.php'; // Corrected path to init.php

// 1. Security Check: Ensure a student is logged in
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role_name'] ?? '') != 'student') {
    header("Location: " . WEB_URL . "auth/login.php");
    exit();
}

$db = dbConn();
$logged_in_student_id = (int)$_SESSION['user_id']; 

// 2. Fetch Student's Profile
$sql_profile = "SELECT 
                    u.FirstName, u.LastName, u.Email, u.ProfileImage, sd.registration_no, 
                    MAX(c.class_level_id) as latest_class_level_id
                FROM users u 
                LEFT JOIN student_details sd ON u.Id = sd.user_id
                LEFT JOIN enrollments e ON u.Id = e.student_user_id AND e.status = 'active'
                LEFT JOIN classes c ON e.class_id = c.id
                WHERE u.Id = '$logged_in_student_id'
                GROUP BY u.Id";
$result_profile = $db->query($sql_profile);
$student_details = $result_profile->fetch_assoc();

if (!$student_details || empty($student_details['FirstName'])) {
    die("Error: Student profile not found or incomplete.");
}
$student_level_id = $student_details['latest_class_level_id'] ?? null;

// 3. Fetch Student's Enrolled Classes
$sql_enrolled = "SELECT 
                    c.id as class_id, s.subject_name, cl.level_name, ct.type_name, 
                    c.day_of_week, c.start_time, c.end_time,
                    CONCAT(u_teacher.FirstName, ' ', u_teacher.LastName) AS teacher_full_name,
                    e.status AS enrollment_status
                FROM enrollments e
                JOIN classes c ON e.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_levels cl ON c.class_level_id = cl.id
                JOIN class_types ct ON c.class_type_id = ct.id
                LEFT JOIN users u_teacher ON c.teacher_id = u_teacher.Id
                WHERE e.student_user_id = '$logged_in_student_id' AND e.status = 'active'
                ORDER BY cl.level_name, s.subject_name ASC";
$result_enrolled = $db->query($sql_enrolled);

// 4. Fetch Exam Results
$sql_student_exam_results = "SELECT a.id as assessment_id, a.title, a.assessment_date, a.max_marks, ar.marks_obtained, gr.grade_name
                             FROM assessments a
                             LEFT JOIN assessment_results ar ON a.id = ar.assessment_id AND ar.student_user_id = '$logged_in_student_id'
                             JOIN classes c ON a.class_id = c.id
                             LEFT JOIN grades gr ON ar.grade_id = gr.id
                             WHERE a.assessment_type_id = 1 AND a.status IN ('Published', 'Graded', 'Archived')
                             AND c.id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$logged_in_student_id' AND status = 'active')
                             ORDER BY a.assessment_date DESC";
$result_student_exam_results = $db->query($sql_student_exam_results);


// 5. Fetch Upcoming Exams
$current_datetime = date('Y-m-d H:i:s');
$sql_upcoming_exams = "SELECT a.id as assessment_id, a.title, a.assessment_date, a.start_time, a.end_time, s.subject_name, cl.level_name
                       FROM assessments a
                       JOIN classes c ON a.class_id = c.id
                       JOIN subjects s ON a.subject_id = s.id
                       JOIN class_levels cl ON c.class_level_id = cl.id
                       WHERE a.assessment_type_id = 1 AND a.status = 'Published'
                       AND CONCAT(a.assessment_date, ' ', a.end_time) > '$current_datetime'
                       AND c.id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$logged_in_student_id' AND status = 'active')
                       ORDER BY a.assessment_date ASC";
$result_upcoming_exams = $db->query($sql_upcoming_exams);

// 6. Fetch Assignments
// 6. Fetch Assignments - UPDATED
$sql_student_assignments = "SELECT a.id as assignment_id, a.title, a.max_marks, a.due_date, ss.submission_status, ar.marks_obtained, gr.grade_name
FROM assessments a
JOIN classes c ON a.class_id = c.id
LEFT JOIN student_submissions ss ON a.id = ss.assessment_id AND ss.student_user_id = '$logged_in_student_id'
LEFT JOIN assessment_results ar ON a.id = ar.assessment_id AND ar.student_user_id = '$logged_in_student_id'
LEFT JOIN grades gr ON ar.grade_id = gr.id
WHERE a.assessment_type_id = 2 AND a.status IN ('Published', 'Graded')
AND c.id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$logged_in_student_id' AND status = 'active')
ORDER BY a.due_date ASC";
$result_student_assignments = $db->query($sql_student_assignments);

// 7. Fetch Quizzes for the logged-in student with full class name
// 7. Fetch Quizzes for the logged-in student with full class name - UPDATED
$sql_student_quizzes = "
SELECT 
a.id as quiz_id,
a.title,
a.time_limit_minutes,
a.max_marks,
ar.marks_obtained,
CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name
FROM assessments a
JOIN classes c ON a.class_id = c.id
JOIN subjects s ON c.subject_id = s.id
JOIN class_levels cl ON c.class_level_id = cl.id
JOIN class_types ct ON c.class_type_id = ct.id
LEFT JOIN assessment_results ar ON a.id = ar.assessment_id AND ar.student_user_id = '$logged_in_student_id'
WHERE a.assessment_type_id = 3 
AND a.status = 'Published'
AND c.id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$logged_in_student_id' AND status = 'active')
ORDER BY a.created_at DESC
";
$result_student_quizzes = $db->query($sql_student_quizzes);

// 8. Fetch Class Materials for all enrolled classes
$sql_materials = "SELECT 
                    cm.title,
                    cm.file_name,
                    cm.file_path,
                    cm.created_at,
                    CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name
                FROM class_materials cm
                JOIN classes c ON cm.class_id = c.id
                JOIN class_levels cl ON c.class_level_id = cl.id
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_types ct ON c.class_type_id = ct.id
                WHERE cm.class_id IN (SELECT class_id FROM enrollments WHERE student_user_id = '$logged_in_student_id' AND status = 'active')
                ORDER BY class_full_name, cm.created_at DESC";

$result_materials = $db->query($sql_materials);
$class_materials_grouped = [];
if ($result_materials) {
    while ($row = $result_materials->fetch_assoc()) {
        $class_materials_grouped[$row['class_full_name']][] = $row;
    }
}

// =================================================================================================
// START: NEWLY ADDED CODE FOR PAYMENTS
// =================================================================================================
// 9. Fetch Invoices for the logged-in student
$sql_invoices = "SELECT 
                    i.id as invoice_id,
                    i.invoice_month,
                    i.invoice_year,
                    i.payable_amount,
                    i.status,
                    CONCAT(cl.level_name, ' - ', s.subject_name, ' (', ct.type_name, ')') as class_full_name
                FROM invoices i
                JOIN classes c ON i.class_id = c.id
                JOIN class_levels cl ON c.class_level_id = cl.id
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_types ct ON c.class_type_id = ct.id
                WHERE i.student_user_id = '$logged_in_student_id'
                ORDER BY i.invoice_year DESC, i.invoice_month DESC";
$result_invoices = $db->query($sql_invoices);
// =================================================================================================
// END: NEWLY ADDED CODE
// =================================================================================================

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    /* Your CSS remains the same */
    :root {
        --primary-color: #e0703c;
        --secondary-color: #1cc1ba;
        --light-grey: #f8f9fa;
        --dark-text: #343a40;
        --light-text: #6c757d;
    }

    .dashboard-background {
        background-color: var(--light-grey);
        padding: 40px 15px;
        margin-top: 120px !important;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: auto;
    }

    .dashboard-header {
        margin-bottom: 30px;
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
        margin: 0;
    }

    .flash-message {
        padding: 15px;
        margin-bottom: 25px;
        border-radius: 8px;
        text-align: center;
        font-weight: 500;
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
    }

    .profile-card {
        display: flex;
        align-items: center;
        padding: 20px;
    }

    .profile-pic {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 4px solid var(--secondary-color);
        object-fit: cover;
        margin-right: 25px;
    }

    .profile-info h3 {
        margin: 0 0 5px 0;
    }

    .profile-info p {
        margin: 4px 0;
        font-size: 0.95rem;
    }

    .profile-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
    }

    .edit-profile-btn,
    .logout-btn {
        padding: 8px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        color: white !important;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .edit-profile-btn {
        background-color: var(--primary-color);
    }

    .logout-btn {
        background-color: #6c757d;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }

    .btn-enroll-more {
        display: block;
        text-align: center;
        background-color: var(--secondary-color);
        color: white;
        padding: 12px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 20px;
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

    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #fff;
    }

    .badge-info {
        background-color: #17a2b8;
    }

    .badge-success {
        background-color: #28a745;
    }

    .badge-warning {
        background-color: #ffc107;
        color: #333 !important;
    }

    .badge-danger {
        background-color: #dc3545;
    }

    .badge-secondary {
        background-color: #6c757d;
    }

    .accordion-button:not(.collapsed) {
        color: var(--bs-primary);
        background-color: var(--bs-primary-bg-subtle);
    }

    .badge-graded {
        background-color: #1a6289;
        color: #ffffff;
    }
    </style>
</head>

<body>
    <div class="dashboard-background">
        <div class="container dashboard-container">

            <?php
            if (isset($_SESSION['login_success_message'])) {
                echo '<div class="flash-message">' . htmlspecialchars($_SESSION['login_success_message']) . '</div>';
                unset($_SESSION['login_success_message']);
            }
            ?>

            <div class="dashboard-header">
                <h1>Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h1>
                <p>Here is your academic summary and quick access to your resources.</p>
            </div>

            <div class="row mb-5">
                <div class="col-12">
                    <div class="dashboard-card profile-card">
                        <img src="<?= WEB_URL ?>uploads/profile_images/<?= !empty($student_details['ProfileImage']) ? htmlspecialchars($student_details['ProfileImage']) : 'default_avatar.png' ?>"
                            alt="Profile Picture" class="profile-pic">
                        <div class="profile-info">
                            <h3><?= htmlspecialchars($student_details['FirstName'] . ' ' . $student_details['LastName']) ?>
                            </h3>
                            <p><strong>Reg No:</strong> <?= htmlspecialchars($student_details['registration_no']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($student_details['Email']) ?></p>
                        </div>
                        <div class="profile-actions">
                            <a href="edit_profile.php" class="edit-profile-btn"><i class="fas fa-pencil-alt"></i> Edit
                                Profile</a>
                            <a href="<?= WEB_URL ?>auth/logout.php" class="logout-btn"><i
                                    class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- START: PAYMENTS SECTION -->
            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-dollar-sign"></i></span>
                            <h3>My Payments & Invoices</h3>
                        </div>
                        <?php if ($result_invoices && $result_invoices->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
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
                                    <?php while($invoice = $result_invoices->fetch_assoc()): ?>
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
                                                class="btn btn-sm btn-primary">Pay Now</a>
                                            <?php else: ?>
                                            <a href="view_invoice.php?id=<?= $invoice['invoice_id'] ?>"
                                                class="btn btn-sm btn-outline-secondary">View</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted">You have no invoices yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- END: PAYMENTS SECTION -->

            <div class="row mb-5 mt-3">
                <div class="col-lg-12">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-chalkboard-teacher"></i></span>
                            <h3>My Enrolled Classes</h3>
                        </div>
                        <?php if ($result_enrolled && $result_enrolled->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Classes and Subjects</th>
                                    <th>Teacher</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php mysqli_data_seek($result_enrolled, 0); while($enrollment = $result_enrolled->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($enrollment['level_name'] . ' - ' . $enrollment['subject_name'] . ' (' . $enrollment['type_name'] . ')') ?>
                                    </td>
                                    <td><?= htmlspecialchars($enrollment['teacher_full_name']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['day_of_week']) ?><br><small
                                            class="text-muted"><?= htmlspecialchars(date('h:i A', strtotime($enrollment['start_time']))) ?>
                                            -
                                            <?= htmlspecialchars(date('h:i A', strtotime($enrollment['end_time']))) ?></small>
                                    </td>
                                    <td><?= display_status_badge($enrollment['enrollment_status']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p>You have not enrolled in any classes yet.</p>
                        <?php endif; ?>
                        <a href="<?= WEB_URL ?>index.php" class="btn-enroll-more">Enroll in Classes</a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="dashboard-card">
                        <div class="card-title-icon">
                            <span class="icon"><i class="fas fa-book-open"></i></span>
                            <h3>My Class Materials</h3>
                        </div>
                        <?php if (!empty($class_materials_grouped)): ?>
                        <div class="accordion" id="materialsAccordion">
                            <?php $i = 0; foreach ($class_materials_grouped as $class_name => $materials): $i++; ?>
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
                        <p class="text-center text-muted">No class materials have been uploaded for your enrolled
                            classes yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-poll-h"></i></span>
                            <h3>My Exam Results</h3>
                        </div>
                        <?php if ($result_student_exam_results && $result_student_exam_results->num_rows > 0): ?>
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
                                    <?php while ($exam_result = $result_student_exam_results->fetch_assoc()): ?>
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
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?><p class="text-center text-muted">No exam results available for you yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-calendar-alt"></i></span>
                            <h3>Upcoming Exams</h3>
                        </div>
                        <?php if ($result_upcoming_exams && $result_upcoming_exams->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php mysqli_data_seek($result_upcoming_exams, 0); while($exam = $result_upcoming_exams->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <div>
                                    <span
                                        class="subject"><strong><?= htmlspecialchars($exam['title']) ?></strong></span><br>
                                    <small
                                        class="text-muted"><?= htmlspecialchars($exam['level_name'] . ' - ' . $exam['subject_name']) ?></small>
                                </div>
                                <div class="text-end">
                                    <span
                                        class="badge bg-primary"><?= htmlspecialchars(date('M d, Y', strtotime($exam['assessment_date']))) ?></span><br>
                                    <small
                                        class="text-muted"><?= htmlspecialchars(date('h:i A', strtotime($exam['start_time']))) ?>
                                        - <?= htmlspecialchars(date('h:i A', strtotime($exam['end_time']))) ?></small>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-center text-muted">No upcoming exams scheduled.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <div class="card-title-icon"><span class="icon"><i class="fas fa-file-alt"></i></span>
                            <h3>My Assignments</h3>
                        </div>
                        <?php if ($result_student_assignments && $result_student_assignments->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php mysqli_data_seek($result_student_assignments, 0); while($assignment = $result_student_assignments->fetch_assoc()): 
                                 $is_submitted = !empty($assignment['submission_status']);
                                 $is_graded = !is_null($assignment['marks_obtained']);
                                 $due_date_timestamp = strtotime($assignment['due_date']);
                                 $is_overdue = time() > $due_date_timestamp && !$is_submitted;
                                 
                                 $status_class = 'badge-secondary'; $status_text = 'Pending';
                                 if ($is_graded) { $status_class = 'badge-info'; $status_text = 'Graded (' . htmlspecialchars($assignment['grade_name'] ?? 'N/A') . ')'; } 
                                 elseif ($is_submitted) { $status_class = 'badge-success'; $status_text = 'Submitted'; } 
                                 elseif ($is_overdue) { $status_class = 'badge-danger'; $status_text = 'Overdue'; }
                             ?>
                            <li class="list-group-item">
                                <div>
                                    <span
                                        class="subject"><strong><?= htmlspecialchars($assignment['title']) ?></strong></span><br>
                                    <small class="text-muted">Max Marks:
                                        <?= htmlspecialchars(number_format($assignment['max_marks'], 2)) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span><br>
                                    <small class="text-muted">Due:
                                        <?= htmlspecialchars(date('M d, Y', $due_date_timestamp)) ?></small><br>
                                    <a href="view_assignment.php?assignment_id=<?= $assignment['assignment_id'] ?>"
                                        class="btn btn-sm btn-outline-primary mt-1">View/Submit</a>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-center text-muted">No assignments available for you yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="card-title-icon">
                            <span class="icon"><i class="fas fa-question-circle"></i></span>
                            <h3>My Quizzes</h3>
                        </div>
                        <?php if ($result_student_quizzes && $result_student_quizzes->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php mysqli_data_seek($result_student_quizzes, 0); while($quiz = $result_student_quizzes->fetch_assoc()): 
                                $is_attempted = !is_null($quiz['marks_obtained']);
                            ?>
                            <li class="list-group-item">
                                <div>
                                    <span
                                        class="subject"><strong><?= htmlspecialchars($quiz['title']) ?></strong></span><br>
                                    <small class="text-muted"><?= htmlspecialchars($quiz['class_full_name']) ?> |
                                        <?= htmlspecialchars($quiz['time_limit_minutes']) ?> Minutes</small>
                                </div>
                                <div class="text-end">
                                    <?php if ($is_attempted): ?>
                                    <span class="status-badge badge-info">Completed</span><br>
                                    <small class="text-muted">Marks:
                                        <?= htmlspecialchars(number_format($quiz['marks_obtained'], 2)) ?> /
                                        <?= htmlspecialchars(number_format($quiz['max_marks'], 2)) ?></small><br>
                                    <a href="view_quiz_result.php?quiz_id=<?= $quiz['quiz_id'] ?>"
                                        class="btn btn-sm btn-outline-secondary mt-1">View Result</a>
                                    <?php else: ?>
                                    <span class="status-badge badge-success">Available</span><br>
                                    <small class="text-muted">Max Marks:
                                        <?= htmlspecialchars(number_format($quiz['max_marks'], 2)) ?></small><br>
                                    <a href="take_quiz.php?quiz_id=<?= $quiz['quiz_id'] ?>"
                                        class="btn btn-sm btn-primary mt-1">Start Quiz</a>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-center text-muted">No quizzes available for you at the moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>