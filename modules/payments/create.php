<?php
/**
 * Record New Payment View
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Record Customer Payment';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('payments.create');

$preSelectedBookingId = (int)($_GET['booking_id'] ?? 0);
$bookings = [];

try {
    $pdo = get_db_connection();

    // Fetch active bookings (non-cancelled and non-deleted)
    $stmt = $pdo->query("
        SELECT 
            b.id,
            b.booking_number,
            b.travel_date,
            b.total_amount,
            b.paid_amount,
            b.due_amount,
            b.payment_status,
            b.booking_status,
            c.name AS customer_name,
            c.customer_code,
            p.name AS package_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages p ON b.tour_package_id = p.id
        WHERE b.deleted_at IS NULL AND b.booking_status != 'cancelled'
        ORDER BY b.id DESC
    ");
    $bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Payment create query error: ' . $e->getMessage());
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
                        <li class="breadcrumb-item active" aria-current="page">Record Payment</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Record Customer Payment</h2>
            </div>
            <div>
                <a href="<?= url('modules/payments/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Payments
                </a>
            </div>
        </div>

        <form action="<?= url('modules/payments/store.php'); ?>" method="POST" id="paymentForm">
            <?= csrf_field(); ?>

            <div class="row g-4">
                <!-- Left Column: Payment Form -->
                <div class="col-12 col-lg-8">
                    <!-- Step 1: Booking Selection -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title"><i class="bi bi-calendar-check me-2 text-primary"></i> 1. Select Tour Reservation</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="booking_id" class="form-label fw-semibold">Target Booking <span class="text-danger">*</span></label>
                                    <select class="form-select" id="booking_id" name="booking_id" required>
                                        <option value="">-- Choose a Booking Reservation --</option>
                                        <?php foreach ($bookings as $b): 
                                            $dueAmt = (float)$b['due_amount'];
                                        ?>
                                            <option 
                                                value="<?= (int)$b['id']; ?>"
                                                data-number="<?= e($b['booking_number']); ?>"
                                                data-client="<?= e($b['customer_name']); ?>"
                                                data-package="<?= e($b['package_name']); ?>"
                                                data-date="<?= format_date($b['travel_date'], 'M d, Y'); ?>"
                                                data-total="<?= (float)$b['total_amount']; ?>"
                                                data-paid="<?= (float)$b['paid_amount']; ?>"
                                                data-due="<?= $dueAmt; ?>"
                                                data-status="<?= e($b['payment_status']); ?>"
                                                <?= $preSelectedBookingId === (int)$b['id'] ? 'selected' : ''; ?>
                                            >
                                                <?= e($b['booking_number']); ?> — <?= e($b['customer_name']); ?> | <?= e($b['package_name']); ?> (Due: ৳<?= number_format($dueAmt, 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Dynamic Booking Summary Banner -->
                                <div class="col-12 d-none" id="bookingSummaryBanner">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="row g-2 text-dark small">
                                            <div class="col-6 col-sm-3">
                                                <span class="text-muted d-block">Client:</span>
                                                <strong id="bannerClient">—</strong>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <span class="text-muted d-block">Tour Package:</span>
                                                <strong id="bannerPackage">—</strong>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <span class="text-muted d-block">Total Amount:</span>
                                                <strong id="bannerTotal" class="text-dark">—</strong>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <span class="text-muted d-block">Current Due:</span>
                                                <strong id="bannerDue" class="text-danger">—</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Payment Details -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title"><i class="bi bi-wallet2 me-2 text-primary"></i> 2. Transaction Information</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <div class="row g-3">
                                <!-- Payment Date -->
                                <div class="col-12 col-md-6">
                                    <label for="payment_date" class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        id="payment_date" 
                                        name="payment_date" 
                                        value="<?= date('Y-m-d'); ?>" 
                                        max="<?= date('Y-m-d'); ?>" 
                                        required
                                    >
                                    <div class="form-text">Cannot be a future date.</div>
                                </div>

                                <!-- Payment Amount -->
                                <div class="col-12 col-md-6">
                                    <label for="amount" class="form-label fw-semibold">Payment Amount (৳) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">৳</span>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            class="form-control fw-bold fs-6" 
                                            id="amount" 
                                            name="amount" 
                                            min="0.01" 
                                            placeholder="0.00" 
                                            required
                                        >
                                    </div>
                                    <div class="form-text text-danger d-none" id="amountWarning">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Amount exceeds current remaining balance!
                                    </div>
                                </div>

                                <!-- Payment Method -->
                                <div class="col-12 col-md-6">
                                    <label for="payment_method" class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="cash">Cash Counter Deposit</option>
                                        <option value="bank_transfer">Bank Transfer (EFT/NPSB/RTGS)</option>
                                        <option value="mobile_banking">Mobile Banking (bKash/Nagad/Rocket)</option>
                                        <option value="card">Credit / Debit Card</option>
                                        <option value="other">Other Method</option>
                                    </select>
                                </div>

                                <!-- Transaction Reference ID -->
                                <div class="col-12 col-md-6">
                                    <label for="transaction_id" class="form-label fw-semibold">Transaction Reference ID</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="transaction_id" 
                                        name="transaction_id" 
                                        placeholder="e.g. Bank Trx ID, bKash TrxID, Cheque #"
                                    >
                                    <div class="form-text">Recommended for bank/card/mobile payments.</div>
                                </div>

                                <!-- Transaction Status -->
                                <div class="col-12 col-md-6">
                                    <label for="payment_status" class="form-label fw-semibold">Transaction Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="completed">Completed (Successfully Received)</option>
                                        <option value="pending">Pending (Under Verification)</option>
                                        <option value="failed">Failed / Bounced</option>
                                    </select>
                                    <div class="form-text small">Only <strong>Completed</strong> payments reduce the booking due balance.</div>
                                </div>

                                <!-- Notes -->
                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold">Payment Notes / Remarks</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="e.g. Received via City Bank Banani Branch A/C #123456..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Live Balance Preview Voucher -->
                <div class="col-12 col-lg-4">
                    <div class="admin-card sticky-top" style="top: 80px;">
                        <div class="admin-card-header bg-dark text-white">
                            <h3 class="admin-card-title text-white"><i class="bi bi-receipt me-2"></i> Payment Balance Preview</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <!-- Booking Total -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Booking Total Amount:</span>
                                <span class="fw-semibold text-dark" id="lblBookingTotal">৳0.00</span>
                            </div>

                            <!-- Previously Paid -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Previously Collected:</span>
                                <span class="fw-semibold text-success" id="lblPrevPaid">৳0.00</span>
                            </div>

                            <!-- Current Due -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Current Due Balance:</span>
                                <span class="fw-bold text-danger fs-6" id="lblCurrentDue">৳0.00</span>
                            </div>

                            <hr class="my-2">

                            <!-- New Payment Entered -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold text-primary">New Payment:</span>
                                <span class="fw-bold text-primary fs-5" id="lblNewPayment">৳0.00</span>
                            </div>

                            <!-- Estimated Due After Payment -->
                            <div class="p-3 bg-light rounded border mb-4 mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-6 fw-bold text-dark">Remaining Due:</span>
                                    <span class="fs-4 fw-bold text-dark" id="lblAfterDue">৳0.00</span>
                                </div>
                                <div class="small mt-1 text-muted" id="lblProjectedStatus">
                                    Projected Status: <span class="badge bg-secondary">Unpaid</span>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg" id="btnSubmitPayment">
                                    <i class="bi bi-check-circle me-1"></i> Save Payment Receipt
                                </button>
                                <a href="<?= url('modules/payments/index.php'); ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Balance Engine Vanilla JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const bkSelect       = document.getElementById('booking_id');
        const amountInput    = document.getElementById('amount');
        const statusSelect   = document.getElementById('payment_status');
        const warningBox     = document.getElementById('amountWarning');
        const submitBtn      = document.getElementById('btnSubmitPayment');

        const bannerBox      = document.getElementById('bookingSummaryBanner');
        const bannerClient   = document.getElementById('bannerClient');
        const bannerPackage  = document.getElementById('bannerPackage');
        const bannerTotal    = document.getElementById('bannerTotal');
        const bannerDue      = document.getElementById('bannerDue');

        const lblBookingTotal    = document.getElementById('lblBookingTotal');
        const lblPrevPaid        = document.getElementById('lblPrevPaid');
        const lblCurrentDue      = document.getElementById('lblCurrentDue');
        const lblNewPayment      = document.getElementById('lblNewPayment');
        const lblAfterDue        = document.getElementById('lblAfterDue');
        const lblProjectedStatus = document.getElementById('lblProjectedStatus');

        function formatMoney(val) {
            return '৳' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function recalculate() {
            const opt = bkSelect.options[bkSelect.selectedIndex];
            if (!opt || !opt.value) {
                bannerBox.classList.add('d-none');
                lblBookingTotal.textContent = '৳0.00';
                lblPrevPaid.textContent     = '৳0.00';
                lblCurrentDue.textContent   = '৳0.00';
                lblNewPayment.textContent   = '৳0.00';
                lblAfterDue.textContent     = '৳0.00';
                warningBox.classList.add('d-none');
                submitBtn.disabled = false;
                return;
            }

            const total = parseFloat(opt.getAttribute('data-total')) || 0;
            const paid  = parseFloat(opt.getAttribute('data-paid')) || 0;
            const due   = parseFloat(opt.getAttribute('data-due')) || 0;
            const client = opt.getAttribute('data-client') || '—';
            const pkg   = opt.getAttribute('data-package') || '—';

            // Show banner
            bannerBox.classList.remove('d-none');
            bannerClient.textContent  = client;
            bannerPackage.textContent = pkg;
            bannerTotal.textContent   = formatMoney(total);
            bannerDue.textContent     = formatMoney(due);

            lblBookingTotal.textContent = formatMoney(total);
            lblPrevPaid.textContent     = formatMoney(paid);
            lblCurrentDue.textContent   = formatMoney(due);

            const newPayment = parseFloat(amountInput.value) || 0;
            lblNewPayment.textContent = formatMoney(newPayment);

            const isCompleted = statusSelect.value === 'completed';

            if (isCompleted && newPayment > (due + 0.01)) {
                warningBox.classList.remove('d-none');
                submitBtn.disabled = true;
            } else {
                warningBox.classList.add('d-none');
                submitBtn.disabled = false;
            }

            let afterDue = due;
            if (isCompleted) {
                afterDue = Math.max(0, due - newPayment);
            }
            lblAfterDue.textContent = formatMoney(afterDue);

            // Projected Status
            let projectedStatus = 'Unpaid';
            let badgeClass = 'bg-danger';
            const totalProjectedPaid = isCompleted ? (paid + newPayment) : paid;

            if (totalProjectedPaid >= total && total > 0) {
                projectedStatus = 'Paid';
                badgeClass = 'bg-success';
            } else if (totalProjectedPaid > 0) {
                projectedStatus = 'Partial';
                badgeClass = 'bg-warning text-dark';
            }

            lblProjectedStatus.innerHTML = `Projected Status: <span class="badge ${badgeClass}">${projectedStatus}</span>`;
        }

        bkSelect.addEventListener('change', function() {
            const opt = bkSelect.options[bkSelect.selectedIndex];
            if (opt && opt.value) {
                const due = parseFloat(opt.getAttribute('data-due')) || 0;
                if (!amountInput.value || parseFloat(amountInput.value) === 0) {
                    amountInput.value = due > 0 ? due.toFixed(2) : '0.00';
                }
            }
            recalculate();
        });

        amountInput.addEventListener('input', recalculate);
        statusSelect.addEventListener('change', recalculate);

        // Run once on load
        if (bkSelect.value) {
            const opt = bkSelect.options[bkSelect.selectedIndex];
            const due = parseFloat(opt.getAttribute('data-due')) || 0;
            if (!amountInput.value) {
                amountInput.value = due > 0 ? due.toFixed(2) : '0.00';
            }
            recalculate();
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
