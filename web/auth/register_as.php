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
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
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

    .button-container {
        text-align: center;
        background-color: #ffffff;
        padding: 50px 40px;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        max-width: 700px;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 25px; 
    }

    .button-container h2 {
        color: #1cc1ba;
        font-weight: 800;
        margin-bottom: 40px;
        font-size: 2.2rem;
        letter-spacing: 1px;
        width: 100%;
    }

    .btn-custom {
        font-size: 18px;
        padding: 15px 30px;
        border-radius: 10px;
        min-width: 220px;
        transition: all 0.3s ease;
        font-weight: 600;
        text-decoration: none;
        color: #fff;
        border: none;
    }

    .btn-student {
        background-color: #d76c3a;
    }

    .btn-student:hover {
        background-color: #bb5a2e;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-parent {
        background-color: #4b545c;
    }

    .btn-parent:hover {
        background-color: #3b4249;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
    }
</style>

<div class="container-fluid registration-form-background">
    <div class="button-container">
        <h2>Register Here</h2>
        <a href="student_registration.php" class="btn btn-custom btn-student">Register as a Student</a>
        <a href="parent_registration.php" class="btn btn-custom btn-parent">Register as a Parent</a>
    </div>
</div>

<?php
// Buffer කරගත් HTML අන්තර්ගතය $content විචල්‍යයට ලබාගෙන layout ගොනුව include කිරීම
$content = ob_get_clean();
include '../layouts.php';
?>
