<?php
/**
 * Application Configuration
 * Tour & Travel Booking Management System
 */

// Prevent direct script execution if accessed outside PHP context
if (defined('APP_INIT') === false) {
    define('APP_INIT', true);
}

// --------------------------------------------------------------------------
// Core Application Settings
// --------------------------------------------------------------------------
define('APP_NAME', 'Tour & Travel Booking Management System');
define('APP_SHORT_NAME', 'TravelMgt');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // 'development' or 'production'

// Default Timezone
define('APP_TIMEZONE', 'Asia/Dhaka');
date_default_timezone_set(APP_TIMEZONE);

// Currency
define('APP_CURRENCY', 'BDT');
define('APP_CURRENCY_SYMBOL', '৳');

// --------------------------------------------------------------------------
// Database Settings
// --------------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'travel_mgt_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --------------------------------------------------------------------------
// Paths & URL Settings
// --------------------------------------------------------------------------
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('AVATAR_PATH', UPLOAD_PATH . DIRECTORY_SEPARATOR . 'avatars');

// Detect Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Compute URL path segment
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// Find travel-mgt segment
$pos = strpos($scriptDir, '/travel-mgt');
if ($pos !== false) {
    $baseSegment = substr($scriptDir, 0, $pos + strlen('/travel-mgt'));
} else {
    $baseSegment = '/travel-mgt';
}

define('APP_URL', rtrim($protocol . $host . $baseSegment, '/'));
define('UPLOAD_URL', APP_URL . '/uploads');
define('AVATAR_URL', UPLOAD_URL . '/avatars');

// --------------------------------------------------------------------------
// Session Initialization
// --------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // Set secure cookie options
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0, // session cookie expires on browser close
        'path'     => '/',
        'domain'   => $cookieParams['domain'] ?? '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}
