<?php
ob_start();
include '../../../init.php'; // Path from /system/assessments/quizzes/

$db = dbConn();
?>

<div class="container-fluid">
    <?php show_status_message(); ?>

    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-question-circle mt-1 me-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Manage Quizzes</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-start mb-4">
                <a href="add_quiz.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add New Quiz
                </a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quiz List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="quizzesTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Time Limit</th>
                                    <th>Total Marks</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT 
                                            a.id, 
                                            a.title, 
                                            a.time_limit_minutes,
                                            a.status,
                                            cl.level_name,
                                            s.subject_name,
                                            ct.type_name,
                                            (SELECT SUM(marks) FROM assessment_questions WHERE assessment_id = a.id) as total_marks
                                        FROM assessments a
                                        JOIN classes c ON a.class_id = c.id
                                        JOIN class_levels cl ON c.class_level_id = cl.id
                                        JOIN subjects s ON c.subject_id = s.id
                                        JOIN class_types ct ON c.class_type_id = ct.id
                                        WHERE a.assessment_type = 'Quiz'
                                        ORDER BY a.created_at DESC";
                                $result = $db->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $class_full_name = htmlspecialchars($row['level_name'] . ' - ' . $row['subject_name'] . ' (' . $row['type_name'] . ')');
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= $class_full_name ?></td>
                                            <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($row['time_limit_minutes']) ?> mins</td>
                                            <td><?= htmlspecialchars(number_format($row['total_marks'] ?? 0, 2)) ?></td>
                                            <td><?= display_status_badge($row['status']) ?></td>
                                            <td class="text-start">
                                                <div class="btn-group">
                                                    <a href="manage_questions.php?quiz_id=<?= $row['id'] ?>" class="btn btn-info btn-sm mr-1" title="Manage Questions">
                                                        <i class="fas fa-list-ol"></i>
                                                    </a>
                                                    <a href="edit_quiz.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm mr-1" title="Edit Quiz">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="delete_quiz.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this quiz and all its questions? This action cannot be undone.');">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm mr-1" title="Delete Quiz">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <a href="view_quiz_results.php?quiz_id=<?= $row['id'] ?>" class="btn btn-success btn-sm" title="View Results">
                                                        <i class="fas fa-poll"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">No quizzes found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() { 
        $('#quizzesTable').DataTable(); 
    });
</script>

<?php
$content = ob_get_clean();
include '../../layouts.php';
?>
