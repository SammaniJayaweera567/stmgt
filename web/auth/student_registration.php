<?php
ob_start();
include '../../init.php';

function uploadImage($file, $uploadPath) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $fileName = 'student_' . uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadPath . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }
    }
    return 'default_avatar.png';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = dbConn();
    $db->begin_transaction();
    try {
        $FirstName = dataClean($_POST['first_name'] ?? '');
        $Email = dataClean($_POST['email'] ?? '');
        $Password = $_POST['password'] ?? '';
        $guardian_nic = dataClean($_POST['guardian_nic'] ?? '');

        if (empty($FirstName) || empty($Email) || empty($Password) || empty($guardian_nic)) {
            throw new Exception("Please fill all required fields marked with *.");
        }
        
        $profileImageName = uploadImage($_FILES['profile_image'], '../../web/uploads/profile_images/');

        $hashed_password = password_hash($Password, PASSWORD_DEFAULT);
        $role_id = 4;
        
        $sql_user = "INSERT INTO users (FirstName, LastName, date_of_birth, gender, Address, TelNo, Email, username, Password, NIC, ProfileImage, user_role_id, Status) 
                     VALUES ('$FirstName', 
                             '".dataClean($_POST['last_name'] ?? '')."', 
                             '".dataClean($_POST['dob'] ?? '')."', 
                             '".dataClean($_POST['gender'] ?? '')."', 
                             '".dataClean($_POST['address'] ?? '')."', 
                             '".dataClean($_POST['tel_no'] ?? '')."', 
                             '$Email', 
                             '".dataClean($_POST['username'] ?? $Email)."', 
                             '$hashed_password', 
                             '".dataClean($_POST['student_nic'] ?? '')."', 
                             '$profileImageName', 
                             '$role_id', 
                             'Active')";
        if (!$db->query($sql_user)) { throw new Exception("Error creating user account: " . $db->error); }
        $new_user_id = $db->insert_id;

        $registration_no = "STU-" . date('Y') . str_pad($new_user_id, 4, '0', STR_PAD_LEFT);
        
        // UPDATED: Get school_name from the form
        $school_name = dataClean($_POST['school_name'] ?? ''); 
        $guardian_name = dataClean($_POST['guardian_name'] ?? '');
        $guardian_contact = dataClean($_POST['guardian_contact'] ?? '');
        
        // UPDATED: 'school_name' variable added to the INSERT query
        $sql_student_details = "INSERT INTO student_details (user_id, registration_no, school_name, guardian_name, guardian_contact, guardian_nic) 
                                VALUES ('$new_user_id', 
                                        '$registration_no', 
                                        '$school_name', 
                                        '$guardian_name', 
                                        '$guardian_contact', 
                                        '$guardian_nic')";
        if (!$db->query($sql_student_details)) { throw new Exception("Error creating student details: " . $db->error); }

        $db->commit();
        header("Location: success.php");
        exit();

    } catch (Exception $e) {
        $db->rollback();
        header("Location: student_registration.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white"><h3>Student Registration</h3></div>
        <div class="card-body">
            <?php if(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                <div class="alert alert-danger"><?= htmlspecialchars(urldecode($_GET['message'])) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
                <fieldset class="border p-3 mb-3">
                    <legend class="w-auto px-2">Personal Details</legend>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>First Name <span class="text-danger">*</span></label><input type="text" name="first_name" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label>Last Name</label><input type="text" name="last_name" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label>Student's NIC (If available)</label><input type="text" name="student_nic" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label>Date of Birth</label><input type="date" name="dob" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label>Gender</label><select name="gender" class="form-select"><option value="">-- Select --</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                        <div class="col-md-6 mb-3"><label>Telephone</label><input type="tel" name="tel_no" class="form-control"></div>
                        <div class="col-md-12 mb-3"><label>Address</label><textarea name="address" class="form-control"></textarea></div>
                    </div>
                </fieldset>
                <fieldset class="border p-3 mb-3">
                    <legend class="w-auto px-2">Account Details</legend>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>Email <span class="text-danger">*</span></label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label>Username <span class="text-danger">*</span></label><input type="text" name="username" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label>Password (min 8 chars) <span class="text-danger">*</span></label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label>Profile Image</label><input type="file" name="profile_image" class="form-control"></div>
                    </div>
                </fieldset>
                <fieldset class="border p-3 mb-3">
                    <legend class="w-auto px-2">Guardian & School Details</legend>
                    <div class="row">
                        <div class="col-md-12 mb-3"><label>School Name</label><input type="text" name="school_name" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label>Guardian Name</label><input type="text" name="guardian_name" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label>Guardian Contact</label><input type="text" name="guardian_contact" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label>Guardian's NIC <span class="text-danger">*</span></label><input type="text" name="guardian_nic" class="form-control" required></div>
                    </div>
                </fieldset>
                <button type="submit" class="btn btn-primary w-100">Register Student</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>