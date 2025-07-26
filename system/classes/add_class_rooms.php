<?php
ob_start();
include '../../init.php';

// Initialize variables
$room_name = '';
$capacity = '';
$status = '';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_name = dataClean($_POST['room_name'] ?? '');
    $capacity = dataClean($_POST['capacity'] ?? '');
    $status = dataClean($_POST['status'] ?? '');

    // --- Validation ---
    if (empty($room_name)) {
        $messages['room_name'] = "Room Name is required.";
    } else {
        $db = dbConn();
        $sql_check = "SELECT id FROM class_rooms WHERE room_name = '$room_name'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['room_name'] = "This Room Name already exists.";
        }
    }

    if ($capacity === '') { // Check for empty string specifically
        $messages['capacity'] = "Capacity is required.";
    } elseif (!is_numeric($capacity) || $capacity <= 0) {
        $messages['capacity'] = "Capacity must be a positive number.";
    }
    
    if (empty($status)) {
        $messages['status'] = "Status is required.";
    }

    if (empty($messages)) {
        $db = dbConn();
        $sql = "INSERT INTO class_rooms (room_name, capacity, status) VALUES ('$room_name', '$capacity', '$status')";
        
        $db->query($sql);
        header("Location: manage_class_rooms.php?status=added");
        exit();
    }
}
?>
<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Create New Classroom</h3></div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="card-body">
                <div class="form-group mb-3">
                    <label>Room Name</label>
                    <input type="text" name="room_name" class="form-control" placeholder="E.g., Hall A" value="<?= htmlspecialchars(@$room_name) ?>">
                    <span class="text-danger"><?= @$messages['room_name'] ?></span>
                </div>
                <div class="form-group mb-3">
                    <label>Capacity (Max Students)</label>
                    <input type="number" name="capacity" class="form-control" placeholder="E.g., 50" value="<?= htmlspecialchars(@$capacity) ?>">
                    <span class="text-danger"><?= @$messages['capacity'] ?></span>
                </div>
                <div class="form-group mb-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">-- Select Status --</option>
                        <option value="Active" <?= (@$status == 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= (@$status == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <span class="text-danger"><?= @$messages['status'] ?></span>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Classroom</button>
                <a href="manage_class_rooms.php" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layouts.php';
?>
