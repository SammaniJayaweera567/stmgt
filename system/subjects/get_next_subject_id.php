<?php
include '../../init.php';

$db = dbConn();
// This query gets the next auto-increment ID for the specified table
$sql = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'stmgt' AND TABLE_NAME = 'subjects'";
$result = $db->query($sql);
$row = $result->fetch_assoc();

// Send the next ID back as a JSON response
echo json_encode(['next_id' => $row['AUTO_INCREMENT']]);
?>