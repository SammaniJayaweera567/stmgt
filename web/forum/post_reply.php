<?php
include '../../init.php';
$db = dbConn();
extract($_POST);

echo $parent_post_id = isset($_POST['parent_post_id']) ? $_POST['parent_post_id'] : 'NULL';

$user_id = $_SESSION['ID']; // Assumes login session

if ($message != '') {
    $db->query("INSERT INTO forum_posts (topic_id, user_id, message, parent_post_id) VALUES ('$topic_id', '$user_id', '$message', '$parent_post_id')");
}
header("Location: view_topic.php?id=$topic_id");
exit;