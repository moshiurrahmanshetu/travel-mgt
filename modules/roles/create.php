<?php
/**
 * Create Custom System Role
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Create New Role';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('roles.create');

$permissions = [];
$groupedPermissions = [];

try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, name, slug, description FROM permissions ORDER BY id ASC");
    $permissions = $stmt->fetchAll();

    // Group permissions logically by prefix / module
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
    error_log('Load permissions error: ' . $e->getMessage());
}

$selectedPerms = old('permissions', []);
$selectedPermIds = is_array($selectedPerms) ? array_map('intval', $selectedPerms) : [];
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
                        <li class="breadcrumb-item active" aria-current="page">Create Role</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Create Custom System Role</h2>
            </div>
            <div>
                <a href="<?= url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Role List
                </a>
            </div>
        </div>

        <form action="<?= url('modules/roles/store.php'); ?>" method="POST">
            <?= csrf_field(); ?>

            <!-- Role Details Card -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h3 class="admin-card-title"><i class="bi bi-shield-plus me-2 text-primary"></i> Role Information</h3>
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
                                placeholder="e.g. Booking Coordinator" 
                                value="<?= e(old('name')); ?>"
                            >
                            <small class="text-muted">Must be descriptive and unique.</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Description</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="description" 
                                placeholder="e.g. Frontline staff responsible for managing bookings and client payments" 
                                value="<?= e(old('description')); ?>"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permission Matrix Card -->
            <div class="admin-card mb-4">
                <div class="admin-card-header d-flex justify-content-between align-items-center">
                    <h3 class="admin-card-title">
                        <i class="bi bi-key me-2 text-primary"></i> Assign Permissions
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
                                            $isChecked = in_array((int)$item['id'], $selectedPermIds, true);
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
                    <i class="bi bi-save me-1"></i> Save Custom Role
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

<?php 
clear_old_input();
require_once __DIR__ . '/../../includes/admin_footer.php'; 
?>
