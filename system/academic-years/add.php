<?php
ob_start();
include '../../init.php'; // Make sure this path is correct

// Initialize form variables to prevent errors on initial page load
$year_name = '';
$start_date = '';
$end_date = '';
$status = '';

$messages = []; // Array to hold validation messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean the input data using your dataClean() function
    $year_name = dataClean($_POST['year_name'] ?? '');
    $start_date = dataClean($_POST['start_date'] ?? '');
    $end_date = dataClean($_POST['end_date'] ?? '');
    $status = dataClean($_POST['status'] ?? '');

    // --- Validation ---
    if (empty($year_name)) {
        $messages['year_name'] = "Academic Year Name is required.";
    } else {
        // Check for duplicates only if year_name is not empty
        $db = dbConn();
        $sql_check = "SELECT id FROM academic_years WHERE year_name = '$year_name'";
        if ($db->query($sql_check)->num_rows > 0) {
            $messages['year_name'] = "This Academic Year already exists.";
        }
    }
    
    if (empty($start_date)) {
        $messages['start_date'] = "Start Date is required.";
    }
    
    if (empty($end_date)) {
        $messages['end_date'] = "End Date is required.";
    }

    if (empty($status)) {
        $messages['status'] = "Status is required.";
    }

    // NEW: Check if the end date is after the start date
    if (!empty($start_date) && !empty($end_date)) {
        if ($start_date >= $end_date) {
            $messages['end_date'] = "End Date must be after the Start Date.";
        }
    }

    // If there are no validation errors, proceed
    if (empty($messages)) {
        $db = dbConn();
        
        // Following your pattern, we manually set the created_at timestamp
        $created_at = date('Y-m-d H:i:s');
        
        // Build the SQL query string directly
        $sql = "INSERT INTO academic_years (year_name, start_date, end_date, status, created_at) 
                VALUES ('$year_name', '$start_date', '$end_date', '$status', '$created_at')";
        
        $db->query($sql);
        
        // Set a success message and redirect
        $_SESSION['status_message'] = "Academic Year added successfully!";
        header("Location: manage.php"); // Assuming your list page is named manage.php
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Create New Academic Year</h3>
                </div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label>Academic Year Name</label>
                                <input type="text" name="year_name" class="form-control" placeholder="E.g., 2025" value="<?= htmlspecialchars(@$year_name) ?>">
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
                        <button type="submit" class="btn btn-primary">Create Year</button>
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
