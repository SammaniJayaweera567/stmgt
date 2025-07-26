<?php
ob_start();
include '../../init.php';

// Check if an ID is passed via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $db = dbConn();
    
    // SQL query to get all details of the specific user, including the role name
    $sql = "SELECT u.*, r.RoleName 
            FROM users u 
            LEFT JOIN user_roles r ON u.user_role_id = r.Id 
            WHERE u.Id = '$id'";
            
    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        // If no user is found with that ID, redirect back
        header("Location: manage.php?status=notfound");
        exit();
    }
} else {
    // If someone tries to access this page directly without an ID, redirect them
    header("Location: manage.php");
    exit();
}
?>

<div class="container-fluid mb-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <!-- Changed card class to match add.php for a consistent look -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-circle me-2 mr-1"></i>User Profile</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Removed text-center class to align content to the left -->
                        <div class="col-md-4 border-end">
                            <!-- User's Profile Image -->
                            <img class="img-fluid img-thumbnail rounded-circle mb-3" 
                                 src="../../web/uploads/profile_images/<?= htmlspecialchars(!empty($user['ProfileImage']) ? $user['ProfileImage'] : 'default_avatar.png') ?>" 
                                 alt="User profile picture"
                                 style="width: 160px; height: 160px; object-fit: cover; border: 3px solid #dee2e6;">
                            
                            <!-- User's Full Name -->
                            <h3 class="profile-username text-primary"><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></h3>
                            
                            <!-- User's Role -->
                            <p class="text-muted"><?= htmlspecialchars($user['RoleName']) ?></p>

                             <!-- Account Status -->
                             <p class="mt-3"><strong>Account Status:</strong><br><?= display_status_badge($user['Status']) ?></p>
                        </div>
                        <div class="col-md-8">
                            <h5 class="mb-3 text-secondary" style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">User Information</h5>
                            
                            <!-- Using a Description List for a clean look -->
                            <dl class="row">
                                <dt class="col-sm-3">Username</dt>
                                <dd class="col-sm-9">: <?= htmlspecialchars($user['username']) ?></dd>

                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9">: <?= htmlspecialchars($user['Email']) ?></dd>

                                <dt class="col-sm-3">Telephone</dt>
                                <dd class="col-sm-9">: <?= htmlspecialchars($user['TelNo']) ?></dd>

                                <dt class="col-sm-3">Gender</dt>
                                <dd class="col-sm-9">: <?= htmlspecialchars($user['gender']) ?></dd>

                                <dt class="col-sm-3">Date of Birth</dt>
                                <dd class="col-sm-9">: <?= htmlspecialchars(date('F j, Y', strtotime($user['date_of_birth']))) ?></dd>

                                <dt class="col-sm-3">NIC</dt>
                                <dd class="col-sm-9">: <?= htmlspecialchars($user['NIC']) ?></dd>

                                <dt class="col-sm-3">Address</dt>
                                <dd class="col-sm-9">: <?= nl2br(htmlspecialchars($user['Address'])) ?></dd>
                                
                                <dt class="col-sm-3 text-success">Registered On</dt>
                                <dd class="col-sm-9 text-success">: <?= htmlspecialchars(date('l, F j, Y', strtotime($user['registered_at']))) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <!-- Changed footer to match add.php style (removed text-center and bg-light) -->
                <div class="card-footer">
                    <a href="manage.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to User List</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
