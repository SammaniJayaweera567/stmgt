<?php
ob_start();
// [FIX 1] All paths corrected to '../'
include '../../init.php';

// [FIX 2] DEV_MODE check added to bypass login/role checks during development
if (defined('DEV_MODE') && DEV_MODE === false) {
    // This security check only runs when DEV_MODE is false (in production)
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php"); 
        exit();
    }
    
    // You can add role checks here for production if needed
    $allowed_roles = ['Admin', 'Teacher']; 
    if (!in_array($_SESSION['user_role_name'], $allowed_roles)) {
        die("Access Denied.");
    }
}

// Initialize messages array for add form
$messages = []; 
// Initialize messages array for update form (will be used if validation fails on update)
$update_messages = [];

// --- Registration Number Auto-Generation Logic ---
$new_registration_no = 'STU-' . time(); // Simple example: STU-1678888888

// Show success message if redirected with success=1 (for Add Student)
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>
        window.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Student added successfully!',
                showConfirmButton: false,
                timer: 2000
            });

            setTimeout(() => {
                const triggerEl = document.querySelector('[data-bs-target=\"#list\"]');
                if (triggerEl) {
                    const tab = new bootstrap.Tab(triggerEl);
                    tab.show();
                }
            }, 500);
        });
    </script>";
}

// Show success message if redirected with update_success=1 (for Edit Student)
if (isset($_GET['update_success']) && $_GET['update_success'] == 1) {
    echo "<script>
        window.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Student updated successfully!',
                showConfirmButton: false,
                timer: 2000
            });
            // Keep on the current tab (if you implement client-side tab persistence)
        });
    </script>";
}


// Add Student Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_student') {
    // The registration_no is now received from the form, but it was pre-generated and read-only.
    $registration_no = dataClean($_POST['registration_no']);
    $first_name = dataClean($_POST['first_name']);
    $last_name = dataClean($_POST['last_name']);
    $email = dataClean($_POST['email']);
    $school_name = dataClean($_POST['school_name']);
    $address_line1 = dataClean($_POST['address_line1']);
    $address_line2 = dataClean($_POST['address_line2']);
    $address_line3 = dataClean($_POST['address_line3']);
    $tel_home = dataClean($_POST['tel_home']);
    $mobile_no = dataClean($_POST['mobile_no']);
    $date_of_birth = dataClean($_POST['date_of_birth']);
    $gender = dataClean($_POST['gender']);
    $class_id = dataClean($_POST['class_id']);
    $username = dataClean($_POST['username']);
    $password = $_POST['password']; // Don't clean before validation/hashing

    // Validation for Add Student
    // Removed validation for registration_no being empty.
    if (empty($first_name)) $messages['first_name'] = "First Name is required.";
    if (empty($last_name)) $messages['last_name'] = "Last Name is required.";
    if (empty($email)) $messages['email'] = "Email is required.";
    if (empty($school_name)) $messages['school_name'] = "School Name is required.";
    if (empty($address_line1)) $messages['address_line1'] = "Address Line 1 is required.";
    if (empty($tel_home)) $messages['tel_home'] = "Home Telephone is required.";
    if (empty($mobile_no)) $messages['mobile_no'] = "Mobile Number is required.";
    if (empty($date_of_birth)) $messages['date_of_birth'] = "Date of Birth is required.";
    if (empty($gender)) $messages['gender'] = "Gender is required.";
    if (empty($class_id)) $messages['class_id'] = "Class is required.";
    if (empty($username)) $messages['username'] = "Username is required.";
    if (empty($password)) $messages['password'] = "Password is required.";

    $db = dbConn();

    // Check for existing Registration No. (Still important as a safeguard, though it's auto-generated)
    if (!empty($registration_no)) {
        $sql = "SELECT * FROM students WHERE registration_no='$registration_no'";
        $result = $db->query($sql);
        if ($result->num_rows > 0) {
            $messages['registration_no'] = "Generated Registration Number already exists. Please try again.";
        }
    }

    // Check for existing Email
    if (!empty($email)) {
        $sql = "SELECT * FROM students WHERE email='$email'";
        $result = $db->query($sql);
        if ($result->num_rows > 0) {
            $messages['email'] = "Email already exists.";
        }
    }
    
    // Check for existing Username
    if (!empty($username)) {
        $sql = "SELECT * FROM students WHERE username='$username'";
        $result = $db->query($sql);
        if ($result->num_rows > 0) {
            $messages['username'] = "Username already exists.";
        }
    }

    // Password strength validation
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $messages['password'] = "Password must be at least 8 characters.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $messages['password'] = "Password must contain uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $messages['password'] = "Password must contain lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $messages['password'] = "Password must contain number.";
        } elseif (!preg_match('/[\W]/', $password)) {
            $messages['password'] = "Password must contain special character.";
        }
    }

    if (empty($messages)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO students (registration_no, first_name, last_name, email, school_name, address_line1, address_line2, address_line3, tel_home, mobile_no, date_of_birth, gender, class_id, username, password)
                VALUES ('$registration_no', '$first_name', '$last_name', '$email', '$school_name', '$address_line1', '$address_line2', '$address_line3', '$tel_home', '$mobile_no', '$date_of_birth', '$gender', '$class_id', '$username', '$password')";
        
        if ($db->query($sql)) {
            header("Location: manage_students.php?success=1");
            exit();
        } else {
             $messages['db_error'] = "Database error: " . $db->error;
        }
    } else {
        // If there are validation errors, ensure the add form tab is shown
        echo "<script>
            window.addEventListener('DOMContentLoaded', function() {
                const triggerEl = document.querySelector('[data-bs-target=\"#add\"]');
                if (triggerEl) {
                    const tab = new bootstrap.Tab(triggerEl);
                    tab.show();
                }
            });
        </script>";
    }
}

// Edit Student Form Submission (within modal) - No changes needed for auto-gen registration no here
// because it's for *editing* existing students, not adding new ones.
// However, the registration number field in the edit modal should also be readonly.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_student') {
    $Id = dataClean($_POST['Id']); // Student ID
    $registration_no = dataClean($_POST['registration_no']); // Still get it, but it should be readonly
    $first_name = dataClean($_POST['first_name']);
    $last_name = dataClean($_POST['last_name']);
    $email = dataClean($_POST['email']);
    $school_name = dataClean($_POST['school_name']);
    $address_line1 = dataClean($_POST['address_line1']);
    $address_line2 = dataClean($_POST['address_line2']);
    $address_line3 = dataClean($_POST['address_line3']);
    $tel_home = dataClean($_POST['tel_home']);
    $mobile_no = dataClean($_POST['mobile_no']);
    $date_of_birth = dataClean($_POST['date_of_birth']);
    $gender = dataClean($_POST['gender']);
    $class_id = dataClean($_POST['class_id']);
    $username = dataClean($_POST['username']);

    // Validation for Edit Student
    // No longer check if registration_no is empty for update, as it's read-only
    if (empty($first_name)) $update_messages['first_name'] = "First Name is required.";
    if (empty($last_name)) $update_messages['last_name'] = "Last Name is required.";
    if (empty($email)) $update_messages['email'] = "Email is required.";
    if (empty($school_name)) $update_messages['school_name'] = "School Name is required.";
    if (empty($address_line1)) $update_messages['address_line1'] = "Address Line 1 is required.";
    if (empty($tel_home)) $update_messages['tel_home'] = "Home Telephone is required.";
    if (empty($mobile_no)) $update_messages['mobile_no'] = "Mobile Number is required.";
    if (empty($date_of_birth)) $update_messages['date_of_birth'] = "Date of Birth is required.";
    if (empty($gender)) $update_messages['gender'] = "Gender is required.";
    if (empty($class_id)) $update_messages['class_id'] = "Class is required.";
    if (empty($username)) $update_messages['username'] = "Username is required.";

    $db = dbConn();

    // Check for existing Registration No. (excluding current student)
    // This is crucial if registration_no could be manually changed (which it won't be now)
    // or if the auto-generation might rarely conflict.
    if (!empty($registration_no)) {
        $sql = "SELECT * FROM students WHERE registration_no='$registration_no' AND Id!='$Id'";
        $result = $db->query($sql);
        if ($result->num_rows > 0) {
            $update_messages['registration_no'] = "Registration Number already exists for another student.";
        }
    }

    // Check for existing Email (excluding current student)
    if (!empty($email)) {
        $sql = "SELECT * FROM students WHERE email='$email' AND Id!='$Id'";
        $result = $db->query($sql);
        if ($result->num_rows > 0) {
            $update_messages['email'] = "Email already exists.";
        }
    }

    // Check for existing Username (excluding current student)
    if (!empty($username)) {
        $sql = "SELECT * FROM students WHERE username='$username' AND Id!='$Id'";
        $result = $db->query($sql);
        if ($result->num_rows > 0) {
            $update_messages['username'] = "Username already exists.";
        }
    }

    if (empty($update_messages)) {
        $sql = "UPDATE students SET 
                    registration_no='$registration_no', 
                    first_name='$first_name', 
                    last_name='$last_name', 
                    email='$email', 
                    school_name='$school_name', 
                    address_line1='$address_line1', 
                    address_line2='$address_line2', 
                    address_line3='$address_line3', 
                    tel_home='$tel_home', 
                    mobile_no='$mobile_no', 
                    date_of_birth='$date_of_birth', 
                    gender='$gender', 
                    class_id='$class_id', 
                    username='$username'
                WHERE Id='$Id'";
        
        if ($db->query($sql)) {
            header("Location: manage_students.php?update_success=1");
            exit();
        } else {
            $update_messages['db_error'] = "Database error: " . $db->error;
             echo "<script>
                 window.addEventListener('DOMContentLoaded', function() {
                     Swal.fire({
                         icon: 'error',
                         title: 'Database Error!',
                         text: 'A database error occurred during the student update: " . addslashes($db->error) . "',
                         showConfirmButton: true
                     });
                 });
             </script>";
        }
    } else {
        echo "<script>
            window.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error!',
                    text: 'Please check your inputs for the student update. " . implode(", ", array_values($update_messages)) . "',
                    showConfirmButton: true
                });
            });
        </script>";
    }
}


// Fetch student data for displaying in the table
$db = dbConn();
$allStudentsSql = "SELECT s.*, c.class_full_name AS ClassName 
                   FROM students s
                   LEFT JOIN classes c ON s.class_id = c.Id";
$allStudentsResult = $db->query($allStudentsSql);

// Fetch classes for dropdowns
$classesSql = "SELECT Id, class_full_name AS Description FROM classes";
$classesResult = $db->query($classesSql);
$classes = [];
if ($classesResult && $classesResult->num_rows > 0) {
    while ($row = $classesResult->fetch_assoc()) {
        $classes[] = $row;
    }
}

?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text"><i class="fas fa-arrow-alt-circle-right" style="font-size: 20px;"></i>
            <h5 class="mb-5 w-auto">Manage Students</h5>
        </div>
        <div class="col-12 mt-3">
            <ul class="nav nav-tabs" id="studentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list"
                        type="button" role="tab">
                        <i class="fas fa-list me-1" style="font-size: 15px; margin-right: 5px;"></i> Student List
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button"
                        role="tab">
                        <i class="fas fa-plus-circle me-1" style="font-size: 15px; margin-right: 5px;"></i>
                        Add Student
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="studentTabsContent">
                <div class="tab-pane fade show active mt-3" id="list" role="tabpanel">
                    <div class="card mt-5">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="studentTable" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Reg No.</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Mobile</th>
                                            <th>Class</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                if ($allStudentsResult && $allStudentsResult->num_rows > 0) {
                                    while ($row = $allStudentsResult->fetch_assoc()) {
                                        echo "<tr>
                                                    <td>{$row['registration_no']}</td>
                                                    <td>{$row['first_name']} {$row['last_name']}</td>
                                                    <td>{$row['email']}</td>
                                                    <td>{$row['mobile_no']}</td>
                                                    <td>" . ($row['ClassName'] ?? 'N/A') . "</td> 
                                                    <td>
                                                        <button type='button' class='btn btn-info btn-sm view-btn me-1' data-id='{$row['Id']}'><i class='fas fa-eye'></i></button>
                                                        <button type='button' class='btn btn-primary btn-sm edit-btn' data-id='{$row['Id']}'><i class='fas fa-edit'></i></button>
                                                        <button type='button' class='btn btn-danger btn-sm delete-btn' data-id='{$row['Id']}'><i class='fas fa-trash'></i></button>
                                                    </td>
                                                </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>No students found.</td></tr>";
                                }
                                ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="add" role="tabpanel">
                    <div class="col-md-12 mt-5">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Register New Student</h3>
                            </div>
                            <form id="addStudentForm" method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                                novalidate class="p-4">
                                <div class="card-body">
                                    <div class="form-row row">
                                        <div class="form-group col-md-6">
                                            <label>Registration No.</label>
                                            <input type="text" name="registration_no" class="form-control"
                                                value="<?= htmlspecialchars($new_registration_no) ?>" readonly>
                                            <span class="text-danger"><?= @$messages['registration_no'] ?></span>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>First Name</label>
                                            <input type="text" name="first_name" class="form-control"
                                                value="<?= @$first_name ?>">
                                            <span class="text-danger"><?= @$messages['first_name'] ?></span>
                                        </div>
                                    </div>

                                    <div class="form-row row mt-3">
                                        <div class="form-group col-md-6">
                                            <label>Last Name</label>
                                            <input type="text" name="last_name" class="form-control"
                                                value="<?= @$last_name ?>">
                                            <span class="text-danger"><?= @$messages['last_name'] ?></span>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Email</label>
                                            <input type="email" name="email" class="form-control"
                                                value="<?= @$email ?>">
                                            <span class="text-danger"><?= @$messages['email'] ?></span>
                                        </div>
                                    </div>

                                    <div class="form-group mt-3">
                                        <label>School Name</label>
                                        <input type="text" name="school_name" class="form-control"
                                            value="<?= @$school_name ?>">
                                        <span class="text-danger"><?= @$messages['school_name'] ?></span>
                                    </div>

                                    <div class="form-group mt-3">
                                        <label>Address Line 1</label>
                                        <input type="text" name="address_line1" class="form-control"
                                            value="<?= @$address_line1 ?>">
                                        <span class="text-danger"><?= @$messages['address_line1'] ?></span>
                                    </div>
                                    <div class="form-group mt-3">
                                        <label>Address Line 2 (Optional)</label>
                                        <input type="text" name="address_line2" class="form-control"
                                            value="<?= @$address_line2 ?>">
                                    </div>
                                    <div class="form-group mt-3">
                                        <label>Address Line 3 (Optional)</label>
                                        <input type="text" name="address_line3" class="form-control"
                                            value="<?= @$address_line3 ?>">
                                    </div>

                                    <div class="form-row row mt-3">
                                        <div class="form-group col-md-6">
                                            <label>Home Telephone</label>
                                            <input type="text" name="tel_home" class="form-control"
                                                value="<?= @$tel_home ?>">
                                            <span class="text-danger"><?= @$messages['tel_home'] ?></span>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Mobile No</label>
                                            <input type="text" name="mobile_no" class="form-control"
                                                value="<?= @$mobile_no ?>">
                                            <span class="text-danger"><?= @$messages['mobile_no'] ?></span>
                                        </div>
                                    </div>

                                    <div class="form-row row mt-3">
                                        <div class="form-group col-md-6">
                                            <label>Date of Birth</label>
                                            <input type="date" name="date_of_birth" class="form-control"
                                                value="<?= @$date_of_birth ?>">
                                            <span class="text-danger"><?= @$messages['date_of_birth'] ?></span>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Gender</label>
                                            <select name="gender" class="form-control">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?= (@$gender == 'Male') ? 'selected' : ''; ?>>Male
                                                </option>
                                                <option value="Female" <?= (@$gender == 'Female') ? 'selected' : ''; ?>>
                                                    Female</option>
                                                <option value="Other" <?= (@$gender == 'Other') ? 'selected' : ''; ?>>Other
                                                </option>
                                            </select>
                                            <span class="text-danger"><?= @$messages['gender'] ?></span>
                                        </div>
                                    </div>

                                    <div class="form-row row mt-3">
                                        <div class="form-group col-md-6">
                                            <label>Class</label>
                                            <select name="class_id" class="form-control">
                                                <option value="">Select Class</option>
                                                <?php foreach ($classes as $class): ?>
                                                <option value="<?= $class['Id'] ?>"
                                                    <?= (@$class_id == $class['Id']) ? 'selected' : ''; ?>>
                                                    <?= $class['Description'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="text-danger"><?= @$messages['class_id'] ?></span>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Username</label>
                                            <input type="text" name="username" class="form-control"
                                                value="<?= @$username ?>">
                                            <span class="text-danger"><?= @$messages['username'] ?></span>
                                        </div>
                                    </div>

                                    <div class="form-group mt-3">
                                        <label>Password</label>
                                        <input type="password" name="password" class="form-control">
                                        <span class="text-danger"><?= @$messages['password'] ?></span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="action" value="add_student"
                                        class="btn btn-primary px-4 py-2">Save
                                        Student</button>
                                    <button type="reset" class="btn btn-outline-secondary px-4 py-2 ms-2"
                                        id="resetAddStudentForm">
                                        Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="color: #ffff; background-color: #d76c3a;">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
            </div>
            <form id="editStudentForm" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_student">
                    <input type="hidden" name="Id" id="editStudentId">

                    <div class="form-row row">
                        <div class="form-group col-md-6">
                            <label>Registration No.</label>
                            <input type="text" name="registration_no" id="editRegistrationNo" class="form-control" readonly>
                            <span class="text-danger" id="editRegistrationNoError"></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="editFirstName" class="form-control">
                            <span class="text-danger" id="editFirstNameError"></span>
                        </div>
                    </div>

                    <div class="form-row row mt-3">
                        <div class="form-group col-md-6">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="editLastName" class="form-control">
                            <span class="text-danger" id="editLastNameError"></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control">
                            <span class="text-danger" id="editEmailError"></span>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>School Name</label>
                        <input type="text" name="school_name" id="editSchoolName" class="form-control">
                        <span class="text-danger" id="editSchoolNameError"></span>
                    </div>

                    <div class="form-group mt-3">
                        <label>Address Line 1</label>
                        <input type="text" name="address_line1" id="editAddressLine1" class="form-control">
                        <span class="text-danger" id="editAddressLine1Error"></span>
                    </div>
                    <div class="form-group mt-3">
                        <label>Address Line 2 (Optional)</label>
                        <input type="text" name="address_line2" id="editAddressLine2" class="form-control">
                    </div>
                    <div class="form-group mt-3">
                        <label>Address Line 3 (Optional)</label>
                        <input type="text" name="address_line3" id="editAddressLine3" class="form-control">
                    </div>

                    <div class="form-row row mt-3">
                        <div class="form-group col-md-6">
                            <label>Home Telephone</label>
                            <input type="text" name="tel_home" id="editTelHome" class="form-control">
                            <span class="text-danger" id="editTelHomeError"></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Mobile No</label>
                            <input type="text" name="mobile_no" id="editMobileNo" class="form-control">
                            <span class="text-danger" id="editMobileNoError"></span>
                        </div>
                    </div>

                    <div class="form-row row mt-3">
                        <div class="form-group col-md-6">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" id="editDateOfBirth" class="form-control">
                            <span class="text-danger" id="editDateOfBirthError"></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Gender</label>
                            <select name="gender" id="editGender" class="form-control">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <span class="text-danger" id="editGenderError"></span>
                        </div>
                    </div>

                    <div class="form-row row mt-3">
                        <div class="form-group col-md-6">
                            <label>Class</label>
                            <select name="class_id" id="editClassId" class="form-control">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['Id'] ?>"><?= $class['Description'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="text-danger" id="editClassIdError"></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Username</label>
                            <input type="text" name="username" id="editUsername" class="form-control">
                            <span class="text-danger" id="editUsernameError"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="color: #ffff; background-color: #d76c3a;">
                <h5 class="modal-title" id="viewStudentModalLabel">Student Details</h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>ID:</strong> <span id="viewId"></span></p>
                        <p><strong>Registration No.:</strong> <span id="viewRegistrationNo"></span></p>
                        <p><strong>Full Name:</strong> <span id="viewFullName"></span></p>
                        <p><strong>Email:</strong> <span id="viewEmail"></span></p>
                        <p><strong>School Name:</strong> <span id="viewSchoolName"></span></p>
                        <p><strong>Address:</strong> <span id="viewAddress"></span></p>
                        <p><strong>Home Tel:</strong> <span id="viewTelHome"></span></p>
                        <p><strong>Mobile No:</strong> <span id="viewMobileNo"></span></p>
                        <p><strong>Date of Birth:</strong> <span id="viewDateOfBirth"></span></p>
                        <p><strong>Gender:</strong> <span id="viewGender"></span></p>
                        <p><strong>Class:</strong> <span id="viewClass"></span></p>
                        <p><strong>Username:</strong> <span id="viewUsername"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#studentTable').DataTable();

// Handle Edit button click
$(document).on('click', '.edit-btn', function() {
    var studentId = $(this).data('id');
    $('#editStudentForm .text-danger').text('');

    $.ajax({
        url: 'fetch_student_data.php',
        type: 'POST',
        data: {
            id: studentId
        },
        dataType: 'json',
        success: function(response) {
            // alert(response); // <-- මෙය ඉවත් කරන්න!

            if (response.success) {
                $('#editStudentId').val(response.data.Id);
                $('#editRegistrationNo').val(response.data.registration_no);
                $('#editFirstName').val(response.data.first_name);
                $('#editLastName').val(response.data.last_name);
                $('#editEmail').val(response.data.email);
                $('#editSchoolName').val(response.data.school_name);
                $('#editAddressLine1').val(response.data.address_line1);
                $('#editAddressLine2').val(response.data.address_line2);
                $('#editAddressLine3').val(response.data.address_line3);
                $('#editTelHome').val(response.data.tel_home);
                $('#editMobileNo').val(response.data.mobile_no);
                $('#editDateOfBirth').val(response.data.date_of_birth);
                $('#editGender').val(response.data.gender);
                $('#editClassId').val(response.data.class_id);
                $('#editUsername').val(response.data.username);
                
                $('#editStudentModal').modal('show'); // <-- මෙය uncomment කරන්න! (ඔබේ edit modal එකේ ID එක `#editStudentModal` යැයි උපකල්පනය කර ඇත)
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message || 'Failed to fetch student data.',
                    showConfirmButton: true
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: ", status, error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while fetching student data. Please try again.',
                showConfirmButton: true
            });
        }
    });
});

    // Handle Delete button click
    $(document).on('click', '.delete-btn', function() {
        var studentId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_student.php',
                    type: 'POST',
                    data: {
                        id: studentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error);
                        Swal.fire(
                            'Error!',
                            'An error occurred during deletion. Please try again.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Handle View button click
    $(document).on('click', '.view-btn', function() {
        var studentId = $(this).data('id');

        $.ajax({
            url: 'fetch_student_data.php',
            type: 'POST',
            data: {
                id: studentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#viewId').text(response.data.Id);
                    $('#viewRegistrationNo').text(response.data.registration_no);
                    $('#viewFullName').text(response.data.first_name + ' ' + response.data.last_name);
                    $('#viewEmail').text(response.data.email);
                    $('#viewSchoolName').text(response.data.school_name);
                    let fullAddress = response.data.address_line1;
                    if (response.data.address_line2) fullAddress += ', ' + response.data.address_line2;
                    if (response.data.address_line3) fullAddress += ', ' + response.data.address_line3;
                    $('#viewAddress').text(fullAddress);
                    
                    $('#viewTelHome').text(response.data.tel_home);
                    $('#viewMobileNo').text(response.data.mobile_no);
                    $('#viewDateOfBirth').text(response.data.date_of_birth);
                    $('#viewGender').text(response.data.gender);
                    $('#viewClass').text(response.data.ClassName || 'N/A');
                    $('#viewUsername').text(response.data.username);

                    $('#viewStudentModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Failed to fetch student data for viewing.',
                        showConfirmButton: true
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while fetching student data. Please try again.',
                    showConfirmButton: true
                });
            }
        });
    });

    // Handle Reset Form button click for Add Student Form
    // This needs to also regenerate the registration number
    $(document).on('click', '#resetAddStudentForm', function() {
        const addStudentForm = document.getElementById('addStudentForm');
        if (addStudentForm) {
            addStudentForm.reset();
            document.querySelectorAll('#add .text-danger').forEach(span => {
                span.textContent = ''; // Clear error messages
            });
            // Regenerate the registration number on form reset for the add form
            $.ajax({
                url: 'generate_registration_no.php', // A new file to generate just the reg no
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('input[name="registration_no"]').val(response.registration_no);
                    } else {
                        console.error('Failed to regenerate registration number:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error generating new reg no:', status, error);
                }
            });
        }
    });

    // Re-generate registration number when switching to the Add Student tab
    // This ensures a fresh number each time the tab is opened
    $('button[data-bs-target="#add"]').on('shown.bs.tab', function (e) {
        $.ajax({
            url: 'generate_registration_no.php', // A new file to generate just the reg no
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('input[name="registration_no"]').val(response.registration_no);
                } else {
                    console.error('Failed to regenerate registration number on tab switch:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error generating new reg no on tab switch:', status, error);
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>