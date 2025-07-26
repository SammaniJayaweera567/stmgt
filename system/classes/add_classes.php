<?php
ob_start();
include '../../init.php'; // Include initialization file

$db = dbConn();
$messages = [];

// Define variables to hold submitted values to repopulate the form
$academic_year_id = '';
$class_level_id = '';
$subject_id = '';
$class_type_id = '';
$teacher_id = '';
$class_room_id = '';
$max_students = '';
$class_fee = '';
$day_of_week = '';
$start_time = '';
$end_time = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Extract and clean POST data
    $academic_year_id = dataClean($_POST['academic_year_id'] ?? null);
    $class_level_id = dataClean($_POST['class_level_id'] ?? null);
    $subject_id = dataClean($_POST['subject_id'] ?? null);
    $class_type_id = dataClean($_POST['class_type_id'] ?? null);
    $teacher_id = dataClean($_POST['teacher_id'] ?? null);
    $class_room_id = dataClean($_POST['class_room_id'] ?? null);
    $max_students = dataClean($_POST['max_students'] ?? null);
    $class_fee = dataClean($_POST['class_fee'] ?? null);
    $day_of_week = dataClean($_POST['day_of_week'] ?? null);
    $start_time = dataClean($_POST['start_time'] ?? null);
    $end_time = dataClean($_POST['end_time'] ?? null);

    // --- Server-side Validation ---
    if (empty($academic_year_id) || empty($class_level_id) || empty($subject_id) || empty($class_type_id) || empty($teacher_id) || empty($class_room_id) || empty($day_of_week) || empty($start_time) || empty($end_time)) {
        $messages['error'] = "All fields marked with * are mandatory.";
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $messages['error'] = "End Time must be after the Start Time.";
    } else {
        // --- Schedule Conflict Validation (FIXED) ---
        // The 'OR class_level_id' part has been removed to allow multiple classes of the same grade at the same time.
        // Now it only checks for Teacher and Classroom conflicts.
        $sql_conflict = "SELECT id FROM classes 
                         WHERE day_of_week = '$day_of_week' 
                         AND (start_time < '$end_time' AND end_time > '$start_time')
                         AND (teacher_id = '$teacher_id' OR class_room_id = '$class_room_id')";
        
        $result_conflict = $db->query($sql_conflict);
        if ($result_conflict->num_rows > 0) {
            // Updated the error message to be more accurate.
            $messages['error'] = '<strong>Schedule Conflict!</strong> The selected Teacher or Classroom is already booked for this day and time.';
        }
    }

    // --- Classroom Capacity Validation (only if no other errors) ---
    if (empty($messages) && !empty($max_students)) {
        $sql_room_capacity = "SELECT capacity FROM class_rooms WHERE id='$class_room_id'";
        $room_result = $db->query($sql_room_capacity);
        if($room_result->num_rows > 0){
            $room_capacity = $room_result->fetch_assoc()['capacity'];
            if ($max_students > $room_capacity) {
                $messages['error'] = "Max Students ({$max_students}) cannot exceed the selected room's capacity ({$room_capacity}).";
            }
        }
    }

    // --- If all validation passes, insert into DB ---
    if (empty($messages)) {
        $sql = "INSERT INTO classes (academic_year_id, class_level_id, subject_id, class_type_id, teacher_id, class_room_id, max_students, class_fee, day_of_week, start_time, end_time, status) 
                VALUES ('$academic_year_id', '$class_level_id', '$subject_id', '$class_type_id', '$teacher_id', '$class_room_id', '$max_students', '$class_fee', '$day_of_week', '$start_time', '$end_time', 'Active')";
        
        if ($db->query($sql)) {
            header("Location: manage_classes.php?status=added");
            exit();
        } else {
            $messages['error'] = "Database error: Could not create the class. Please try again. Error: " . $db->error;
        }
    }
}

// --- Fetch data for dropdowns ---
$academic_years_result = $db->query("SELECT * FROM academic_years WHERE status='Active'");
$class_levels_result = $db->query("SELECT * FROM class_levels WHERE status='Active'");
$class_types_result = $db->query("SELECT * FROM class_types WHERE status='Active'");
$teachers_result = $db->query("SELECT u.Id, u.FirstName, u.LastName FROM users u JOIN user_roles ur ON u.user_role_id=ur.Id WHERE ur.RoleName='Teacher' AND u.Status='Active'");
$days_of_week_options = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Create New Class</h3></div>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="card-body">
                <?php if(!empty($messages['error'])): ?>
                    <div class="alert alert-danger"><?= $messages['error'] ?></div>
                <?php endif; ?>

                <h4>Class Details</h4><hr>
                <div class="row">
                    <div class="form-group col-md-4">
                        <label>Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-control" required>
                            <?php mysqli_data_seek($academic_years_result, 0); while($row = $academic_years_result->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" <?= ($academic_year_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['year_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Class Level <span class="text-danger">*</span></label>
                        <select name="class_level_id" id="class_level_id" class="form-control" required>
                            <option value=''>-- Select Class Level --</option>
                            <?php mysqli_data_seek($class_levels_result, 0); while($row = $class_levels_result->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" <?= ($class_level_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['level_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-control" required>
                            <option value=''>-- Select Class Level First --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Class Type <span class="text-danger">*</span></label>
                        <select name="class_type_id" class="form-control" required>
                            <option value=''>-- Select Class Type --</option>
                            <?php mysqli_data_seek($class_types_result, 0); while($row = $class_types_result->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" <?= ($class_type_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['type_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-control" required>
                            <option value=''>-- Select Teacher --</option>
                            <?php mysqli_data_seek($teachers_result, 0); while($row = $teachers_result->fetch_assoc()): ?>
                                <option value="<?= $row['Id'] ?>" <?= ($teacher_id == $row['Id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['FirstName'].' '.$row['LastName']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <hr class="mt-4">
                <h4>Schedule &amp; Room</h4>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label>Day of the Week <span class="text-danger">*</span></label>
                        <select name="day_of_week" id="day_of_week" class="form-control schedule-trigger" required>
                            <option value="">-- Select Day --</option>
                            <?php foreach($days_of_week_options as $day): ?>
                                <option value="<?= $day ?>" <?= ($day_of_week == $day) ? 'selected' : '' ?>><?= $day ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Start Time <span class="text-danger">*</span></label>
                        <input type="time" id="start_time" name="start_time" class="form-control schedule-trigger" value="<?= htmlspecialchars($start_time) ?>" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>End Time <span class="text-danger">*</span></label>
                        <input type="time" id="end_time" name="end_time" class="form-control schedule-trigger" value="<?= htmlspecialchars($end_time) ?>" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Available Class Rooms <span class="text-danger">*</span></label>
                        <select name="class_room_id" id="class_room_id" class="form-control" required>
                            <option value=''>-- Select Day & Time First --</option>
                        </select>
                    </div>
                </div>

                <hr class="mt-4">
                <h4>Capacity &amp; Fee</h4>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Max Students</label>
                        <input type="number" name="max_students" class="form-control" placeholder="e.g., 50" value="<?= htmlspecialchars($max_students) ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Class Fee (Rs.)</label>
                        <input type="number" step="0.01" name="class_fee" class="form-control" placeholder="e.g., 2500.00" value="<?= htmlspecialchars($class_fee) ?>">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create Class</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    function updateAvailableRooms() {
        var dayOfWeek = $('#day_of_week').val();
        var startTime = $('#start_time').val();
        var endTime = $('#end_time').val();
        var selectedRoom = '<?= $class_room_id ?>';

        if (dayOfWeek && startTime && endTime && (new Date('1970-01-01T' + startTime) < new Date('1970-01-01T' + endTime))) {
            $('#class_room_id').html('<option value="">Loading...</option>');
            $.ajax({
                url: 'get_available_rooms.php',
                type: 'GET',
                data: { day_of_week: dayOfWeek, start_time: startTime, end_time: endTime },
                dataType: 'json',
                success: function(response) {
                    var roomSelect = $('#class_room_id');
                    roomSelect.empty();
                    if (response.length > 0) {
                        roomSelect.append('<option value="">-- Select an Available Room --</option>');
                        $.each(response, function(index, room) {
                            var displayText = room.room_name + ' (Capacity: ' + room.capacity + ')';
                            var option = $('<option>', { value: room.id, text: displayText });
                            if(room.id == selectedRoom) {
                                option.attr('selected', 'selected');
                            }
                            roomSelect.append(option);
                        });
                    } else {
                        roomSelect.append('<option value="">No rooms available for this slot</option>');
                    }
                },
                error: function() {
                    $('#class_room_id').html('<option value="">Error loading rooms</option>');
                }
            });
        }
    }

    function updateSubjectsByLevel() {
        var levelId = $('#class_level_id').val();
        var selectedSubject = '<?= $subject_id ?>';

        if (levelId) {
            $('#subject_id').html('<option value="">Loading...</option>');
            $.ajax({
                url: 'get_subjects_by_level.php',
                type: 'GET',
                data: { class_level_id: levelId },
                dataType: 'json',
                success: function(subjects) {
                    var subjectSelect = $('#subject_id');
                    subjectSelect.empty();
                    if (subjects.length > 0) {
                        subjectSelect.append('<option value="">-- Select Subject --</option>');
                        $.each(subjects, function(index, subject) {
                            var option = $('<option>', { value: subject.id, text: subject.subject_name });
                            if(subject.id == selectedSubject){
                                option.attr('selected', 'selected');
                            }
                            subjectSelect.append(option);
                        });
                    } else {
                        subjectSelect.append('<option value="">No subjects found for this level</option>');
                    }
                },
                error: function() {
                    $('#subject_id').html('<option value="">Error loading subjects</option>');
                }
            });
        } else {
            $('#subject_id').html('<option value="">-- Select Class Level First --</option>');
        }
    }

    $('.schedule-trigger').on('change', updateAvailableRooms);
    $('#class_level_id').on('change', updateSubjectsByLevel);

    if ($('#class_level_id').val()) {
        updateSubjectsByLevel();
    }
    if ($('#day_of_week').val() && $('#start_time').val() && $('#end_time').val()) {
        updateAvailableRooms();
    }
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
