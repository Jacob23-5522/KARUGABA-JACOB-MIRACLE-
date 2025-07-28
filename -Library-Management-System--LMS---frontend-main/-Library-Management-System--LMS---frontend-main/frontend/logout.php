<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session only if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();
error_log("Logout redirect to login.php");
header("Location: login.php");
exit();
?>