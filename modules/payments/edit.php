<?php
/**
 * Edit Payment Remarks View
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Edit Payment Remarks';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('payments.edit');

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
            b.booking_number,
            c.name AS customer_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
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
    error_log('Payment Edit Query Error: ' . $e->getMessage());
    set_flash('error', 'Failed to retrieve payment information.');
    redirect('modules/payments/index.php');
}
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
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= url('modules/payments/index.php'); ?>">Payments</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('modules/payments/view.php?id=' . $payment['id']); ?>"><?= e($payment['payment_number']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Remarks</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Edit Payment <?= e($payment['payment_number']); ?></h2>
            </div>
            <div>
                <a href="<?= url('modules/payments/view.php?id=' . $payment['id']); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Receipt
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title"><i class="bi bi-pencil-square me-2 text-primary"></i> Modify Payment Details</h3>
                    </div>
                    <div class="admin-card-body p-4">
                        <form action="<?= url('modules/payments/update.php'); ?>" method="POST">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="id" value="<?= (int)$payment['id']; ?>">

                            <div class="row g-3">
                                <!-- Target Booking (Read-only) -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">Target Booking</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($payment['booking_number']); ?> (<?= e($payment['customer_name']); ?>)" readonly>
                                </div>

                                <!-- Payment Amount (Immutable) -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">Payment Amount (Immutable)</label>
                                    <input type="text" class="form-control bg-light fw-bold text-primary" value="<?= format_currency($payment['amount']); ?>" readonly>
                                    <div class="form-text small">Transaction amounts are locked for accounting audit integrity.</div>
                                </div>

                                <!-- Payment Date -->
                                <div class="col-12 col-md-6">
                                    <label for="payment_date" class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        id="payment_date" 
                                        name="payment_date" 
                                        value="<?= e($payment['payment_date']); ?>" 
                                        max="<?= date('Y-m-d'); ?>" 
                                        required
                                    >
                                </div>

                                <!-- Payment Method -->
                                <div class="col-12 col-md-6">
                                    <label for="payment_method" class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="cash" <?= $payment['payment_method'] === 'cash' ? 'selected' : ''; ?>>Cash Counter Deposit</option>
                                        <option value="bank_transfer" <?= $payment['payment_method'] === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                        <option value="card" <?= $payment['payment_method'] === 'card' ? 'selected' : ''; ?>>Credit / Debit Card</option>
                                        <option value="mobile_banking" <?= $payment['payment_method'] === 'mobile_banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                                        <option value="other" <?= $payment['payment_method'] === 'other' ? 'selected' : ''; ?>>Other Method</option>
                                    </select>
                                </div>

                                <!-- Transaction ID -->
                                <div class="col-12 col-md-6">
                                    <label for="transaction_id" class="form-label fw-semibold">Transaction Reference ID</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="transaction_id" 
                                        name="transaction_id" 
                                        value="<?= e($payment['transaction_id'] ?? ''); ?>"
                                    >
                                </div>

                                <!-- Transaction Status -->
                                <div class="col-12 col-md-6">
                                    <label for="payment_status" class="form-label fw-semibold">Transaction Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="completed" <?= $payment['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="pending" <?= $payment['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?= $payment['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?= $payment['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                    <div class="form-text small">Toggling status will automatically update the booking due balance.</div>
                                </div>

                                <!-- Notes -->
                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold">Payment Notes / Remarks</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= e($payment['notes'] ?? ''); ?></textarea>
                                </div>

                                <div class="col-12 pt-3 border-top d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i> Update Payment Details
                                    </button>
                                    <a href="<?= url('modules/payments/view.php?id=' . $payment['id']); ?>" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
