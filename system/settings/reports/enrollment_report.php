<?php
ob_start();
include '../../../init.php';

// --- PHP logic to fetch data for both table and chart ---
$db = dbConn();
$sql = "SELECT 
            CONCAT(cl.level_name, ' | ', s.subject_name, ' (', ct.type_name, ')') AS class_description,
            COUNT(e.id) AS enrolled_count
        FROM enrollments e
        JOIN classes c ON e.class_id = c.id
        JOIN academic_years ay ON c.academic_year_id = ay.id
        JOIN class_levels cl ON c.class_level_id = cl.id
        JOIN subjects s ON c.subject_id = s.id
        JOIN class_types ct ON c.class_type_id = ct.id
        GROUP BY c.id
        ORDER BY enrolled_count DESC";
$result = $db->query($sql);

// Prepare data arrays for the chart
$chart_labels = [];
$chart_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $chart_labels[] = $row['class_description'];
        $chart_data[] = $row['enrolled_count'];
    }
    // Reset the result pointer to use it again for the table
    mysqli_data_seek($result, 0);
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Table Column -->
        <div class="col-lg-7">
            <div class="card card-primary h-100">
                <div class="card-header"><h3 class="card-title">Course Enrollment Details</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="reportTable">
                        <thead>
                            <tr>
                                <th>Class Description</th>
                                <th>Number of Enrolled Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                            ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['class_description']) ?></td>
                                        <td><?= htmlspecialchars($row['enrolled_count']) ?></td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="2">No enrollment data found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Chart Column -->
        <div class="col-lg-5">
            <div class="card card-success h-100">
                <div class="card-header">
                    <h3 class="card-title">Enrollment Chart</h3>
                </div>
                <div class="card-body">
                    <!-- Wrapper div with a fixed height to control the chart size -->
                    <div style="height: 350px;">
                        <canvas id="enrollmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('enrollmentChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Number of Students',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: 'rgba(23, 162, 184, 0.7)',
                borderColor: 'rgba(23, 162, 184, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Important for the wrapper div's height to work
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Initialize DataTable for export buttons
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