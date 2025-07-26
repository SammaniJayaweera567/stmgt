<?php
ob_start();
// [FIX 1] All paths corrected to '../'
include '../../init.php';

$db = dbConn(); // Get database connection
$selected_level_id = isset($_GET['level_id']) ? (int)dataClean($_GET['level_id']) : 0; // Get selected grade ID from GET parameter
$view_mode = 'weekly'; // Default view mode is now always 'weekly' as monthly is removed

// Fetch all class levels for the filter dropdown
$class_levels = [];
$sql_class_levels = "SELECT id, level_name FROM class_levels WHERE status = 'Active' ORDER BY level_name ASC";
$result_class_levels = $db->query($sql_class_levels);


if ($result_class_levels === false) {
    error_log("Error fetching class levels: " . $db->error);
} else {
    while ($row = $result_class_levels->fetch_assoc()) {
        $class_levels[] = $row;
    }
}

// Initialize array to store class data
$classes_data = [];
$level_name_display = 'All Class Levels'; // Default display name for the selected level

// Main SQL query to get all available classes with details
$sql_classes = "SELECT
                    c.id AS class_id,
                    c.day_of_week,
                    c.start_time,
                    c.end_time,
                    s.subject_name,
                    ct.type_name,
                    CONCAT(u.FirstName, ' ', u.LastName) AS teacher_name,
                    cr.room_name,
                    cl.level_name
                FROM classes AS c
                LEFT JOIN subjects AS s ON c.subject_id = s.id
                LEFT JOIN class_types AS ct ON c.class_type_id = ct.id
                LEFT JOIN users AS u ON c.teacher_id = u.Id
                LEFT JOIN class_rooms AS cr ON c.class_room_id = cr.id
                LEFT JOIN class_levels AS cl ON c.class_level_id = cl.id
                WHERE c.status = 'Active'"; // Only fetch active classes

if ($selected_level_id > 0) {
    $sql_classes .= " AND c.class_level_id = " . $selected_level_id; // Filter by selected grade ID
    // Get the selected level name for display
    foreach ($class_levels as $level) {
        if ($level['id'] == $selected_level_id) {
            $level_name_display = htmlspecialchars($level['level_name']);
            break;
        }
    }
}

// Order classes by day of the week (Monday to Sunday) and then by start time
$sql_classes .= " ORDER BY FIELD(c.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), c.start_time ASC";

$result_classes = $db->query($sql_classes);

if ($result_classes === false) {
    error_log("Error fetching classes data: " . $db->error);
} elseif ($result_classes->num_rows > 0) {
    while ($row = $result_classes->fetch_assoc()) {
        $classes_data[] = $row; // Add fetched class data to the array
    }
}

// Group classes by day for weekly view
$weekly_schedule = [
    'Monday' => [], 'Tuesday' => [], 'Wednesday' => [], 'Thursday' => [],
    'Friday' => [], 'Saturday' => [], 'Sunday' => []
];

foreach ($classes_data as $class) {
    $weekly_schedule[$class['day_of_week']][] = $class;
}

// English day names for display
$english_days = [
    'Monday' => 'Monday',
    'Tuesday' => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday' => 'Thursday',
    'Friday' => 'Friday',
    'Saturday' => 'Saturday',
    'Sunday' => 'Sunday'
];


?>

<!-- CSS Styles -->
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }
    .container-fluid {
        padding: 20px;
    }
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
    }
    .card-header {
        background-color: #1cc1ba;
        color: white;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
        font-weight: bold;
        font-size: 1.25rem;
        padding: 1rem 1.25rem;
    }
    .schedule-header {
        background-color: #e9ecef;
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
        font-weight: bold;
        font-size: 1.1rem;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .class-item {
        border-bottom: 1px dashed #e0e0e0;
        padding: 10px 0;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }
    .class-item:last-child {
        border-bottom: none;
    }
    .class-item strong {
        color: #343a40;
        flex-basis: 100%;
        margin-bottom: 5px;
    }
    .class-item span {
        color: #6c757d;
        font-size: 0.9rem;
        flex-basis: 50%;
        padding-right: 10px;
    }
    @media (max-width: 768px) {
        .class-item span {
            flex-basis: 100%;
            padding-right: 0;
        }
    }

    .filter-section {
        background-color: #fff;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    .view-mode-tabs .btn {
        border-radius: 50px;
        padding: 8px 20px;
        margin: 0 5px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .view-mode-tabs .btn-primary {
        background-color: #1cc1ba;
        border-color: #1cc1ba;
    }
    .view-mode-tabs .btn-outline-primary {
        color: #1cc1ba;
        border-color: #1cc1ba;
    }
    .view-mode-tabs .btn-outline-primary:hover {
        background-color: #1cc1ba;
        color: white;
        box-shadow: 0 2px 8px rgba(28, 193, 186, 0.3);
    }
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03);
    }
    .table-bordered th, .table-bordered td {
        border: 1px solid #dee2e6;
    }
    .table-striped tbody tr:hover {
        background-color: rgba(28, 193, 186, 0.1);
        transition: background-color 0.2s ease;
    }
    /* Specific styles for the new monthly view table (REMOVED as requested) */
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-calendar-alt mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Class Schedule</h5>
        </div>
    </div>

    <div class="filter-section">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="row align-items-end">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="level_id" class="form-label">Select Class Level (Grade):</label>
                    <select name="level_id" id="level_id" class="form-control form-select">
                        <option value="0">-- All Class Levels --</option>
                        <?php foreach ($class_levels as $level): ?>
                            <option value="<?= $level['id'] ?>" <?= ($selected_level_id == $level['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($level['level_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <!-- Removed hidden input for 'view' as only weekly view remains -->
                    <button type="submit" class="btn btn-primary">Show Schedule</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            Class Schedule for <?= $level_name_display ?>
        </div>
        <div class="card-body">
            <?php if (empty($classes_data) && $selected_level_id != 0): ?>
                <div class="alert alert-info text-center" role="alert">
                    No class schedule found for the selected class level.
                </div>
            <?php elseif (empty($classes_data) && $selected_level_id == 0): ?>
                <div class="alert alert-info text-center" role="alert">
                    No class schedule found for any class level.
                </div>
            <?php else: ?>
                <!-- Always display weekly view since monthly is removed -->
                <div class="row">
                    <?php foreach ($english_days as $day_en => $day_display): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light text-dark">
                                    <?= $day_display ?>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($weekly_schedule[$day_en])): ?>
                                        <p class="text-muted">No classes on this day.</p>
                                    <?php else: ?>
                                        <?php foreach ($weekly_schedule[$day_en] as $class): ?>
                                            <div class="class-item">
                                                <strong><?= htmlspecialchars($class['subject_name']) ?> (<?= htmlspecialchars($class['type_name']) ?>)</strong><br>
                                                <span>Teacher: <?= htmlspecialchars($class['teacher_name']) ?></span><br>
                                                <span>Time: <?= htmlspecialchars(date('h:i A', strtotime($class['start_time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($class['end_time']))) ?></span><br>
                                                <span>Room: <?= htmlspecialchars($class['room_name']) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Assuming layouts.php includes the HTML structure and echoes $content
?>
