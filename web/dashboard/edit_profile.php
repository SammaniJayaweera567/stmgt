<?php
ob_start();
include '../../init.php'; // Correct path

// Security Check: Ensure a student is logged in
// $_SESSION['ID'] සහ $_SESSION['user_role_name'] 
if (!isset($_SESSION['ID']) || strtolower($_SESSION['user_role_name'] ?? '') != 'student') {
    header("Location: " . WEB_URL . "auth/login.php");
    exit();
}

$db = dbConn();
// Student user_id එක $_SESSION['ID']
$student_user_id = (int)$_SESSION['ID']; 
$messages = [];

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Data Cleaning
    $first_name = dataClean($_POST['first_name'] ?? '');
    $last_name = dataClean($_POST['last_name'] ?? '');
    $email = dataClean($_POST['email'] ?? '');
    $tel_no = dataClean($_POST['tel_no'] ?? '');
    $address = dataClean($_POST['address'] ?? '');
    $school_name = dataClean($_POST['school_name'] ?? '');

    // 2. Validation
    if (empty($first_name)) { $messages['error_first_name'] = "First name cannot be empty."; }
    if (empty($last_name)) { $messages['error_last_name'] = "Last name cannot be empty."; }
    if (empty($email)) { $messages['error_email'] = "Email cannot be empty."; }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messages['error_email'] = "Please enter a valid email address.";
    } else {
        // Check if the new email already exists for ANOTHER user
        $escaped_email = $db->real_escape_string($email);
        $sql_email_check = "SELECT Id FROM users WHERE Email = '$escaped_email' AND Id != $student_user_id";
        $result_email_check = $db->query($sql_email_check);
        if ($result_email_check && $result_email_check->num_rows > 0) {
            $messages['error_email'] = "This email address is already taken by another user.";
        }
    }

    // 3. Handle File Upload (if a new file is provided)
    $new_profile_image_name = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        // Your file validation logic from registration
        $target_dir = "../uploads/profile_images/"; // Correct path from web/dashboard/
        $original_filename = basename($_FILES['profile_image']['name']);
        $safe_filename = str_replace(' ', '_', $original_filename);
        $new_profile_image_name = uniqid() . '_' . $safe_filename;
        $target_file = $target_dir . $new_profile_image_name;

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $messages['error_profile_image'] = "There was an error uploading your new image.";
            $new_profile_image_name = null; // Reset on failure
        }
    }

    // 4. If No Validation Errors, Update the Database
    if (empty($messages)) {
        // Get the old image name to delete it later if a new one was uploaded
        $sql_old_image = "SELECT ProfileImage FROM users WHERE Id = $student_user_id";
        $result_old_image = $db->query($sql_old_image);
        $old_image_name = $result_old_image->fetch_assoc()['ProfileImage'];

        // Prepare fields for the UPDATE query
        $db_first_name = $db->real_escape_string($first_name);
        $db_last_name = $db->real_escape_string($last_name);
        $db_email = $db->real_escape_string($email);
        $db_tel_no = $db->real_escape_string($tel_no);
        $db_address = $db->real_escape_string($address);
        $db_school_name = $db->real_escape_string($school_name);

        // Update 'users' table
        $sql_update_user = "UPDATE users SET 
                                FirstName = '$db_first_name', 
                                LastName = '$db_last_name', 
                                Email = '$db_email', 
                                TelNo = '$db_tel_no', 
                                Address = '$db_address'";
        // Only add the ProfileImage to the query if a new one was uploaded
        if ($new_profile_image_name) {
            $db_new_image_name = $db->real_escape_string($new_profile_image_name);
            $sql_update_user .= ", ProfileImage = '$db_new_image_name'";
        }
        $sql_update_user .= " WHERE Id = $student_user_id";
        $db->query($sql_update_user);

        // Update 'student_details' table
        $sql_update_student = "UPDATE student_details SET school_name = '$db_school_name' WHERE user_id = $student_user_id";
        $db->query($sql_update_student);
        
        // Delete the old profile picture if a new one was successfully uploaded
        if ($new_profile_image_name && !empty($old_image_name) && file_exists("../uploads/profile_images/" . $old_image_name)) {
            unlink("../uploads/profile_images/" . $old_image_name);
        }

        // Set success message and redirect to dashboard
        $_SESSION['update_success_message'] = "Your profile has been updated successfully!";
        header("Location: student.php");
        exit();
    }
}

// --- Fetch current student data to display in the form (runs on every page load) ---
$sql_get_data = "SELECT u.FirstName, u.LastName, u.Email, u.TelNo, u.Address, u.ProfileImage, sd.school_name 
                 FROM users u 
                 LEFT JOIN student_details sd ON u.Id = sd.user_id 
                 WHERE u.Id = $student_user_id";
$result = $db->query($sql_get_data);
$student_details = $result->fetch_assoc();

if (!$student_details) {
    echo "Error: Could not retrieve student details.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    /* This UI is consistent with your registration form */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4f4f4;
    }

    .form-background {
        background-color: #f8f9fa;
        padding: 50px 15px;
    }

    .form-container {
        max-width: 800px;
        background-color: #fff;
        margin: auto;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .form-container h2 {
        text-align: center;
        color: #1cc1ba;
        font-weight: 700;
        margin-bottom: 30px;
    }

    .form-section {
        border: 1px solid #e9ecef;
        background-color: #fdfdfd;
        padding: 25px;
        border-radius: 8px;
        margin-bottom: 25px;
    }

    .form-section legend {
        font-size: 1.1rem;
        font-weight: 600;
        color: #343a40;
        padding: 0 10px;
        margin-left: 10px;
    }

    .input-group {
        position: relative;
        margin-bottom: 1.25rem;
    }

    .input-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .input-group .form-control {
        width: 100%;
        height: 48px;
        padding: 0 15px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        box-sizing: border-box;
    }

    .input-group textarea.form-control {
        height: auto;
        padding-top: 10px;
    }

    .profile-image-preview {
        display: block;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 15px;
        border: 3px solid #eee;
    }

    .btn-save {
        display: block;
        width: 100%;
        padding: 12px;
        font-size: 18px;
        font-weight: 600;
        background-color: #1cc1ba;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }

    .error-message {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 5px;
    }

    .edit-profile-container{
        margin: 100px auto;
    }
    </style>
</head>

<body>

    <div class="form-background">
        <div class="form-container edit-profile-container">
            <h2>Edit Your Profile</h2>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">

                <fieldset class="form-section">
                    <legend>Personal Details</legend>
                    <div class="input-group">
                        <label>Current Profile Picture</label>
                        <img src="<?= WEB_URL ?>uploads/profile_images/<?= !empty($student_details['ProfileImage']) ? htmlspecialchars($student_details['ProfileImage']) : 'default_avatar.png' ?>"
                            alt="Profile Picture" class="profile-image-preview">
                        <label for="profile_image">Change Profile Picture (Optional)</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image"
                            accept="image/*">
                        <div class="error-message"><?= $messages['error_profile_image'] ?? '' ?></div>
                    </div>
                    <div class="input-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                            value="<?= htmlspecialchars($student_details['FirstName']) ?>">
                        <div class="error-message"><?= $messages['error_first_name'] ?? '' ?></div>
                    </div>
                    <div class="input-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                            value="<?= htmlspecialchars($student_details['LastName']) ?>">
                        <div class="error-message"><?= $messages['error_last_name'] ?? '' ?></div>
                    </div>
                    <div class="input-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" name="address"
                            rows="3"><?= htmlspecialchars($student_details['Address']) ?></textarea>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Contact & Academic Details</legend>
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?= htmlspecialchars($student_details['Email']) ?>">
                        <div class="error-message"><?= $messages['error_email'] ?? '' ?></div>
                    </div>
                    <div class="input-group">
                        <label for="tel_no">Telephone Number</label>
                        <input type="tel" class="form-control" id="tel_no" name="tel_no"
                            value="<?= htmlspecialchars($student_details['TelNo']) ?>">
                    </div>
                    <div class="input-group">
                        <label for="school_name">School Name</label>
                        <input type="text" class="form-control" id="school_name" name="school_name"
                            value="<?= htmlspecialchars($student_details['school_name']) ?>">
                    </div>
                </fieldset>

                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>

    <?php
$content = ob_get_clean();
include '../layouts.php'; 
?>
</body>

</html>