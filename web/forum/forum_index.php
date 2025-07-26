<?php
ob_start();
include '../../init.php';
$db = dbConn();

$sql = "SELECT T.*, U.FirstName, U.LastName FROM forum_topics T
        INNER JOIN users U ON U.Id = T.created_by
        ORDER BY T.created_at DESC";
$result = $db->query($sql);
?>

<div class="container">
    <h2>Forum Topics</h2>
    <a href="new_topic.php">Start New Topic</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Posted By</th>
                <th>Date</th>
                <th>View</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= $row['FirstName'] . ' ' . $row['LastName'] ?></td>
                <td><?= $row['created_at'] ?></td>
                <td><a href="view_topic.php?id=<?= $row['id'] ?>">View</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>


<?php
$content = ob_get_clean();
include '../layouts.php';
?>