<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'add_teacher')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// --- Validation Functions ---
function isValidNIC($nic) {
    $len = strlen($nic);
    if ($len === 10) {
        $digits = substr($nic, 0, 9);
        $lastChar = strtoupper($nic[9]);
        return ctype_digit($digits) && ($lastChar === 'V' || $lastChar === 'X');
    } elseif ($len === 12) {
        return ctype_digit($nic);
    }
    return false;
}
function isValidMobile($number) {
    if (empty($number)) return true; // Allow empty
    return strlen($number) === 10 && ctype_digit($number);
}
function isBirthYearMatchingNIC($nic, $dob) {
    if (empty($nic) || empty($dob)) return true;
    $nic_year = '';
    $len = strlen($nic);
    if ($len === 10) {
        $nic_year = "19" . substr($nic, 0, 2);
    } elseif ($len === 12) {
        $nic_year = substr($nic, 0, 4);
    }
    return !empty($nic_year) && ($nic_year === substr($dob, 0, 4));
}
function isStrongPassword($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return false;
    return true;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    extract($_POST);

    // --- Data Cleaning ---
    $FirstName = dataClean(@$FirstName);
    $LastName = dataClean(@$LastName);
    $date_of_birth = dataClean(@$date_of_birth);
    $gender = dataClean(@$gender);
    $Address = dataClean(@$Address);
    $TelNo = dataClean(@$TelNo);
    $Email = dataClean(@$Email);
    $username = dataClean(@$username);
    $NIC = dataClean(@$NIC);
    $designation = dataClean(@$designation);
    $marital_status = dataClean(@$marital_status);
    $appointment_date = dataClean(@$appointment_date);
    $qualifications = dataClean(@$qualifications);

    // --- Validation ---
    $messages = array();
    if (empty($FirstName)) { $messages['FirstName'] = "First Name is required."; }
    if (empty($Email)) { $messages['Email'] = "Email is required."; }
    if (empty($username)) { $messages['username'] = "Username is required."; }
    if (empty($designation)) { $messages['designation'] = "Designation is required."; }
    if (empty($appointment_date)) { $messages['appointment_date'] = "Appointment Date is required."; }

    if (empty($NIC)) {
        $messages['NIC'] = "NIC is required.";
    } elseif (!isValidNIC($NIC)) {
        $messages['NIC'] = "Invalid NIC format.";
    } elseif (!isBirthYearMatchingNIC($NIC, $date_of_birth)) {
        $messages['NIC'] = "NIC does not match the year of birth.";
    }

    if (!isValidMobile($TelNo)) {
        $messages['TelNo'] = "Invalid mobile number format (e.g., 07xxxxxxxx).";
    }

    if (empty($Password)) {
        $messages['Password'] = "Password is required.";
    } elseif (!isStrongPassword($Password)) {
        $messages['Password'] = "Password must be strong (min 8 chars, upper, lower, number, special char).";
    }
    
    $db = dbConn();
    if (!empty($Email) && $db->query("SELECT Id FROM users WHERE Email='$Email'")->num_rows > 0) {
        $messages['Email'] = "This Email address already exists.";
    }
    if (!empty($username) && $db->query("SELECT Id FROM users WHERE username='$username'")->num_rows > 0) {
        $messages['username'] = "This Username already exists.";
    }
    if (!empty($NIC) && isValidNIC($NIC) && $db->query("SELECT Id FROM users WHERE NIC='$NIC'")->num_rows > 0) {
        $messages['NIC'] = "This NIC already exists.";
    }

    // --- File Upload Handling ---
    $file_new_name = '';
    if (isset($_FILES['ProfileImage']) && $_FILES['ProfileImage']['error'] === 0) {
        $file_ext = strtolower(pathinfo($_FILES['ProfileImage']['name'], PATHINFO_EXTENSION));
        $file_new_name = uniqid('teacher_', true) . '.' . $file_ext;
        move_uploaded_file($_FILES['ProfileImage']['tmp_name'], '../uploads/' . $file_new_name);
    } else {
        $messages['ProfileImage'] = "Profile Image is required.";
    }

    if (empty($messages)) {
        $role_sql = "SELECT Id FROM user_roles WHERE RoleName = 'Teacher' LIMIT 1";
        $role_result = $db->query($role_sql);
        $teacher_role_id = $role_result->fetch_assoc()['Id'];
        
        $HashedPassword = password_hash($Password, PASSWORD_DEFAULT);
        $Status = 'Active';
        $RegisteredAt = date('Y-m-d H:i:s');

        $sql_users = "INSERT INTO users(FirstName, LastName, date_of_birth, gender, Address, TelNo, Email, username, Password, NIC, ProfileImage, user_role_id, Status, registered_at) 
                      VALUES('$FirstName', '$LastName', '$date_of_birth', '$gender', '$Address', '$TelNo', '$Email', '$username', '$HashedPassword', '$NIC', '$file_new_name', '$teacher_role_id', '$Status', '$RegisteredAt')";
        
        $db->query($sql_users);
        $new_user_id = $db->insert_id;
        
        if ($new_user_id > 0) {
            $sql_teacher = "INSERT INTO teacher_details(user_id, marital_status, appointment_date, designation, qualifications) 
                            VALUES ('$new_user_id', '$marital_status', '$appointment_date', '$designation', '$qualifications')";
            $db->query($sql_teacher);
            header("Location: manage.php?status=added");
            exit();
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Create New Teacher Account</h3>
            </div>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
                <div class="card-body">
                     <h5>Personal & Account Details</h5>
                     <hr>
                     <div class="row">
                         <div class="form-group col-md-6">
                             <label>First Name</label>
                             <input type="text" name="FirstName" class="form-control" value="<?= @$FirstName ?>">
                             <span class="text-danger"><?= @$messages['FirstName'] ?></span>
                         </div>
                         <div class="form-group col-md-6">
                             <label>Last Name</label>
                             <input type="text" name="LastName" class="form-control" value="<?= @$LastName ?>">
                         </div>
                     </div>
                     <div class="row">
                         <div class="form-group col-md-6">
                             <label>Date of Birth</label>
                             <input type="date" class="form-control" name="date_of_birth" value="<?= @$date_of_birth ?>">
                         </div>
                         <div class="form-group col-md-6">
                             <label>Gender</label>
                             <select name="gender" class="form-control">
                                 <option value="">--Select--</option>
                                 <option value="Male" <?= @$gender=='Male'?'selected':'' ?>>Male</option>
                                 <option value="Female" <?= @$gender=='Female'?'selected':'' ?>>Female</option>
                             </select>
                         </div>
                     </div>
                     <div class="row">
                         <div class="form-group col-md-6">
                             <label>Email</label>
                             <input type="email" name="Email" class="form-control" value="<?= @$Email ?>">
                             <span class="text-danger"><?= @$messages['Email'] ?></span>
                         </div>
                         <div class="form-group col-md-6">
                             <label>Telephone</label>
                             <input type="text" name="TelNo" class="form-control" value="<?= @$TelNo ?>">
                             <span class="text-danger"><?= @$messages['TelNo'] ?></span>
                         </div>
                     </div>
                     <div class="row">
                         <div class="form-group col-md-6">
                             <label>Username</label>
                             <input type="text" name="username" class="form-control" value="<?= @$username ?>">
                             <span class="text-danger"><?= @$messages['username'] ?></span>
                         </div>
                         <div class="form-group col-md-6">
                             <label>Password</label>
                             <input type="password" name="Password" class="form-control">
                             <span class="text-danger"><?= @$messages['Password'] ?></span>
                         </div>
                     </div>
                     <div class="form-group">
                         <label>Address</label>
                         <textarea name="Address" class="form-control" rows="2"><?= @$Address ?></textarea>
                     </div>
                     <h5 class="mt-4">Professional Details</h5>
                     <hr>
                     <div class="row">
                         <div class="form-group col-md-6">
                             <label>NIC</label>
                             <input type="text" name="NIC" class="form-control" value="<?= @$NIC ?>">
                             <span class="text-danger"><?= @$messages['NIC'] ?></span>
                         </div>
                         <div class="form-group col-md-6">
                             <label>Marital Status</label>
                             <select name="marital_status" class="form-control">
                                 <option value="">--Select--</option>
                                 <option value="Single" <?= @$marital_status=='Single'?'selected':'' ?>>Single</option>
                                 <option value="Married" <?= @$marital_status=='Married'?'selected':'' ?>>Married</option>
                             </select>
                         </div>
                     </div>
                     <div class="row">
                         <div class="form-group col-md-6">
                             <label>Designation (e.g., Senior Teacher)</label>
                             <input type="text" name="designation" class="form-control" value="<?= @$designation ?>">
                             <span class="text-danger"><?= @$messages['designation'] ?></span>
                         </div>
                         <div class="form-group col-md-6">
                             <label>Appointment Date</label>
                             <input type="date" class="form-control" name="appointment_date" value="<?= @$appointment_date ?>">
                             <span class="text-danger"><?= @$messages['appointment_date'] ?></span>
                         </div>
                     </div>
                     <div class="form-group">
                         <label>Qualifications</label>
                         <textarea name="qualifications" class="form-control" rows="2" placeholder="Enter qualifications separated by commas..."><?= @$qualifications ?></textarea>
                     </div>
                     <div class="form-group">
                         <label>Profile Image</label>
                         <input type="file" name="ProfileImage" class="form-control">
                         <span class="text-danger"><?= @$messages['ProfileImage'] ?></span>
                     </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Create Teacher Account</button>
                    <a href="manage.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>

