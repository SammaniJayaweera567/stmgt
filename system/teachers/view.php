<?php
ob_start();
include '../../init.php'; // Include init.php

// Check for the user_id from the URL (GET method)
$user_id = null;
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = dataClean($_GET['user_id']);
}

// If no valid ID is provided, redirect back to manage page
if ($user_id === null) {
    header("Location: manage.php?status=notfound");
    exit();
}

$db = dbConn();

// IMPORTANT: Using a Prepared Statement to prevent SQL Injection
$sql = "SELECT 
            u.*, 
            ur.RoleName,
            td.marital_status, 
            td.appointment_date, 
            td.designation, 
            td.qualifications
        FROM users u 
        LEFT JOIN user_roles ur ON u.user_role_id = ur.Id 
        LEFT JOIN teacher_details td ON u.Id = td.user_id
        WHERE u.Id = ? AND ur.RoleName = 'Teacher'";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$teacher = null;
if ($result && $result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
} else {
    // If no teacher is found with that ID, redirect back
    header("Location: manage.php?status=notfound");
    exit();
}
$stmt->close();

// Determine profile image source with the correct path
$profile_image_src = !empty($teacher['ProfileImage']) ? '../uploads/' . htmlspecialchars($teacher['ProfileImage']) : '../assets/img/default_avatar.png';
?>

<div class="container-fluid mb-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-tie me-2 mr-1"></i>Teacher Profile</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center border-end">
                            <img class="img-fluid img-thumbnail rounded-circle mb-3" 
                                 src="<?= $profile_image_src ?>" 
                                 alt="Teacher profile picture"
                                 style="width: 160px; height: 160px; object-fit: cover; border: 3px solid #dee2e6;">
                            
                            <h4 class="profile-username text-primary"><?= htmlspecialchars($teacher['FirstName'] . ' ' . $teacher['LastName']) ?></h4>
                            
                            <p class="text-muted"><?= htmlspecialchars($teacher['designation']) ?></p>

                            <p class="mt-3"><strong>Account Status:</strong><br><?= display_status_badge($teacher['Status']) ?></p>
                        </div>
                        <div class="col-md-8">
                            <h5 class="mb-3 text-secondary" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Personal & Contact Information</h5>
                            
                            <dl class="row">
                                <dt class="col-sm-4">Full Name</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($teacher['FirstName'] . ' ' . $teacher['LastName']) ?></dd>

                                <dt class="col-sm-4">Username</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($teacher['username']) ?></dd>

                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($teacher['Email']) ?></dd>

                                <dt class="col-sm-4">Telephone</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($teacher['TelNo']) ?></dd>

                                <dt class="col-sm-4">Gender</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($teacher['gender']) ?></dd>

                                <dt class="col-sm-4">Date of Birth</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars(date('F j, Y', strtotime($teacher['date_of_birth']))) ?></dd>

                                <dt class="col-sm-4">NIC</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($teacher['NIC']) ?></dd>

                                <dt class="col-sm-4">Address</dt>
                                <dd class="col-sm-8">: <?= nl2br(htmlspecialchars($teacher['Address'])) ?></dd>
                            </dl>

                            <h5 class="mb-3 mt-4 text-secondary" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Professional Details</h5>
                            <dl class="row">
                                <dt class="col-sm-4">Marital Status</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($teacher['marital_status']) ?></dd>

                                <dt class="col-sm-4">Appointment Date</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars(date('F j, Y', strtotime($teacher['appointment_date']))) ?></dd>

                                <dt class="col-sm-4">Qualifications</dt>
                                <dd class="col-sm-8">: <?= nl2br(htmlspecialchars($teacher['qualifications'])) ?></dd>
                                
                                <dt class="col-sm-4">Registered On</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars(date('l, F j, Y', strtotime($teacher['registered_at']))) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="manage.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
                    <a href="edit.php?user_id=<?= htmlspecialchars($teacher['Id']) ?>" class="btn btn-primary"><i class="fas fa-edit me-1"></i> Edit Teacher</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Make sure this path is correct
?>