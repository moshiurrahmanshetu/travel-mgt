<?php
/**
 * Edit User Account
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Edit User';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('users.edit');

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    set_flash('error', 'Invalid user ID.');
    redirect('modules/users/index.php');
}

$currentUser = current_user();
$isCurrentUserAdmin = ($currentUser['role_slug'] === 'administrator');

$user = null;
$roles = [];
$isLastAdmin = is_last_active_administrator($userId);

try {
    $pdo = get_db_connection();

    // Fetch user details
    $stmt = $pdo->prepare("
        SELECT u.*, r.slug AS role_slug, r.name AS role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = :id AND u.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('error', 'User account not found or has been deleted.');
        redirect('modules/users/index.php');
    }

    // Query roles
    $rolesStmt = $pdo->query("SELECT id, name, slug, description FROM roles ORDER BY id ASC");
    $allRoles = $rolesStmt->fetchAll();

    foreach ($allRoles as $r) {
        if (!$isCurrentUserAdmin && $r['slug'] === 'administrator') {
            continue;
        }
        $roles[] = $r;
    }

} catch (PDOException $e) {
    error_log('User edit load error: ' . $e->getMessage());
    set_flash('error', 'Unable to load user details.');
    redirect('modules/users/index.php');
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
                        <li class="breadcrumb-item active" aria-current="page">Edit User</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Edit User: <?= e($user['name']); ?></h2>
            </div>
            <div>
                <a href="<?= url('modules/users/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to User List
                </a>
            </div>
        </div>

        <?php if ($isLastAdmin): ?>
            <div class="alert alert-warning d-flex align-items-center shadow-sm mb-4" role="alert">
                <i class="bi bi-shield-lock-fill fs-5 me-2 flex-shrink-0"></i>
                <div>
                    <strong>Primary Administrator Protection:</strong> This user is the only active Administrator in the system. The Administrator role and active status cannot be removed to prevent system lockout.
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-pencil-square me-2 text-primary"></i> Account Profile & Permissions
                        </h3>
                    </div>
                    <div class="admin-card-body p-4">
                        <form action="<?= url('modules/users/update.php'); ?>" method="POST">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">

                            <div class="row g-3 mb-3">
                                <!-- First Name -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="first_name" 
                                        required 
                                        value="<?= e($user['first_name']); ?>"
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
                                        value="<?= e($user['last_name']); ?>"
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
                                        value="<?= e($user['email']); ?>"
                                    >
                                </div>

                                <!-- Phone Number -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="phone" 
                                        value="<?= e($user['phone'] ?? ''); ?>"
                                    >
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <!-- Role Selection -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Assigned Role <span class="text-danger">*</span></label>
                                    <?php if ($isLastAdmin): ?>
                                        <input type="hidden" name="role_id" value="<?= (int)$user['role_id']; ?>">
                                        <input type="text" class="form-control" value="Administrator (Protected)" disabled readonly>
                                        <small class="text-danger">Cannot change role of last active Administrator.</small>
                                    <?php else: ?>
                                        <select class="form-select" name="role_id" required>
                                            <?php foreach ($roles as $r): ?>
                                                <option value="<?= (int)$r['id']; ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : ''; ?>>
                                                    <?= e($r['name']); ?> <?= !empty($r['description']) ? '— ' . e($r['description']) : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>

                                <!-- Status -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Account Status <span class="text-danger">*</span></label>
                                    <?php if ($isLastAdmin): ?>
                                        <input type="hidden" name="status" value="active">
                                        <input type="text" class="form-control" value="Active (Protected)" disabled readonly>
                                        <small class="text-danger">Cannot deactivate last active Administrator.</small>
                                    <?php else: ?>
                                        <select class="form-select" name="status" required>
                                            <option value="active" <?= $user['status'] === 'active' ? 'selected' : ''; ?>>Active (Can login)</option>
                                            <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Suspended)</option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                <a href="<?= url('modules/users/index.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- User Meta Card -->
            <div class="col-12 col-lg-4">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-info-circle me-2 text-primary"></i> Account Metadata
                        </h3>
                    </div>
                    <div class="admin-card-body p-3">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Account ID:</span>
                                <code>#<?= (int)$user['id']; ?></code>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Registered On:</span>
                                <span><?= format_date($user['created_at']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Last Updated:</span>
                                <span><?= format_date($user['updated_at']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Last Login:</span>
                                <span><?= !empty($user['last_login']) ? format_date($user['last_login']) : 'Never'; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
