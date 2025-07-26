<?php
ob_start();
include '../../init.php'; // Correct path

// No session_start() here if it's in init.php
// No security check here, as it's a registration page.

// Define an array to hold error messages and previously submitted data
$messages = [];
$form_data = [
    'parent_name' => '', 'parent_email' => '', 'parent_phone' => '',
    'child_name' => '', 'student_reg_no' => '', 'relationship' => '',
    'parent_password' => '', 'parent_confirm_password' => ''
];

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- 1. Data Cleaning & Extraction ---
    $form_data['parent_name'] = dataClean($_POST['parent_name'] ?? '');
    $form_data['parent_email'] = dataClean($_POST['parent_email'] ?? '');
    $form_data['parent_phone'] = dataClean($_POST['parent_phone'] ?? '');
    $form_data['child_name'] = dataClean($_POST['child_name'] ?? '');
    $form_data['student_reg_no'] = dataClean($_POST['student_reg_no'] ?? '');
    $form_data['relationship'] = dataClean($_POST['relationship'] ?? '');
    $form_data['parent_password'] = $_POST['parent_password'] ?? ''; // Do not clean password yet
    $form_data['parent_confirm_password'] = $_POST['parent_confirm_password'] ?? '';


    // --- 2. Validation ---
    if (empty($form_data['parent_name'])) { $messages['parent_name'] = "Parent's Name is required."; }
    if (empty($form_data['parent_email'])) { $messages['parent_email'] = "Parent's Email is required."; }
    if (empty($form_data['parent_phone'])) { $messages['parent_phone'] = "Parent's Phone is required."; }
    if (empty($form_data['child_name'])) { $messages['child_name'] = "Child's Name is required."; }
    if (empty($form_data['student_reg_no'])) { $messages['student_reg_no'] = "Child's Registration Number is required."; }
    if (empty($form_data['relationship'])) { $messages['relationship'] = "Relationship to Child is required."; }
    if (empty($form_data['parent_password'])) { $messages['parent_password'] = "Password is required."; }
    if (empty($form_data['parent_confirm_password'])) { $messages['parent_confirm_password'] = "Confirm Password is required."; }


    // Email format and uniqueness check
    if (!empty($form_data['parent_email']) && !filter_var($form_data['parent_email'], FILTER_VALIDATE_EMAIL)) {
        $messages['parent_email'] = "Please enter a valid email address.";
    } else if (!empty($form_data['parent_email'])) {
        $db = dbConn();
        $escaped_email = $db->real_escape_string($form_data['parent_email']);
        $sql_email_check = "SELECT Id FROM users WHERE Email='$escaped_email'";
        $result_email_check = $db->query($sql_email_check);
        if ($result_email_check && $result_email_check->num_rows > 0) {
            $messages['parent_email'] = "This email address is already registered.";
        }
    }

    // Password strength and match check
    if (!empty($form_data['parent_password']) && strlen($form_data['parent_password']) < 8) {
        $messages['parent_password'] = "Password should be at least 8 characters long.";
    }
    if ($form_data['parent_password'] !== $form_data['parent_confirm_password']) {
        $messages['parent_confirm_password'] = "Passwords do not match.";
    }

    // Check if the student registration number exists and belongs to a student
    $db = dbConn(); // Make sure this is the only dbConn() call for POST processing
    $escaped_student_reg_no = $db->real_escape_string($form_data['student_reg_no']);
    $sql_student_check = "SELECT u.Id, u.FirstName, u.LastName 
                          FROM users u 
                          JOIN student_details sd ON u.Id = sd.user_id 
                          WHERE sd.registration_no = '$escaped_student_reg_no' AND u.user_role_id = (SELECT Id FROM user_roles WHERE RoleName = 'Student')";
    $result_student_check = $db->query($sql_student_check);
    
    $linked_student_id = null;
    if (!$result_student_check || $result_student_check->num_rows === 0) {
        $messages['student_reg_no'] = "No active student found with this Registration Number.";
    } else {
        $student_row = $result_student_check->fetch_assoc();
        $linked_student_id = $student_row['Id'];
    }

    // --- 3. If no validation errors, proceed with Database Insertion ---
    if (empty($messages)) {
        $db->begin_transaction(); // Start transaction for atomicity
        try {
            // Get Parent User Role ID
            $sql_get_parent_role_id = "SELECT Id FROM user_roles WHERE RoleName = 'Parent'";
            $parent_role_result = $db->query($sql_get_parent_role_id);
            if (!$parent_role_result || $parent_role_result->num_rows === 0) {
                throw new Exception("Parent user role not found in the system. Please ensure 'Parent' role exists in user_roles table.");
            }
            $parent_user_role_id = $parent_role_result->fetch_assoc()['Id'];

            // 1. Insert into 'users' table for Parent
            $hashed_password = password_hash($form_data['parent_password'], PASSWORD_DEFAULT);
            $parent_username = $form_data['parent_email']; // Using email as username for simplicity, adjust if needed
            
            // Split parent_name into FirstName and LastName
            $name_parts = explode(' ', $form_data['parent_name'], 2); // Split only at the first space
            $db_parent_first_name = $db->real_escape_string($name_parts[0]);
            $db_parent_last_name = $db->real_escape_string($name_parts[1] ?? ''); // Second part, or empty if no space

            $db_parent_phone = $db->real_escape_string($form_data['parent_phone']);
            $db_parent_email = $db->real_escape_string($form_data['parent_email']);
            $db_parent_username = $db->real_escape_string($parent_username);

            // Using default values for date_of_birth, gender, Address, NIC, ProfileImage as they are not in form
            $sql_insert_parent_user = "INSERT INTO users (FirstName, LastName, TelNo, Email, username, Password, user_role_id, Status, registered_at)
                                       VALUES ('$db_parent_first_name', '$db_parent_last_name', '$db_parent_phone', '$db_parent_email', '$db_parent_username', '$hashed_password', '$parent_user_role_id', 'Active', NOW())";
            $db->query($sql_insert_parent_user);
            $new_parent_user_id = $db->insert_id;

            if (!$new_parent_user_id) { throw new Exception("Failed to create parent user account. Database insert error: " . $db->error); }

            // 2. Insert into 'parent_details' table (if parent_details table has other fields, set them to default/null)
            // You mentioned parent_details table has 'id', 'user_id', 'occupation', 'created_at', 'updated_at' 
            // We only have user_id here.
            $sql_insert_parent_details = "INSERT INTO parent_details (user_id, occupation, created_at, updated_at) VALUES ('$new_parent_user_id', NULL, NOW(), NOW())";
            $db->query($sql_insert_parent_details);

            // 3. Insert into 'student_guardian_relationship' table
            if ($linked_student_id) {
                $db_relationship = $db->real_escape_string($form_data['relationship']);
                $sql_insert_relationship = "INSERT INTO student_guardian_relationship (student_user_id, guardian_user_id, relationship_type, created_at)
                                            VALUES ('$linked_student_id', '$new_parent_user_id', '$db_relationship', NOW())";
                $db->query($sql_insert_relationship);
            } else {
                // If student is not linked, parent account is created but not linked to a student
                // You might want a message for this case or make student linking mandatory
            }

            $db->commit(); // Commit the transaction
            
            // Set success message for login page
            $_SESSION['registration_success'] = "Parent account created successfully! You can now log in.";
            header("Location: success.php"); // Redirect to success page
            exit();

        } catch (Exception $e) {
            $db->rollback(); // Rollback on error
            $messages['db_error'] = "An error occurred during registration: " . $e->getMessage();
            // You can also add specific messages based on $e->getCode() for SQL errors
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    /* Your CSS remains the same */
    .registration-form-background {
        position: relative;
        background-image: url('<?= WEB_URL ?>images/school.jpg'); /* Confirm if your image path is correct */
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        min-height: 100vh; /* Allow page height to expand with content */
        height: auto;
        padding: 50px 15px; /* Add padding at the top and bottom */
        z-index: 1;
    }

    /* Applying a dark overlay on the background image */
    .registration-form-background::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background: rgba(10, 72, 97, 0.88); /* Dark blue overlay */
        z-index: -1;
    }

    /* --- White container box for the form --- */
    .form-container {
        width: 100%;
        max-width: 800px;
        background-color: #fff;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.37);
        margin: 120px auto 30px;
    }

    /* --- Form Title --- */
    .form-container h2 {
        color: #1cc1ba;
        font-weight: 800;
        text-align: center;
        margin-bottom: 35px;
        font-size: 2.2rem;
    }

    /* --- Styles for Form Inputs and Labels --- */
    .form-container label {
        font-size: 16px;
        margin-bottom: 5px;
        display: block;
        font-weight: 500;
        color: #333;
    }

    .form-control, .form-select {
        border-radius: 5px;
        padding: 10px;
        border: 1px solid #ced4da;
    }

    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25); /* Adjusted for warning color */
        border-color: #ffc107;
    }

    /* --- Styles for the Submit Button --- */
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #212529;
        font-size: 18px;
        padding: 12px 20px;
        border-radius: 8px;
        transition: background-color 0.3s ease;
        width: 100%;
        margin-top: 20px;
    }

    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
    }

    /* --- Styles for the Login Link --- */
    .login-section {
        text-align: center;
        margin-top: 25px;
        font-size: 16px;
    }

    .login-section a {
        color: #1cc1ba;
        text-decoration: none;
        font-weight: 600;
    }

    .login-section a:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>

    <div class="container-fluid registration-form-background">
        <div class="form-container">
            <h2>Parent Registration</h2>
            <?php if (!empty($messages['db_error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($messages['db_error']) ?></div>
            <?php endif; ?>
            <?php if (!empty($messages['student_reg_no'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($messages['student_reg_no']) ?></div>
            <?php endif; ?>
            
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="parent_name">Full Name</label>
                        <input type="text" class="form-control" id="parent_name" name="parent_name" value="<?= htmlspecialchars($form_data['parent_name']) ?>" required>
                        <div class="error-message"><?= $messages['parent_name'] ?? '' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="parent_email">Email</label>
                        <input type="email" class="form-control" id="parent_email" name="parent_email" value="<?= htmlspecialchars($form_data['parent_email']) ?>" required>
                        <div class="error-message"><?= $messages['parent_email'] ?? '' ?></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="parent_phone">Phone Number</label>
                        <input type="tel" class="form-control" id="parent_phone" name="parent_phone" value="<?= htmlspecialchars($form_data['parent_phone']) ?>" required>
                        <div class="error-message"><?= $messages['parent_phone'] ?? '' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="child_name">Child's Full Name</label>
                        <input type="text" class="form-control" id="child_name" name="child_name" value="<?= htmlspecialchars($form_data['child_name']) ?>" required>
                        <div class="error-message"><?= $messages['child_name'] ?? '' ?></div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="student_reg_no">Student Registration Number</label>
                        <input type="text" class="form-control" id="student_reg_no" name="student_reg_no" value="<?= htmlspecialchars($form_data['student_reg_no']) ?>" required>
                        <div class="error-message"><?= $messages['student_reg_no'] ?? '' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="relationship">Relationship to Child</label>
                        <select class="form-select" id="relationship" name="relationship" required>
                            <option value="" disabled selected>-- Select Relationship --</option>
                            <option value="Father" <?= ($form_data['relationship'] == 'Father') ? 'selected' : '' ?>>Father</option>
                            <option value="Mother" <?= ($form_data['relationship'] == 'Mother') ? 'selected' : '' ?>>Mother</option>
                            <option value="Guardian" <?= ($form_data['relationship'] == 'Guardian') ? 'selected' : '' ?>>Guardian</option>
                        </select>
                        <div class="error-message"><?= $messages['relationship'] ?? '' ?></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="parent_password">Password</label>
                        <input type="password" class="form-control" id="parent_password" name="parent_password" required>
                        <div class="error-message"><?= $messages['parent_password'] ?? '' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="parent_confirm_password">Confirm Password</label>
                        <input type="password" class="form-control" id="parent_confirm_password" name="parent_confirm_password" required>
                        <div class="error-message"><?= $messages['parent_confirm_password'] ?? '' ?></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-warning">Register as Parent</button>
            </form>

            <div class="login-section">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>

<?php
// Get the buffered HTML content into the $content variable and include the layout file
$content = ob_get_clean();
include '../layouts.php';
?>