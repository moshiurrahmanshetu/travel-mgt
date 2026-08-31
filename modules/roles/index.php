<?php
/**
 * Role & Permission Management Directory
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Roles & Permissions';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('roles.view');

$canCreate = has_permission('roles.create');
$canEdit   = has_permission('roles.edit');
$canDelete = has_permission('roles.delete');

$roles = [];
try {
    $pdo = get_db_connection();

    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.name,
            r.slug,
            r.description,
            r.is_system,
            r.created_at,
            COUNT(DISTINCT u.id) AS active_user_count,
            COUNT(DISTINCT rp.permission_id) AS permission_count
        FROM roles r
        LEFT JOIN users u ON u.role_id = r.id AND u.deleted_at IS NULL AND u.status = 'active'
        LEFT JOIN role_permissions rp ON rp.role_id = r.id
        GROUP BY r.id, r.name, r.slug, r.description, r.is_system, r.created_at
        ORDER BY r.id ASC
    ");
    $roles = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Roles list error: ' . $e->getMessage());
}
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= url('modules/dashboard/index.php'); ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('modules/users/index.php'); ?>">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Roles & Permissions</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">System Roles & Permission Mappings</h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('modules/users/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-people me-1"></i> System Users
                </a>
                <?php if ($canCreate): ?>
                    <a href="<?= url('modules/roles/create.php'); ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Create Custom Role
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Roles Table Card -->
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h3 class="admin-card-title"><i class="bi bi-shield-lock me-2 text-primary"></i> Defined Access Roles</h3>
                <span class="badge bg-secondary"><?= count($roles); ?> Roles</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Role Name</th>
                                <th>Identifier Slug</th>
                                <th>Description</th>
                                <th class="text-center">Assigned Permissions</th>
                                <th class="text-center">Active Users</th>
                                <th>Role Type</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $r): 
                                    $isSystemRole = ((int)($r['is_system'] ?? 0) === 1 || in_array($r['slug'], ['administrator', 'manager', 'staff'], true));
                                    $hasActiveUsers = ((int)$r['active_user_count'] > 0);
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark fs-6"><?= e($r['name']); ?></div>
                                        </td>
                                        <td>
                                            <code><?= e($r['slug']); ?></code>
                                        </td>
                                        <td>
                                            <span class="small text-muted"><?= e($r['description'] ?? '—'); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">
                                                <i class="bi bi-key me-1"></i> <?= (int)$r['permission_count']; ?> Granted
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= url('modules/users/index.php?role_id=' . $r['id']); ?>" class="badge bg-light text-dark border text-decoration-none" title="View Assigned Users">
                                                <i class="bi bi-people me-1"></i> <?= (int)$r['active_user_count']; ?> Users
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($isSystemRole): ?>
                                                <span class="badge bg-dark"><i class="bi bi-lock-fill me-1"></i> Core System</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Custom Role</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($canEdit): ?>
                                                    <a href="<?= url('modules/roles/edit.php?id=' . $r['id']); ?>" class="btn btn-outline-secondary" title="Edit Permissions & Role">
                                                        <i class="bi bi-sliders me-1"></i> Permissions
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($canDelete && !$isSystemRole): ?>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteRoleModal<?= (int)$r['id']; ?>"
                                                        title="Delete Custom Role"
                                                        <?= $hasActiveUsers ? 'disabled' : ''; ?>
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Delete Role Modal (Custom Roles only) -->
                                            <?php if ($canDelete && !$isSystemRole && !$hasActiveUsers): ?>
                                                <div class="modal fade" id="deleteRoleModal<?= (int)$r['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered text-start">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fs-6 fw-bold text-dark">
                                                                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Delete Custom Role
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="mb-1">Are you sure you want to delete the custom role <strong><?= e($r['name']); ?></strong>?</p>
                                                                <small class="text-muted">All associated permission mappings for this role will also be removed.</small>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                <form action="<?= url('modules/roles/delete.php'); ?>" method="POST" class="d-inline">
                                                                    <?= csrf_field(); ?>
                                                                    <input type="hidden" name="role_id" value="<?= (int)$r['id']; ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                                        <i class="bi bi-trash me-1"></i> Delete Role
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No roles found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
