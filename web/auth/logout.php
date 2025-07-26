<?php
// Step 1: Start the session.
// We need to start the session to be able to access and destroy it.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Step 2: Unset all session variables.
// This removes all data stored in the $_SESSION array.
$_SESSION = array();

// Step 3: Destroy the session.
// This completely ends the current session.
session_destroy();

// Step 4: Redirect to the login page with a success message.
// We can't set a session message here because we just destroyed the session.
// So, we pass the message via a GET parameter.
header("Location: login.php?status=logout_success");
exit();
?>