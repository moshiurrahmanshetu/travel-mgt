<?php
/**
 * System Settings Foundation Module
 * Tour & Travel Booking Management System
 */

$pageTitle = 'System Settings';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-sliders me-2 text-primary"></i> Application Configuration (Read-Only)
                        </h3>
                        <span class="badge bg-secondary">System Settings</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Application Name</label>
                                <input type="text" class="form-control" value="<?= e(APP_NAME); ?>" disabled readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Short Name</label>
                                <input type="text" class="form-control" value="<?= e(APP_SHORT_NAME); ?>" disabled readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">System Version</label>
                                <input type="text" class="form-control" value="<?= e(APP_VERSION); ?>" disabled readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Environment</label>
                                <input type="text" class="form-control" value="<?= e(APP_ENV); ?>" disabled readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Default Timezone</label>
                                <input type="text" class="form-control" value="<?= e(APP_TIMEZONE); ?>" disabled readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" value="<?= e(APP_CURRENCY_SYMBOL) . ' (' . e(APP_CURRENCY) . ')'; ?>" disabled readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Database Name</label>
                                <input type="text" class="form-control" value="<?= e(DB_NAME); ?>" disabled readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Upload Base Path</label>
                                <input type="text" class="form-control" value="<?= e(UPLOAD_PATH); ?>" disabled readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-hdd-network me-2 text-primary"></i> Server Environment
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">PHP Version:</span>
                                <span class="fw-semibold"><?= PHP_VERSION; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">PDO Driver:</span>
                                <span class="fw-semibold">MySQL (InnoDB)</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Web Server:</span>
                                <span class="fw-semibold"><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Session Status:</span>
                                <span class="badge bg-success">Active</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
