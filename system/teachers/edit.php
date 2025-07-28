<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'edit_teacher')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// --- Validation Functions (Same as add.php) ---
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
    if (empty($number)) return true;
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

// Handle form submission for UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_teacher'])) {
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
    $Status = dataClean(@$Status);
    $user_id = dataClean(@$user_id);
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
    if (empty($Status)) { $messages['Status'] = "Status is required."; }

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

    if (!empty($Password) && !isStrongPassword($Password)) {
        $messages['Password'] = "Password must be strong (min 8 chars, upper, lower, number, special char).";
    }

    $db = dbConn();
    if (!empty($Email) && $db->query("SELECT Id FROM users WHERE Email='$Email' AND Id != '$user_id'")->num_rows > 0) {
        $messages['Email'] = "This Email is already used by another user.";
    }
    if (!empty($username) && $db->query("SELECT Id FROM users WHERE username='$username' AND Id != '$user_id'")->num_rows > 0) {
        $messages['username'] = "This Username is already used by another user.";
    }
    if (!empty($NIC) && isValidNIC($NIC) && $db->query("SELECT Id FROM users WHERE NIC='$NIC' AND Id != '$user_id'")->num_rows > 0) {
        $messages['NIC'] = "This NIC is already used by another user.";
    }

    if (empty($messages)) {
        $file_new_name = $current_image;
        if (isset($_FILES['ProfileImage']) && $_FILES['ProfileImage']['error'] === 0) {
            $file_ext = strtolower(pathinfo($_FILES['ProfileImage']['name'], PATHINFO_EXTENSION));
            $file_new_name = uniqid('teacher_', true) . '.' . $file_ext;
            move_uploaded_file($_FILES['ProfileImage']['tmp_name'], '../uploads/' . $file_new_name);
            if (!empty($current_image) && file_exists('../uploads/' . $current_image)) {
                unlink('../uploads/' . $current_image);
            }
        }

        $sql_users = "UPDATE users SET FirstName='$FirstName', LastName='$LastName', date_of_birth='$date_of_birth', gender='$gender', Address='$Address', TelNo='$TelNo', Email='$Email', username='$username', NIC='$NIC', ProfileImage='$file_new_name', Status='$Status' ";
        if (!empty($Password)) {
            $HashedPassword = password_hash($Password, PASSWORD_DEFAULT);
            $sql_users .= ", Password='$HashedPassword' ";
        }
        $sql_users .= " WHERE Id='$user_id'";
        $db->query($sql_users);

        $sql_teacher = "UPDATE teacher_details SET marital_status='$marital_status', appointment_date='$appointment_date', designation='$designation', qualifications='$qualifications' WHERE user_id='$user_id'";
        $db->query($sql_teacher);
        
        header("Location: manage.php?status=updated");
        exit();
    }
} 
else {
    // --- Fetch data for the form ---
    $user_id = $_GET['user_id'] ?? $_POST['user_id'];
    if (empty($user_id)) {
        header("Location: manage.php");
        exit();
    }
    $db = dbConn();
    $sql = "SELECT * FROM users u LEFT JOIN teacher_details td ON u.Id = td.user_id WHERE u.Id = '$user_id'";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
        $teacher_data = $result->fetch_assoc();
        extract($teacher_data);
    } else {
        header("Location: manage.php?status=notfound");
        exit();
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Teacher Details: <?= htmlspecialchars(@$FirstName . ' ' . @$LastName) ?></h3>
            </div>
            
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?= @$user_id ?>">
                <input type="hidden" name="current_image" value="<?= @$ProfileImage ?>">
                <input type="hidden" name="update_teacher" value="1">

                <div class="card-body">
                    <h5>Personal & Account Details</h5>
                    <hr>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>First Name</label>
                            <input type="text" name="FirstName" class="form-control" value="<?= htmlspecialchars(@$FirstName) ?>">
                            <span class="text-danger"><?= @$messages['FirstName'] ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Last Name</label>
                            <input type="text" name="LastName" class="form-control" value="<?= htmlspecialchars(@$LastName) ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?= htmlspecialchars(@$date_of_birth) ?>">
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
                            <input type="email" name="Email" class="form-control" value="<?= htmlspecialchars(@$Email) ?>">
                            <span class="text-danger"><?= @$messages['Email'] ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Telephone</label>
                            <input type="text" name="TelNo" class="form-control" value="<?= htmlspecialchars(@$TelNo) ?>">
                            <span class="text-danger"><?= @$messages['TelNo'] ?></span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars(@$username) ?>">
                            <span class="text-danger"><?= @$messages['username'] ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>New Password</label>
                            <input type="password" name="Password" class="form-control" placeholder="Leave blank to keep current password">
                            <span class="text-danger"><?= @$messages['Password'] ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="Address" class="form-control" rows="2"><?= htmlspecialchars(@$Address) ?></textarea>
                    </div>

                    <h5 class="mt-4">Professional Details</h5>
                    <hr>

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>NIC</label>
                            <input type="text" name="NIC" class="form-control" value="<?= htmlspecialchars(@$NIC) ?>">
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
                            <label>Designation</label>
                            <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars(@$designation) ?>">
                            <span class="text-danger"><?= @$messages['designation'] ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Appointment Date</label>
                            <input type="date" class="form-control" name="appointment_date" value="<?= htmlspecialchars(@$appointment_date) ?>">
                             <span class="text-danger"><?= @$messages['appointment_date'] ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Qualifications</label>
                        <textarea name="qualifications" class="form-control" rows="2"><?= htmlspecialchars(@$qualifications) ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Account Status</label>
                            <select name="Status" class="form-control">
                                <option value="">--Select Status--</option>
                                <option value="Active" <?= (@$Status == 'Active') ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= (@$Status == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <span class="text-danger"><?= @$messages['Status'] ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Profile Image</label>
                            <input type="file" name="ProfileImage" class="form-control">
                            <?php if (!empty(@$ProfileImage)) { ?>
                                <div class="mt-2">
                                    <small>Current Image:</small>
                                    <img src="../uploads/<?= htmlspecialchars($ProfileImage) ?>" width="90" class="img-thumbnail">
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" name="update_teacher" class="btn btn-primary">Update Teacher Details</button>
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
