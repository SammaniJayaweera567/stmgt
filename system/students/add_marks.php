<?php
ob_start();
include '../../init.php';

// Grade calculation function
function getGrade($marks)
{
    if ($marks >= 75) return 'A';
    elseif ($marks >= 65) return 'B';
    elseif ($marks >= 55) return 'C';
    elseif ($marks !== '') return 'S';
    else return '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $db = dbConn();

    if (!empty($_POST['subject_id']) && isset($_POST['student_id']) && isset($_POST['marks'])) {
        $subject_id = $_POST['subject_id'];
        $student_id = $_POST['student_id'];
        $marks = $_POST['marks'];

        foreach ($student_id as $index => $sid) {
            $m = trim($marks[$index]);

            if (empty($sid) || $m === '') {
                continue;
            }

            if (!is_numeric($m)) {
                continue;
            }

            // Check if record exists
            $checkSql = "SELECT * FROM students_marks WHERE student_id = '$sid' AND subject_id = '$subject_id'";
            $checkResult = $db->query($checkSql);

            if ($checkResult->num_rows > 0) {
                // Update
                $updateSql = "UPDATE students_marks SET marks = '$m' WHERE student_id = '$sid' AND subject_id = '$subject_id'";
                $db->query($updateSql);
            } else {
                // Insert
                $insertSql = "INSERT INTO students_marks(student_id, subject_id, marks) VALUES('$sid', '$subject_id', '$m')";
                $db->query($insertSql);
            }
        }

        echo "<div class='alert alert-success'>Marks saved successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Please select subject and enter valid marks.</div>";
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Add Marks</h3>
            </div>

            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
                <div class="card-body">
                    <div class="form-group">
                        <label for="subject_id">Subjects</label>
                        <select name="subject_id" id="subject_id" class="form-control" required>
                            <option value="">-- Select Subject --</option>
                            <?php
                            $db = dbConn();
                            $sql = "SELECT * FROM subjects";
                            $result = $db->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $subjectId = htmlspecialchars($row['id']);
                                    $subjectName = htmlspecialchars($row['SubjectName']);
                                    echo "<option value='$subjectId'>$subjectName</option>";
                                }
                            } else {
                                echo "<option value=''>No subjects found</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <?php
                    $sql = "SELECT S.Id, S.Name, M.marks FROM students S 
                            LEFT JOIN students_marks M 
                            ON S.Id = M.student_id";
                    $result = $db->query($sql);
                    ?>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Marks</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['Name']) ?></td>
                                        <td>
                                            <input type="hidden" name="student_id[]" value="<?= $row['Id'] ?>">
                                            <input type="text" name="marks[]" value="<?= $row['marks'] ?? '' ?>" class="marks-input form-control">
                                        </td>
                                        <td>
                                            <span class="grade-output"><?= isset($row['marks']) ? getGrade($row['marks']) : '' ?></span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='3'>No students found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Grade calculation script -->
<script>
document.querySelectorAll('.marks-input').forEach(function(input) {
    input.addEventListener('input', function() {
        const value = parseFloat(this.value);
        const gradeSpan = this.closest('tr').querySelector('.grade-output');

        if (!isNaN(value)) {
            let grade = '';
            if (value >= 75) grade = 'A';
            else if (value >= 65) grade = 'B';
            else if (value >= 55) grade = 'C';
            else grade = 'S';
            gradeSpan.textContent = grade;
        } else {
            gradeSpan.textContent = '';
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>
