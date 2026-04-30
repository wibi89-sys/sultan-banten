<?php
/**
 * Logout
 */

require_once 'includes/functions.php';
startSession();

// Log logout activity before destroying session
if (isset($_SESSION['user_id'])) {
    logActivity('logout', 'auth', "User {$_SESSION['nama_lengkap']} logout");
}

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start new session for next visit
session_start();

// Redirect to login page with login parameter
header('Location: index.php?login=1');
exit;
