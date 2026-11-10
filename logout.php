
<?php
session_start();

// Remove all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Start a new session for notification
session_start();

$_SESSION["flash_message"] = "Logout successful! See you again.";
$_SESSION["flash_type"] = "success";

// Go back to homepage
header("Location: index.php");
exit;
?>
