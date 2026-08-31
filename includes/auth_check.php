<?php
/**
 * Authentication Check Guard
 * Tour & Travel Booking Management System
 * 
 * Include this file at the beginning of any protected page or module
 * to automatically enforce authentication and active account status.
 */

require_once __DIR__ . '/functions.php';

// Enforce login and active account state
require_login();
