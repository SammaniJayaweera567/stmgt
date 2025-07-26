<?php
ob_start();
// The path needs to be from system/classes/materials/
include '../../../init.php'; 

// Initialize variables
$messages = [];
$id = null;
$title = '';
$description = '';
$class_id = '';
$current_file = ''; // To hold the name of the existing file

// --- Get the ID from the URL ---
$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: manage_materials.php");
    exit();
}

$db = dbConn();

// --- Handle form submission for the UPDATE action ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean all submitted data
    $id = dataClean($_POST['id']);
    $title = dataClean($_POST['title']);
    $description = dataClean($_POST['description']);
    $class_id = dataClean($_POST['class_id']);

    // --- Validation ---
    if (empty($title)) { $messages['title'] = "Material Title is required."; }
    if (empty($class_id)) { $messages['class_id'] = "A class must be selected."; }

    // If there are no validation errors, proceed with the update
    if (empty($messages)) {
        $file_update_sql = "";

        // Check if a new file has been uploaded to replace the old one
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] == UPLOAD_ERR_OK) {
            // --- New file is being uploaded ---

            // 1. Get the old file path to delete it
            $sql_old_file = "SELECT file_path FROM class_materials WHERE id = '$id'";
            $result_old_file = $db->query($sql_old_file);
            if ($result_old_file && $result_old_file->num_rows > 0) {
                $old_file_data = $result_old_file->fetch_assoc();
                $old_file_path = "../../../web/uploads/materials/" . $old_file_data['file_path'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path); // Delete the old file
                }
            }

            // 2. Upload the new file
            $target_dir = "../../../web/uploads/materials/";
            $file_name = basename($_FILES['material_file']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $unique_file_name = $class_id . '_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $unique_file_name;

            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
                // Prepare SQL part to update file details
                $file_update_sql = ", file_name='$file_name', file_path='$unique_file_name'";
            } else {
                 $messages['main_error'] = "Sorry, there was an error uploading the new file.";
            }
        }

        // Proceed only if there was no file upload error
        if (!isset($messages['main_error'])) {
            $sql = "UPDATE class_materials SET 
                        title='$title', 
                        description='$description', 
                        class_id='$class_id'
                        $file_update_sql 
                    WHERE id='$id'";
            
            if ($db->query($sql)) {
                $_SESSION['status_message'] = "Material updated successfully!";
                header("Location: manage_materials.php");
                exit();
            } else {
                 $messages['main_error'] = "Database error. Could not update the material.";
            }
        }
    }
}

// --- Fetch existing data to show in the form ---
$sql_fetch = "SELECT * FROM class_materials WHERE id='$id'";
$result = $db->query($sql_fetch);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $title = $row['title'];
    $description = $row['description'];
    $class_id = $row['class_id'];
    $current_file = $row['file_name'];
} else {
    header("Location: manage_materials.php");
    exit();
}

// Fetch all active classes for the dropdown
$sql_classes = "SELECT c.id, cl.level_name, s.subject_name, ct.type_name 
                FROM classes c 
                JOIN class_levels cl ON c.class_level_id = cl.id 
                JOIN subjects s ON c.subject_id = s.id 
                JOIN class_types ct ON c.class_type_id = ct.id 
                WHERE c.status='Active' 
                ORDER BY cl.level_name, s.subject_name";
$result_classes = $db->query($sql_classes);

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Edit Class Material</h3></div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $id ?>" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="card-body">
                        <?php if(!empty($messages['main_error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($messages['main_error']) ?></div>
                        <?php endif; ?>

                        <div class="form-group mb-3">
                            <label>Class <span class="text-danger">*</span></label>
                            <select name="class_id" class="form-control">
                                <option value="">-- Select Class --</option>
                                <?php 
                                if ($result_classes && $result_classes->num_rows > 0) {
                                    while($row = $result_classes->fetch_assoc()) { 
                                        $class_full_name = htmlspecialchars($row['level_name'] . ' - ' . $row['subject_name'] . ' (' . $row['type_name'] . ')');
                                        $selected = ($class_id == $row['id']) ? 'selected' : '';
                                        echo "<option value='{$row['id']}' $selected>{$class_full_name}</option>";
                                    }
                                }
                                ?>
                            </select>
                            <span class="text-danger"><?= @$messages['class_id'] ?></span>
                        </div>

                        <div class="form-group mb-3">
                            <label>Material Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars(@$title) ?>">
                            <span class="text-danger"><?= @$messages['title'] ?></span>
                        </div>

                        <div class="form-group mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars(@$description) ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label>File</label>
                            <p class="text-muted">Current File: <strong><?= htmlspecialchars($current_file) ?></strong></p>
                            <input class="form-control" type="file" name="material_file">
                            <small class="form-text text-muted">Only choose a file if you want to replace the current one.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Material</button>
                        <a href="manage_materials.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
// The path needs to be from system/classes/materials/
include '../../layouts.php'; 
?>
