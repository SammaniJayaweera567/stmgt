<?php
// Include necessary files and configurations at the beginning of the page
ob_start();
// The logic in layouts.php will automatically handle the nav buttons.
include '../../init.php'; 

// Start session to access session variables
// session_start();

// Retrieve the registration number from the session
$registration_no = '';
if (isset($_SESSION['registration_no'])) {
    $registration_no = $_SESSION['registration_no'];
    // Unset the session variable after displaying it to prevent it from persisting
    unset($_SESSION['registration_no']); 
}
?>

<style>
    /* --- Background and Main Layout (Consistent with other forms) --- */
    .success-background {
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
        min-height: calc(100vh - 98px); /* Full height minus header */
    }

    /* Dark overlay for better text readability */
    .success-background::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background: rgba(10, 72, 97, 0.88);
        z-index: -1;
    }

    /* --- White container box for the success message --- */
    .success-container {
        width: 100%;
        max-width: 550px;
        background-color: #fff;
        padding: 50px 40px;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        text-align: center;
        transform: scale(0.95);
        animation: pop-in 0.5s forwards ease-out;
        margin: 120px auto 30px;
    }

    @keyframes pop-in {
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* --- Success Icon (Checkmark) --- */
    .success-icon {
        width: 80px;
        height: 80px;
        background-color: #1cc1ba; /* Teal color */
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 25px;
    }

    .success-icon svg {
        width: 40px;
        height: 40px;
        color: #fff;
    }

    /* --- Title and Message --- */
    .success-container h2 {
        color: #1cc1ba; /* Teal color */
        font-weight: 800;
        margin-bottom: 15px;
        font-size: 2rem;
    }

    .success-container p {
        color: #6c757d;
        margin-bottom: 35px;
        font-size: 18px;
        line-height: 1.6;
    }

    /* Style for the registration number */
    .registration-info {
        background-color: #f0f8ff; /* Light blue background */
        border: 1px solid #cceeff; /* Light blue border */
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-size: 1.1em;
        color: #0056b3; /* Darker blue text */
        font-weight: bold;
    }
    
    /* --- Login Button --- */
    .btn-login {
        background-color: #e0703c; /* Main theme orange color */
        border-color: #e0703c;
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        padding: 12px 30px;
        border-radius: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-login:hover {
        background-color: #c76131;
        border-color: #c76131;
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

</style>

<div class="container-fluid success-background">
    <div class="success-container">
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        
        <h2>Registration Successful!</h2>
        <p>Thank you for registering. You can now log in to your account using the credentials you created.</p>
        
        <?php if (!empty($registration_no)): ?>
            <div class="registration-info">
                Your Registration Number is: <strong><?= htmlspecialchars($registration_no) ?></strong>
            </div>
        <?php endif; ?>

        <a href="<?= WEB_URL ?>auth/login.php" class="btn btn-login">Proceed to Login</a>
    </div>
</div>

<?php
// Get the buffered HTML content into the $content variable and include the layout file
$content = ob_get_clean();
include '../layouts.php';
?>