<?php
ob_start();
include '../../init.php';
$db = dbConn();

extract($_GET);
$topic_id = $_GET['id'];
$topic_id = $id;

$sql = "SELECT T.*, U.FirstName, U.LastName FROM forum_topics T
        INNER JOIN users U ON U.Id = T.created_by
        WHERE T.id = $topic_id";
$topic = $db->query($sql)->fetch_assoc();

echo $sql = "SELECT P.*, U.FirstName, U.LastName FROM forum_posts P
        INNER JOIN users U ON U.Id = P.user_id
        WHERE P.topic_id = $topic_id AND P.parent_post_id =0
        ORDER BY P.created_at ASC";
$posts = $db->query($sql);
?>

<h3><?= htmlspecialchars($topic['title']) ?></h3>
<p><small>By <?= $topic['FirstName'] ?> <?= $topic['LastName'] ?> on <?= $topic['created_at'] ?></small></p>

<hr>
<h4>Posts</h4>
<?php while ($post = $posts->fetch_assoc()): ?>
    <div style="margin-bottom: 20px;">
        <p><strong><?= $post['FirstName'] ?> <?= $post['LastName'] ?>:</strong></p>
        <p><?= nl2br(htmlspecialchars($post['message'])) ?></p>
        <form method="post" action="post_reply.php">
            <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
            <input type="hidden" name="parent_post_id" value="<?= $post['id'] ?>">
            <textarea name="message" rows="2" cols="60" placeholder="Reply..."></textarea><br>
            <button type="submit">Reply</button>
        </form>

        <?php
        $post_id = $post['id'];
        $replies = $db->query("SELECT R.*, U.FirstName, U.LastName FROM forum_posts R
                               INNER JOIN users U ON U.Id = R.user_id
                               WHERE R.parent_post_id = $post_id
                               ORDER BY R.created_at ASC");
        while ($reply = $replies->fetch_assoc()):
        ?>
            <div style="margin-left: 40px; background: #f9f9f9; padding: 10px;">
                <p><strong><?= $reply['FirstName'] ?> <?= $reply['LastName'] ?>:</strong></p>
                <p><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
            </div>
        <?php endwhile; ?>
    </div>
<?php endwhile; ?>

<hr>
<h4>Add a new post</h4>
<form method="post" action="post_reply.php">
    <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
    <textarea name="message" rows="4" cols="60" placeholder="Write your message..."></textarea><br>
    <button type="submit">Post</button>
</form>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>