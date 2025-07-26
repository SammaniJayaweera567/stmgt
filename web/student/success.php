<?php
include '../../init.php';
ob_start();
?>

<style>
.card.success-card {
    margin-top: 187px;
}

.card-header {
    background: #8196e5;
    color: #ffff;
}
</style>
<div class="container-xxl py-5 site-wrap-success">
    <div class="card success-card">
        <div class="card-header">
            <h5>Registration Successful!</h5>
        </div>
        <div class="card-body">
            <h6>Your Registration : J0123456789</h6>
            <p>You can now log in</p>
            <a href="login.php" class="btn btn-primary">Login</a>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>