<?php
ob_start();
// Path to init.php is correct for /system/dashboard_admin.php
include '../init.php'; 

// --- Security Check for Backend Dashboards ---
// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . SYS_URL . "login.php");
    exit();
}

// Check if the logged-in user has an authorized backend role
$user_role_name = strtolower($_SESSION['user_role_name'] ?? ''); 
$authorized_backend_roles = ['admin', 'manager', 'system operator', 'teacher', 'card checker'];

if (!in_array($user_role_name, $authorized_backend_roles)) {
    // If not an authorized backend role, redirect to system login with an error
    $_SESSION['login_error_message'] = "Access Denied: Your role (" . htmlspecialchars($user_role_name) . ") does not have permission to view this dashboard.";
    header("Location: " . SYS_URL . "login.php");
    exit();
}

// User is authorized, display content
$user_first_name = $_SESSION['first_name'] ?? 'User';
$user_last_name = $_SESSION['last_name'] ?? '';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-tachometer-alt mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Admin Dashboard</h5>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Welcome to the System Backend!</h3>
        </div>
        <div class="card-body">
            <p>Hello, <strong><?= htmlspecialchars($user_first_name . ' ' . $user_last_name) ?></strong> (Role: <?= htmlspecialchars(ucfirst($user_role_name)) ?>).</p>
            <p>This is your central dashboard for managing the Student Management System.</p>
            <p>You can navigate through the sidebar to access different modules.</p>

            <div class="row mt-4">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>150</h3>
                            <p>New Orders</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-bag"></i>
                        </div>
                        <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>53<sup style="font-size: 20px">%</sup></h3>
                            <p>Bounce Rate</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                        <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                </div>
        </div>
        <div class="card-footer">
            <a href="<?= SYS_URL ?>logout.php" class="btn btn-secondary">Logout from System</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts.php'; // layouts.php is in the same directory as dashboard_admin.php
?>