<?php
ob_start();

// --- FINAL FIX: The init.php file is now solely responsible for starting the session. ---
// We removed the session_start() call from this file to prevent conflicts.
include '../../init.php'; 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    /* Your CSS remains the same */
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }

    .login-background {
        position: relative;
        background-image: url('<?= WEB_URL ?>images/school.jpg');
        background-size: cover;
        background-position: center;
        padding: 50px 15px;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-background::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background: rgba(10, 72, 97, 0.88);
        z-index: 0;
    }

    .login-container {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 450px;
        background-color: #fff;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        margin: auto;
    }

    .login-container h2 {
        color: #e0703c;
        font-weight: 800;
        text-align: center;
        margin-bottom: 35px;
        font-size: 2.2rem;
    }

    .error-message,
    .success-message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        text-align: center;
        font-weight: 500;
    }

    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .success-message {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .input-group {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .input-group label {
        font-size: 16px;
        margin-bottom: 8px;
        display: block;
        font-weight: 500;
        color: #333;
    }

    .input-group .form-control {
        width: 100%;
        height: 50px;
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding-left: 45px;
        box-sizing: border-box;
        font-size: 16px;
    }

    .input-group .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(224, 112, 60, 0.25);
        border-color: #e0703c;
        outline: none;
    }

    .input-group .input-icon {
        position: absolute;
        left: 18px;
        top: 44px;
        color: #adb5bd;
    }

    .password-toggle-icon {
        position: absolute;
        right: 18px;
        top: 44px;
        color: #6c757d;
        cursor: pointer;
    }

    .extra-links {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 15px;
    }

    .extra-links a,
    .form-check-label {
        color: #555;
        text-decoration: none;
    }

    .extra-links a:hover {
        color: #e0703c;
        text-decoration: underline;
    }

    .btn-login {
        background-color: #e0703c;
        border: none;
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        padding: 12px 20px;
        border-radius: 8px;
        width: 100%;
        margin-top: 20px;
        cursor: pointer;
        transition: background-color 0.3s ease; /* fixed here */
    }

    .btn-login:hover {
        background-color: #c76131;
    }

    .register-section {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        font-size: 16px;
    }

    .register-section a {
        color: #e0703c;
        text-decoration: none;
        font-weight: 600;
    }
    </style>
</head>

<body>

    <div class="container-fluid login-background">
        <div class="login-container">
            <h2>User Login</h2>

            <?php
            $error_msg = null;
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                extract($_POST);

                $username = dataClean($username ?? '');
                
                if (empty($username) || empty($password)) {
                    $error_msg = "Username and Password should not be empty!";
                } else {
                    $db = dbConn();
                    
                    $escaped_username = $db->real_escape_string($username);
                    
                    $sql = "SELECT u.*, r.RoleName 
                            FROM users u 
                            LEFT JOIN user_roles r ON u.user_role_id = r.Id 
                            WHERE u.username = '$escaped_username' OR u.Email = '$escaped_username'";
                    
                    $result = $db->query($sql);

                    if ($result && $result->num_rows == 1) {
                        $row = $result->fetch_assoc();
                        
                        if (password_verify($password, $row['Password'])) {
                            
                            if ($row['Status'] == 'Active') {
                                
                                // --- SECURITY IMPROVEMENT: Regenerate session ID ---
                                // This creates a fresh, new session for the logged-in user to prevent session fixation attacks.
                                session_regenerate_id(true);

                                // --- Set Session Variables ---
                                // Use consistent session variable names across the entire system.
                                $_SESSION['user_id'] = $row['Id']; 
                                $_SESSION['username'] = $row['username'];
                                $_SESSION['first_name'] = $row['FirstName'];
                                $_SESSION['last_name'] = $row['LastName']; 
                                $_SESSION['user_role_id'] = $row['user_role_id']; 
                                $_SESSION['user_role_name'] = $row['RoleName'];

                                $_SESSION['login_success_message'] = "Login Successful! Welcome back, " . htmlspecialchars($row['FirstName']) . ".";
                                
                                $role = strtolower($row['RoleName']);
                                if ($role == 'admin' || $role == 'manager' || $role == 'system operator' || $role == 'teacher' || $role == 'card checker') {
                                    // Backend users
                                    header("Location: " . WEB_URL . "dashboard_admin.php");
                                } elseif ($role == 'student') {
                                    header("Location: " . WEB_URL . "dashboard/student.php");
                                } elseif ($role == 'parent') {
                                    header("Location: " . WEB_URL . "dashboard/parent.php");
                                } else {
                                    // Other roles or general public
                                    header("Location: " . WEB_URL . "index.php");
                                }
                                exit();
                            } else {
                                $error_msg = "Your account is not active. Please contact support.";
                            }
                        } else {
                            $error_msg = "Invalid username/email or password.";
                        }
                    } else {
                        $error_msg = "Invalid username/email or password.";
                    }
                }
            }
            
            if ($error_msg) {
                echo '<div class="error-message">' . htmlspecialchars($error_msg) . '</div>';
            }

            // Display logout message if redirected from logout.php
            if (isset($_GET['status']) && $_GET['status'] == 'logout_success') {
                echo '<div class="success-message">You have been successfully logged out.</div>';
            }

            if(isset($_SESSION['registration_success'])) {
                echo '<div class="success-message">' . htmlspecialchars($_SESSION['registration_success']) . '</div>';
                unset($_SESSION['registration_success']);
            }
            ?>

            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
                <div class="input-group">
                    <label for="username">Username or Email</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" class="form-control" id="username" name="username" required
                        value="<?= @htmlspecialchars($username) ?>">
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <i class="fas fa-eye password-toggle-icon" id="togglePassword"></i>
                </div>

                <div class="extra-links">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                        <label class="form-check-label" for="remember_me">Remember me</label>
                    </div>
                    <a href="<?= WEB_URL ?>auth/forgot_password.php">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-login">Login</button>
            </form>

            <div class="register-section">
                <p>Don't have an account? <a href="<?= WEB_URL ?>auth/register_as.php">Register Now</a></p>
            </div>
        </div>
    </div>

    <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    }
    </script>

</body>

</html>
<?php
ob_end_flush();
?>
