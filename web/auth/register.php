<?php
ob_start();
include '../../init.php';
?>

<style>
.registration-form-background {
    position: relative;
    background-image: url('../images/school.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    min-height: 100vh;
    z-index: 1;
}

.site-wrap {
    margin-top: -98px;
}

.form-container {
    max-width: 100%;
    margin: 40px auto;
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.37);
}

.nav-tabs .nav-link.active {
    background-color: #000;
    color: #fff !important;
}

.nav-tabs .nav-link {
    color: #000;
}

.form-control:focus {
    box-shadow: none;
    border-color: #fd7e14;
}

.btn-orange {
    background-color: #fd7e14;
    color: #fff;
}

.btn-orange:hover {
    background-color: #e96b0d;
}

.form-container {
    max-width: 100%;
    margin: 0px auto;
    padding: 50px 30px;
    background: #fff;
    border-top-right-radius: 20px;
    border-top-left-radius: 0px;
    border-bottom-left-radius: 0px;
    border-bottom-right-radius: 20px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}

.col-md-6.reg-form-right {
    padding-left: 0px;
}

.nav-tabs .nav-link.active {
    background-color: #0d6efd;
    color: #fff;
}

.register-form {
    max-width: 100%;
    margin: 0 auto;
    padding: 50px 60px 100px 60px;
    margin: 150px auto;
    align-items: center;
    justify-content: center;
    display: flex;
}

.registration-form-background::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 100%;
    background: rgba(10, 72, 97, 0.88);
    z-index: -1;
}

.reg-form-left {
    padding: 50px;
    background: #1cc1ba82;
    border-top-left-radius: 20px;
    border-bottom-left-radius: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.42);
}

label {
    font-size: 16px;
}

h3.text-center {
    color: #8196e5;
    font-weight: 700;
}

.text-white {
    color: #fff !important;
    font-size: 20px;
}

.left-main-text,
.right-main-text {
    letter-spacing: 1.8px;
    line-height: 30px;
    font-weight: 800;
}

.row.center-row {
    margin: 0 auto;
}

img.bottom-img {
    width: 250px;
    position: absolute;
    bottom: 0px;
    left: 30px;
}

.nav-tabs .nav-link.active {
    background-color: #fdbc16;
    color: #000000 !important;
}

.nav-tabs .nav-link {
    color: #000;
    background: #fff7e4;
}
</style>

<!-- Student Registration Form -->
<div class="container-fluid registration-form-background mt-5 pt-5">
    <div class="register-form vertical-center">
        <div class="row center-row">
            <div class="col-md-12 reg-form-right pl-0">
                <div class="right-side-form">
                    <div class="form-container m-0">
                        <h2 class="text-center mb-5 pb-3 right-main-text" style="color: #1cc1ba;">REGISTRATION
                        </h2>

                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" id="registrationTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="student-tab" data-bs-toggle="tab"
                                    data-bs-target="#student-form" type="button" role="tab" aria-controls="student-form"
                                    aria-selected="true">Student Registration</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="parent-tab" data-bs-toggle="tab"
                                    data-bs-target="#parent-form" type="button" role="tab" aria-controls="parent-form"
                                    aria-selected="false">Parent Registration</button>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content mt-3" id="registrationTabContent">

                            <!-- Student Form -->
                            <div class="tab-pane fade show active" id="student-form" role="tabpanel"
                                aria-labelledby="student-tab">
                                <form action="success.php" method="post">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>First Name</label>
                                            <input type="text" class="form-control" name="first_name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Last Name</label>
                                            <input type="text" class="form-control" name="last_name" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Email</label>
                                            <input type="email" class="form-control" name="email" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>School Name</label>
                                            <input type="text" class="form-control" name="school_name" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label>Address Line 1</label>
                                        <input type="text" class="form-control" name="address_line1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Address Line 2</label>
                                        <input type="text" class="form-control" name="address_line2">
                                    </div>
                                    <div class="mb-3">
                                        <label>Address Line 3</label>
                                        <input type="text" class="form-control" name="address_line3">
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Tel. No. (Home)</label>
                                            <input type="tel" class="form-control" name="home_phone">
                                        </div>
                                        <div class="col-md-6">
                                            <label>Mobile No.</label>
                                            <input type="tel" class="form-control" name="mobile_phone" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Date of Birth</label>
                                            <input type="date" class="form-control" name="dob" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Gender</label>
                                            <select class="form-control" name="gender" required>
                                                <option value="" disabled selected>Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Select Grade</label>
                                            <select class="form-control" name="grade" required>
                                                <option value="" disabled selected>Select Grade</option>
                                                <option value="Grade 6">Grade 6</option>
                                                <option value="Grade 7">Grade 7</option>
                                                <option value="Grade 8">Grade 8</option>
                                                <option value="Grade 9">Grade 9</option>
                                                <option value="Grade 10">Grade 10</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Select Class</label>
                                            <select class="form-control" name="class" required>
                                                <option value="A">Class A</option>
                                                <option value="B">Class B</option>
                                                <option value="C">Class C</option>
                                                <option value="D">Class D</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Username</label>
                                            <input type="text" class="form-control" name="username" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Password</label>
                                            <input type="password" class="form-control" name="password" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label>Confirm Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">Register as Student</button>
                                </form>
                            </div>

                            <!-- Parent Form -->
                            <div class="tab-pane fade" id="parent-form" role="tabpanel" aria-labelledby="parent-tab">
                                <form action="success.php" method="post">
                                    <div class="mb-3">
                                        <label>Full Name</label>
                                        <input type="text" class="form-control" name="parent_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Email</label>
                                        <input type="email" class="form-control" name="parent_email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Phone Number</label>
                                        <input type="tel" class="form-control" name="parent_phone" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Child’s Full Name</label>
                                        <input type="text" class="form-control" name="child_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Student Registration Number</label>
                                        <input type="text" class="form-control" name="student_reg_no" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Relationship</label>
                                        <select class="form-select" name="relationship" required>
                                            <option value="">-- Select --</option>
                                            <option value="Father">Father</option>
                                            <option value="Mother">Mother</option>
                                            <option value="Guardian">Guardian</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Password</label>
                                        <input type="password" class="form-control" name="parent_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Confirm Password</label>
                                        <input type="password" class="form-control" name="parent_confirm_password"
                                            required>
                                    </div>
                                    <button type="submit" class="btn btn-warning w-100">Register as Parent</button>
                                </form>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <p>Already have an account?</p>
                            <a href="student_login.php" class="btn btn-outline-primary me-2">Login as Student</a>
                            <a href="parent_login.php" class="btn btn-outline-warning">Login as Parent</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
$content = ob_get_clean();
include '../layouts.php';
?>