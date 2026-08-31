<?php
/**
 * Customer Profile Details View
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('customers.view');

$canEdit   = has_permission('customers.edit');
$canDelete = has_permission('customers.delete');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Customer profile not found.');
    redirect('modules/customers/index.php');
}

$customer = null;

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash('error', 'Customer does not exist.');
        redirect('modules/customers/index.php');
    }
} catch (PDOException $e) {
    error_log('Customer View Error: ' . $e->getMessage());
    set_flash('error', 'Failed to load customer profile.');
    redirect('modules/customers/index.php');
}

$pageTitle = $customer['name'] . ' (' . $customer['customer_code'] . ')';
$avatarUrl = get_customer_avatar_url($customer['profile_photo'] ?? null);
$initials = get_customer_initials($customer['name']);
$isDeleted = !empty($customer['deleted_at']);

// Calculate age if DOB exists
$ageText = '';
if (!empty($customer['date_of_birth'])) {
    try {
        $dob = new DateTime($customer['date_of_birth']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        $ageText = ' (' . $age . ' years)';
    } catch (Exception $e) {
        $ageText = '';
    }
}

// Passport expiry status
$passportStatusBadge = '';
if (!empty($customer['passport_expiry'])) {
    $expiryDate = strtotime($customer['passport_expiry']);
    $today = time();
    if ($expiryDate < $today) {
        $passportStatusBadge = '<span class="badge bg-danger ms-1">Expired</span>';
    } elseif ($expiryDate < ($today + (180 * 86400))) {
        $passportStatusBadge = '<span class="badge bg-warning text-dark ms-1">Expiring Soon</span>';
    } else {
        $passportStatusBadge = '<span class="badge bg-success ms-1">Valid</span>';
    }
}

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Page Header -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-secondary"><code><?= e($customer['customer_code']); ?></code></span>
                    <?php if ($isDeleted): ?>
                        <span class="badge bg-danger">Archived / Soft-Deleted</span>
                    <?php else: ?>
                        <span class="badge <?= $customer['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                            <?= ucfirst(e($customer['status'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h2 class="fs-4 fw-bold text-dark mb-0"><?= e($customer['name']); ?></h2>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
                <?php if (!$isDeleted && $canEdit): ?>
                    <a href="<?= url('modules/customers/edit.php?id=' . $customer['id']); ?>" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> Edit Profile
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Profile Card & Quick Info (Col-4) -->
            <div class="col-12 col-lg-4">
                <!-- Customer Hero Card -->
                <div class="admin-card mb-4 text-center">
                    <div class="admin-card-body p-4">
                        <div class="mb-3">
                            <?php if ($avatarUrl): ?>
                                <img src="<?= e($avatarUrl); ?>" alt="<?= e($customer['name']); ?>" class="rounded-circle border" style="width: 110px; height: 110px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-light text-primary border d-flex align-items-center justify-content-center fw-bold mx-auto" style="width: 110px; height: 110px; font-size: 2.25rem;">
                                    <?= e($initials); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3 class="fs-5 fw-bold text-dark mb-1"><?= e($customer['name']); ?></h3>
                        <p class="text-muted small mb-3"><?= e($customer['customer_code']); ?></p>

                        <div class="d-grid gap-2">
                            <a href="tel:<?= e($customer['phone']); ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-telephone me-1"></i> Call <?= e($customer['phone']); ?>
                            </a>
                            <?php if (!empty($customer['email'])): ?>
                                <a href="mailto:<?= e($customer['email']); ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-envelope me-1"></i> Email Client
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contact & Account Meta Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h4 class="admin-card-title">
                            <i class="bi bi-info-circle me-2 text-primary"></i> Contact & Meta
                        </h4>
                    </div>
                    <div class="admin-card-body">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Primary Phone:</span>
                                <span class="fw-semibold text-dark"><?= e($customer['phone']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Alternate Phone:</span>
                                <span class="fw-semibold text-dark"><?= e($customer['alternate_phone'] ?: '—'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Email:</span>
                                <span class="fw-semibold text-dark"><?= e($customer['email'] ?: '—'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Registered On:</span>
                                <span class="fw-semibold text-dark"><?= format_date($customer['created_at']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Last Updated:</span>
                                <span class="fw-semibold text-dark"><?= format_date($customer['updated_at']); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column: Details, Travel Docs & Booking History (Col-8) -->
            <div class="col-12 col-lg-8">
                <!-- Personal & Address Info Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h4 class="admin-card-title">
                            <i class="bi bi-person-lines-fill me-2 text-primary"></i> Personal & Address Details
                        </h4>
                    </div>
                    <div class="admin-card-body">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted small d-block">Gender:</span>
                                <strong class="text-dark"><?= $customer['gender'] ? ucfirst(e($customer['gender'])) : '—'; ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted small d-block">Date of Birth:</span>
                                <strong class="text-dark"><?= !empty($customer['date_of_birth']) ? format_date($customer['date_of_birth'], 'F d, Y') . e($ageText) : '—'; ?></strong>
                            </div>
                            <div class="col-12">
                                <hr class="my-2">
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted small d-block">Street Address:</span>
                                <strong class="text-dark"><?= e($customer['address'] ?: '—'); ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted small d-block">City & Postal Code:</span>
                                <strong class="text-dark"><?= e($customer['city'] ?: '—'); ?><?= !empty($customer['postal_code']) ? ' - ' . e($customer['postal_code']) : ''; ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted small d-block">State / Division:</span>
                                <strong class="text-dark"><?= e($customer['state'] ?: '—'); ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted small d-block">Country:</span>
                                <strong class="text-dark"><?= e($customer['country'] ?: 'Bangladesh'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Travel Documents & Identification Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h4 class="admin-card-title">
                            <i class="bi bi-passport me-2 text-primary"></i> Travel Documents & Credentials
                        </h4>
                    </div>
                    <div class="admin-card-body">
                        <div class="row g-3">
                            <!-- Passport Number -->
                            <div class="col-12 col-sm-4">
                                <span class="text-muted small d-block">Passport Number:</span>
                                <?php if (!empty($customer['passport_number'])): ?>
                                    <code class="fs-6 fw-bold"><?= e($customer['passport_number']); ?></code>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>

                            <!-- Passport Expiry -->
                            <div class="col-12 col-sm-4">
                                <span class="text-muted small d-block">Passport Expiry:</span>
                                <?php if (!empty($customer['passport_expiry'])): ?>
                                    <strong class="text-dark"><?= format_date($customer['passport_expiry'], 'M d, Y'); ?></strong>
                                    <?= $passportStatusBadge; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>

                            <!-- National ID -->
                            <div class="col-12 col-sm-4">
                                <span class="text-muted small d-block">National ID / Smart Card:</span>
                                <?php if (!empty($customer['national_id'])): ?>
                                    <code class="fs-6 fw-bold"><?= e($customer['national_id']); ?></code>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Special Notes / CRM Card -->
                <?php if (!empty($customer['notes'])): ?>
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h4 class="admin-card-title">
                                <i class="bi bi-journal-text me-2 text-primary"></i> Internal Notes & Preferences
                            </h4>
                        </div>
                        <div class="admin-card-body">
                            <p class="text-muted mb-0 small" style="line-height: 1.6; white-space: pre-line;"><?= e($customer['notes']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Booking History (Phase 04 Placeholder) -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h4 class="admin-card-title">
                            <i class="bi bi-calendar-check me-2 text-primary"></i> Booking History
                        </h4>
                        <span class="badge bg-secondary">0 Bookings</span>
                    </div>
                    <div class="admin-card-body text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                            <h5 class="fs-6 fw-bold text-dark mb-1">No bookings found for this customer.</h5>
                            <p class="small text-muted mb-0">Booking history will appear here once tour bookings are created in Phase 04.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
