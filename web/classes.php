<?php
ob_start();
include '../init.php'; // Assuming init.php contains dbConn() and dataClean()

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$messages = [];

// --- Part 1: Handle Enrollment Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll'])) {
    // Security: Check if user is logged in to enroll
    error_log(print_r($_SESSION, true));
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['login_error'] = "Please log in to enroll in a class.";
        header("Location: " . WEB_URL . "auth/login.php");
        exit();
    }

    // Check if the user role is 'student'
    if (!isset($_SESSION['user_role_name']) || strtolower($_SESSION['user_role_name']) != 'student') {
        $messages['error'] = "Only students can enroll in classes.";
    } else {
        $student_user_id = (int)$_SESSION['user_id'];
        $class_id = (int)$_POST['class_id'];
        $db = dbConn();

        // Validation 1: Check if already enrolled
        $sql_check_enrollment = "SELECT id FROM enrollments WHERE student_user_id = $student_user_id AND class_id = $class_id AND status = 'active'";
        $result_check_enrollment = $db->query($sql_check_enrollment);
        if ($result_check_enrollment && $result_check_enrollment->num_rows > 0) {
            $messages['error'] = "You are already enrolled in this class.";
        } else {
            // Validation 2: Check if the class is full
            $sql_class_details = "SELECT max_students, day_of_week, start_time, end_time FROM classes WHERE id = $class_id AND status = 'Active'";
            $result_class_details = $db->query($sql_class_details);

            if (!$result_class_details || $result_class_details->num_rows == 0) {
                $messages['error'] = "Selected class not found or it is not open for enrollment.";
            } else {
                $class_info = $result_class_details->fetch_assoc();
                $max_students = $class_info['max_students'];
                $new_class_day = $class_info['day_of_week'];
                $new_class_start = $class_info['start_time'];
                $new_class_end = $class_info['end_time'];

                $sql_current_students = "SELECT COUNT(id) AS enrolled_count FROM enrollments WHERE class_id = $class_id AND status = 'active'";
                $result_current = $db->query($sql_current_students);
                $enrolled_count = $result_current->fetch_assoc()['enrolled_count'];

                if ($enrolled_count >= $max_students) {
                    $messages['error'] = "Sorry, this class is full and cannot accept new enrollments.";
                } else {
                    // Validation 3: Check for student's schedule conflict with other enrolled classes
                    $sql_student_overlap = "SELECT e.id
                                            FROM enrollments e
                                            JOIN classes c ON e.class_id = c.id
                                            WHERE e.student_user_id = $student_user_id
                                            AND e.status = 'active'
                                            AND c.day_of_week = '$new_class_day'
                                            AND (
                                                ('$new_class_start' < c.end_time AND '$new_class_end' > c.start_time)
                                            )";
                    $result_student_overlap = $db->query($sql_student_overlap);

                    if ($result_student_overlap && $result_student_overlap->num_rows > 0) {
                        $messages['error'] = "You are already enrolled in another class at this time. Please choose a different slot.";
                    } else {
                        // All checks passed, proceed with enrollment
                        $enrollment_date = date('Y-m-d H:i:s');
                        $status = 'active'; // Status is now active by default

                        $sql_enroll = "INSERT INTO enrollments (student_user_id, class_id, enrollment_date, status)
                                       VALUES ($student_user_id, $class_id, '$enrollment_date', '$status')";

                        if ($db->query($sql_enroll)) {
                            $_SESSION['enroll_success'] = "You have successfully enrolled in the class!";
                            header("Location: " . WEB_URL . "dashboard/student.php");
                            exit();
                        } else {
                            $messages['error'] = "An error occurred during enrollment. Please try again.";
                        }
                    }
                }
            }
        }
    }
}


// --- Part 2: Fetch and Display Classes (GET Request) ---
$level_id = (int)($_GET['level_id'] ?? 0);
$subject_id = (int)($_GET['subject_id'] ?? 0);

if (!$level_id || !$subject_id) {
    // If no level_id or subject_id, redirect to a page where student can select them
    header("Location: " . WEB_URL . "student/select_class_criteria.php"); // Create this page if it doesn't exist
    exit();
}

$db = dbConn(); // Ensure connection is established for GET part

// Fetch details for the page title
$sql_level = "SELECT level_name FROM class_levels WHERE id = $level_id";
$level_name_result = $db->query($sql_level);
$level_name = ($level_name_result && $level_name_result->num_rows > 0) ? $level_name_result->fetch_assoc()['level_name'] : 'Unknown Grade';

$sql_subject = "SELECT subject_name FROM subjects WHERE id = $subject_id";
$subject_name_result = $db->query($sql_subject);
$subject_name = ($subject_name_result && $subject_name_result->num_rows > 0) ? $subject_name_result->fetch_assoc()['subject_name'] : 'Unknown Subject';

// Main query to get all available classes with details
$sql_classes = "SELECT
                    c.id AS class_id,
                    c.class_fee,
                    c.day_of_week,
                    c.start_time,
                    c.end_time,
                    c.max_students,
                    u.FirstName AS teacher_first_name,
                    u.LastName AS teacher_last_name,
                    ct.type_name AS class_type,
                    cr.room_name AS class_room_name,
                    s.subject_name,
                    cl.level_name AS class_level_name,
                    (SELECT COUNT(e.id) FROM enrollments e WHERE e.class_id = c.id AND e.status = 'active') AS current_enrolled_students
                FROM classes AS c
                LEFT JOIN users AS u ON c.teacher_id = u.Id
                LEFT JOIN user_roles AS ur ON u.user_role_id = ur.Id AND ur.RoleName = 'Teacher'
                LEFT JOIN class_types AS ct ON c.class_type_id = ct.id
                LEFT JOIN class_rooms AS cr ON c.class_room_id = cr.id
                LEFT JOIN subjects AS s ON c.subject_id = s.id
                LEFT JOIN class_levels AS cl ON c.class_level_id = cl.id
                WHERE c.class_level_id = $level_id
                AND c.subject_id = $subject_id
                AND c.status = 'Active'
                AND ur.RoleName = 'Teacher'
                ORDER BY c.day_of_week, c.start_time ASC"; // Order by day and time for better readability

$result_classes = $db->query($sql_classes);
?>
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    .page-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }

    .page-header {
        text-align: center;
        color: #343a40;
        margin-bottom: 40px;
    }

    .page-header h1 {
        font-weight: 700;
        font-size: 2.5rem;
        /* Increased font size for prominence */
    }

    .page-header span {
        color: #1cc1ba;
    }

    .classes-list {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .class-card {
        display: flex;
        flex-wrap: wrap;
        /* Allow wrapping on smaller screens */
        align-items: center;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        padding: 25px;
        transition: all 0.3s ease;
    }

    .class-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    .class-info {
        flex-grow: 1;
        flex-basis: 70%;
        /* Take more space for info on larger screens */
    }

    .class-info h3 {
        margin: 0 0 10px 0;
        color: #343a40;
        font-size: 1.5rem;
    }

    .class-info p {
        margin: 4px 0;
        color: #6c757d;
        font-size: 0.95rem;
    }

    .class-info strong {
        color: #343a40;
    }

    .class-enroll {
        flex-basis: 30%;
        /* Take remaining space for button on larger screens */
        text-align: right;
        padding-left: 20px;
        /* Add some spacing */
    }

    @media (max-width: 768px) {
        .class-card {
            flex-direction: column;
            /* Stack elements vertically on small screens */
            align-items: flex-start;
        }

        .class-info,
        .class-enroll {
            flex-basis: 100%;
            /* Full width on small screens */
            padding-left: 0;
            text-align: left;
        }

        .class-enroll {
            margin-top: 15px;
            /* Add space between info and button */
        }
    }

    .class-enroll .btn-enroll {
        text-decoration: none;
        color: #fff;
        background-color: #e0703c;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .class-enroll .btn-enroll:hover {
        background-color: #c76131;
    }

    .message-box {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        text-align: center;
        font-weight: 500;
        animation: fadeIn 0.5s ease-out;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
</head>

<>

    <div class="page-container">
        <div class="page-header">
            <h1>Classes for <span><?= htmlspecialchars($level_name . ' - ' . $subject_name) ?></span></h1>
        </div>

        <?php
        // Display any error messages from the enrollment attempt
        if (!empty($messages['error'])) {
            echo '<div class="message-box error">' . htmlspecialchars($messages['error']) . '</div>';
        }
        // Display success message from session if redirected after successful enrollment
        if (isset($_SESSION['enroll_success'])) {
            echo '<div class="message-box success">' . htmlspecialchars($_SESSION['enroll_success']) . '</div>';
            unset($_SESSION['enroll_success']); // Clear the message after displaying
        }
        // Display login error message from session if redirected due to not logged in
        if (isset($_SESSION['login_error'])) {
            echo '<div class="message-box error">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
            unset($_SESSION['login_error']); // Clear the message after displaying
        }
        ?>

        <div class="classes-list">
            <?php
            if ($result_classes && $result_classes->num_rows > 0) {
                while ($class = $result_classes->fetch_assoc()) {
                    // Dynamically create a class name for display
                    $class_display_name = htmlspecialchars($subject_name . ' - ' . $class['class_type']);
                    $is_full = ($class['current_enrolled_students'] >= $class['max_students']);
            ?>
            <div class="class-card">
                <div class="class-info">
                    <h3><?= $class_display_name ?></h3>
                    <p><strong>Teacher:</strong>
                        <?= htmlspecialchars($class['teacher_first_name'] . ' ' . $class['teacher_last_name']) ?></p>
                    <p><strong>Classroom:</strong> <?= htmlspecialchars($class['class_room_name']) ?></p>
                    <p><strong>Fee:</strong> Rs. <?= htmlspecialchars(number_format($class['class_fee'], 2)) ?></p>
                    <p><strong>Schedule:</strong> <?= htmlspecialchars($class['day_of_week']) ?>,
                        <?= htmlspecialchars(date('h:i A', strtotime($class['start_time']))) ?> -
                        <?= htmlspecialchars(date('h:i A', strtotime($class['end_time']))) ?></p>
                    <p><strong>Students:</strong> <?= htmlspecialchars($class['current_enrolled_students']) ?> /
                        <?= htmlspecialchars($class['max_students']) ?></p>
                    <?php if ($is_full): ?>
                    <p class="text-danger"><strong>This class is full!</strong></p>
                    <?php endif; ?>
                </div>
                <div class="class-enroll">
                    <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="post">
                        <input type="hidden" name="class_id" value="<?= $class['class_id'] ?>">
                        <button type="submit" name="enroll" class="btn-enroll" <?= $is_full ? 'disabled' : '' ?>>
                            <?= $is_full ? 'Full' : 'Enroll Now' ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php
                }
            } else {
                echo '<div class="class-card"><p>No classes are currently open for <strong>' . htmlspecialchars($level_name) . ' - ' . htmlspecialchars($subject_name) . '</strong>. Please check back later.</p></div>';
            }
            ?>
        </div>
    </div>

    <?php
$content = ob_get_clean();
include 'layouts.php'; // Your main layout file for the website
?>