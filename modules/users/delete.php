<?php
/**
 * Soft Delete User Account Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('users.delete');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/users/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/users/index.php');
}

$userId = (int)($_POST['user_id'] ?? 0);
$currentUserId = current_user_id();

if ($userId <= 0) {
    set_flash('error', 'Invalid user ID.');
    redirect('modules/users/index.php');
}

// Cannot delete oneself
if ($userId === $currentUserId) {
    set_flash('error', 'Security Rule: You cannot delete your own active user account.');
    redirect('modules/users/index.php');
}

try {
    $pdo = get_db_connection();

    // Fetch user details
    $stmt = $pdo->prepare("SELECT id, name, role_id FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('error', 'User account not found or already removed.');
        redirect('modules/users/index.php');
    }

    // Last Active Administrator Protection
    if (is_last_active_administrator($userId)) {
        set_flash('error', 'Security Rule: You cannot delete the last active Administrator in the system.');
        redirect('modules/users/index.php');
    }

    // Soft delete
    $delStmt = $pdo->prepare("UPDATE users SET deleted_at = NOW(), status = 'inactive' WHERE id = :id");
    $delStmt->execute(['id' => $userId]);

    set_flash('success', "User account for <strong>" . e($user['name']) . "</strong> has been deleted successfully.");
    redirect('modules/users/index.php');

} catch (PDOException $e) {
    error_log('User delete error: ' . $e->getMessage());
    set_flash('error', 'Unable to delete user account due to a system error.');
    redirect('modules/users/index.php');
}
