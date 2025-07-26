<?php

include '../init.php';

// Destroy the session (sessions remove wenawa)
session_destroy();

// Redirect to login page
header("Location: login.php");
?>