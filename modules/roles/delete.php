<?php
/**
 * Delete Custom Role Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('roles.delete');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/roles/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/roles/index.php');
}

$roleId = (int)($_POST['role_id'] ?? 0);
if ($roleId <= 0) {
    set_flash('error', 'Invalid role ID.');
    redirect('modules/roles/index.php');
}

try {
    $pdo = get_db_connection();

    // 2. Fetch role
    $stmt = $pdo->prepare("SELECT id, name, slug, is_system FROM roles WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $roleId]);
    $role = $stmt->fetch();

    if (!$role) {
        set_flash('error', 'Role not found.');
        redirect('modules/roles/index.php');
    }

    // 3. Protect System Roles
    if ((int)($role['is_system'] ?? 0) === 1 || in_array($role['slug'], ['administrator', 'manager', 'staff'], true)) {
        set_flash('error', 'Security Rule: Core system roles cannot be deleted.');
        redirect('modules/roles/index.php');
    }

    // 4. Protect roles assigned to active users
    $userCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = :role_id AND deleted_at IS NULL");
    $userCountStmt->execute(['role_id' => $roleId]);
    $assignedUsers = (int)$userCountStmt->fetchColumn();

    if ($assignedUsers > 0) {
        set_flash('error', "This role is currently assigned to {$assignedUsers} user(s) and cannot be deleted.");
        redirect('modules/roles/index.php');
    }

    // 5. Transactional Delete
    $pdo->beginTransaction();

    $delPerms = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
    $delPerms->execute(['role_id' => $roleId]);

    $delRole = $pdo->prepare("DELETE FROM roles WHERE id = :id");
    $delRole->execute(['id' => $roleId]);

    $pdo->commit();

    set_flash('success', "Role <strong>" . e($role['name']) . "</strong> has been deleted successfully.");
    redirect('modules/roles/index.php');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Role delete error: ' . $e->getMessage());
    set_flash('error', 'Unable to delete role due to a database constraint.');
    redirect('modules/roles/index.php');
}
