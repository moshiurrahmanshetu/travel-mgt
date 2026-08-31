<?php
/**
 * User Profile Page
 * Tour & Travel Booking Management System
 */

$pageTitle = 'My Profile';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

$avatarUrl = get_avatar_url($currentUser['avatar'] ?? null);
$initials = get_user_initials($currentUser['name'] ?? '');
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <div class="row g-4">
            <!-- Left Column: Avatar & Account Summary -->
            <div class="col-12 col-lg-4">
                <!-- Avatar Upload Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-image me-2 text-primary"></i> Profile Avatar
                        </h3>
                    </div>
                    <div class="admin-card-body text-center">
                        <div class="mb-3 position-relative d-inline-block">
                            <img 
                                src="<?= $avatarUrl ? e($avatarUrl) : ''; ?>" 
                                alt="<?= e($currentUser['name']); ?>" 
                                id="avatar-preview-img" 
                                class="avatar-circle-lg <?= $avatarUrl ? '' : 'd-none'; ?>"
                            >
                            <span 
                                id="avatar-preview-fallback" 
                                class="avatar-circle-lg <?= $avatarUrl ? 'd-none' : ''; ?>"
                            >
                                <?= e($initials); ?>
                            </span>
                        </div>

                        <h4 class="fs-6 fw-bold mb-0 text-dark"><?= e($currentUser['name']); ?></h4>
                        <span class="badge bg-secondary mt-1 mb-3"><?= e($currentUser['role_name']); ?></span>

                        <!-- Upload Avatar Form -->
                        <form action="<?= url('modules/profile/upload-avatar.php'); ?>" method="POST" enctype="multipart/form-data" class="text-start">
                            <?= csrf_field(); ?>

                            <div class="mb-3">
                                <label for="avatar-input" class="form-label">Upload New Avatar</label>
                                <input 
                                    type="file" 
                                    class="form-control form-control-sm" 
                                    id="avatar-input" 
                                    name="avatar" 
                                    accept="image/jpeg,image/png,image/webp" 
                                    required
                                >
                                <div class="form-text small text-muted">
                                    Allowed: JPG, PNG, WEBP. Max size: 2MB.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-cloud-arrow-up me-1"></i> Upload Avatar
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Quick Actions Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-shield-check me-2 text-primary"></i> Security
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <p class="small text-muted mb-3">
                            Keep your account safe by updating your password regularly.
                        </p>
                        <a href="<?= url('modules/profile/change-password.php'); ?>" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-key me-1"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Information Form -->
            <div class="col-12 col-lg-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-person-lines-fill me-2 text-primary"></i> Personal Information
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <form action="<?= url('modules/profile/update.php'); ?>" method="POST" autocomplete="off">
                            <?= csrf_field(); ?>

                            <div class="row g-3 mb-3">
                                <!-- First Name -->
                                <div class="col-12 col-sm-6">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="first_name" 
                                        name="first_name" 
                                        value="<?= e($currentUser['first_name']); ?>" 
                                        required
                                    >
                                </div>

                                <!-- Last Name -->
                                <div class="col-12 col-sm-6">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="last_name" 
                                        name="last_name" 
                                        value="<?= e($currentUser['last_name']); ?>" 
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Display Full Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Display Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="name" 
                                    name="name" 
                                    value="<?= e($currentUser['name']); ?>" 
                                    required
                                >
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Email Address -->
                                <div class="col-12 col-sm-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="email" 
                                        name="email" 
                                        value="<?= e($currentUser['email']); ?>" 
                                        required
                                    >
                                </div>

                                <!-- Phone Number -->
                                <div class="col-12 col-sm-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="phone" 
                                        name="phone" 
                                        placeholder="+880 1700-000000" 
                                        value="<?= e($currentUser['phone']); ?>"
                                    >
                                </div>
                            </div>

                            <!-- Assigned Role (Read-Only) -->
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label">Assigned Role</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        value="<?= e($currentUser['role_name']); ?>" 
                                        disabled 
                                        readonly
                                    >
                                    <div class="form-text small text-muted">Role permissions can only be modified by a super administrator.</div>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label class="form-label">Account Created</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        value="<?= format_date($currentUser['created_at']); ?>" 
                                        disabled 
                                        readonly
                                    >
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
