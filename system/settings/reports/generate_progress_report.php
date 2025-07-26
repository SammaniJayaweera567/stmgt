<?php
ob_start();
include '../../../init.php';

// Check if a student ID was submitted
if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
    die("No student selected. Please go back and select a student.");
}

$student_id = dataClean($_POST['student_id']);
$db = dbConn();

// --- Query 1: Get Student's Personal Details ---
$student_sql = "SELECT u.*, sd.registration_no, sd.school_name 
                FROM users u 
                LEFT JOIN student_details sd ON u.Id = sd.user_id 
                WHERE u.Id = '$student_id' AND u.user_role_id = 4";
$student_result = $db->query($student_sql);
if ($student_result->num_rows == 0) {
    die("Selected student not found or is not a valid student.");
}
$student = $student_result->fetch_assoc();

// --- Query 2: Get Student's Assessment Results ---
$marks_sql = "SELECT s.subject_name, a.title, ar.marks_obtained, a.max_marks, g.grade_name 
              FROM assessment_results ar 
              JOIN assessments a ON ar.assessment_id = a.id 
              JOIN subjects s ON a.subject_id = s.id 
              LEFT JOIN grades g ON ar.grade_id = g.id 
              WHERE ar.student_user_id = '$student_id' 
              ORDER BY s.subject_name, a.assessment_date";
$marks_result = $db->query($marks_sql);

// Group marks by subject
$performance_by_subject = [];
if ($marks_result) {
    while($row = $marks_result->fetch_assoc()) {
        $performance_by_subject[$row['subject_name']][] = $row;
    }
}

// --- Query 3: Get Student's Attendance Summary ---
$attendance_sql = "SELECT c.id, CONCAT(cl.level_name, ' | ', s.subject_name) AS class_description, 
                   SUM(CASE WHEN att.status = 'Present' THEN 1 ELSE 0 END) AS present_days, 
                   SUM(CASE WHEN att.status = 'Absent' THEN 1 ELSE 0 END) AS absent_days 
                   FROM attendance att 
                   JOIN classes c ON att.class_id = c.id 
                   JOIN subjects s ON c.subject_id = s.id 
                   JOIN class_levels cl ON c.class_level_id = cl.id 
                   WHERE att.student_user_id = '$student_id' 
                   GROUP BY att.class_id";
$attendance_result = $db->query($attendance_sql);

?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 text-end">
            <button onclick="window.print()" class="btn btn-info"><i class="fas fa-print"></i> Print Report</button>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2 class="card-title mb-0">Student Progress Report</h2>
        </div>
        <div class="card-body">
            <h4>1. Student Details</h4>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></p>
                    <p><strong>Registration No:</strong> <?= htmlspecialchars($student['registration_no']) ?></p>
                    <p><strong>NIC:</strong> <?= htmlspecialchars($student['NIC']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Email:</strong> <?= htmlspecialchars($student['Email']) ?></p>
                    <p><strong>Telephone:</strong> <?= htmlspecialchars($student['TelNo']) ?></p>
                    <p><strong>School:</strong> <?= htmlspecialchars($student['school_name']) ?></p>
                </div>
            </div>

            <h4 class="mt-4">2. Academic Performance</h4>
            <hr>
            <?php if (!empty($performance_by_subject)): ?>
                <?php foreach($performance_by_subject as $subject => $assessments): ?>
                    <h5>Subject: <?= htmlspecialchars($subject) ?></h5>
                    <table class="table table-bordered table-sm mb-4">
                        <thead class="table-light">
                            <tr>
                                <th>Assessment</th>
                                <th>Marks</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assessments as $assessment): ?>
                            <tr>
                                <td><?= htmlspecialchars($assessment['title']) ?></td>
                                <td><?= htmlspecialchars($assessment['marks_obtained']) ?> / <?= htmlspecialchars($assessment['max_marks']) ?></td>
                                <td><?= htmlspecialchars($assessment['grade_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No assessment results found for this student.</p>
            <?php endif; ?>

            <h4 class="mt-4">3. Attendance Summary</h4>
            <hr>
            <?php if ($attendance_result && $attendance_result->num_rows > 0): ?>
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Class</th>
                            <th>Present Days</th>
                            <th>Absent Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $attendance_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['class_description']) ?></td>
                            <td><?= htmlspecialchars($row['present_days']) ?></td>
                            <td><?= htmlspecialchars($row['absent_days']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No attendance records found for this student.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
// We include a simpler layout for printing, or you can create a specific 'print_layout.php'
include '../../layouts.php'; 
?>