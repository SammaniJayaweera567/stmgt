<?php
// Include necessary files and configurations at the beginning of the page
ob_start();
// The logic in layouts.php will automatically handle the nav buttons.
include '../../init.php'; 
?>

<style>
    .forgot-password-background {
        position: relative;
        background-image: url('<?= WEB_URL ?>images/school.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        padding: 50px 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    .forgot-password-background::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background: rgba(10, 72, 97, 0.88);
        z-index: -1;
    }

    .forgot-password-container {
        width: 100%;
        max-width: 500px;
        background-color: #fff;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        margin: 120px auto 30px;
        text-align: center;
    }

    .forgot-password-container h2 {
        color: #e0703c;
        font-weight: 800;
        margin-bottom: 15px;
        font-size: 2.2rem;
    }

    .forgot-password-container p {
        color: #6c757d;
        margin-bottom: 30px;
        font-size: 16px;
    }
    
    .form-control {
        border-radius: 8px;
        padding: 12px;
        border: 1px solid #ced4da;
        height: 50px;
        text-align: left;
        transition: all 0.3s ease-in-out;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(224, 112, 60, 0.25);
        border-color: #e0703c;
    }

    .btn-reset {
        background-color: #e0703c;
        border-color: #e0703c;
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        padding: 12px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
        width: 100%;
    }

    .btn-reset:hover {
        background-color: #c76131;
        border-color: #c76131;
        transform: translateY(-2px);
    }

    .back-to-login {
        margin-top: 25px;
        font-size: 16px;
    }

    .back-to-login a {
        color: #555;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .back-to-login a:hover {
        color: #e0703c;
    }

</style>

<!-- HTML Content -->
<div class="container-fluid forgot-password-background">
    <div class="forgot-password-container">
        <h2>Forgot Password?</h2>
        <p>No problem. Just enter the email address you used to register and we will send you a password reset link.</p>
        
        <form action="<?= WEB_URL ?>auth/handle_reset_password.php" method="post">
            <div class="mb-4">
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
            </div>

            <button type="submit" class="btn btn-reset">Send Reset Link</button>
        </form>

        <div class="back-to-login">
            <a href="<?= WEB_URL ?>auth/login.php">← Back to Login</a>
        </div>
    </div>
</div>

<?php
// Get the buffered HTML content into the $content variable and include the layout file
$content = ob_get_clean();
include '../layouts.php';
?>
