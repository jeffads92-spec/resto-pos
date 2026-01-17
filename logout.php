<?php
// ============================================
// LOGOUT
// File: logout.php
// ============================================

session_start();

// Destroy session
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect to login
header('Location: login.php');
exit;
?>