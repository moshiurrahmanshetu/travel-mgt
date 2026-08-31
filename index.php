<?php
/**
 * Root Router / Entry Point
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('modules/dashboard/index.php');
} else {
    redirect('auth/login.php');
}
