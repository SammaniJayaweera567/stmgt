<?php
ob_start();
include '../../init.php';

// --- Validation Functions ---
function isValidNIC($nic) {
    if (empty($nic)) return true;
    $len = strlen($nic);
    if ($len === 10) { return ctype_digit(substr($nic, 0, 9)) && in_array(strtoupper($nic[9]), ['V', 'X']); }
    if ($len === 12) { return ctype_digit($nic); }
    return false;
}
function isValidMobile($number) {
    if (empty($number)) return true;
    return strlen($number) === 10 && ctype_digit($number);
}

// --- Function to handle image upload ---
function uploadImage($file, $uploadPath, $currentImage) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        if (!empty($currentImage) && $currentImage != 'default_avatar.png' && file_exists($uploadPath . $currentImage)) {
            unlink($uploadPath . $currentImage);
        }
        $fileName = 'student_' . uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadPath . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }
    }
    return $currentImage;
}

$db = dbConn();
$messages = [];
$student_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($student_id <= 0) {
    header("Location: manage_students.php");
    exit();
}

// --- Handle form submission for UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    extract($_POST);
    // --- Data Cleaning ---
    $FirstName = dataClean($FirstName ?? '');
    $LastName = dataClean($LastName ?? '');
    $date_of_birth = dataClean($date_of_birth ?? '');
    $gender = dataClean($gender ?? '');
    $Address = dataClean($Address ?? '');
    $TelNo = dataClean($TelNo ?? '');
    $Email = dataClean($Email ?? '');
    $student_nic = dataClean($student_nic ?? '');
    $Status = dataClean($Status ?? '');
    $school_name = dataClean($school_name ?? '');
    $guardian_name = dataClean($guardian_name ?? '');
    $guardian_contact = dataClean($guardian_contact ?? '');
    $guardian_nic = dataClean($guardian_nic ?? '');
    $current_image = dataClean($current_image ?? '');

    // --- Server-side Validations ---
    if (empty($FirstName)) { $messages['FirstName'] = "First Name is required."; }
    if (empty($Email)) { $messages['Email'] = "Email is required."; }
    if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) { $messages['Email'] = "Invalid email format.";
    } else {
        $sql_check_email = "SELECT Id FROM users WHERE Email = '$Email' AND Id != '$student_id'";
        if ($db->query($sql_check_email)->num_rows > 0) {
            $messages['Email'] = "This email is already used by another account.";
        }
    }
    if (!isValidNIC($student_nic)) { $messages['student_nic'] = "Invalid Student NIC format."; }
    if (!isValidNIC($guardian_nic)) { $messages['guardian_nic'] = "Invalid Guardian NIC format."; }
    if (!isValidMobile($TelNo)) { $messages['TelNo'] = "Invalid telephone number format (10 digits)."; }
    if (!isValidMobile($guardian_contact)) { $messages['guardian_contact'] = "Invalid guardian contact number format (10 digits)."; }

    if (empty($messages)) {
        // Handle image upload
        $profileImageName = uploadImage($_FILES['ProfileImage'], '../../web/uploads/profile_images/', $current_image);

        // Update the 'users' table
        $sql_users = "UPDATE users SET FirstName = '$FirstName', LastName = '$LastName', date_of_birth = '$date_of_birth', gender = '$gender', Address = '$Address', TelNo = '$TelNo', Email = '$Email', NIC = '$student_nic', ProfileImage = '$profileImageName', Status = '$Status' WHERE Id = '$student_id'";
        // Update the 'student_details' table
        $sql_student_details = "UPDATE student_details SET school_name = '$school_name', guardian_name = '$guardian_name', guardian_contact = '$guardian_contact', guardian_nic = '$guardian_nic' WHERE user_id = '$student_id'";

        if ($db->query($sql_users) && $db->query($sql_student_details)) {
            header("Location: manage_students.php?status=updated");
            exit();
        } else {
            $messages['db_error'] = "Database update failed: " . $db->error;
        }
    }
}

// --- Fetch current student data to show in the form ---
$sql_fetch = "SELECT u.*, sd.school_name, sd.guardian_name, sd.guardian_contact, sd.guardian_nic FROM users u LEFT JOIN student_details sd ON u.Id = sd.user_id WHERE u.Id = '$student_id' AND u.user_role_id = 4";
$result_fetch = $db->query($sql_fetch);
if ($result_fetch->num_rows == 0) {
    header("Location: manage_students.php?status=notfound");
    exit();
}
$student = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $result_fetch->fetch_assoc();
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit Student: <?= htmlspecialchars($student['FirstName']) ?></h3></div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $student_id ?>">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($student['ProfileImage'] ?? '') ?>">
            <div class="card-body">
                <?php if (!empty($messages['db_error'])) { echo '<div class="alert alert-danger">'.$messages['db_error'].'</div>'; } ?>
                
                <h5 class="mt-3">Personal Details</h5><hr>
                <div class="row">
                    <div class="col-md-6 mb-3"><label>First Name</label><input type="text" name="FirstName" class="form-control" value="<?= htmlspecialchars($student['FirstName'] ?? '') ?>"><span class="text-danger"><?= @$messages['FirstName'] ?></span></div>
                    <div class="col-md-6 mb-3"><label>Last Name</label><input type="text" name="LastName" class="form-control" value="<?= htmlspecialchars($student['LastName'] ?? '') ?>"></div>
                    <div class="col-md-6 mb-3"><label>Student's NIC (If available)</label><input type="text" name="student_nic" class="form-control" value="<?= htmlspecialchars($student['NIC'] ?? '') ?>"><span class="text-danger"><?= @$messages['student_nic'] ?></span></div>
                    <div class="col-md-6 mb-3"><label>Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($student['date_of_birth'] ?? '') ?>"></div>
                    <div class="col-md-6 mb-3"><label>Gender</label><select name="gender" class="form-select form-control"><option value="Male" <?= (($student['gender'] ?? '') == 'Male') ? 'selected' : '' ?>>Male</option><option value="Female" <?= (($student['gender'] ?? '') == 'Female') ? 'selected' : '' ?>>Female</option></select></div>
                </div>

                <h5 class="mt-4">Contact & Account Details</h5><hr>
                <div class="row">
                    <div class="col-md-6 mb-3"><label>Email</label><input type="email" name="Email" class="form-control" value="<?= htmlspecialchars($student['Email'] ?? '') ?>"><span class="text-danger"><?= @$messages['Email'] ?></span></div>
                    <div class="col-md-6 mb-3"><label>Telephone</label><input type="tel" name="TelNo" class="form-control" value="<?= htmlspecialchars($student['TelNo'] ?? '') ?>"><span class="text-danger"><?= @$messages['TelNo'] ?></span></div>
                    <div class="col-md-12 mb-3"><label>Address</label><textarea name="Address" class="form-control"><?= htmlspecialchars($student['Address'] ?? '') ?></textarea></div>
                    <div class="col-md-6 mb-3"><label>Account Status</label><select name="Status" class="form-control"><option value="Active" <?= (($student['Status'] ?? '') == 'Active') ? 'selected' : '' ?>>Active</option><option value="Inactive" <?= (($student['Status'] ?? '') == 'Inactive') ? 'selected' : '' ?>>Inactive</option></select></div>
                    <div class="col-md-6 mb-3">
                        <label>Profile Image</label>
                        <input type="file" name="ProfileImage" class="form-control">
                        <div class="mt-2">
                            <img src="../../web/uploads/profile_images/<?= htmlspecialchars($student['ProfileImage'] ?? 'default_avatar.png') ?>" alt="Current Profile Picture" class="img-thumbnail" width="100">
                        </div>
                    </div>
                </div>

                <h5 class="mt-4">Guardian & School Details</h5><hr>
                <div class="row">
                    <div class="col-md-12 mb-3"><label>School Name</label><input type="text" name="school_name" class="form-control" value="<?= htmlspecialchars($student['school_name'] ?? '') ?>"></div>
                    <div class="col-md-4 mb-3"><label>Guardian Name</label><input type="text" name="guardian_name" class="form-control" value="<?= htmlspecialchars($student['guardian_name'] ?? '') ?>"></div>
                    <div class="col-md-4 mb-3"><label>Guardian Contact</label><input type="text" name="guardian_contact" class="form-control" value="<?= htmlspecialchars($student['guardian_contact'] ?? '') ?>"><span class="text-danger"><?= @$messages['guardian_contact'] ?></span></div>
                    <div class="col-md-4 mb-3"><label>Guardian NIC</label><input type="text" name="guardian_nic" class="form-control" value="<?= htmlspecialchars($student['guardian_nic'] ?? '') ?>"><span class="text-danger"><?= @$messages['guardian_nic'] ?></span></div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="update_student" class="btn btn-primary">Update Student</button>
                <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>