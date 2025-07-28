<?php
ob_start();
include '../../init.php';

$student_id = (int)($_GET['id'] ?? 0);
if ($student_id <= 0) {
    header("Location: manage_students.php");
    exit();
}

$db = dbConn();
$sql = "SELECT u.*, sd.registration_no, sd.school_name, sd.guardian_name, sd.guardian_contact, sd.guardian_nic 
        FROM users u 
        LEFT JOIN student_details sd ON u.Id = sd.user_id 
        WHERE u.Id = '$student_id' AND u.user_role_id = 4";
$result = $db->query($sql);

if ($result->num_rows == 0) {
    header("Location: manage_students.php?status=notfound");
    exit();
}
$student = $result->fetch_assoc();
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-graduate me-2"></i>Student Profile</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center border-end">
                    <img class="img-fluid img-thumbnail rounded-circle mb-3" 
                         src="../../web/uploads/profile_images/<?= htmlspecialchars(!empty($student['ProfileImage']) ? $student['ProfileImage'] : 'default_avatar.png') ?>" 
                         alt="Student Profile Picture"
                         style="width: 180px; height: 180px; object-fit: cover;">
                    
                    <h3 class="profile-username text-primary"><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($student['registration_no'] ?? '') ?></p>
                    <p class="mt-3"><strong>Account Status:</strong><br><?= display_status_badge($student['Status']) ?></p>
                </div>

                <div class="col-md-8">
                    <h5 class="mb-3 text-secondary" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">User Information</h5>
                    
                    <dl class="row">
                        <dt class="col-sm-3">Username</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['username'] ?? '') ?></dd>

                        <dt class="col-sm-3">Email</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['Email'] ?? '') ?></dd>

                        <dt class="col-sm-3">Telephone</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['TelNo'] ?? '') ?></dd>

                        <dt class="col-sm-3">Gender</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['gender'] ?? '') ?></dd>

                        <dt class="col-sm-3">Date of Birth</dt>
                        <dd class="col-sm-9">: <?= !empty($student['date_of_birth']) ? htmlspecialchars(date('F j, Y', strtotime($student['date_of_birth']))) : '' ?></dd>

                        <dt class="col-sm-3">NIC</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['NIC'] ?? '') ?></dd>

                        <dt class="col-sm-3">Address</dt>
                        <dd class="col-sm-9">: <?= nl2br(htmlspecialchars($student['Address'] ?? '')) ?></dd>
                        
                        <dt class="col-sm-3 text-success">Registered On</dt>
                        <dd class="col-sm-9 text-success">: <?= !empty($student['registered_at']) ? htmlspecialchars(date('l, F j, Y', strtotime($student['registered_at']))) : '' ?></dd>
                    </dl>

                    <h5 class="mb-3 mt-4 text-secondary" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Guardian & School Information</h5>
                    <dl class="row">
                        <dt class="col-sm-3">School Name</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['school_name'] ?? '') ?></dd>
                        
                        <dt class="col-sm-3">Guardian Name</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['guardian_name'] ?? '') ?></dd>

                        <dt class="col-sm-3">Guardian Contact</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['guardian_contact'] ?? '') ?></dd>

                        <dt class="col-sm-3">Guardian NIC</dt>
                        <dd class="col-sm-9">: <?= htmlspecialchars($student['guardian_nic'] ?? '') ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="manage_students.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Student List</a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layouts.php';
?>