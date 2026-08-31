<?php
/**
 * View Booking Details Voucher
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Booking Reservation Details';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('bookings.view');

$canEdit     = has_permission('bookings.edit');
$canCancel   = has_permission('bookings.cancel');
$canConfirm  = has_permission('bookings.confirm');
$canComplete = has_permission('bookings.complete');
$canCreatePayment = has_permission('payments.create');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid booking identifier.');
    redirect('modules/bookings/index.php');
}

$booking = null;
$capacityInfo = null;
$payments = [];

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            c.id AS customer_id,
            c.customer_code,
            c.name AS customer_name,
            c.email AS customer_email,
            c.phone AS customer_phone,
            c.address AS customer_address,
            c.city AS customer_city,
            c.country AS customer_country,
            c.passport_number,
            c.passport_expiry,
            c.national_id,
            c.profile_photo AS customer_photo,
            p.id AS package_id,
            p.package_code,
            p.name AS package_name,
            p.duration_days,
            p.duration_nights,
            p.available_seats,
            p.departure_location,
            p.hotel_information,
            p.transportation,
            d.name AS destination_name,
            u.name AS creator_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages p ON b.tour_package_id = p.id
        LEFT JOIN tour_destinations d ON p.destination_id = d.id
        LEFT JOIN users u ON b.created_by = u.id
        WHERE b.id = :id AND b.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        set_flash('error', 'Booking record not found.');
        redirect('modules/bookings/index.php');
    }

    // Fetch Payment Transactions for this Booking
    $stmtPay = $pdo->prepare("
        SELECT p.*, u.name AS collector_name
        FROM payments p
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.booking_id = :b_id AND p.deleted_at IS NULL
        ORDER BY p.id DESC
    ");
    $stmtPay->execute(['b_id' => $id]);
    $payments = $stmtPay->fetchAll();

    // Capacity Check for Tour Package
    $totalPax = (int)$booking['adults'] + (int)$booking['children'] + (int)$booking['infants'];
    $capacityInfo = check_tour_capacity((int)$booking['tour_package_id'], $totalPax, $id);

} catch (PDOException $e) {
    error_log('Booking View Error: ' . $e->getMessage());
    set_flash('error', 'Failed to retrieve booking details.');
    redirect('modules/bookings/index.php');
}

$totalTravellers = (int)$booking['adults'] + (int)$booking['children'] + (int)$booking['infants'];

// Status Classes
$bStatusClass = 'bg-secondary';
if ($booking['booking_status'] === 'pending') $bStatusClass = 'bg-warning text-dark';
elseif ($booking['booking_status'] === 'confirmed') $bStatusClass = 'bg-primary';
elseif ($booking['booking_status'] === 'completed') $bStatusClass = 'bg-success';
elseif ($booking['booking_status'] === 'cancelled') $bStatusClass = 'bg-danger';

$pStatusClass = 'bg-secondary';
if ($booking['payment_status'] === 'unpaid') $pStatusClass = 'bg-danger-subtle text-danger border border-danger-subtle';
elseif ($booking['payment_status'] === 'partial') $pStatusClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
elseif ($booking['payment_status'] === 'paid') $pStatusClass = 'bg-success-subtle text-success border border-success-subtle';
elseif ($booking['payment_status'] === 'refunded') $pStatusClass = 'bg-secondary-subtle text-secondary border';
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Top Header & Action Buttons -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= url('modules/bookings/index.php'); ?>">Bookings</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= e($booking['booking_number']); ?></li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="fs-4 fw-bold text-dark mb-0"><?= e($booking['booking_number']); ?></h2>
                    <span class="badge <?= $bStatusClass; ?> px-2 py-1"><?= ucfirst(e($booking['booking_status'])); ?></span>
                    <span class="badge <?= $pStatusClass; ?> px-2 py-1"><?= ucfirst(e($booking['payment_status'])); ?></span>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('modules/bookings/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                <?php if ($booking['booking_status'] === 'pending' && $canConfirm): ?>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmBookingModal">
                        <i class="bi bi-check2-circle me-1"></i> Confirm Booking
                    </button>
                <?php endif; ?>

                <?php if ($booking['booking_status'] === 'confirmed' && $canComplete): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#completeBookingModal">
                        <i class="bi bi-patch-check me-1"></i> Mark as Completed
                    </button>
                <?php endif; ?>

                <?php if ($booking['booking_status'] !== 'cancelled' && $booking['booking_status'] !== 'completed'): ?>
                    <?php if ($canEdit): ?>
                        <a href="<?= url('modules/bookings/edit.php?id=' . $booking['id']); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Voucher Grid -->
        <div class="row g-4">
            <!-- Left Column: Reservation & Client Details -->
            <div class="col-12 col-lg-8">
                <!-- Tour Package Details Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title"><i class="bi bi-compass me-2 text-primary"></i> Tour Package Information</h3>
                        <a href="<?= url('modules/tours/view.php?id=' . $booking['package_id']); ?>" class="btn btn-sm btn-outline-secondary">
                            View Package
                        </a>
                    </div>
                    <div class="admin-card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-8">
                                <h4 class="fs-5 fw-bold text-dark mb-1"><?= e($booking['package_name']); ?></h4>
                                <div class="text-muted small">Package Code: <strong class="text-dark"><?= e($booking['package_code']); ?></strong></div>
                            </div>
                            <div class="col-12 col-sm-4 text-sm-end">
                                <span class="badge bg-light text-dark border px-2 py-1 fs-6">
                                    <i class="bi bi-geo-alt me-1 text-primary"></i> <?= e($booking['destination_name'] ?? '—'); ?>
                                </span>
                            </div>

                            <div class="col-12"><hr class="my-1"></div>

                            <div class="col-6 col-sm-4">
                                <span class="text-muted d-block small">Travel Departure Date:</span>
                                <strong class="text-dark fs-6"><?= format_date($booking['travel_date'], 'l, M d, Y'); ?></strong>
                            </div>
                            <div class="col-6 col-sm-4">
                                <span class="text-muted d-block small">Tour Duration:</span>
                                <strong class="text-dark"><?= (int)$booking['duration_days']; ?> Days / <?= (int)$booking['duration_nights']; ?> Nights</strong>
                            </div>
                            <div class="col-6 col-sm-4">
                                <span class="text-muted d-block small">Departure Point:</span>
                                <strong class="text-dark"><?= e($booking['departure_location'] ?? 'Dhaka'); ?></strong>
                            </div>

                            <?php if (!empty($booking['hotel_information'])): ?>
                                <div class="col-12 col-sm-6">
                                    <span class="text-muted d-block small">Accommodation:</span>
                                    <span class="text-dark small"><?= e($booking['hotel_information']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($booking['transportation'])): ?>
                                <div class="col-12 col-sm-6">
                                    <span class="text-muted d-block small">Transportation:</span>
                                    <span class="text-dark small"><?= e($booking['transportation']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title"><i class="bi bi-person me-2 text-primary"></i> Customer Information</h3>
                        <a href="<?= url('modules/customers/view.php?id=' . $booking['customer_id']); ?>" class="btn btn-sm btn-outline-secondary">
                            View Customer Profile
                        </a>
                    </div>
                    <div class="admin-card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Full Name:</span>
                                <strong class="text-dark fs-6"><?= e($booking['customer_name']); ?></strong>
                                <div class="text-muted small">Code: <?= e($booking['customer_code']); ?></div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Primary Contact Phone:</span>
                                <strong class="text-dark"><i class="bi bi-telephone me-1 text-primary"></i> <?= e($booking['customer_phone']); ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Email Address:</span>
                                <span class="text-dark"><?= !empty($booking['customer_email']) ? e($booking['customer_email']) : '<em class="text-muted">Not provided</em>'; ?></span>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Address:</span>
                                <span class="text-dark"><?= !empty($booking['customer_address']) ? e($booking['customer_address']) . ', ' . e($booking['customer_city'] ?? '') : '—'; ?></span>
                            </div>
                            <?php if (!empty($booking['passport_number']) || !empty($booking['national_id'])): ?>
                                <div class="col-12"><hr class="my-1"></div>
                                <div class="col-6">
                                    <span class="text-muted d-block small">Passport Number:</span>
                                    <span class="text-dark"><?= !empty($booking['passport_number']) ? e($booking['passport_number']) : '—'; ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block small">National ID (NID):</span>
                                    <span class="text-dark"><?= !empty($booking['national_id']) ? e($booking['national_id']) : '—'; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Special Requests & Internal Notes -->
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="admin-card h-100">
                            <div class="admin-card-header py-2">
                                <h4 class="admin-card-title fs-6"><i class="bi bi-chat-left-text me-2 text-primary"></i> Client Special Requests</h4>
                            </div>
                            <div class="admin-card-body p-3">
                                <p class="text-dark small mb-0"><?= !empty($booking['special_request']) ? nl2br(e($booking['special_request'])) : '<em class="text-muted">None specified</em>'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="admin-card h-100">
                            <div class="admin-card-header py-2">
                                <h4 class="admin-card-title fs-6"><i class="bi bi-sticky me-2 text-primary"></i> Internal Notes</h4>
                            </div>
                            <div class="admin-card-body p-3">
                                <p class="text-dark small mb-0"><?= !empty($booking['notes']) ? nl2br(e($booking['notes'])) : '<em class="text-muted">None recorded</em>'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History Card (Phase 05 Integration) -->
                <div class="admin-card mt-4">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h3 class="admin-card-title">
                            <i class="bi bi-credit-card me-2 text-primary"></i> Payment History
                        </h3>
                        <?php if ($canCreatePayment && $booking['booking_status'] !== 'cancelled' && (float)$booking['due_amount'] > 0): ?>
                            <a href="<?= url('modules/payments/create.php?booking_id=' . $booking['id']); ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Payment
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Payment #</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Transaction ID</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($payments)): ?>
                                        <?php foreach ($payments as $p): 
                                            $pStatusClass = 'bg-secondary';
                                            if ($p['payment_status'] === 'completed') $pStatusClass = 'bg-success';
                                            elseif ($p['payment_status'] === 'pending') $pStatusClass = 'bg-warning text-dark';
                                            elseif ($p['payment_status'] === 'failed') $pStatusClass = 'bg-danger';
                                            elseif ($p['payment_status'] === 'refunded') $pStatusClass = 'bg-secondary';

                                            $methodLabels = [
                                                'cash'           => 'Cash',
                                                'bank_transfer'  => 'Bank Transfer',
                                                'card'           => 'Card',
                                                'mobile_banking' => 'Mobile Banking',
                                                'other'          => 'Other'
                                            ];
                                            $methodName = $methodLabels[$p['payment_method']] ?? ucfirst(str_replace('_', ' ', $p['payment_method']));
                                        ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <a href="<?= url('modules/payments/view.php?id=' . $p['id']); ?>" class="fw-bold text-decoration-none">
                                                        <code><?= e($p['payment_number']); ?></code>
                                                    </a>
                                                </td>
                                                <td><span class="small text-dark"><?= format_date($p['payment_date'], 'M d, Y'); ?></span></td>
                                                <td><span class="badge bg-light text-dark border"><?= e($methodName); ?></span></td>
                                                <td><strong class="text-primary"><?= format_currency($p['amount']); ?></strong></td>
                                                <td><span class="small text-muted"><?= !empty($p['transaction_id']) ? e($p['transaction_id']) : '—'; ?></span></td>
                                                <td><span class="badge <?= $pStatusClass; ?>"><?= ucfirst(e($p['payment_status'])); ?></span></td>
                                                <td class="pe-3 text-end">
                                                    <a href="<?= url('modules/payments/view.php?id=' . $p['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Receipt">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                No payment transactions recorded for this reservation yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Financial Breakdown Voucher & Capacity -->
            <div class="col-12 col-lg-4">
                <!-- Passenger Capacity Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title"><i class="bi bi-people me-2 text-primary"></i> Passenger Manifest</h3>
                        <span class="badge bg-primary"><?= $totalTravellers; ?> Total Pax</span>
                    </div>
                    <div class="admin-card-body p-3">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span>Adults (Age 12+)</span>
                                <strong class="text-dark"><?= (int)$booking['adults']; ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span>Children (Age 3-11)</span>
                                <strong class="text-dark"><?= (int)$booking['children']; ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span>Infants (Under 3)</span>
                                <strong class="text-dark"><?= (int)$booking['infants']; ?></strong>
                            </li>
                        </ul>

                        <?php if ($capacityInfo): ?>
                            <div class="mt-3 p-2 bg-light rounded border text-muted small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Total Package Seats:</span>
                                    <strong class="text-dark"><?= $capacityInfo['capacity']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Other Confirmed Pax:</span>
                                    <strong class="text-dark"><?= $capacityInfo['confirmed']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Remaining Available Seats:</span>
                                    <strong class="<?= $capacityInfo['remaining'] >= $totalTravellers ? 'text-success' : 'text-danger'; ?>">
                                        <?= $capacityInfo['remaining']; ?>
                                    </strong>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Financial Price Breakdown Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header bg-dark text-white">
                        <h3 class="admin-card-title text-white"><i class="bi bi-receipt me-2"></i> Price Snapshot & Billing</h3>
                    </div>
                    <div class="admin-card-body p-4">
                        <!-- Adult Pricing Snapshot -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><?= (int)$booking['adults']; ?> Adult(s) &times; <?= format_currency($booking['adult_price']); ?>:</span>
                            <span class="fw-semibold text-dark"><?= format_currency((int)$booking['adults'] * (float)$booking['adult_price']); ?></span>
                        </div>

                        <!-- Child Pricing Snapshot -->
                        <?php if ((int)$booking['children'] > 0): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small"><?= (int)$booking['children']; ?> Child(ren) &times; <?= format_currency($booking['child_price']); ?>:</span>
                                <span class="fw-semibold text-dark"><?= format_currency((int)$booking['children'] * (float)$booking['child_price']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Infant Pricing -->
                        <?php if ((int)$booking['infants'] > 0): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small"><?= (int)$booking['infants']; ?> Infant(s):</span>
                                <span class="badge bg-light text-secondary border">Complimentary</span>
                            </div>
                        <?php endif; ?>

                        <hr class="my-2">

                        <!-- Gross Subtotal -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-dark">Gross Subtotal:</span>
                            <span class="fw-semibold text-dark"><?= format_currency($booking['subtotal']); ?></span>
                        </div>

                        <!-- Discount -->
                        <?php if ((float)$booking['discount_amount'] > 0): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 text-success">
                                <span>Discount (<?= ucfirst(e($booking['discount_type'])); ?>):</span>
                                <span class="fw-semibold">- <?= format_currency($booking['discount_amount']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Net Total Amount -->
                        <div class="p-3 bg-light rounded border mb-3 mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-6 fw-bold text-dark">Total Amount:</span>
                                <span class="fs-4 fw-bold text-primary"><?= format_currency($booking['total_amount']); ?></span>
                            </div>
                        </div>

                        <!-- Payment Breakdown -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Paid Amount:</span>
                            <span class="fw-semibold text-success"><?= format_currency($booking['paid_amount']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small">Outstanding Due:</span>
                            <span class="fw-bold <?= (float)$booking['due_amount'] > 0 ? 'text-danger' : 'text-success'; ?>"><?= format_currency($booking['due_amount']); ?></span>
                        </div>

                        <?php if ($canCreatePayment && $booking['booking_status'] !== 'cancelled' && (float)$booking['due_amount'] > 0): ?>
                            <div class="d-grid gap-2">
                                <a href="<?= url('modules/payments/create.php?booking_id=' . $booking['id']); ?>" class="btn btn-success btn-sm">
                                    <i class="bi bi-credit-card me-1"></i> Record Payment
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Audit Meta -->
                <div class="admin-card">
                    <div class="admin-card-body p-3 text-muted" style="font-size: 0.75rem;">
                        <div>Created At: <strong><?= format_date($booking['created_at'], 'M d, Y h:i A'); ?></strong></div>
                        <div>Created By: <strong><?= e($booking['creator_name'] ?? 'System'); ?></strong></div>
                        <?php if (!empty($booking['cancelled_at'])): ?>
                            <div class="text-danger mt-1">Cancelled At: <strong><?= format_date($booking['cancelled_at'], 'M d, Y h:i A'); ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Booking Modal -->
    <?php if ($canConfirm): ?>
        <div class="modal fade" id="confirmBookingModal" tabindex="-1" aria-labelledby="confirmBookingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/bookings/status-update.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$booking['id']; ?>">
                        <input type="hidden" name="new_status" value="confirmed">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-success" id="confirmBookingModalLabel">Confirm Tour Reservation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to mark booking <strong><?= e($booking['booking_number']); ?></strong> as <strong>Confirmed</strong>?</p>
                            <p class="text-muted small mb-0">This will permanently reserve <strong><?= $totalTravellers; ?> seat(s)</strong> for this tour package.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Confirm Reservation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Complete Booking Modal -->
    <?php if ($canComplete): ?>
        <div class="modal fade" id="completeBookingModal" tabindex="-1" aria-labelledby="completeBookingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/bookings/status-update.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$booking['id']; ?>">
                        <input type="hidden" name="new_status" value="completed">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-primary" id="completeBookingModalLabel">Mark Tour as Completed</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to mark booking <strong><?= e($booking['booking_number']); ?></strong> as <strong>Completed</strong>?</p>
                            <p class="text-muted small mb-0">This indicates the client has completed their tour journey.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Mark Completed</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cancel Booking Modal -->
    <?php if ($canCancel): ?>
        <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/bookings/cancel.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$booking['id']; ?>">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="cancelBookingModalLabel">Confirm Booking Cancellation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to cancel booking <strong><?= e($booking['booking_number']); ?></strong>?</p>
                            <p class="text-muted small mb-0">The reservation will be marked as Cancelled. Any occupied capacity will be released immediately.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Cancel Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
