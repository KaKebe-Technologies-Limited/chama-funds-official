<?php
// ============================================================
// ChamaFunds – logout.php
// ============================================================

// Include config first to get BASE URL
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login using dynamic BASE URL
header('Location: ' . BASE . '/login.php?msg=logged_out');
exit;
?>