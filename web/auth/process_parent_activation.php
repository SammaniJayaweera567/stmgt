<?php
ob_start();
include '../../init.php';

function uploadImage($file, $uploadPath) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $fileName = 'parent_' . uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadPath . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }
    }
    return null;
}

$db = dbConn();

// --- STEP 1: Find Account Button Clicked ---
if (isset($_POST['find_account'])) {
    $guardian_nic = dataClean($_POST['guardian_nic']);
    
    $check_parent_sql = "SELECT Id FROM users WHERE NIC = '$guardian_nic' AND user_role_id = 5";
    if ($db->query($check_parent_sql)->num_rows > 0) {
        header("Location: parent_registration.php?status=error&message=" . urlencode("An active parent account already exists. Please login."));
        exit();
    }
    
    $find_children_sql = "SELECT sd.*, u.FirstName, u.LastName FROM student_details sd JOIN users u ON sd.user_id = u.Id WHERE sd.guardian_nic = '$guardian_nic'";
    $children_result = $db->query($find_children_sql);
    
    if ($children_result && $children_result->num_rows > 0) {
        $children_data = $children_result->fetch_all(MYSQLI_ASSOC);
        $_SESSION['guardian_nic'] = $children_data[0]['guardian_nic'];
        $_SESSION['guardian_name'] = $children_data[0]['guardian_name'];
        $_SESSION['children_to_link'] = $children_data;
    } else {
        header("Location: parent_registration.php?status=error&message=" . urlencode("No students found with this NIC."));
        exit();
    }
}

// --- STEP 2: Create Account Button Clicked ---
if (isset($_POST['create_account'])) {
    $guardian_nic = $_SESSION['guardian_nic'] ?? null;
    $guardian_name = $_SESSION['guardian_name'] ?? null;
    $children_to_link = $_SESSION['children_to_link'] ?? null;

    if (!$guardian_nic || !$children_to_link) {
        header("Location: parent_registration.php?status=error&message=" . urlencode("Session expired. Please start over."));
        exit();
    }

    // Get all fields from the form
    $Email = dataClean($_POST['Email']);
    $Password = $_POST['Password'];
    $TelNo = dataClean($_POST['TelNo']);
    $Address = dataClean($_POST['Address']);
    $date_of_birth = dataClean($_POST['date_of_birth']);
    $gender = dataClean($_POST['gender']);
    $occupation = dataClean($_POST['occupation']);
    
    $profileImageName = uploadImage($_FILES['ProfileImage'], '../../web/uploads/profile_images/');

    $hashed_password = password_hash($Password, PASSWORD_DEFAULT);
    $parent_role_id = 5;
    $name_parts = explode(' ', $guardian_name, 2);
    $FirstName = $name_parts[0];
    $LastName = $name_parts[1] ?? '';

    $sql_user = "INSERT INTO users (FirstName, LastName, date_of_birth, gender, Address, TelNo, Email, username, Password, NIC, ProfileImage, user_role_id, Status) 
                 VALUES ('$FirstName', '$LastName', '$date_of_birth', '$gender', '$Address', '$TelNo', '$Email', '$Email', '$hashed_password', '$guardian_nic', '$profileImageName', '$parent_role_id', 'Active')";
    
    if ($db->query($sql_user)) {
        $new_parent_id = $db->insert_id;

        // UPDATED: Insert into parent_details table with the occupation
        $sql_parent_details = "INSERT INTO parent_details (user_id, occupation) VALUES ('$new_parent_id', '$occupation')";
        $db->query($sql_parent_details);

        // Link children
        foreach ($children_to_link as $child) {
            $student_id = $child['user_id'];
            $sql_link = "INSERT INTO student_guardian_relationship (student_user_id, guardian_user_id, relationship_type) 
                         VALUES ('$student_id', '$new_parent_id', 'Parent')";
            $db->query($sql_link);
        }

        session_unset();
        header("Location: ../auth/login.php?status=parent_success");
        exit();
    } else {
        header("Location: parent_registration.php?status=error&message=" . urlencode("Could not create account. Email may already exist."));
        exit();
    }
}
?>

<?php if (isset($_SESSION['children_to_link'])): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Details & Activate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white"><h3>Confirm Your Details</h3></div>
                <div class="card-body p-4">
                    <h4>Welcome, <?= htmlspecialchars($_SESSION['guardian_name']) ?>!</h4>
                    <p>We found student(s) linked to your NIC. Please complete your profile to activate your account.</p>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">The following children will be linked to your account:</h6>
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['children_to_link'] as $child): ?>
                                <li>
                                    <strong><?= htmlspecialchars($child['FirstName'] . ' ' . $child['LastName']) ?></strong> 
                                    (Reg No: <?= htmlspecialchars($child['registration_no']) ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['guardian_name']) ?>" disabled></div>
                            <div class="col-md-6 mb-3"><label class="form-label">NIC</label><input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['guardian_nic']) ?>" disabled></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Gender</label><select name="gender" class="form-select" required><option value="">-- Select --</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Address</label><textarea name="Address" class="form-control" rows="2" required></textarea></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Profile Image</label><input type="file" name="ProfileImage" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Contact No</label><input type="tel" name="TelNo" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Email Address</label><input type="email" name="Email" class="form-control" required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">New Password</label><input type="password" name="Password" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                        </div>
                        <button type="submit" name="create_account" class="btn btn-success w-100 py-2">Activate My Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php 
    ob_end_flush();
    exit();
endif; 
?> 
?>