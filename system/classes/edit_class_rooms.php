<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'edit_class_room')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
// Initialize variables
$id = null;
$room_name = '';
$capacity = '';
$status = '';
$messages = [];

// Handle form submission for UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = dataClean($_POST['id']);
    $room_name = dataClean($_POST['room_name'] ?? '');
    $capacity = dataClean($_POST['capacity'] ?? '');
    $status = dataClean($_POST['status'] ?? '');

    // --- Validation ---
    if (empty($room_name)) {
        $messages['room_name'] = "Room Name is required.";
    } else {
        $db = dbConn();
        // Check for duplicates, excluding the current record being edited
        $sql_check = "SELECT id FROM class_rooms WHERE room_name = '$room_name' AND id != '$id'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['room_name'] = "This Room Name already exists.";
        }
    }

    if ($capacity === '') { // Check for empty string
        $messages['capacity'] = "Capacity is required.";
    } elseif (!is_numeric($capacity) || $capacity <= 0) {
        $messages['capacity'] = "Capacity must be a positive number.";
    }
    
    if (empty($status)) {
        $messages['status'] = "Status is required.";
    }

    if (empty($messages)) {
        $db = dbConn();
        $sql = "UPDATE class_rooms SET room_name='$room_name', capacity='$capacity', status='$status' WHERE id='$id'";
        
        $db->query($sql);
        header("Location: manage_class_rooms.php?status=updated");
        exit();
    }
}

// Fetch existing data when page loads via POST from manage_class_rooms.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $id = dataClean($_POST['id']);
    $db = dbConn();
    $sql = "SELECT * FROM class_rooms WHERE id='$id'";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $room_name = $row['room_name'];
        $capacity = $row['capacity'];
        $status = $row['status'];
    } else {
        // If no record is found, redirect
        header("Location: manage_class_rooms.php");
        exit();
    }
}
?>
<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit Classroom</h3></div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="id" value="<?= @$id ?>">
            <input type="hidden" name="action" value="update">
            <div class="card-body">
                <div class="form-group mb-3">
                    <label>Room Name</label>
                    <input type="text" name="room_name" class="form-control" value="<?= htmlspecialchars(@$room_name) ?>">
                    <span class="text-danger"><?= @$messages['room_name'] ?></span>
                </div>
                <div class="form-group mb-3">
                    <label>Capacity (Max Students)</label>
                    <input type="number" name="capacity" class="form-control" value="<?= htmlspecialchars(@$capacity) ?>">
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
                <button type="submit" class="btn btn-primary">Update Classroom</button>
                <a href="manage_class_rooms.php" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layouts.php';
?>
