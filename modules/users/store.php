<?php
/**
 * Store New User Account Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('users.create');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/users/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/users/create.php');
}

$currentUser = current_user();
$isCurrentUserAdmin = ($currentUser['role_slug'] === 'administrator');

// 2. Collect and sanitize input
$firstName       = trim($_POST['first_name'] ?? '');
$lastName        = trim($_POST['last_name'] ?? '');
$email           = trim(strtolower($_POST['email'] ?? ''));
$phone           = trim($_POST['phone'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$roleId          = (int)($_POST['role_id'] ?? 0);
$status          = trim($_POST['status'] ?? 'active');

// Save old input
flash_old_input([
    'first_name' => $firstName,
    'last_name'  => $lastName,
    'email'      => $email,
    'phone'      => $phone,
    'role_id'    => $roleId,
    'status'     => $status
]);

// 3. Server-side validation
$errors = [];

if (empty($firstName)) {
    $errors[] = 'First name is required.';
}

if (empty($lastName)) {
    $errors[] = 'Last name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Password confirmation does not match.';
}

if ($roleId <= 0) {
    $errors[] = 'Please select a valid user role.';
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect('modules/users/create.php');
}

try {
    $pdo = get_db_connection();

    // 4. Check if role exists
    $roleStmt = $pdo->prepare("SELECT id, name, slug FROM roles WHERE id = :id LIMIT 1");
    $roleStmt->execute(['id' => $roleId]);
    $targetRole = $roleStmt->fetch();

    if (!$targetRole) {
        set_flash('error', 'The selected role is invalid.');
        redirect('modules/users/create.php');
    }

    // Anti-Escalation Check: Non-administrators cannot assign Administrator role
    if ($targetRole['slug'] === 'administrator' && !$isCurrentUserAdmin) {
        set_flash('error', 'Unauthorized: You do not have permission to assign the Administrator role.');
        redirect('modules/users/create.php');
    }

    // 5. Check email uniqueness (among non-deleted users)
    $emailStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1");
    $emailStmt->execute(['email' => $email]);
    if ($emailStmt->fetch()) {
        set_flash('error', 'An account with this email address already exists.');
        redirect('modules/users/create.php');
    }

    // 6. Hash password and prepare names
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $fullName = trim($firstName . ' ' . $lastName);

    // 7. Transactional Insert
    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("
        INSERT INTO users (
            role_id,
            first_name,
            last_name,
            name,
            email,
            phone,
            password,
            status,
            created_at,
            updated_at
        ) VALUES (
            :role_id,
            :first_name,
            :last_name,
            :name,
            :email,
            :phone,
            :password,
            :status,
            NOW(),
            NOW()
        )
    ");

    $insertStmt->execute([
        'role_id'    => $roleId,
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'name'       => $fullName,
        'email'      => $email,
        'phone'      => $phone ?: null,
        'password'   => $passwordHash,
        'status'     => $status
    ]);

    $newUserId = (int)$pdo->lastInsertId();

    $pdo->commit();

    clear_old_input();
    set_flash('success', "User account for <strong>" . e($fullName) . "</strong> created successfully as <strong>" . e($targetRole['name']) . "</strong>.");
    redirect('modules/users/index.php');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('User store error: ' . $e->getMessage());
    set_flash('error', 'Unable to create user account due to a database error.');
    redirect('modules/users/create.php');
}
