<?php
ob_start();
include '../../init.php';
$db = dbConn();

if($conn) 

extract($_POST);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $user_id = $_SESSION['ID'];

    if ($title != '') {
        $db->query("INSERT INTO forum_topics (title, created_by) VALUES ('$title','$user_id')");

        header("Location: forum_index.php");
        exit;
    }
}
?>

<h2>Start a New Topic</h2>
<form method="post">
    <label>Title:</label><br>
    <input type="text" name="title" required size="60"><br><br>
    <button type="submit">Create Topic</button>
</form>

<?php
$content = ob_get_clean();
include '../layouts.php';
?><?php
ob_start();
include '../../init.php';
$db = dbConn();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $user_id = $_SESSION['ID'];

    if ($title != '') {
        $db->query("INSERT INTO forum_topics (title, created_by) VALUES ('$title','$user_id')");

        header("Location: forum_index.php");
        exit;
    }
}
?>

<h2>Start a New Topic</h2>
<form method="post">
    <label>Title:</label><br>
    <input type="text" name="title" required size="60"><br><br>
    <button type="submit">Create Topic</button>
</form>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>