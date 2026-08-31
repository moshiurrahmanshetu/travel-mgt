<?php
/**
 * Update Role & Permissions Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('roles.edit');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/roles/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/roles/index.php');
}

// 2. Collect input
$roleId      = (int)($_POST['role_id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$permsInput  = $_POST['permissions'] ?? [];
$permIds     = is_array($permsInput) ? array_map('intval', $permsInput) : [];

if ($roleId <= 0) {
    set_flash('error', 'Invalid role ID.');
    redirect('modules/roles/index.php');
}

if (empty($name)) {
    set_flash('error', 'Role name is required.');
    redirect('modules/roles/edit.php?id=' . $roleId);
}

try {
    $pdo = get_db_connection();

    // 3. Fetch existing role
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $roleId]);
    $role = $stmt->fetch();

    if (!$role) {
        set_flash('error', 'Role not found.');
        redirect('modules/roles/index.php');
    }

    $isSystemRole = ((int)($role['is_system'] ?? 0) === 1 || in_array($role['slug'], ['administrator', 'manager', 'staff'], true));
    $isAdminRole = ($role['slug'] === 'administrator');

    // If custom role, check name uniqueness
    if (!$isSystemRole) {
        $dupStmt = $pdo->prepare("SELECT id FROM roles WHERE LOWER(name) = LOWER(:name) AND id != :id LIMIT 1");
        $dupStmt->execute(['name' => $name, 'id' => $roleId]);
        if ($dupStmt->fetch()) {
            set_flash('error', 'Another role with this name already exists.');
            redirect('modules/roles/edit.php?id=' . $roleId);
        }
    } else {
        // System roles retain system name
        $name = $role['name'];
    }

    // 4. Validate submitted permission IDs against database
    $validPermIds = [];
    if (!empty($permIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($permIds), '?'));
        $validPermStmt = $pdo->prepare("SELECT id FROM permissions WHERE id IN ({$inPlaceholders})");
        $validPermStmt->execute($permIds);
        $validPermIds = $validPermStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Administrator role must retain all system permissions
    if ($isAdminRole) {
        $allPermStmt = $pdo->query("SELECT id FROM permissions");
        $validPermIds = $allPermStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // 5. Transactional Update of Role & Permissions
    $pdo->beginTransaction();

    // Update Role details
    $updateRole = $pdo->prepare("
        UPDATE roles SET
            name = :name,
            description = :description,
            updated_at = NOW()
        WHERE id = :id
    ");
    $updateRole->execute([
        'name'        => $name,
        'description' => $description ?: null,
        'id'          => $roleId
    ]);

    // Atomic sync of permissions for THIS ROLE ONLY
    $delPerms = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
    $delPerms->execute(['role_id' => $roleId]);

    if (!empty($validPermIds)) {
        $insertPerm = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :perm_id)");
        foreach ($validPermIds as $pId) {
            $insertPerm->execute([
                'role_id' => $roleId,
                'perm_id' => (int)$pId
            ]);
        }
    }

    $pdo->commit();

    set_flash('success', "Role <strong>" . e($name) . "</strong> and its " . count($validPermIds) . " permissions updated successfully.");
    redirect('modules/roles/index.php');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Role update error: ' . $e->getMessage());
    set_flash('error', 'Unable to update role permissions due to a database error.');
    redirect('modules/roles/edit.php?id=' . $roleId);
}
