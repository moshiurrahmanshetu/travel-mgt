<?php
/**
 * CSRF Protection Helpers
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Generate or retrieve the current session CSRF token
 * 
 * @return string
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Generate hidden input HTML field for forms
 * 
 * @return string
 */
function csrf_field(): string
{
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST request or parameter
 * 
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }

    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Enforce CSRF token verification on POST requests
 * 
 * @param string $redirect_to Fallback redirect URL on failure
 * @return void
 */
function validate_csrf_or_abort(string $redirect_to = ''): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) {
            if (function_exists('set_flash')) {
                set_flash('error', 'Invalid or expired security token. Please refresh and try again.');
            }
            
            if (!empty($redirect_to)) {
                header("Location: " . $redirect_to);
                exit;
            }

            http_response_code(403);
            die('<div style="font-family: sans-serif; text-align: center; padding: 50px;">'
                . '<h3>403 - Forbidden: Invalid Security Token</h3>'
                . '<p>Your request could not be validated. Please return to the previous page and try again.</p>'
                . '<p><a href="javascript:history.back()" style="color: #2563eb; text-decoration: none;">&larr; Go Back</a></p>'
                . '</div>');
        }
    }
}
