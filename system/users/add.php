<?php
ob_start();
include '../../init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
}
if (!hasPermission($_SESSION['user_id'], 'create_new_user')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
?>
<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Create New User</h3></div>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == "POST") {

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

            // UPDATED: More flexible mobile number validation
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

            extract($_POST);

            // Clean all fields from the form
            $FirstName = dataClean(@$FirstName);
            $LastName = dataClean(@$LastName);
            $date_of_birth = dataClean(@$date_of_birth);
            $gender = dataClean(@$gender);
            $Address = dataClean(@$Address);
            $TelNo = dataClean(@$TelNo);
            $Email = dataClean(@$Email);
            $username = dataClean(@$username);
            $NIC = dataClean(@$NIC);
            $user_role_id = dataClean(@$user_role_id);
            // Password is not cleaned

            $messages = array();
            if (empty($FirstName)) { $messages['FirstName'] = "First Name is required."; }
            if (empty($Email)) { $messages['Email'] = "Email is required."; }
            if (empty($username)) { $messages['username'] = "Username is required."; }
            if (empty($user_role_id)) { $messages['user_role_id'] = "User Role is required."; }

            // --- UPDATED: NIC, DOB, Password and Telephone Validation ---
            if (empty($NIC)) {
                $messages['NIC'] = "NIC is required.";
            } elseif (!isValidNIC($NIC)) {
                $messages['NIC'] = "Invalid NIC format. Use 123456789V or 12-digit format.";
            } elseif (!isBirthYearMatchingNIC($NIC, $date_of_birth)) {
                $messages['NIC'] = "NIC does not match the year of birth.";
            }

            // UPDATED: Telephone validation message
            if (!isValidMobile($TelNo)) {
                $messages['TelNo'] = "Invalid mobile number. Use a 10-digit format like 07xxxxxxxx.";
            }
            
            if (empty($Password)) { 
                $messages['Password'] = "Password is required."; 
            } elseif (!isStrongPassword($Password)) {
                $messages['Password'] = "Password must be at least 8 characters and include uppercase, lowercase, number, & special character.";
            }
            // --- END of updated validation ---

            $db = dbConn();
            if (!empty($Email) && $db->query("SELECT Id FROM users WHERE Email='$Email'")->num_rows > 0) {
                $messages['Email'] = "This Email already exists.";
            }
            if (!empty($username) && $db->query("SELECT Id FROM users WHERE username='$username'")->num_rows > 0) {
                $messages['username'] = "This Username already exists.";
            }

            $file_new_name = '';
            if (isset($_FILES['ProfileImage']) && $_FILES['ProfileImage']['error'] === 0) {
                $file_ext = strtolower(pathinfo($_FILES['ProfileImage']['name'], PATHINFO_EXTENSION));
                $file_new_name = uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES['ProfileImage']['tmp_name'], '../../web/uploads/profile_images/' . $file_new_name);
            }

            if (empty($messages)) {
                $HashedPassword = password_hash($Password, PASSWORD_DEFAULT);
                $Status = 'Active'; // Set default status
                $RegisteredAt = date('Y-m-d H:i:s'); // Set current timestamp

                // Corrected INSERT statement with all fields
                $sql = "INSERT INTO users(FirstName, LastName, date_of_birth, gender, Address, TelNo, Email, username, Password, NIC, ProfileImage, user_role_id, Status, registered_at) 
                        VALUES('$FirstName', '$LastName', '$date_of_birth', '$gender', '$Address', '$TelNo', '$Email', '$username', '$HashedPassword', '$NIC', '$file_new_name', '$user_role_id', '$Status', '$RegisteredAt')";
                
                $db->query($sql);
                
                header("Location: manage.php?status=added");
                exit();
            }
        }
        ?>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-6"><label>First Name</label><input type="text" name="FirstName" class="form-control" value="<?= @$FirstName ?>"><span class="text-danger"><?= @$messages['FirstName'] ?></span></div>
                    <div class="form-group col-md-6"><label>Last Name</label><input type="text" name="LastName" class="form-control" value="<?= @$LastName ?>"></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6"><label>Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="<?= @$date_of_birth ?>"></div>
                    <div class="form-group col-md-6"><label>Gender</label><select name="gender" class="form-control"><option value="">--</option><option value="Male" <?= @$gender=='Male'?'selected':'' ?>>Male</option><option value="Female" <?= @$gender=='Female'?'selected':'' ?>>Female</option></select></div>
                </div>
                <div class="form-group"><label>Address</label><textarea name="Address" class="form-control"><?= @$Address ?></textarea></div>
                <div class="row">
                    <div class="form-group col-md-6"><label>Email</label><input type="email" name="Email" class="form-control" value="<?= @$Email ?>"><span class="text-danger"><?= @$messages['Email'] ?></span></div>
                    <div class="form-group col-md-6"><label>Telephone</label><input type="text" name="TelNo" class="form-control" value="<?= @$TelNo ?>"><span class="text-danger"><?= @$messages['TelNo'] ?></span></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6"><label>Username</label><input type="text" name="username" class="form-control" value="<?= @$username ?>"><span class="text-danger"><?= @$messages['username'] ?></span></div>
                    <div class="form-group col-md-6"><label>Password</label><input type="password" name="Password" class="form-control"><span class="text-danger"><?= @$messages['Password'] ?></span></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6"><label>NIC</label><input type="text" name="NIC" class="form-control" value="<?= @$NIC ?>"><span class="text-danger"><?= @$messages['NIC'] ?></span></div>
                    <div class="form-group col-md-6"><label>User Role</label>
                        <select name="user_role_id" class="form-control">
                            <option value="">Select Role</option>
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
                </div>
                <div class="form-group"><label>Profile Image</label><input type="file" name="ProfileImage" class="form-control"></div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="manage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layouts.php'; // Make sure this path is correct
?>
