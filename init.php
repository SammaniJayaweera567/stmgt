<?php
session_start();
include 'config.php';
include 'function.php';

$host = "localhost";
$username = "dev1";
$password = "123456";
$dbname = "stmgt";
$port = 3307;

$db = dbConn($host, $username, $password, $dbname, $port);

// Optional: Set default timezone
date_default_timezone_set('Asia/Colombo'); // Or your relevant Timezone

?>
