<?php
/**
 * Admin Dashboard Module
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Dashboard Overview';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Welcome Banner Card -->
        <div class="welcome-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h2 class="fs-4 fw-bold mb-0 text-dark">Welcome back, <?= e($currentUser['name']); ?>!</h2>
                        <span class="badge bg-primary px-2 py-1" style="font-size: 0.75rem;"><?= e($currentUser['role_name']); ?></span>
                    </div>
                    <p class="text-muted mb-0 small">
                        Here is an overview of the Tour & Travel Management System.
                    </p>
                </div>
                <div class="text-md-end text-muted small">
                    <div><i class="bi bi-calendar-event me-1"></i> <?= date('l, F d, Y'); ?></div>
                    <div class="mt-1"><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> System Operational</span></div>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards (Phase 01 Placeholders) -->
        <div class="row g-3 mb-4">
            <!-- Total Tour Packages -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Tour Packages</div>
                        <div class="kpi-value">0</div>
                    </div>
                </div>
            </div>

            <!-- Total Customers -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-success">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Total Customers</div>
                        <div class="kpi-value">0</div>
                    </div>
                </div>
            </div>

            <!-- Total Bookings -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-warning">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Total Bookings</div>
                        <div class="kpi-value">0</div>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-info">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Total Revenue</div>
                        <div class="kpi-value"><?= e(APP_CURRENCY_SYMBOL); ?>0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status & Quick Actions -->
        <div class="row g-3">
            <!-- Left Column: System Information -->
            <div class="col-12 col-lg-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-info-square me-2 text-primary"></i> Phase 01: System Foundation
                        </h3>
                        <span class="badge bg-secondary">Phase 01 Active</span>
                    </div>
                    <div class="admin-card-body">
                        <p class="text-muted mb-3">
                            The Tour & Travel Booking Management System core foundation and authentication engine is fully configured. 
                            Core features implemented in this phase:
                        </p>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light d-flex align-items-center gap-2">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">Pure Raw PHP Architecture</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light d-flex align-items-center gap-2">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">MySQL PDO Connection & Migrations</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light d-flex align-items-center gap-2">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">CSRF & Secure Session Engine</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light d-flex align-items-center gap-2">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">Role & Permission Foundation</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light d-flex align-items-center gap-2">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">Collapsible Sidebar & Tooltips</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light d-flex align-items-center gap-2">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">Profile Management & Avatar Upload</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Account Quick Summary -->
            <div class="col-12 col-lg-4">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-person-badge me-2 text-primary"></i> Account Summary
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="text-center mb-3">
                            <?php 
                            $avatarUrl = get_avatar_url($currentUser['avatar'] ?? null);
                            $initials = get_user_initials($currentUser['name'] ?? '');
                            ?>
                            <?php if ($avatarUrl): ?>
                                <img src="<?= e($avatarUrl); ?>" alt="<?= e($currentUser['name']); ?>" class="avatar-circle-lg mb-2">
                            <?php else: ?>
                                <span class="avatar-circle-lg mb-2"><?= e($initials); ?></span>
                            <?php endif; ?>
                            <h4 class="fs-6 fw-bold mb-0 text-dark"><?= e($currentUser['name']); ?></h4>
                            <span class="badge bg-secondary mt-1"><?= e($currentUser['role_name']); ?></span>
                        </div>

                        <ul class="list-group list-group-flush small mb-3">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Email:</span>
                                <span class="fw-semibold"><?= e($currentUser['email']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Phone:</span>
                                <span class="fw-semibold"><?= e($currentUser['phone'] ?: '—'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Last Login:</span>
                                <span class="fw-semibold"><?= format_date($currentUser['last_login']); ?></span>
                            </li>
                        </ul>

                        <div class="d-grid gap-2">
                            <a href="<?= url('modules/profile/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil-square me-1"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
