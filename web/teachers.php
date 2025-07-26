<?php
ob_start();
include '../init.php'; // init.php ගොනුවට නිවැරදි path එක
?>

<style>
    /* New simplified header banner */
    .simple-header {
        margin-top: 70px;
        padding: 4rem 0;
        background-color: #f8f9fa;
        background-image: linear-gradient(rgba(44, 83, 100, 0.7), rgba(15, 32, 39, 0.8)), url('images/tusion-bg1.jpg');
        background-size: cover;
        background-position: center;
        color: #fff;
    }
    .simple-header h1 {
        font-weight: 700;
    }
    .simple-header .text-primary {
        color: #1cc1ba !important;
    }

    /* Teacher card styling */
    .teacher-card {
        border-radius: 15px;
        transition: all 0.3s ease;
        border: none;
        background: #ffffff;
        overflow: hidden; /* Important for the top border */
        border-top: 5px solid transparent;
    }
    .teacher-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
        border-top-color: #1cc1ba; /* Primary color on hover */
    }
    .teacher-card .profile-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border: 4px solid #e9ecef;
    }
    .teacher-card .card-body {
        padding: 2rem;
    }
</style>

<div class="container-fluid simple-header text-center text-white">
    <div class="container">
        <h1 class="display-5 animated fadeIn mb-4">Our Expert <span class="text-primary">Teaching Panel</span></h1>
        <p class="animated fadeIn mb-0">Meet our dedicated and experienced teachers.</p>
    </div>
</div>
<div class="container-xxl py-5">
    <div class="container">
        <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
            <h2 class="mb-3">Meet Our Teachers</h2>
        </div>
        <div class="row g-4 justify-content-center">
            <?php
            $db = dbConn();
            $sql_teachers = "SELECT u.Id, u.FirstName, u.LastName, u.ProfileImage, td.qualifications 
                             FROM users u 
                             LEFT JOIN teacher_details td ON u.Id = td.user_id 
                             WHERE u.user_role_id = 6 AND u.Status = 'Active'";
            $result_teachers = $db->query($sql_teachers);
            if ($result_teachers && $result_teachers->num_rows > 0) {
                while ($teacher = $result_teachers->fetch_assoc()) {
                    $teacher_id = $teacher['Id'];
            ?>
            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                <div class="teacher-card text-center h-100">
                    <div class="py-4">
                        <img class="rounded-circle profile-image" src="<?= SYS_URL ?>uploads/<?= htmlspecialchars($teacher['ProfileImage'] ?? 'default_avatar.png') ?>" alt="Teacher Profile Picture">
                        <h5 class="fw-bold mt-3 mb-1"><?= htmlspecialchars($teacher['FirstName'] . ' ' . $teacher['LastName']) ?></h5>
                        <p class="text-muted mb-3"><small><?= htmlspecialchars($teacher['qualifications'] ?? 'Qualified Teacher') ?></small></p>
                    </div>
                    <div class="card-body pt-0">
                        <h6 class="text-secondary text-start border-bottom pb-2 mb-2"><i class="fas fa-chalkboard-teacher me-2"></i>Classes</h6>
                        <ul class="list-group list-group-flush text-start small">
                            <?php
                            $sql_classes = "SELECT s.subject_name, cl.level_name
                                            FROM classes c
                                            JOIN subjects s ON c.subject_id = s.id
                                            JOIN class_levels cl ON c.class_level_id = cl.id
                                            WHERE c.teacher_id = '$teacher_id' AND c.status = 'Active'";
                            $result_classes = $db->query($sql_classes);
                            if ($result_classes && $result_classes->num_rows > 0) {
                                while ($class = $result_classes->fetch_assoc()) {
                                    echo "<li class='list-group-item px-0'><i class='fa fa-check text-success me-2'></i>" . htmlspecialchars($class['level_name'] . ' - ' . $class['subject_name']) . "</li>";
                                }
                            } else {
                                echo '<li class="list-group-item px-0 text-muted">No active classes.</li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
                echo '<div class="col-12"><p class="text-center text-muted">No teachers found.</p></div>';
            }
            ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layouts.php';
?>