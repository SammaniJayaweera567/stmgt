<?php
ob_start();
include '../../init.php'; // Correct path

$db = dbConn();
$messages = [];

// Initialize variables with empty strings to prevent notices
$id = null;
$subject_name = '';
$subject_code = '';
$status = '';


// --- BLOCK 1: Handle Form Submission (POST Request for UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    // Extract submitted data. This will overwrite initial values if coming from GET.
    extract($_POST);

    // Clean and re-assign variables (using ?? '' for null safety)
    $id = dataClean($id ?? ''); // Ensure ID from hidden field is cleaned
    $subject_name = dataClean($subject_name ?? '');
    $subject_code = dataClean($subject_code ?? ''); // This is readonly, but good to have it cleaned
    $status = dataClean($status ?? '');

    // Server-side Validation
    if (empty($subject_name)) { $messages['subject_name'] = "Subject Name is required."; }
    if (empty($status)) { $messages['status'] = "Status is required."; }

    if (empty($messages)) {
        // Check for duplicate subject name (excluding current ID being edited)
        $escaped_subject_name = $db->real_escape_string($subject_name);
        $sql_check_name = "SELECT id FROM subjects WHERE subject_name = '$escaped_subject_name' AND id != '$id'";
        if ($db->query($sql_check_name)->num_rows > 0) {
            $messages['subject_name'] = "This Subject Name already exists for another subject.";
        }
    }

    // If no validation errors, proceed with update
    if (empty($messages)) {
        $updated_at = date('Y-m-d H:i:s'); // Get current timestamp for updated_at

        // The subject_code is NOT updated because it's auto-generated at creation and readonly.
        $sql = "UPDATE subjects SET subject_name='$escaped_subject_name', status='$status', updated_at='$updated_at' WHERE id='$id'";
        
        if ($db->query($sql)) {
            header("Location: manage_subjects.php?status=updated");
            exit();
        } else {
            // Database error
            $messages['main'] = "Database error: Could not update the subject. " . $db->error;
        }
    }
} 
// --- BLOCK 2: Load existing data when the page is initially accessed (GET or POST from manage_subjects.php) ---
else {
    // Check if ID is provided via GET or POST
    $id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0)); 

    if ($id === 0) {
        header("Location: manage_subjects.php?status=notfound&message=Subject ID not provided.");
        exit();
    }

    $sql_fetch = "SELECT * FROM subjects WHERE id='$id'";
    $result_fetch = $db->query($sql_fetch);

    if ($result_fetch && $result_fetch->num_rows > 0) {
        $row = $result_fetch->fetch_assoc();
        // Populate variables for the form
        $subject_name = $row['subject_name'];
        $subject_code = $row['subject_code'];
        $status = $row['status'];
    } else {
        // Subject not found, redirect
        header("Location: manage_subjects.php?status=notfound&message=Subject not found.");
        exit();
    }
}
?>

<div class="container-fluid">
    <?php show_status_message(); // Call the common function to show toast notifications ?>

    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Edit Subject: <?= htmlspecialchars(@$subject_name) ?></h3></div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars(@$id) ?>">
            <input type="hidden" name="action" value="update">
            
            <div class="card-body">
                <?php if(!empty($messages['main'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($messages['main']) ?></div>
                <?php endif; ?>

                <div class="form-group mb-3">
                    <label>Subject Name <span class="text-danger">*</span></label>
                    <input type="text" name="subject_name" class="form-control" 
                           value="<?= htmlspecialchars($subject_name) ?>">
                    <span class="text-danger"><?= @$messages['subject_name'] ?></span>
                </div>
                
                <div class="form-group mb-3">
                    <label>Subject Code (Auto-Generated)</label>
                    <input type="text" name="subject_code" class="form-control" 
                           value="<?= htmlspecialchars($subject_code) ?>" readonly>
                </div>
                
                <div class="form-group mb-3">
                    <label>Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control">
                        <option value="">-- Select Status --</option>
                        <option value="Active" <?= (isset($status) && $status == 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= (isset($status) && $status == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <span class="text-danger"><?= @$messages['status'] ?></span>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Subject</button>
                <a href="manage_subjects.php" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>