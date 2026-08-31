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

    // Fetch Customer Bookings (Phase 04 Integration)
    $bookingStmt = $pdo->prepare("
        SELECT 
            b.*, 
            p.name AS package_name, 
            p.package_code, 
            d.name AS destination_name
        FROM bookings b
        JOIN tour_packages p ON b.tour_package_id = p.id
        LEFT JOIN tour_destinations d ON p.destination_id = d.id
        WHERE b.customer_id = :cus_id AND b.deleted_at IS NULL
        ORDER BY b.id DESC
    ");
    $bookingStmt->execute(['cus_id' => $id]);
    $customerBookings = $bookingStmt->fetchAll();

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

// Calculate Financial Totals
$totalCustomerInvoiced = 0.0;
$totalCustomerPaid     = 0.0;
$totalCustomerDue      = 0.0;

foreach ($customerBookings as $cb) {
    if ($cb['booking_status'] !== 'cancelled') {
        $totalCustomerInvoiced += (float)$cb['total_amount'];
        $totalCustomerPaid     += (float)$cb['paid_amount'];
        $totalCustomerDue      += (float)$cb['due_amount'];
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
                <?php if (!$isDeleted): ?>
                    <?php if (has_permission('customers.edit')): ?>
                        <a href="<?= url('modules/customers/edit.php?id=' . $customer['id']); ?>" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i> Edit Profile
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Profile Card & Quick Info (Col-4) -->
            <div class="col-12 col-lg-4">
                <!-- Profile Avatar & Key Stats Card -->
                <div class="admin-card mb-4 text-center">
                    <div class="admin-card-body p-4">
                        <div class="mb-3">
                            <?php if ($avatarUrl): ?>
                                <img src="<?= e($avatarUrl); ?>" alt="<?= e($customer['name']); ?>" class="rounded-circle border p-1" style="width: 110px; height: 110px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center fw-bold fs-2" style="width: 110px; height: 110px;">
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

                <!-- Financial Summary Card (Phase 05 Integration) -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h4 class="admin-card-title">
                            <i class="bi bi-wallet2 me-2 text-primary"></i> Payment Summary
                        </h4>
                    </div>
                    <div class="admin-card-body">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Total Reservations:</span>
                                <span class="fw-semibold text-dark"><?= count($customerBookings); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Total Invoiced:</span>
                                <strong class="text-dark"><?= format_currency($totalCustomerInvoiced); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Total Collected:</span>
                                <strong class="text-success"><?= format_currency($totalCustomerPaid); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Total Due:</span>
                                <strong class="<?= $totalCustomerDue > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?= format_currency($totalCustomerDue); ?>
                                </strong>
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

                <!-- Customer Booking History (Phase 04 Integration) -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h4 class="admin-card-title">
                            <i class="bi bi-calendar-check me-2 text-primary"></i> Customer Booking History
                        </h4>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary"><?= count($customerBookings); ?> Bookings</span>
                            <?php if (has_permission('bookings.create') && !$isDeleted): ?>
                                <a href="<?= url('modules/bookings/create.php?customer_id=' . $customer['id']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-circle me-1"></i> New Booking
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="admin-card-body p-0">
                        <?php if (!empty($customerBookings)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Booking #</th>
                                            <th>Tour Package</th>
                                            <th>Travel Date</th>
                                            <th class="text-center">Pax</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th class="pe-3 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customerBookings as $cb): 
                                            $cbPax = (int)$cb['adults'] + (int)$cb['children'] + (int)$cb['infants'];

                                            $cbStatusClass = 'bg-secondary';
                                            if ($cb['booking_status'] === 'pending') $cbStatusClass = 'bg-warning text-dark';
                                            elseif ($cb['booking_status'] === 'confirmed') $cbStatusClass = 'bg-primary';
                                            elseif ($cb['booking_status'] === 'completed') $cbStatusClass = 'bg-success';
                                            elseif ($cb['booking_status'] === 'cancelled') $cbStatusClass = 'bg-danger';
                                        ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <a href="<?= url('modules/bookings/view.php?id=' . $cb['id']); ?>" class="fw-bold text-decoration-none">
                                                        <code><?= e($cb['booking_number']); ?></code>
                                                    </a>
                                                    <div class="text-muted" style="font-size: 0.7rem;"><?= format_date($cb['created_at'], 'M d, Y'); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($cb['package_name']); ?></div>
                                                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;"><?= e($cb['destination_name'] ?? '—'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="text-dark small fw-semibold"><?= format_date($cb['travel_date'], 'M d, Y'); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border"><?= $cbPax; ?> Pax</span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark small"><?= format_currency($cb['total_amount']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $cbStatusClass; ?>" style="font-size: 0.7rem;">
                                                        <?= ucfirst(e($cb['booking_status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="pe-3 text-end">
                                                    <a href="<?= url('modules/bookings/view.php?id=' . $cb['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Reservation Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                                <h5 class="fs-6 fw-bold text-dark mb-1">No reservations found for this customer.</h5>
                                <p class="small text-muted mb-3">Create a new tour booking reservation for this client.</p>
                                <?php if (has_permission('bookings.create') && !$isDeleted): ?>
                                    <a href="<?= url('modules/bookings/create.php?customer_id=' . $customer['id']); ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-calendar-plus me-1"></i> Create Booking
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
