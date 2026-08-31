<?php
/**
 * Process Login Request
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('auth/login.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try logging in again.');
    redirect('auth/login.php');
}

// 2. Collect and sanitize input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Flash old email in case of failure
flash_old_input(['email' => $email]);

// 3. Validation
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
    set_flash('error', 'Invalid email or password.');
    redirect('auth/login.php');
}

try {
    $pdo = get_db_connection();

    // 4. Query active and non-deleted user
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.role_id,
            u.first_name,
            u.last_name,
            u.name,
            u.email,
            u.password,
            u.status,
            r.name AS role_name,
            r.slug AS role_slug
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.email = :email AND u.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // 5. Verify credentials and active status
    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
        // Generic security error message
        set_flash('error', 'Invalid email or password.');
        redirect('auth/login.php');
    }

    // 6. Successful Authentication: Regenerate Session ID
    session_regenerate_id(true);

    // Clear old form input
    clear_old_input();

    // 7. Store essential session variables (never store password in session)
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role_id'] = (int)$user['role_id'];
    $_SESSION['role_slug'] = $user['role_slug'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['logged_in_at'] = time();

    // 8. Update last_login in database
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
    $updateStmt->execute(['id' => $user['id']]);

    // 9. Success message and redirect to Dashboard
    $greetingName = !empty($user['first_name']) ? $user['first_name'] : $user['name'];
    set_flash('success', 'Welcome back, ' . $greetingName . '!');
    redirect('modules/dashboard/index.php');

} catch (PDOException $e) {
    error_log('Login Process Error: ' . $e->getMessage());
    set_flash('error', 'A system error occurred. Please try again later.');
    redirect('auth/login.php');
}
