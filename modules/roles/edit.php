<?php
/**
 * Edit Role & Permission Matrix
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Edit Role & Permissions';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('roles.edit');

$roleId = (int)($_GET['id'] ?? 0);
if ($roleId <= 0) {
    set_flash('error', 'Invalid role ID.');
    redirect('modules/roles/index.php');
}

$role = null;
$permissions = [];
$assignedPermIds = [];
$groupedPermissions = [];

try {
    $pdo = get_db_connection();

    // Fetch Role
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $roleId]);
    $role = $stmt->fetch();

    if (!$role) {
        set_flash('error', 'Role not found.');
        redirect('modules/roles/index.php');
    }

    // Fetch Assigned Permission IDs FOR THIS ROLE ONLY (Cross-role isolation)
    $assignedStmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = :role_id");
    $assignedStmt->execute(['role_id' => $roleId]);
    $assignedPermIds = $assignedStmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch All Permissions
    $allStmt = $pdo->query("SELECT id, name, slug, description FROM permissions ORDER BY id ASC");
    $permissions = $allStmt->fetchAll();

    // Group permissions by module
    foreach ($permissions as $p) {
        $slug = $p['slug'];
        $parts = explode('.', $slug);
        $groupKey = $parts[0] ?? 'general';

        $groupNames = [
            'dashboard'    => 'Dashboard & Overview',
            'profile'      => 'User Profile',
            'password'     => 'Password Security',
            'users'        => 'User Account Management',
            'roles'        => 'Role Management',
            'permissions'  => 'Permission Management',
            'tours'        => 'Tour Packages',
            'categories'   => 'Tour Categories',
            'destinations' => 'Tour Destinations',
            'customers'    => 'Customer Management',
            'bookings'     => 'Booking Reservations',
            'payments'     => 'Payment Collections',
            'reports'      => 'Reports & Analytics',
            'settings'     => 'System Settings'
        ];

        $groupLabel = $groupNames[$groupKey] ?? ucfirst($groupKey) . ' Management';
        $groupedPermissions[$groupLabel][] = $p;
    }

} catch (PDOException $e) {
    error_log('Role edit load error: ' . $e->getMessage());
    set_flash('error', 'Unable to load role permissions.');
    redirect('modules/roles/index.php');
}

$isSystemRole = ((int)($role['is_system'] ?? 0) === 1 || in_array($role['slug'], ['administrator', 'manager', 'staff'], true));
$isAdminRole = ($role['slug'] === 'administrator');
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
                        <li class="breadcrumb-item"><a href="<?= url('modules/roles/index.php'); ?>">Roles</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Role</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Edit Role & Permissions: <?= e($role['name']); ?></h2>
            </div>
            <div>
                <a href="<?= url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Role List
                </a>
            </div>
        </div>

        <?php if ($isAdminRole): ?>
            <div class="alert alert-info d-flex align-items-center shadow-sm mb-4" role="alert">
                <i class="bi bi-shield-fill-check fs-5 me-2 flex-shrink-0"></i>
                <div>
                    <strong>Super Administrator Role:</strong> The Administrator role always possesses full operational control over all current and future modules.
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= url('modules/roles/update.php'); ?>" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="role_id" value="<?= (int)$role['id']; ?>">

            <!-- Role Details Card -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h3 class="admin-card-title"><i class="bi bi-sliders me-2 text-primary"></i> Role Details</h3>
                </div>
                <div class="admin-card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="name" 
                                required 
                                value="<?= e($role['name']); ?>"
                                <?= $isAdminRole ? 'readonly' : ''; ?>
                            >
                            <small class="text-muted">Slug: <code><?= e($role['slug']); ?></code></small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Description</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="description" 
                                value="<?= e($role['description'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permission Matrix Card -->
            <div class="admin-card mb-4">
                <div class="admin-card-header d-flex justify-content-between align-items-center">
                    <h3 class="admin-card-title">
                        <i class="bi bi-key me-2 text-primary"></i> Granted Permissions (<?= count($assignedPermIds); ?> Assigned)
                    </h3>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnCheckAll">
                            <i class="bi bi-check-all me-1"></i> Check All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnUncheckAll">
                            <i class="bi bi-x me-1"></i> Uncheck All
                        </button>
                    </div>
                </div>
                <div class="admin-card-body p-4">
                    <div class="row g-4">
                        <?php 
                        $groupIndex = 0;
                        foreach ($groupedPermissions as $groupTitle => $groupItems): 
                            $groupIndex++;
                        ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded p-3 h-100 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                        <h4 class="fs-6 fw-bold text-dark mb-0"><?= e($groupTitle); ?></h4>
                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 toggle-group-btn" data-group="grp_<?= $groupIndex; ?>" style="font-size: 0.75rem;">
                                            Toggle
                                        </button>
                                    </div>
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach ($groupItems as $item): 
                                            $isChecked = in_array((int)$item['id'], array_map('intval', $assignedPermIds), true);
                                        ?>
                                            <div class="form-check">
                                                <input 
                                                    class="form-check-input perm-checkbox grp_<?= $groupIndex; ?>" 
                                                    type="checkbox" 
                                                    name="permissions[]" 
                                                    value="<?= (int)$item['id']; ?>" 
                                                    id="perm_<?= (int)$item['id']; ?>"
                                                    <?= $isChecked ? 'checked' : ''; ?>
                                                >
                                                <label class="form-check-label small text-dark fw-semibold" for="perm_<?= (int)$item['id']; ?>">
                                                    <?= e($item['name']); ?>
                                                </label>
                                                <?php if (!empty($item['description'])): ?>
                                                    <div class="text-muted" style="font-size: 0.72rem; line-height: 1.2;"><?= e($item['description']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="<?= url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Save Role Permissions
                </button>
            </div>
        </form>
    </div>

    <!-- Toggle Checkboxes Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAllBtn = document.getElementById('btnCheckAll');
        const uncheckAllBtn = document.getElementById('btnUncheckAll');
        const allCheckboxes = document.querySelectorAll('.perm-checkbox');

        if (checkAllBtn) {
            checkAllBtn.addEventListener('click', function() {
                allCheckboxes.forEach(cb => cb.checked = true);
            });
        }

        if (uncheckAllBtn) {
            uncheckAllBtn.addEventListener('click', function() {
                allCheckboxes.forEach(cb => cb.checked = false);
            });
        }

        document.querySelectorAll('.toggle-group-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const grpClass = this.getAttribute('data-group');
                const grpCheckboxes = document.querySelectorAll('.' + grpClass);
                const allChecked = Array.from(grpCheckboxes).every(cb => cb.checked);
                grpCheckboxes.forEach(cb => cb.checked = !allChecked);
            });
        });
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
