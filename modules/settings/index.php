<?php
/**
 * System Settings & Configuration Module
 * Tour & Travel Booking Management System
 */

$pageTitle = 'System Settings';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('settings.view');

$canEdit = has_permission('settings.edit');
$settings = get_all_settings();
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
                        <li class="breadcrumb-item active" aria-current="page">System Settings</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">System Configuration & Company Profile</h2>
            </div>
            <?php if (!$canEdit): ?>
                <div>
                    <span class="badge bg-secondary"><i class="bi bi-eye me-1"></i> Read-Only View</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <!-- Settings Form Column -->
            <div class="col-12 col-lg-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-sliders me-2 text-primary"></i> Organization Profile & Regional Settings
                        </h3>
                    </div>
                    <div class="admin-card-body p-4">
                        <form action="<?= url('modules/settings/update.php'); ?>" method="POST">
                            <?= csrf_field(); ?>

                            <h5 class="fs-6 fw-bold text-dark mb-3 pb-2 border-bottom">Company Information</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Company / Agency Name <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="company_name" 
                                        required 
                                        value="<?= e($settings['company_name'] ?? 'GlobeTrek Travels & Tours'); ?>"
                                        <?= !$canEdit ? 'disabled readonly' : ''; ?>
                                    >
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Official Email <span class="text-danger">*</span></label>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        name="company_email" 
                                        required 
                                        value="<?= e($settings['company_email'] ?? 'info@globetrektravels.com'); ?>"
                                        <?= !$canEdit ? 'disabled readonly' : ''; ?>
                                    >
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Phone / Hotline</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="company_phone" 
                                        value="<?= e($settings['company_phone'] ?? '+880 1700-000000'); ?>"
                                        <?= !$canEdit ? 'disabled readonly' : ''; ?>
                                    >
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Official Website</label>
                                    <input 
                                        type="url" 
                                        class="form-control" 
                                        name="company_website" 
                                        value="<?= e($settings['company_website'] ?? 'https://www.globetrektravels.com'); ?>"
                                        <?= !$canEdit ? 'disabled readonly' : ''; ?>
                                    >
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Head Office Address</label>
                                    <textarea 
                                        class="form-control" 
                                        name="company_address" 
                                        rows="2"
                                        <?= !$canEdit ? 'disabled readonly' : ''; ?>
                                    ><?= e($settings['company_address'] ?? 'Level 4, Plot 12, Gulshan-2, Dhaka-1212, Bangladesh'); ?></textarea>
                                </div>
                            </div>

                            <h5 class="fs-6 fw-bold text-dark mb-3 pb-2 border-bottom">Localization & Currency</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold">Currency Code <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="currency" 
                                        required 
                                        placeholder="e.g. BDT" 
                                        value="<?= e($settings['currency'] ?? 'BDT'); ?>"
                                        <?= !$canEdit ? 'disabled readonly' : ''; ?>
                                    >
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold">Currency Symbol <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="currency_symbol" 
                                        required 
                                        placeholder="e.g. ৳" 
                                        value="<?= e($settings['currency_symbol'] ?? '৳'); ?>"
                                        <?= !$canEdit ? 'disabled readonly' : ''; ?>
                                    >
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold">Timezone <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="timezone" 
                                        required 
                                        value="<?= e($settings['timezone'] ?? 'Asia/Dhaka'); ?>"
                                        <?= !$canEdit ? 'disabled readonly' : ''; ?>
                                    >
                                </div>
                            </div>

                            <?php if ($canEdit): ?>
                                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-save me-1"></i> Save Settings
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Server Environment Diagnostics Column -->
            <div class="col-12 col-lg-4">
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-hdd-network me-2 text-primary"></i> Server Diagnostics
                        </h3>
                    </div>
                    <div class="admin-card-body p-0">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between p-3">
                                <span class="text-muted">Application Version:</span>
                                <span class="fw-semibold"><?= e(APP_VERSION); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between p-3">
                                <span class="text-muted">PHP Version:</span>
                                <span class="fw-semibold"><?= PHP_VERSION; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between p-3">
                                <span class="text-muted">Database Engine:</span>
                                <span class="fw-semibold">MySQL (InnoDB)</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between p-3">
                                <span class="text-muted">Web Server:</span>
                                <span class="fw-semibold"><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between p-3">
                                <span class="text-muted">Database Name:</span>
                                <code><?= e(DB_NAME); ?></code>
                            </li>
                            <li class="list-group-item d-flex justify-content-between p-3">
                                <span class="text-muted">Upload Storage:</span>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Writable</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
