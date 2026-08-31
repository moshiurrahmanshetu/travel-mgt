<?php
/**
 * Logout Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Unset all session variables
$_SESSION = [];

// Expire the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
if (session_id() !== '') {
    session_destroy();
}

// Start fresh session for flash message
session_start();
set_flash('info', 'You have been logged out successfully.');

// Redirect to login page
redirect('auth/login.php');
