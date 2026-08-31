<?php
/**
 * View Payment Transaction Receipt
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Payment Transaction Receipt';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('payments.view');

$canEdit   = has_permission('payments.edit');
$canDelete = has_permission('payments.delete');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid payment identifier.');
    redirect('modules/payments/index.php');
}

$payment = null;

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            b.id AS booking_id,
            b.booking_number,
            b.travel_date,
            b.total_amount AS booking_total,
            b.paid_amount AS booking_paid,
            b.due_amount AS booking_due,
            b.payment_status AS booking_payment_status,
            b.booking_status,
            c.id AS customer_id,
            c.name AS customer_name,
            c.customer_code,
            c.phone AS customer_phone,
            c.email AS customer_email,
            tp.id AS package_id,
            tp.name AS package_name,
            tp.package_code,
            u.name AS collector_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages tp ON b.tour_package_id = tp.id
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = :id AND p.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        set_flash('error', 'Payment transaction record not found.');
        redirect('modules/payments/index.php');
    }

} catch (PDOException $e) {
    error_log('Payment View Error: ' . $e->getMessage());
    set_flash('error', 'Failed to retrieve payment details.');
    redirect('modules/payments/index.php');
}

// Payment Status Class
$pStatusClass = 'bg-secondary';
if ($payment['payment_status'] === 'completed') $pStatusClass = 'bg-success';
elseif ($payment['payment_status'] === 'pending') $pStatusClass = 'bg-warning text-dark';
elseif ($payment['payment_status'] === 'failed') $pStatusClass = 'bg-danger';
elseif ($payment['payment_status'] === 'refunded') $pStatusClass = 'bg-secondary';

// Method Labels
$methodLabels = [
    'cash'           => 'Cash Counter Deposit',
    'bank_transfer'  => 'Bank Transfer',
    'card'           => 'Credit / Debit Card',
    'mobile_banking' => 'Mobile Banking',
    'other'          => 'Other Method'
];
$methodName = $methodLabels[$payment['payment_method']] ?? ucfirst(str_replace('_', ' ', $payment['payment_method']));
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Top Header & Action Controls -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= url('modules/payments/index.php'); ?>">Payments</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= e($payment['payment_number']); ?></li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="fs-4 fw-bold text-dark mb-0"><?= e($payment['payment_number']); ?></h2>
                    <span class="badge <?= $pStatusClass; ?> px-2 py-1"><?= ucfirst(e($payment['payment_status'])); ?></span>
                    <span class="badge bg-light text-dark border px-2 py-1"><?= e($methodName); ?></span>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('modules/payments/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Payments
                </a>
                <a href="<?= url('modules/bookings/view.php?id=' . $payment['booking_id']); ?>" class="btn btn-outline-primary">
                    <i class="bi bi-calendar-check me-1"></i> View Booking
                </a>
                <?php if ($canEdit): ?>
                    <a href="<?= url('modules/payments/edit.php?id=' . $payment['id']); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i> Edit Remarks
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Receipt Grid -->
        <div class="row g-4">
            <!-- Left Column: Payment Receipt & Details -->
            <div class="col-12 col-lg-8">
                <!-- Payment Receipt Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header bg-dark text-white">
                        <h3 class="admin-card-title text-white"><i class="bi bi-receipt me-2"></i> Payment Receipt Details</h3>
                    </div>
                    <div class="admin-card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Receipt Number:</span>
                                <code class="fs-5 fw-bold"><?= e($payment['payment_number']); ?></code>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Payment Amount Received:</span>
                                <h3 class="fs-3 fw-bold text-primary mb-0"><?= format_currency($payment['amount']); ?></h3>
                            </div>

                            <div class="col-12"><hr class="my-1"></div>

                            <div class="col-6 col-sm-4">
                                <span class="text-muted d-block small">Payment Date:</span>
                                <strong class="text-dark"><?= format_date($payment['payment_date'], 'l, M d, Y'); ?></strong>
                            </div>
                            <div class="col-6 col-sm-4">
                                <span class="text-muted d-block small">Payment Method:</span>
                                <strong class="text-dark"><?= e($methodName); ?></strong>
                            </div>
                            <div class="col-6 col-sm-4">
                                <span class="text-muted d-block small">Transaction Reference ID:</span>
                                <strong class="text-dark"><?= !empty($payment['transaction_id']) ? e($payment['transaction_id']) : '<em class="text-muted">None (Cash)</em>'; ?></strong>
                            </div>

                            <?php if (!empty($payment['notes'])): ?>
                                <div class="col-12">
                                    <span class="text-muted d-block small mb-1">Payment Remarks & Notes:</span>
                                    <div class="p-3 bg-light rounded border text-dark small" style="white-space: pre-line;">
                                        <?= e($payment['notes']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Associated Booking & Customer Details Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h3 class="admin-card-title"><i class="bi bi-person-check me-2 text-primary"></i> Tour Reservation Details</h3>
                        <a href="<?= url('modules/bookings/view.php?id=' . $payment['booking_id']); ?>" class="btn btn-sm btn-outline-secondary">
                            View Reservation
                        </a>
                    </div>
                    <div class="admin-card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Booking Number:</span>
                                <a href="<?= url('modules/bookings/view.php?id=' . $payment['booking_id']); ?>" class="fw-bold text-decoration-none">
                                    <code><?= e($payment['booking_number']); ?></code>
                                </a>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Travel Departure Date:</span>
                                <strong class="text-dark"><?= format_date($payment['travel_date'], 'M d, Y'); ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Customer Name:</span>
                                <strong class="text-dark"><?= e($payment['customer_name']); ?></strong> (<?= e($payment['customer_code']); ?>)
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block small">Customer Phone:</span>
                                <span class="text-dark"><i class="bi bi-telephone me-1 text-primary"></i> <?= e($payment['customer_phone']); ?></span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted d-block small">Tour Package:</span>
                                <span class="text-dark fw-semibold"><?= e($payment['package_name']); ?></span> (<code><?= e($payment['package_code']); ?></code>)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Booking Financial Summary -->
            <div class="col-12 col-lg-4">
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title"><i class="bi bi-pie-chart me-2 text-primary"></i> Booking Financial Status</h3>
                    </div>
                    <div class="admin-card-body p-4">
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Total Invoiced:</span>
                                <strong class="text-dark"><?= format_currency($payment['booking_total']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Total Collected:</span>
                                <strong class="text-success"><?= format_currency($payment['booking_paid']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Remaining Balance:</span>
                                <strong class="<?= (float)$payment['booking_due'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?= format_currency($payment['booking_due']); ?>
                                </strong>
                            </li>
                        </ul>

                        <div class="p-3 bg-light rounded border text-center mb-2">
                            <span class="text-muted small d-block mb-1">Booking Payment Status</span>
                            <span class="badge <?= $payment['booking_payment_status'] === 'paid' ? 'bg-success' : ($payment['booking_payment_status'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger'); ?> fs-6 px-3 py-1">
                                <?= ucfirst(e($payment['booking_payment_status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Audit Meta Card -->
                <div class="admin-card">
                    <div class="admin-card-body p-3 text-muted" style="font-size: 0.75rem;">
                        <div>Receipt Created: <strong><?= format_date($payment['created_at'], 'M d, Y h:i A'); ?></strong></div>
                        <div>Collected By: <strong><?= e($payment['collector_name'] ?? 'System'); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
