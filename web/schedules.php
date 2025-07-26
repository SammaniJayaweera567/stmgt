<?php
ob_start();
include '../init.php'; // init.php ගොනුවට නිවැරදි path එක
?>

<style>
    .schedule-header {
        margin-top: 70px;
        padding: 4rem 0;
        background-color: #f8f9fa;
        background-image: linear-gradient(rgba(44, 83, 100, 0.7), rgba(15, 32, 39, 0.8)), url('images/tusion-bg2.jpg');
        background-size: cover;
        background-position: center;
        color: #fff;
    }
    .schedule-header h1 {
        font-weight: 700;
    }
    .schedule-header .text-primary {
        color: #1cc1ba !important;
    }
    .day-title {
        font-weight: 700;
        color: #0f2027;
        margin-top: 2.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 3px solid #1cc1ba;
        padding-bottom: 0.5rem;
        display: inline-block;
    }
    .schedule-card {
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 1.5rem;
        height: 100%;
        transition: all 0.3s ease;
    }
    .schedule-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .schedule-card .class-time {
        font-weight: 600;
        color: #1cc1ba; /* Primary theme color */
        font-size: 1.2rem;
    }
    .schedule-card .class-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .schedule-card .class-details-list {
        list-style: none;
        padding: 0;
        margin: 0;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .schedule-card .class-details-list li {
        margin-bottom: 0.25rem;
    }
</style>

<div class="container-fluid schedule-header text-center text-white">
    <div class="container">
        <h1 class="display-5 animated fadeIn mb-4">Class <span class="text-primary">Schedules</span></h1>
        <p class="animated fadeIn mb-0">Find the weekly schedule for all classes below.</p>
    </div>
</div>
<div class="container-xxl py-5">
    <div class="container">
        <?php
        $db = dbConn();
        // Fetch all active classes and order them by day of the week
        $sql = "SELECT 
                    c.day_of_week, 
                    c.start_time, 
                    c.end_time,
                    s.subject_name,
                    cl.level_name,
                    cr.room_name,
                    u.FirstName AS teacher_fname,
                    u.LastName AS teacher_lname
                FROM classes c
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_levels cl ON c.class_level_id = cl.id
                LEFT JOIN class_rooms cr ON c.class_room_id = cr.id
                JOIN users u ON c.teacher_id = u.Id
                WHERE c.status = 'Active'
                ORDER BY 
                    FIELD(c.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), 
                    c.start_time";
        
        $result = $db->query($sql);
        $schedule_by_day = [];

        if ($result && $result->num_rows > 0) {
            // Group the results by the day of the week
            while ($row = $result->fetch_assoc()) {
                $schedule_by_day[$row['day_of_week']][] = $row;
            }
        }
        
        // Define the order of days to display
        $days_order = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($days_order as $day):
            if (!empty($schedule_by_day[$day])):
        ?>
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h2 class="day-title"><?= htmlspecialchars($day) ?></h2>
            </div>
            <div class="row g-4">
                <?php foreach ($schedule_by_day[$day] as $class): ?>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="schedule-card">
                        <div class="class-time"><?= date('h:i A', strtotime($class['start_time'])) ?> - <?= date('h:i A', strtotime($class['end_time'])) ?></div>
                        <h5 class="class-title"><?= htmlspecialchars($class['level_name']) . ' - ' . htmlspecialchars($class['subject_name']) ?></h5>
                        <ul class="class-details-list">
                            <li><i class="fas fa-user-tie me-2"></i><strong>Teacher:</strong> <?= htmlspecialchars($class['teacher_fname'] . ' ' . $class['teacher_lname']) ?></li>
                            <li><i class="fas fa-chalkboard me-2"></i><strong>Room:</strong> <?= htmlspecialchars($class['room_name'] ?? 'N/A') ?></li>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php 
            endif;
        endforeach;

        if (empty($schedule_by_day)) {
            echo '<div class="col-12 text-center"><p class="text-muted">No active class schedules are available at the moment.</p></div>';
        }
        ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layouts.php'; // Your main layout file for the website
?>