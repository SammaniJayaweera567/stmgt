<?php
ob_start();
include '../../init.php'; // Make sure this path is correct

// Initialize variables to hold form data and messages
$messages = [];
$id = null;
$year_name = '';
$start_date = '';
$end_date = '';
$status = '';

// --- Handle form submission for the UPDATE action ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    // Clean all submitted data
    $id = dataClean($_POST['id']);
    $year_name = dataClean($_POST['year_name']);
    $start_date = dataClean($_POST['start_date']);
    $end_date = dataClean($_POST['end_date']);
    $status = dataClean($_POST['status']);

    // --- Validation ---
    if (empty($year_name)) { $messages['year_name'] = "Academic Year Name is required."; }
    if (empty($start_date)) { $messages['start_date'] = "Start Date is required."; }
    if (empty($end_date)) { $messages['end_date'] = "End Date is required."; }
    if (empty($status)) { $messages['status'] = "Status is required."; }

    // NEW: Check if the end date is after the start date
    if (!empty($start_date) && !empty($end_date)) {
        if ($start_date >= $end_date) {
            $messages['end_date'] = "End Date must be after the Start Date.";
        }
    }

    // --- Check for Duplicates (ignoring the current record) ---
    $db = dbConn();
    if (!empty($year_name)) {
        $sql_check = "SELECT id FROM academic_years WHERE year_name = '$year_name' AND id != '$id'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['year_name'] = "This Academic Year already exists.";
        }
    }

    // If there are no validation errors, proceed with the update
    if (empty($messages)) {
        // Manually set the updated_at timestamp, following your pattern
        $updated_at = date('Y-m-d H:i:s');
        $sql = "UPDATE academic_years SET 
                    year_name='$year_name', 
                    start_date='$start_date', 
                    end_date='$end_date', 
                    status='$status', 
                    updated_at='$updated_at' 
                WHERE id='$id'";
        
        $db->query($sql);

        $_SESSION['status_message'] = "Academic Year updated successfully!";
        header("Location: manage.php"); // Redirect to the list page
        exit();
    }
}

// --- Fetch existing data when the page is loaded via POST from manage.php ---
// This runs when the 'Edit' button on the list page is clicked
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $id = dataClean($_POST['id']);
    $db = dbConn();
    $sql = "SELECT * FROM academic_years WHERE id='$id'";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Populate variables with data from the database
        $year_name = $row['year_name'];
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];
        $status = $row['status'];
    } else {
        // If no record found, redirect away
        header("Location: manage.php");
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Edit Academic Year</h3></div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <input type="hidden" name="id" value="<?= @$id ?>">
                    <input type="hidden" name="action" value="update">

                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label>Academic Year Name</label>
                                <input type="text" name="year_name" class="form-control" value="<?= htmlspecialchars(@$year_name) ?>">
                                <span class="text-danger"><?= @$messages['year_name'] ?></span>
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">-- Select Status --</option>
                                    <option value="active" <?= (@$status == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= (@$status == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                                <span class="text-danger"><?= @$messages['status'] ?></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars(@$start_date) ?>">
                                <span class="text-danger"><?= @$messages['start_date'] ?></span>
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label>End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars(@$end_date) ?>">
                                <span class="text-danger"><?= @$messages['end_date'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Year</button>
                        <a href="manage.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Make sure this path is correct
?>
