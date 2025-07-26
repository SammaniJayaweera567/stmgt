<?php
ob_start();
include '../../init.php'; // Assuming init.php contains dbConn() and dataClean()


$db = dbConn(); // Database connection
$messages = [];
$class_id = null;

// --- STEP 1: Get the Class ID to Edit ---
if (isset($_GET['id'])) {
    $class_id = dataClean($_GET['id']);
} elseif (isset($_POST['id'])) {
    $class_id = dataClean($_POST['id']);
}

if (!$class_id) {
    header("Location: manage_classes.php?status=noid");
    exit();
}

// --- STEP 2: Handle Form Submission (Update Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Explicitly assign and clean POST data
    $academic_year_id = dataClean($_POST['academic_year_id'] ?? '');
    $class_level_id = dataClean($_POST['class_level_id'] ?? '');
    $subject_id = dataClean($_POST['subject_id'] ?? '');
    $class_type_id = dataClean($_POST['class_type_id'] ?? '');
    $teacher_id = dataClean($_POST['teacher_id'] ?? '');
    $class_room_id = dataClean($_POST['class_room_id'] ?? '');
    $max_students = dataClean($_POST['max_students'] ?? '');
    $class_fee = dataClean($_POST['class_fee'] ?? '');
    $day_of_week = dataClean($_POST['day_of_week'] ?? '');
    $start_time = dataClean($_POST['start_time'] ?? '');
    $end_time = dataClean($_POST['end_time'] ?? '');
    $status = dataClean($_POST['status'] ?? '');

    // --- FINAL: Bulletproof Validation ---
    if (empty($academic_year_id)) { $messages['error'] = "Academic Year is required."; }
    elseif (empty($class_level_id)) { $messages['error'] = "Class Level is required."; }
    elseif (empty($subject_id) || !is_numeric($subject_id)) { $messages['error'] = "A valid Subject must be selected. Please select the Class Level again to refresh the list."; }
    elseif (empty($class_type_id)) { $messages['error'] = "Class Type is required."; }
    elseif (empty($teacher_id)) { $messages['error'] = "Teacher is required."; }
    elseif (empty($class_room_id)) { $messages['error'] = "Class Room is required."; }
    elseif (empty($day_of_week)) { $messages['error'] = "Day of the Week is required."; }
    elseif (empty($start_time)) { $messages['error'] = "Start Time is required."; }
    elseif (empty($end_time)) { $messages['error'] = "End Time is required."; }
    elseif (empty($status)) { $messages['error'] = "Status is required."; }
    elseif (strtotime($start_time) >= strtotime($end_time)) { $messages['error'] = "End Time must be after the Start Time."; }
    else {
        // --- Complex Validations (if basic validation passes) (FIXED) ---
        $sql_conflict = "SELECT id FROM classes 
                         WHERE day_of_week = '$day_of_week' 
                         AND (start_time < '$end_time' AND end_time > '$start_time')
                         AND (teacher_id = '$teacher_id' OR class_room_id = '$class_room_id')
                         AND id != '$class_id'";
        
        $result_conflict = $db->query($sql_conflict);
        if ($result_conflict->num_rows > 0) {
            $messages['error'] = "<b>Schedule Conflict!</b> The selected Teacher or Classroom is already booked for this time slot.";
        }
        
        if (empty($messages['error']) && !empty($max_students)) {
            $sql_room_capacity = "SELECT capacity FROM class_rooms WHERE id = '$class_room_id'";
            $result_room_cap = $db->query($sql_room_capacity);
            if ($result_room_cap && $result_room_cap->num_rows > 0) {
                $room_capacity = $result_room_cap->fetch_assoc()['capacity'];
                if ($max_students > $room_capacity) {
                    $messages['error'] = "Max Students ({$max_students}) cannot exceed the selected room's capacity ({$room_capacity}).";
                }
            }
        }
    }

    // --- If all validation passes, update the database ---
    if (empty($messages)) {
        $sql_update = "UPDATE classes SET 
                        academic_year_id='$academic_year_id', class_level_id='$class_level_id', subject_id='$subject_id', 
                        class_type_id='$class_type_id', teacher_id='$teacher_id', class_room_id='$class_room_id', 
                        max_students='$max_students', class_fee='$class_fee', day_of_week='$day_of_week', 
                        start_time='$start_time', end_time='$end_time', status='$status' 
                      WHERE id='$class_id'";
        
        if ($db->query($sql_update)) {
            header("Location: manage_classes.php?status=updated");
            exit();
        } else {
            $messages['error'] = "Database error: Could not update the class. " . $db->error;
        }
    }
} else {
    // --- STEP 3: Load Existing Data on Initial Page Visit (GET Request) ---
    $sql_load = "SELECT * FROM classes WHERE id='$class_id'";
    $result_load = $db->query($sql_load);
    
    if ($result_load && $result_load->num_rows > 0) {
        // Explicitly assign variables from the fetched row
        $row = $result_load->fetch_assoc();
        $academic_year_id = $row['academic_year_id'];
        $class_level_id = $row['class_level_id'];
        $subject_id = $row['subject_id'];
        $class_type_id = $row['class_type_id'];
        $teacher_id = $row['teacher_id'];
        $class_room_id = $row['class_room_id'];
        $max_students = $row['max_students'];
        $class_fee = $row['class_fee'];
        $day_of_week = $row['day_of_week'];
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];
        $status = $row['status'];
    } else {
        header("Location: manage_classes.php?status=notfound");
        exit();
    }
}

// --- STEP 4: Fetch Data for Dropdowns ---
$academic_years_result = $db->query("SELECT id, year_name FROM academic_years WHERE status = 'Active' ORDER BY year_name DESC");
$class_levels_result = $db->query("SELECT id, level_name FROM class_levels WHERE status = 'Active' ORDER BY level_name ASC");
$class_types_result = $db->query("SELECT id, type_name FROM class_types WHERE status = 'Active' ORDER BY type_name ASC");
$teachers_result = $db->query("SELECT u.id, u.FirstName, u.LastName FROM users u JOIN user_roles ur ON u.user_role_id = ur.Id WHERE ur.RoleName = 'Teacher' AND u.Status = 'Active' ORDER BY u.FirstName ASC");
$days_of_week_options = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit Class</h3></div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="id" id="class_id" value="<?= htmlspecialchars($class_id) ?>">
            <div class="card-body">
                <?php if (!empty($messages['error'])): ?>
                    <div class="alert alert-danger" role="alert"><?= $messages['error'] ?></div>
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
                                <option value="<?= $row['id'] ?>" <?= ($teacher_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['FirstName'].' '.$row['LastName']) ?></option>
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
                <h4>Capacity, Fee &amp; Status</h4>
                <div class="row">
                    <div class="form-group col-md-4">
                        <label>Max Students</label>
                        <input type="number" name="max_students" class="form-control" placeholder="e.g., 50" value="<?= htmlspecialchars($max_students) ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Class Fee (Rs.)</label>
                        <input type="number" step="0.01" name="class_fee" class="form-control" placeholder="e.g., 2500.00" value="<?= htmlspecialchars($class_fee) ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="Active" <?= ($status == 'Active') ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= ($status == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                            <option value="Completed" <?= ($status == 'Completed') ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Class</button>
                <a href="manage_classes.php" class="btn btn-secondary">Cancel</a>
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
        var excludeClassId = $('#class_id').val();
        var selectedRoomId = '<?= $class_room_id ?>';

        if (dayOfWeek && startTime && endTime && (new Date('1970-01-01T' + startTime) < new Date('1970-01-01T' + endTime))) {
            $('#class_room_id').html('<option value="">Loading...</option>');
            $.ajax({
                url: 'get_available_rooms.php',
                type: 'GET',
                data: { day_of_week: dayOfWeek, start_time: startTime, end_time: endTime, exclude_class_id: excludeClassId },
                dataType: 'json',
                success: function(response) {
                    var roomSelect = $('#class_room_id');
                    roomSelect.empty();
                    
                    var currentRoomAppended = false;
                    if (response.current_room) {
                         var currentRoom = response.current_room;
                         var displayText = currentRoom.room_name + ' (Capacity: ' + currentRoom.capacity + ')';
                         roomSelect.append($('<option>', { value: currentRoom.id, text: displayText }));
                         currentRoomAppended = true;
                    }

                    if (response.available_rooms.length > 0) {
                        if (!currentRoomAppended) {
                           roomSelect.append('<option value="">-- Select an Available Room --</option>');
                        }
                        $.each(response.available_rooms, function(index, room) {
                            if (!response.current_room || response.current_room.id != room.id) {
                                var displayText = room.room_name + ' (Capacity: ' + room.capacity + ')';
                                roomSelect.append($('<option>', { value: room.id, text: displayText }));
                            }
                        });
                    } else if (!currentRoomAppended) {
                        roomSelect.append('<option value="">No other rooms available</option>');
                    }
                    roomSelect.val(selectedRoomId);
                },
                error: function() {
                    $('#class_room_id').html('<option value="">Error loading rooms</option>');
                }
            });
        }
    }

    function updateSubjectsByLevel() {
        var levelId = $('#class_level_id').val();
        var selectedSubjectId = '<?= $subject_id ?>';

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
                            subjectSelect.append($('<option>', { value: subject.id, text: subject.subject_name }));
                        });
                        subjectSelect.val(selectedSubjectId);
                    } else {
                        subjectSelect.append('<option value="">No subjects found</option>');
                    }
                },
                error: function() {
                    var subjectSelect = $('#subject_id');
                    subjectSelect.empty();
                    subjectSelect.append('<option value="">Error loading subjects</option>');
                }
            });
        } else {
            $('#subject_id').html('<option value="">-- Select Class Level First --</option>');
        }
    }

    $('.schedule-trigger').on('change', updateAvailableRooms);
    $('#class_level_id').on('change', updateSubjectsByLevel);

    updateSubjectsByLevel();
    updateAvailableRooms();
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
