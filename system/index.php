<?php
// Start output buffering
ob_start();

// Include the initialization file
include '../init.php';

// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location:login.php");
    exit;
}

// No content is generated here, so the dashboard area will be blank.
// The main layout will be loaded with empty content.

// Get the content from the buffer (which is empty) and clean it
$content = ob_get_clean();

// Include your main layout file
include 'layouts.php';
?>