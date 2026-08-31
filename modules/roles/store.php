<?php
/**
 * Store Custom Role Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('roles.create');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/roles/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/roles/create.php');
}

// 2. Collect input
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$permsInput  = $_POST['permissions'] ?? [];
$permIds     = is_array($permsInput) ? array_map('intval', $permsInput) : [];

flash_old_input([
    'name'        => $name,
    'description' => $description,
    'permissions' => $permIds
]);

// 3. Validation
if (empty($name)) {
    set_flash('error', 'Role name is required.');
    redirect('modules/roles/create.php');
}

try {
    $pdo = get_db_connection();

    // Check duplicate name
    $dupStmt = $pdo->prepare("SELECT id FROM roles WHERE LOWER(name) = LOWER(:name) LIMIT 1");
    $dupStmt->execute(['name' => $name]);
    if ($dupStmt->fetch()) {
        set_flash('error', 'A role with this name already exists.');
        redirect('modules/roles/create.php');
    }

    // Generate unique slug
    $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    if (empty($baseSlug)) {
        $baseSlug = 'custom-role';
    }

    $slug = $baseSlug;
    $slugStmt = $pdo->prepare("SELECT id FROM roles WHERE slug = :slug LIMIT 1");
    $slugStmt->execute(['slug' => $slug]);
    $counter = 1;
    while ($slugStmt->fetch()) {
        $slug = $baseSlug . '-' . $counter;
        $slugStmt->execute(['slug' => $slug]);
        $counter++;
    }

    // Validate permission IDs against DB
    $validPermIds = [];
    if (!empty($permIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($permIds), '?'));
        $validPermStmt = $pdo->prepare("SELECT id FROM permissions WHERE id IN ({$inPlaceholders})");
        $validPermStmt->execute($permIds);
        $validPermIds = $validPermStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // 4. Transactional Insert
    $pdo->beginTransaction();

    $insertRole = $pdo->prepare("
        INSERT INTO roles (name, slug, description, is_system, created_at, updated_at)
        VALUES (:name, :slug, :description, 0, NOW(), NOW())
    ");
    $insertRole->execute([
        'name'        => $name,
        'slug'        => $slug,
        'description' => $description ?: null
    ]);

    $newRoleId = (int)$pdo->lastInsertId();

    if (!empty($validPermIds)) {
        $insertPerm = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :perm_id)");
        foreach ($validPermIds as $pId) {
            $insertPerm->execute([
                'role_id' => $newRoleId,
                'perm_id' => (int)$pId
            ]);
        }
    }

    $pdo->commit();

    clear_old_input();
    set_flash('success', "Role <strong>" . e($name) . "</strong> created successfully with " . count($validPermIds) . " permissions assigned.");
    redirect('modules/roles/index.php');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Role store error: ' . $e->getMessage());
    set_flash('error', 'Unable to create role due to a database error.');
    redirect('modules/roles/create.php');
}
