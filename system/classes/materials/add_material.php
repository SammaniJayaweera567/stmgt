<?php
ob_start();
// The path needs to be from system/classes/materials/
include '../../../init.php'; 

// Initialize form variables
$title = '';
$description = '';
$class_id = '';

$messages = []; // Array to hold validation messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean the input data
    $title = dataClean($_POST['title'] ?? '');
    $description = dataClean($_POST['description'] ?? '');
    $class_id = dataClean($_POST['class_id'] ?? 0);
    $uploader_id = $_SESSION['user_id'] ?? null;

    // --- Validation ---
    if (empty($title)) {
        $messages['title'] = "Material Title is required.";
    }
    if (empty($class_id)) {
        $messages['class_id'] = "A class must be selected.";
    }
    if (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] != UPLOAD_ERR_OK) {
        $messages['material_file'] = "A file must be uploaded.";
    }

    // If there are no validation errors, proceed with file upload and DB insert
    if (empty($messages)) {
        $db = dbConn();

        $target_dir = "../../../web/uploads/materials/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = basename($_FILES['material_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_file_name = $class_id . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $unique_file_name;

        // You can add more validation for file types and size here if needed
        
        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
            // File uploaded successfully, now insert into DB
            $sql = "INSERT INTO class_materials (class_id, title, description, file_name, file_path, uploaded_by_user_id)
                    VALUES ('$class_id', '$title', '$description', '$file_name', '$unique_file_name', '$uploader_id')";
            
            if ($db->query($sql)) {
                $_SESSION['status_message'] = "Material added successfully!";
                header("Location: manage_materials.php");
                exit();
            } else {
                // If DB insert fails, delete the uploaded file
                unlink($target_file);
                $messages['main_error'] = "Database error. Could not save the material.";
            }
        } else {
            $messages['main_error'] = "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch all active classes for the dropdown
$db = dbConn();
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
                <div class="card-header">
                    <h3 class="card-title">Add New Class Material</h3>
                </div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
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
                                        $selected = (@$class_id == $row['id']) ? 'selected' : '';
                                        echo "<option value='{$row['id']}' $selected>{$class_full_name}</option>";
                                    }
                                }
                                ?>
                            </select>
                            <span class="text-danger"><?= @$messages['class_id'] ?></span>
                        </div>

                        <div class="form-group mb-3">
                            <label>Material Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="E.g., 2025 Syllabus or Chapter 1 Tute" value="<?= htmlspecialchars(@$title) ?>">
                            <span class="text-danger"><?= @$messages['title'] ?></span>
                        </div>

                        <div class="form-group mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Optional: A brief description of the material."><?= htmlspecialchars(@$description) ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label>File <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" name="material_file">
                            <span class="text-danger"><?= @$messages['material_file'] ?></span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Add Material</button>
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
