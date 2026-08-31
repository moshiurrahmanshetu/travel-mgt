<?php
/**
 * Create New User Account
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Add New User';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('users.create');

$currentUser = current_user();
$isCurrentUserAdmin = ($currentUser['role_slug'] === 'administrator');

$roles = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, name, slug, description FROM roles ORDER BY id ASC");
    $allRoles = $stmt->fetchAll();

    // Anti-Escalation: Non-administrators cannot assign Administrator role
    foreach ($allRoles as $r) {
        if (!$isCurrentUserAdmin && $r['slug'] === 'administrator') {
            continue;
        }
        $roles[] = $r;
    }
} catch (PDOException $e) {
    error_log('Load roles error: ' . $e->getMessage());
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
                        <li class="breadcrumb-item active" aria-current="page">Add User</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Create New System User</h2>
            </div>
            <div>
                <a href="<?= url('modules/users/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to User List
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-person-plus me-2 text-primary"></i> Account Details & Role Assignment
                        </h3>
                    </div>
                    <div class="admin-card-body p-4">
                        <form action="<?= url('modules/users/store.php'); ?>" method="POST">
                            <?= csrf_field(); ?>

                            <div class="row g-3 mb-3">
                                <!-- First Name -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="first_name" 
                                        required 
                                        placeholder="e.g. Moshiur"
                                        value="<?= e(old('first_name')); ?>"
                                    >
                                </div>

                                <!-- Last Name -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="last_name" 
                                        required 
                                        placeholder="e.g. Rahman"
                                        value="<?= e(old('last_name')); ?>"
                                    >
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Email Address -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        name="email" 
                                        required 
                                        placeholder="user@example.com"
                                        value="<?= e(old('email')); ?>"
                                    >
                                    <small class="text-muted">Must be unique and active.</small>
                                </div>

                                <!-- Phone Number -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="phone" 
                                        placeholder="e.g. +880 1700-000000"
                                        value="<?= e(old('phone')); ?>"
                                    >
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Password (Never Repopulated) -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        name="password" 
                                        required 
                                        minlength="8" 
                                        placeholder="Minimum 8 characters"
                                    >
                                    <small class="text-muted">Minimum 8 characters with letters & numbers.</small>
                                </div>

                                <!-- Confirm Password (Never Repopulated) -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        name="confirm_password" 
                                        required 
                                        minlength="8" 
                                        placeholder="Retype password"
                                    >
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <!-- Role Selection -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Assigned Role <span class="text-danger">*</span></label>
                                    <select class="form-select" name="role_id" required>
                                        <option value="">-- Select System Role --</option>
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= (int)$r['id']; ?>" <?= (string)old('role_id') === (string)$r['id'] ? 'selected' : ''; ?>>
                                                <?= e($r['name']); ?> <?= !empty($r['description']) ? '— ' . e($r['description']) : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Account Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" required>
                                        <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : ''; ?>>Active (Can login)</option>
                                        <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : ''; ?>>Inactive (Suspended)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                <a href="<?= url('modules/users/index.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-person-check me-1"></i> Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Role Permissions Guidelines Card -->
            <div class="col-12 col-lg-4">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-shield-lock me-2 text-primary"></i> Role Guidelines
                        </h3>
                    </div>
                    <div class="admin-card-body p-3 small text-muted">
                        <p class="mb-2"><strong>Administrator:</strong> Has unrestricted operational and administrative access across all modules, reports, system settings, and user management.</p>
                        <p class="mb-2"><strong>Manager:</strong> Has full access to tours, customers, bookings, payments, and analytical reports.</p>
                        <p class="mb-0"><strong>Staff:</strong> Frontline reservation agent access to create bookings and customer records.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php 
clear_old_input();
require_once __DIR__ . '/../../includes/admin_footer.php'; 
?>
