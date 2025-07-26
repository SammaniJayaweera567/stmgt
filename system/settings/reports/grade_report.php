<?php
ob_start();
include '../../../init.php';

// --- NEW: All PHP logic is now at the top for better scope management ---

$db = dbConn();

// Get filter values from GET request to keep them selected
$selected_year = isset($_GET['academic_year']) ? dataClean($_GET['academic_year']) : '';
$selected_level = isset($_GET['class_level']) ? dataClean($_GET['class_level']) : '';

// Build the WHERE clause for SQL queries based on the selected filters
$where_clause = "WHERE 1=1";
if (!empty($selected_year)) {
    $where_clause .= " AND c.academic_year_id = '$selected_year'";
}
if (!empty($selected_level)) {
    $where_clause .= " AND c.class_level_id = '$selected_level'";
}

// SQL Query for the main table
$table_sql = "SELECT a.title AS assessment_title, s.subject_name, g.grade_name, COUNT(ar.id) AS student_count 
              FROM assessment_results ar 
              JOIN assessments a ON ar.assessment_id = a.id 
              JOIN grades g ON ar.grade_id = g.id 
              JOIN subjects s ON a.subject_id = s.id 
              JOIN classes c ON a.class_id = c.id 
              $where_clause 
              GROUP BY a.id, g.id 
              ORDER BY a.title, g.min_percentage DESC";
$table_result = $db->query($table_sql);

// SQL query specifically for the aggregated chart data
$chart_sql = "SELECT g.grade_name, COUNT(ar.id) AS total_students 
              FROM assessment_results ar 
              JOIN grades g ON ar.grade_id = g.id 
              JOIN assessments a ON ar.assessment_id = a.id 
              JOIN classes c ON a.class_id = c.id 
              $where_clause 
              GROUP BY g.id 
              ORDER BY g.min_percentage DESC";
$chart_result = $db->query($chart_sql);

// Prepare data arrays for the chart
$chart_labels = [];
$chart_data = [];
if ($chart_result && $chart_result->num_rows > 0) {
    while($row = $chart_result->fetch_assoc()){
        $chart_labels[] = $row['grade_name'];
        $chart_data[] = $row['total_students'];
    }
}
?>

<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filter Report</h3>
        </div>
        <div class="card-body">
            <form method="get" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Select Academic Year:</label>
                            <select name="academic_year" class="form-control">
                                <option value="">-- All Years --</option>
                                <?php
                                $sql_years = "SELECT id, year_name FROM academic_years ORDER BY year_name DESC";
                                $result_years = $db->query($sql_years);
                                while ($row_year = $result_years->fetch_assoc()) {
                                    $selected = ($selected_year == $row_year['id']) ? 'selected' : '';
                                    echo "<option value='{$row_year['id']}' $selected>" . htmlspecialchars($row_year['year_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Select Class Level (Grade):</label>
                            <select name="class_level" class="form-control">
                                <option value="">-- All Levels --</option>
                                <?php
                                $sql_levels = "SELECT id, level_name FROM class_levels ORDER BY id";
                                $result_levels = $db->query($sql_levels);
                                while ($row_level = $result_levels->fetch_assoc()) {
                                    $selected = ($selected_level == $row_level['id']) ? 'selected' : '';
                                    echo "<option value='{$row_level['id']}' $selected>" . htmlspecialchars($row_level['level_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Filter Report</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-lg-7">
            <div class="card card-primary h-100">
                <div class="card-header"><h3 class="card-title">Grade Distribution Details</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="reportTable">
                        <thead>
                            <tr>
                                <th>Assessment</th>
                                <th>Subject</th>
                                <th>Grade</th>
                                <th>Number of Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($table_result && $table_result->num_rows > 0) {
                                while ($row = $table_result->fetch_assoc()) {
                                    $row_class = (in_array($row['grade_name'], ['F', 'E'])) ? 'bg-danger text-white' : '';
                                    echo "<tr class='$row_class'>";
                                    echo "<td>" . htmlspecialchars($row['assessment_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['subject_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['grade_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['student_count']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo '<tr><td colspan="4">No grade data found for the selected criteria.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-success h-100">
                <div class="card-header"><h3 class="card-title">Overall Grade Distribution Chart</h3></div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="gradeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('gradeChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Number of Students',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: ['#28a745', '#007bff', '#ffc107', '#fd7e14', '#dc3545', '#6c757d'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });

    $(function() {
        $("#reportTable").DataTable({
            "responsive": true, "lengthChange": true, "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print"]
        }).buttons().container().appendTo('#reportTable_wrapper .col-md-6:eq(0)');
    });
</script>
<?php
$content = ob_get_clean();
include '../../layouts.php';
?>