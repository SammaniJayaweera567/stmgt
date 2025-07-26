<?php
// init.php file එක include කිරීම සහ session ආරම්භ කිරීම
// ඔබගේ init.php එකේ session_start(); නොමැති නම් එය මෙතැනට එක් කරන්න
include '../init.php';

// PHP Logic කොටස
$messages = array(); // පණිවිඩ සඳහා array එකක්
$submitted_email = ''; // Form එකේ email එක රඳවා ගැනීමට

// Form එක submit කර ඇත්දැයි පරීක්ෂා කිරීම
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $Email = isset($_POST['Email']) ? $_POST['Email'] : '';
    $Password = isset($_POST['Password']) ? $_POST['Password'] : '';
    $submitted_email = $Email; // වැරදුනත් email එක form එකේ රඳවා ගැනීමට

    // අනිවාර්යය ಕ್ಷೇತ್ರ (compulsory fields) පරීක්ෂාව
    if (empty($Email)) {
        $messages['Email'] = 'Email should not be blank!';
    }
    if (empty($Password)) {
        $messages['Password'] = "Password should not be blank!";
    }

    // වෙනත් දෝෂ නොමැති නම් database පරීක්ෂාව
    if (empty($messages)) {
        $db = dbConn();

        // SQL Injection වලින් ආරක්ෂා වීමට Prepared Statement එකක් සකස් කිරීම
        $sql = "SELECT u.Id, u.LastName, u.Password, u.Status, u.user_role_id, r.RoleName 
                FROM users u 
                INNER JOIN user_roles r ON r.Id = u.user_role_id 
                WHERE u.Email = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $Email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();

            // Status එකේ whitespace සහ case-sensitive ගැටලු නිරාකරණය කර පරීක්ෂා කිරීම
            if (trim(strtolower($row['Status'])) == 'active') {

                // Password එක නිවැරදිදැයි පරීක්ෂා කිරීම
                if (password_verify($Password, $row['Password'])) {
                    // Login සාර්ථකයි, session variables සකසන්න
                    $_SESSION['user_id'] = $row['Id'];
                    $_SESSION['user_name'] = $row['LastName'];
                    $_SESSION['user_role_id'] = $row['user_role_id'];
                    $_SESSION['user_role_name'] = $row['RoleName'];
                    $_SESSION['loggedin'] = true;

                    // Dashboard එකට redirect කිරීම
                    header("Location: index.php");
                    exit();
                } else {
                    // වැරදි password
                    $messages['Invalid'] = "The Email or Password you entered is incorrect.";
                }
            } else {
                // Account එක අක්‍රිය කර ඇත්නම්
                $messages['Invalid'] = "Your account has been deactivated. Please contact an administrator.";
            }
        } else {
            // User සොයාගත නොහැකි නම්
            $messages['Invalid'] = "The Email or Password you entered is incorrect.";
        }
        $stmt->close();
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LogIn</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="<?= SYS_URL ?>dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="card card-outline card-primary" style="border-top: 3px solid #46667b">
            <div class="card-header text-center">
                <img src="dist/img/thaksalawa-logo.png" alt="Image" class="img-fluid">
            </div>
            <div class="card-body">
                <p class="login-box-msg">Sign in to start your session</p>

                <?php if (!empty($messages)) : ?>
                    <div class="alert alert-danger p-2" role="alert">
                        <?php
                        if (isset($messages['Email'])) echo $messages['Email'] . '<br>';
                        if (isset($messages['Password'])) echo $messages['Password'] . '<br>';
                        if (isset($messages['Invalid'])) echo $messages['Invalid'];
                        ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" novalidate>
                    <div class="input-group mb-3">
                        <input type="text" id="Email" name="Email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($submitted_email) ?>">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" id="Password" name="Password" class="form-control" placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember">
                                <label for="remember">Remember Me</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block" style="background-color: #e0703c;">Sign In</button>
                        </div>
                    </div>
                </form>

                <p class="mb-1 mt-3">
                    <a href="forgot-password.html">I forgot my password</a>
                </p>
                <p class="mb-0">
                    <a href="register.html" class="text-center">Register a new membership</a>
                </p>
            </div>
        </div>
    </div>
    <script src="<?= SYS_URL ?>plugins/jquery/jquery.min.js"></script>
    <script src="<?= SYS_URL ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= SYS_URL ?>dist/js/adminlte.min.js"></script>
</body>
</html>