<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'add_subject')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
$subject_name = '';
$status = '';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_name = dataClean($_POST['subject_name'] ?? '');
    $status = dataClean($_POST['status'] ?? '');

    if (empty($subject_name)) { $messages['subject_name'] = "Subject Name is required."; }
    if (empty($status)) { $messages['status'] = "Status is required."; }

    if (empty($messages)) {
        $db = dbConn();
        if ($db->query("SELECT id FROM subjects WHERE subject_name = '$subject_name'")->num_rows > 0) {
            $messages['subject_name'] = "This Subject Name already exists.";
        }
    }

    if (empty($messages)) {
        $db = dbConn();
        
        // Step 1: Insert with a temporary code
        $sql_insert = "INSERT INTO subjects (subject_name, subject_code, status) VALUES ('$subject_name', 'TEMP', '$status')";
        $db->query($sql_insert);
        
        // Step 2: Get the new ID
        $new_subject_id = $db->insert_id;
        
        // --- FIX IS HERE (PHP PART) ---
        // Step 3: Generate the new, formatted subject code
        $name_prefix = strtoupper(substr($subject_name, 0, 3));
        // Pad the ID with leading zeros to make it 3 digits long (e.g., 1 -> 001)
        $padded_id = str_pad($new_subject_id, 3, '0', STR_PAD_LEFT); 
        $subject_code = $name_prefix . $padded_id; // Example: SIN001, MAT012
        // --- END OF PHP FIX ---
        
        // Step 4: Update the record
        $sql_update = "UPDATE subjects SET subject_code = '$subject_code' WHERE id = '$new_subject_id'";
        $db->query($sql_update);

        header("Location: manage_subjects.php?status=added");
        exit();
    }
}
?>
<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Create New Subject</h3></div>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="card-body">
                <div class="form-group mb-3">
                    <label>Subject Name</label>
                    <input type="text" id="subject_name" name="subject_name" class="form-control" 
                           value="<?= htmlspecialchars(@$subject_name) ?>" onkeyup="generateCode();">
                    <span class="text-danger"><?= @$messages['subject_name'] ?></span>
                </div>
                
                <div class="form-group mb-3">
                    <label>Subject Code (Auto-Generated)</label>
                    <input type="text" id="subject_code" name="subject_code" class="form-control" readonly>
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
                <button type="submit" class="btn btn-primary">Save Subject</button>
                <a href="manage_subjects.php" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function generateCode() {
    var nameInput = document.getElementById('subject_name');
    var subjectName = nameInput.value;
    var prefix = subjectName.substring(0, 3).toUpperCase();
    var codeInput = document.getElementById('subject_code');

    if (subjectName.length >= 3) {
        $.ajax({
            url: 'get_next_subject_id.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                var nextId = response.next_id;
                
                // --- FIX IS HERE (JAVASCRIPT PART) ---
                // Pad the number to 3 digits with leading zeros (e.g., 1 -> "001")
                var paddedId = String(nextId).padStart(3, '0');
                // Combine prefix and padded ID without a hyphen
                codeInput.value = prefix + paddedId; // Example: SIN001
                // --- END OF JAVASCRIPT FIX ---
            },
            error: function() {
                codeInput.value = 'Error';
            }
        });
    } else {
        codeInput.value = '';
    }
}
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>