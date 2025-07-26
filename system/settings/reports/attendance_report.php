<?php
ob_start();
include '../../../init.php';

// --- PHP logic to fetch data for both table and chart ---
$db = dbConn();
$sql = "SELECT 
            CONCAT(u.FirstName, ' ', u.LastName) AS student_name, 
            CONCAT(cl.level_name, ' | ', s.subject_name) AS class_description, 
            SUM(CASE WHEN att.status = 'Present' THEN 1 ELSE 0 END) AS present_days, 
            SUM(CASE WHEN att.status = 'Absent' THEN 1 ELSE 0 END) AS absent_days 
        FROM attendance att 
        JOIN users u ON att.student_user_id = u.Id 
        JOIN classes c ON att.class_id = c.id 
        JOIN subjects s ON c.subject_id = s.id 
        JOIN class_levels cl ON c.class_level_id = cl.id 
        GROUP BY att.student_user_id, att.class_id 
        ORDER BY student_name, class_description";
$result = $db->query($sql);

// Prepare data arrays for the chart
$chart_labels = [];
$present_data = [];
$absent_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Shorten the label for the chart to avoid clutter
        $chart_labels[] = $row['student_name'] . ' [' . substr($row['class_description'], 0, 15) . '..]';
        $present_data[] = $row['present_days'];
        $absent_data[] = $row['absent_days'];
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
                <div class="card-header"><h3 class="card-title">Student Attendance Summary Details</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="reportTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Present Days</th>
                                <th>Absent Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $row_class = ($row['absent_days'] > 3) ? 'table-warning' : '';
                            ?>
                                    <tr class="<?= $row_class ?>">
                                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                                        <td><?= htmlspecialchars($row['class_description']) ?></td>
                                        <td><?= htmlspecialchars($row['present_days']) ?></td>
                                        <td><?= htmlspecialchars($row['absent_days']) ?></td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="4">No attendance data found.</td></tr>';
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
                    <h3 class="card-title">Attendance Chart</h3>
                </div>
                <div class="card-body">
                    <!-- Wrapper div with a fixed height to control the chart size -->
                    <div style="height: 350px;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('attendanceChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Present Days',
                    data: <?= json_encode($present_data) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                },
                {
                    label: 'Absent Days',
                    data: <?= json_encode($absent_data) ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Important for the wrapper div's height to work
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
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