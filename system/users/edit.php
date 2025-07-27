<?php
ob_start();
include '../../init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
    exit();
}
if (!hasPermission($_SESSION['user_id'], 'edit_user')) {
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
    if (empty($number)) return true; // Allow empty phone number
    return strlen($number) === 10 && ctype_digit($number);
}

function isBirthYearMatchingNIC($nic, $dob) {
    if (empty($nic) || empty($dob)) {
        return true;
    }
    $nic_year = '';
    $len = strlen($nic);
    if ($len === 10) {
        $nic_year = "19" . substr($nic, 0, 2);
    } elseif ($len === 12) {
        $nic_year = substr($nic, 0, 4);
    } else {
        return true;
    }
    $dob_year = substr($dob, 0, 4);
    return $nic_year === $dob_year;
}

function isStrongPassword($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return false;
    return true;
}


// Check if the form is submitted for an update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    
    // --- Data Cleaning and Explicit POST assignment ---
    $id = dataClean(@$_POST['id']);
    $FirstName = dataClean(@$_POST['FirstName']);
    $LastName = dataClean(@$_POST['LastName']);
    $date_of_birth = dataClean(@$_POST['date_of_birth']);
    $gender = dataClean(@$_POST['gender']);
    $Address = dataClean(@$_POST['Address']);
    $TelNo = dataClean(@$_POST['TelNo']);
    $Email = dataClean(@$_POST['Email']);
    $username = dataClean(@$_POST['username']);
    $NIC = dataClean(@$_POST['NIC']);
    $user_role_id = dataClean(@$_POST['user_role_id']);
    $Status = dataClean(@$_POST['Status']);
    $Password = @$_POST['Password']; // Not cleaned
    $current_image = dataClean(@$_POST['current_image']);
    
    // FIXED: Preserve profile image name across failed validations
    $ProfileImage = $current_image;

    // --- Validation ---
    $messages = array();
    if (empty($FirstName)) { $messages['FirstName'] = "First Name is required."; }
    if (empty($Email)) { $messages['Email'] = "Email is required."; }
    if (empty($username)) { $messages['username'] = "Username is required."; }
    if (empty($user_role_id)) { $messages['user_role_id'] = "User Role is required."; }
    if (empty($Status)) { $messages['Status'] = "Status is required."; }

    if (empty($NIC)) {
        $messages['NIC'] = "NIC is required.";
    } elseif (!isValidNIC($NIC)) {
        $messages['NIC'] = "Invalid NIC format. Use 123456789V or 12-digit format.";
    } elseif (!isBirthYearMatchingNIC($NIC, $date_of_birth)) {
        $messages['NIC'] = "NIC does not match the year of birth.";
    }

    if (!isValidMobile($TelNo)) {
        $messages['TelNo'] = "Invalid mobile number. Use a 10-digit format like 07xxxxxxxx.";
    }

    if (!empty($Password)) {
        if (!isStrongPassword($Password)) {
            $messages['Password'] = "Password must be at least 8 characters and include uppercase, lowercase, number, & special character.";
        }
    }
    
    $db = dbConn();
    if (!empty($Email) && $db->query("SELECT Id FROM users WHERE Email='$Email' AND Id != '$id'")->num_rows > 0) {
        $messages['Email'] = "This Email is already used by another user.";
    }
    if (!empty($username) && $db->query("SELECT Id FROM users WHERE username='$username' AND Id != '$id'")->num_rows > 0) {
        $messages['username'] = "This Username is already used by another user.";
    }

    // --- Handle File Upload ---
    $file_new_name = $current_image; 
    if (isset($_FILES['ProfileImage']) && $_FILES['ProfileImage']['error'] === 0) {
        $file_ext = strtolower(pathinfo($_FILES['ProfileImage']['name'], PATHINFO_EXTENSION));
        $file_new_name = uniqid() . '.' . $file_ext;
        move_uploaded_file($_FILES['ProfileImage']['tmp_name'], '../../web/uploads/profile_images/' . $file_new_name);
        
        // After successful upload, update the $ProfileImage variable
        $ProfileImage = $file_new_name;

        if (!empty($current_image) && $current_image != 'default_avatar.png') {
            $old_image_path = '../../web/uploads/profile_images/' . $current_image;
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
    }

    if (empty($messages)) {
        // --- Build the UPDATE SQL Query ---
        $sql = "UPDATE users SET 
                    FirstName='$FirstName', 
                    LastName='$LastName', 
                    date_of_birth='$date_of_birth', 
                    gender='$gender', 
                    Address='$Address', 
                    TelNo='$TelNo', 
                    Email='$Email', 
                    username='$username', 
                    NIC='$NIC', 
                    ProfileImage='$file_new_name', 
                    user_role_id='$user_role_id', 
                    Status='$Status' ";

        if (!empty($Password)) {
            $HashedPassword = password_hash($Password, PASSWORD_DEFAULT);
            $sql .= ", Password='$HashedPassword' ";
        }

        $sql .= " WHERE Id='$id'";
        
        $db->query($sql);
        
        header("Location: manage.php?status=updated");
        exit();
    }
} 
// This block runs when the page is first loaded from manage.php
else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = dataClean($_POST['id']);
    $db = dbConn();
    $sql = "SELECT * FROM users WHERE Id = '$id'";
    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        // Assign fetched data to variables to populate the form
        $FirstName = $user_data['FirstName'];
        $LastName = $user_data['LastName'];
        $date_of_birth = $user_data['date_of_birth'];
        $gender = $user_data['gender'];
        $Address = $user_data['Address'];
        $TelNo = $user_data['TelNo'];
        $Email = $user_data['Email'];
        $username = $user_data['username'];
        $NIC = $user_data['NIC'];
        $user_role_id = $user_data['user_role_id'];
        $Status = $user_data['Status'];
        $ProfileImage = $user_data['ProfileImage'];
    } else {
        header("Location: manage.php?status=notfound");
        exit();
    }
} else {
    // If someone tries to access edit.php directly, redirect them
    header("Location: manage.php");
    exit();
}
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit User Details</h3></div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
            <!-- Use the lowercase '$id' variable which is consistent -->
            <input type="hidden" name="id" value="<?= @$id ?>">
            <input type="hidden" name="current_image" value="<?= @$ProfileImage ?>">

            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-6"><label>First Name</label><input type="text" name="FirstName" class="form-control" value="<?= htmlspecialchars(@$FirstName) ?>"><span class="text-danger"><?= @$messages['FirstName'] ?></span></div>
                    <div class="form-group col-md-6"><label>Last Name</label><input type="text" name="LastName" class="form-control" value="<?= htmlspecialchars(@$LastName) ?>"></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6"><label>Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="<?= htmlspecialchars(@$date_of_birth) ?>"></div>
                    <div class="form-group col-md-6"><label>Gender</label><select name="gender" class="form-control"><option value="">--</option><option value="Male" <?= @$gender=='Male'?'selected':'' ?>>Male</option><option value="Female" <?= @$gender=='Female'?'selected':'' ?>>Female</option></select></div>
                </div>
                <div class="form-group"><label>Address</label><textarea name="Address" class="form-control"><?= htmlspecialchars(@$Address) ?></textarea></div>
                <div class="row">
                    <div class="form-group col-md-6"><label>Email</label><input type="email" name="Email" class="form-control" value="<?= htmlspecialchars(@$Email) ?>"><span class="text-danger"><?= @$messages['Email'] ?></span></div>
                    <div class="form-group col-md-6"><label>Telephone</label><input type="text" name="TelNo" class="form-control" value="<?= htmlspecialchars(@$TelNo) ?>"><span class="text-danger"><?= @$messages['TelNo'] ?></span></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6"><label>Username</label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars(@$username) ?>"><span class="text-danger"><?= @$messages['username'] ?></span></div>
                    <div class="form-group col-md-6"><label>New Password</label><input type="password" name="Password" class="form-control" placeholder="Leave blank to keep current password"><span class="text-danger"><?= @$messages['Password'] ?></span></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4"><label>NIC</label><input type="text" name="NIC" class="form-control" value="<?= htmlspecialchars(@$NIC) ?>"><span class="text-danger"><?= @$messages['NIC'] ?></span></div>
                    <div class="form-group col-md-4"><label>User Role</label>
                        <select name="user_role_id" class="form-control">
                            <option value="">-- Select Role --</option>
                            <?php 
                            $db = dbConn(); 
                            $sql_roles = "SELECT * FROM user_roles WHERE Status='Active'";
                            $result_roles = $db->query($sql_roles);
                            while($row_role = $result_roles->fetch_assoc()){
                                $selected = (@$user_role_id == $row_role['Id']) ? 'selected' : '';
                                echo "<option value='{$row_role['Id']}' $selected>{$row_role['RoleName']}</option>";
                            }
                            ?>
                        </select>
                        <span class="text-danger"><?= @$messages['user_role_id'] ?></span>
                    </div>
                     <div class="form-group col-md-4"><label>Status</label>
                        <select name="Status" class="form-control">
                            <option value="">-- Select Status --</option>
                            <option value="Active" <?= @$Status == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= @$Status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                         <span class="text-danger"><?= @$messages['Status'] ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Profile Image</label>
                    <input type="file" name="ProfileImage" class="form-control">
                    <?php if (!empty(@$ProfileImage)) { ?>
                        <div class="mt-2">
                            <small>Current Image:</small>
                            <img src="../../web/uploads/profile_images/<?= htmlspecialchars($ProfileImage) ?>" width="80" class="img-thumbnail">
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                <a href="manage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layouts.php';
?>
