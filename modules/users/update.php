<?php
/**
 * Update User Account Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('users.edit');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/users/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/users/index.php');
}

$currentUser = current_user();
$isCurrentUserAdmin = ($currentUser['role_slug'] === 'administrator');

// 2. Collect input
$userId    = (int)($_POST['user_id'] ?? 0);
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim(strtolower($_POST['email'] ?? ''));
$phone     = trim($_POST['phone'] ?? '');
$roleId    = (int)($_POST['role_id'] ?? 0);
$status    = trim($_POST['status'] ?? 'active');

if ($userId <= 0) {
    set_flash('error', 'Invalid user ID.');
    redirect('modules/users/index.php');
}

// 3. Validate
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

if ($roleId <= 0) {
    $errors[] = 'Please select a valid role.';
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect('modules/users/edit.php?id=' . $userId);
}

try {
    $pdo = get_db_connection();

    // 4. Fetch existing target user
    $userStmt = $pdo->prepare("
        SELECT u.*, r.slug AS role_slug
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = :id AND u.deleted_at IS NULL
        LIMIT 1
    ");
    $userStmt->execute(['id' => $userId]);
    $targetUser = $userStmt->fetch();

    if (!$targetUser) {
        set_flash('error', 'User account not found.');
        redirect('modules/users/index.php');
    }

    // 5. Fetch target role
    $roleStmt = $pdo->prepare("SELECT id, name, slug FROM roles WHERE id = :id LIMIT 1");
    $roleStmt->execute(['id' => $roleId]);
    $targetRole = $roleStmt->fetch();

    if (!$targetRole) {
        set_flash('error', 'Selected role is invalid.');
        redirect('modules/users/edit.php?id=' . $userId);
    }

    // Anti-Escalation Check: Non-admin cannot promote anyone to Administrator
    if ($targetRole['slug'] === 'administrator' && !$isCurrentUserAdmin) {
        set_flash('error', 'Unauthorized: You do not have permission to assign the Administrator role.');
        redirect('modules/users/edit.php?id=' . $userId);
    }

    // 6. Last Active Administrator Protection Check
    $isLastAdmin = is_last_active_administrator($userId);
    if ($isLastAdmin) {
        if ($targetRole['slug'] !== 'administrator' || $status !== 'active') {
            set_flash('error', 'You cannot remove or deactivate the last active Administrator.');
            redirect('modules/users/edit.php?id=' . $userId);
        }
    }

    // 7. Check email uniqueness (excluding this user)
    $emailStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id AND deleted_at IS NULL LIMIT 1");
    $emailStmt->execute(['email' => $email, 'id' => $userId]);
    if ($emailStmt->fetch()) {
        set_flash('error', 'This email address is already in use by another user account.');
        redirect('modules/users/edit.php?id=' . $userId);
    }

    $fullName = trim($firstName . ' ' . $lastName);

    // 8. Transactional Update
    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare("
        UPDATE users SET
            role_id = :role_id,
            first_name = :first_name,
            last_name = :last_name,
            name = :name,
            email = :email,
            phone = :phone,
            status = :status,
            updated_at = NOW()
        WHERE id = :id
    ");

    $updateStmt->execute([
        'role_id'    => $roleId,
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'name'       => $fullName,
        'email'      => $email,
        'phone'      => $phone ?: null,
        'status'     => $status,
        'id'         => $userId
    ]);

    $pdo->commit();

    set_flash('success', "User account for <strong>" . e($fullName) . "</strong> updated successfully.");
    redirect('modules/users/index.php');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('User update error: ' . $e->getMessage());
    set_flash('error', 'Unable to update user account due to a database error.');
    redirect('modules/users/edit.php?id=' . $userId);
}
