<?php
/**
 * Update System Settings Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('settings.edit');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/settings/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/settings/index.php');
}

// 2. Collect input
$companyName    = trim($_POST['company_name'] ?? '');
$companyEmail   = trim($_POST['company_email'] ?? '');
$companyPhone   = trim($_POST['company_phone'] ?? '');
$companyAddress = trim($_POST['company_address'] ?? '');
$companyWebsite = trim($_POST['company_website'] ?? '');
$currency       = trim($_POST['currency'] ?? 'BDT');
$currencySymbol = trim($_POST['currency_symbol'] ?? '৳');
$timezone       = trim($_POST['timezone'] ?? 'Asia/Dhaka');

// 3. Validation
$errors = [];

if (empty($companyName)) {
    $errors[] = 'Company / Agency name is required.';
}

if (empty($companyEmail) || !filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid company email address is required.';
}

if (empty($currency)) {
    $errors[] = 'Currency code is required.';
}

if (empty($currencySymbol)) {
    $errors[] = 'Currency symbol is required.';
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect('modules/settings/index.php');
}

try {
    // 4. Save settings
    set_setting('company_name', $companyName);
    set_setting('company_email', $companyEmail);
    set_setting('company_phone', $companyPhone ?: null);
    set_setting('company_address', $companyAddress ?: null);
    set_setting('company_website', $companyWebsite ?: null);
    set_setting('currency', $currency);
    set_setting('currency_symbol', $currencySymbol);
    set_setting('timezone', $timezone);

    set_flash('success', 'System configuration and company profile settings updated successfully.');
    redirect('modules/settings/index.php');

} catch (Exception $e) {
    error_log('Settings update error: ' . $e->getMessage());
    set_flash('error', 'Unable to save settings due to an unexpected error.');
    redirect('modules/settings/index.php');
}
