<?php
ob_start();
// Path from system/payments/
include '../../init.php'; 

// Initialize variables
$messages = [];
$id = (int)($_GET['id'] ?? 0);
if ($id === 0) { header("Location: manage_discounts.php"); exit(); }

$db = dbConn();

// --- Handle form submission for the UPDATE action ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean all submitted data
    $id = dataClean($_POST['id']);
    $student_id = dataClean($_POST['student_id']);
    $class_id = dataClean($_POST['class_id']);
    $discount_type_id = dataClean($_POST['discount_type_id']);
    $discount_value = dataClean($_POST['discount_value'] ?? ''); // Allow empty string initially
    $reason = dataClean($_POST['reason']);
    $status = dataClean($_POST['status']);

    // --- Validation ---
    if (empty($student_id)) { $messages['student_id'] = "A student must be selected."; }
    if (empty($class_id)) { $messages['class_id'] = "A class must be selected."; }
    if (empty($discount_type_id)) { $messages['discount_type_id'] = "A discount type must be selected."; }
    if (empty($status)) { $messages['status'] = "Status is required."; }

    // **FIX for Fatal Error:** If discount_value is not numeric, set it to 0.
    if (!is_numeric($discount_value)) {
        $discount_value = 0;
    }

    // Check for duplicates (ignoring the current record)
    $sql_check = "SELECT id FROM student_discounts WHERE student_user_id = '$student_id' AND class_id = '$class_id' AND id != '$id'";
    if ($db->query($sql_check)->num_rows > 0) {
        $messages['student_id'] = "A discount for this student in this class already exists.";
    }

    // If there are no validation errors, proceed with the update
    if (empty($messages)) {
        $sql = "UPDATE student_discounts SET 
                    student_user_id='$student_id', 
                    class_id='$class_id', 
                    discount_type_id='$discount_type_id', 
                    discount_value='$discount_value', 
                    reason='$reason', 
                    status='$status' 
                WHERE id='$id'";
        
        if ($db->query($sql)) {
            $_SESSION['status_message'] = "Discount updated successfully!";
            header("Location: manage_discounts.php");
            exit();
        } else {
            $messages['main_error'] = "Database error. Could not update the discount.";
        }
    }
}

// --- Fetch existing data to show in the form ---
$sql_fetch = "SELECT * FROM student_discounts WHERE id='$id'";
$result = $db->query($sql_fetch);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_id = $row['student_user_id'];
    $class_id = $row['class_id'];
    $discount_type_id = $row['discount_type_id'];
    $discount_value = $row['discount_value'];
    $reason = $row['reason'];
    $status = $row['status'];
} else {
    header("Location: manage_discounts.php");
    exit();
}

// Fetch data for dropdowns
$sql_classes = "SELECT c.id, cl.level_name, s.subject_name, ct.type_name FROM classes c JOIN class_levels cl ON c.class_level_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN class_types ct ON c.class_type_id = ct.id WHERE c.status='Active' ORDER BY cl.level_name, s.subject_name";
$result_classes = $db->query($sql_classes);
$sql_discount_types = "SELECT * FROM discount_types";
$result_discount_types = $db->query($sql_discount_types);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Edit Student Discount</h3></div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $id ?>">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="card-body">
                        <?php if(!empty($messages['main_error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($messages['main_error']) ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label>1. Select Class <span class="text-danger">*</span></label>
                                <select name="class_id" id="class_id_selector" class="form-control">
                                    <option value="">-- Select Class --</option>
                                    <?php mysqli_data_seek($result_classes, 0); while($row = $result_classes->fetch_assoc()) { 
                                        $class_display = htmlspecialchars($row['level_name'] . ' - ' . $row['subject_name'] . ' (' . $row['type_name'] . ')');
                                        $selected = ($class_id == $row['id']) ? 'selected' : '';
                                        echo "<option value='{$row['id']}' $selected>{$class_display}</option>";
                                    } ?>
                                </select>
                                <span class="text-danger"><?= @$messages['class_id'] ?></span>
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label>2. Select Student <span class="text-danger">*</span></label>
                                <select name="student_id" id="student_id_selector" class="form-control">
                                    <option value="">-- Select a class to see students --</option>
                                </select>
                                <span class="text-danger"><?= @$messages['student_id'] ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label>Discount Type <span class="text-danger">*</span></label>
                                <select name="discount_type_id" id="discount_type_id" class="form-control">
                                    <option value="">-- Select Type --</option>
                                    <?php mysqli_data_seek($result_discount_types, 0); while($row = $result_discount_types->fetch_assoc()) { 
                                        $selected = ($discount_type_id == $row['id']) ? 'selected' : '';
                                        echo "<option value='{$row['id']}' data-logic='{$row['value_logic']}' data-value='{$row['default_value']}' $selected>".htmlspecialchars($row['type_name'])."</option>";
                                    } ?>
                                </select>
                                <span class="text-danger"><?= @$messages['discount_type_id'] ?></span>
                            </div>
                            <div class="form-group col-md-6 mb-3" id="discount_value_wrapper">
                                <label>Value</label>
                                <input type="number" step="0.01" name="discount_value" id="discount_value_input" class="form-control" placeholder="Enter value if applicable" value="<?= htmlspecialchars(@$discount_value) ?>">
                                <small id="value_helper_text" class="form-text text-muted"></small>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Reason</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Optional: Reason for giving the discount."><?= htmlspecialchars(@$reason) ?></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control">
                                <option value="Active" <?= (@$status == 'Active') ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= (@$status == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <span class="text-danger"><?= @$messages['status'] ?></span>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Discount</button>
                        <a href="manage_discounts.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelector = document.getElementById('class_id_selector');
    const studentSelector = document.getElementById('student_id_selector');
    const discountTypeSelect = document.getElementById('discount_type_id');
    const discountValueWrapper = document.getElementById('discount_value_wrapper');
    const discountValueInput = document.getElementById('discount_value_input');
    const valueHelperText = document.getElementById('value_helper_text');
    
    const initialStudentId = <?= (int)$student_id ?>;

    function fetchStudents(classId, selectedStudentId) {
        studentSelector.innerHTML = '<option value="">Loading...</option>';
        studentSelector.disabled = true;

        if (classId) {
            fetch(`get_students_for_class.php?class_id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    studentSelector.innerHTML = '<option value="">-- Select Student --</option>';
                    data.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.Id;
                        option.textContent = `${student.FirstName} ${student.LastName} (${student.registration_no})`;
                        if (student.Id == selectedStudentId) {
                            option.selected = true;
                        }
                        studentSelector.appendChild(option);
                    });
                    studentSelector.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching students:', error);
                    studentSelector.innerHTML = '<option value="">-- Error loading students --</option>';
                });
        } else {
            studentSelector.innerHTML = '<option value="">-- Select a class to see students --</option>';
        }
    }

    classSelector.addEventListener('change', function() {
        fetchStudents(this.value, null);
    });

    function handleDiscountTypeChange() {
        const selectedOption = discountTypeSelect.options[discountTypeSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const logic = selectedOption.dataset.logic;
            const value = selectedOption.dataset.value;

            if (logic === 'Percentage' || logic === 'Fixed Amount') {
                discountValueWrapper.style.display = 'block';
                discountValueInput.value = value;
                discountValueInput.readOnly = false;
                valueHelperText.textContent = logic === 'Percentage' ? 'Enter percentage (e.g., 10 for 10%)' : 'Enter fixed LKR amount.';
            } else {
                discountValueWrapper.style.display = 'block';
                discountValueInput.value = value;
                discountValueInput.readOnly = true;
                valueHelperText.textContent = 'This value is fixed for the selected type.';
            }
        } else {
             discountValueWrapper.style.display = 'none';
             valueHelperText.textContent = '';
        }
    }

    discountTypeSelect.addEventListener('change', handleDiscountTypeChange);
    
    // Initial setup on page load
    if (classSelector.value) {
        fetchStudents(classSelector.value, initialStudentId);
    }
    handleDiscountTypeChange();
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; 
?>
