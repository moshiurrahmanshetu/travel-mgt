<?php
/**
 * Edit Booking View
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Edit Booking Reservation';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('bookings.edit');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid booking identifier.');
    redirect('modules/bookings/index.php');
}

$booking = null;
$customers = [];
$packages = [];

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        set_flash('error', 'Booking record not found.');
        redirect('modules/bookings/index.php');
    }

    if ($booking['booking_status'] === 'cancelled') {
        set_flash('warning', 'Cancelled bookings cannot be modified.');
        redirect('modules/bookings/view.php?id=' . $id);
    }

    // Fetch active customers + current customer
    $cusStmt = $pdo->prepare("
        SELECT id, customer_code, name, phone 
        FROM customers 
        WHERE (status = 'active' OR id = :current_cus) AND deleted_at IS NULL 
        ORDER BY name ASC
    ");
    $cusStmt->execute(['current_cus' => $booking['customer_id']]);
    $customers = $cusStmt->fetchAll();

    // Fetch active tour packages + current package
    $pkgStmt = $pdo->prepare("
        SELECT 
            p.id, p.package_code, p.name, p.price, p.child_price, 
            p.duration_days, p.duration_nights, p.available_seats,
            d.name AS destination_name
        FROM tour_packages p
        LEFT JOIN tour_destinations d ON p.destination_id = d.id
        WHERE (p.status = 'active' OR p.id = :current_pkg) AND p.deleted_at IS NULL 
        ORDER BY p.name ASC
    ");
    $pkgStmt->execute(['current_pkg' => $booking['tour_package_id']]);
    $packages = $pkgStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Booking Edit Query Error: ' . $e->getMessage());
    set_flash('error', 'Failed to retrieve booking information for editing.');
    redirect('modules/bookings/index.php');
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
                        <li class="breadcrumb-item"><a href="<?= url('modules/bookings/index.php'); ?>">Bookings</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('modules/bookings/view.php?id=' . $booking['id']); ?>"><?= e($booking['booking_number']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Edit Booking <?= e($booking['booking_number']); ?></h2>
            </div>
            <div>
                <a href="<?= url('modules/bookings/view.php?id=' . $booking['id']); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Details
                </a>
            </div>
        </div>

        <form action="<?= url('modules/bookings/update.php'); ?>" method="POST" id="editBookingForm">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$booking['id']; ?>">

            <div class="row g-4">
                <!-- Left Column: Edit Form -->
                <div class="col-12 col-lg-8">
                    <!-- Step 1: Customer & Tour Package -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title"><i class="bi bi-person-check me-2 text-primary"></i> 1. Customer & Tour Selection</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <div class="row g-3">
                                <!-- Customer Select -->
                                <div class="col-12 col-md-6">
                                    <label for="customer_id" class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                                    <select class="form-select" id="customer_id" name="customer_id" required>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?= (int)$c['id']; ?>" <?= (int)$booking['customer_id'] === (int)$c['id'] ? 'selected' : ''; ?>>
                                                <?= e($c['customer_code']); ?> — <?= e($c['name']); ?> (<?= e($c['phone']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Tour Package Select -->
                                <div class="col-12 col-md-6">
                                    <label for="tour_package_id" class="form-label fw-semibold">Tour Package <span class="text-danger">*</span></label>
                                    <select class="form-select" id="tour_package_id" name="tour_package_id" required>
                                        <?php foreach ($packages as $p): 
                                            $childPr = (float)($p['child_price'] ?? 0);
                                        ?>
                                            <option 
                                                value="<?= (int)$p['id']; ?>" 
                                                data-price="<?= (float)$p['price']; ?>"
                                                data-child-price="<?= $childPr; ?>"
                                                data-destination="<?= e($p['destination_name'] ?? ''); ?>"
                                                data-duration="<?= (int)$p['duration_days']; ?>D / <?= (int)$p['duration_nights']; ?>N"
                                                <?= (int)$booking['tour_package_id'] === (int)$p['id'] ? 'selected' : ''; ?>
                                            >
                                                <?= e($p['package_code']); ?> — <?= e($p['name']); ?> (৳<?= number_format((float)$p['price'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Travel Date & Travellers -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title"><i class="bi bi-calendar-event me-2 text-primary"></i> 2. Travel Schedule & Passengers</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <div class="row g-3">
                                <!-- Travel Date -->
                                <div class="col-12 col-md-6">
                                    <label for="travel_date" class="form-label fw-semibold">Travel Departure Date <span class="text-danger">*</span></label>
                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        id="travel_date" 
                                        name="travel_date" 
                                        value="<?= e($booking['travel_date']); ?>" 
                                        required
                                    >
                                </div>

                                <div class="col-12 col-md-6"></div>

                                <!-- Adults Count -->
                                <div class="col-4">
                                    <label for="adults" class="form-label fw-semibold">Adults <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control calc-trigger" id="adults" name="adults" min="1" max="100" value="<?= (int)$booking['adults']; ?>" required>
                                </div>

                                <!-- Children Count -->
                                <div class="col-4">
                                    <label for="children" class="form-label fw-semibold">Children</label>
                                    <input type="number" class="form-control calc-trigger" id="children" name="children" min="0" max="50" value="<?= (int)$booking['children']; ?>">
                                </div>

                                <!-- Infants Count -->
                                <div class="col-4">
                                    <label for="infants" class="form-label fw-semibold">Infants</label>
                                    <input type="number" class="form-control calc-trigger" id="infants" name="infants" min="0" max="20" value="<?= (int)$booking['infants']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Discounts & Notes -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title"><i class="bi bi-tag me-2 text-primary"></i> 3. Discounts & Special Requests</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <div class="row g-3">
                                <!-- Discount Type -->
                                <div class="col-12 col-md-6">
                                    <label for="discount_type" class="form-label fw-semibold">Discount Type</label>
                                    <select class="form-select calc-trigger" id="discount_type" name="discount_type">
                                        <option value="none" <?= $booking['discount_type'] === 'none' ? 'selected' : ''; ?>>No Discount</option>
                                        <option value="percentage" <?= $booking['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                        <option value="fixed" <?= $booking['discount_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount (৳)</option>
                                    </select>
                                </div>

                                <!-- Discount Value -->
                                <div class="col-12 col-md-6">
                                    <label for="discount_value" class="form-label fw-semibold">Discount Value</label>
                                    <input type="number" step="0.01" class="form-control calc-trigger" id="discount_value" name="discount_value" min="0" value="<?= (float)$booking['discount_value']; ?>">
                                </div>

                                <!-- Special Requests -->
                                <div class="col-12">
                                    <label for="special_request" class="form-label fw-semibold">Special Client Requests</label>
                                    <textarea class="form-control" id="special_request" name="special_request" rows="2"><?= e($booking['special_request'] ?? ''); ?></textarea>
                                </div>

                                <!-- Internal Notes -->
                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold">Internal Booking Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= e($booking['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Recalculated Live Price Voucher -->
                <div class="col-12 col-lg-4">
                    <div class="admin-card sticky-top" style="top: 80px;">
                        <div class="admin-card-header bg-dark text-white">
                            <h3 class="admin-card-title text-white"><i class="bi bi-receipt me-2"></i> Price Breakdown (Preview)</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <!-- Adult Price Row -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted"><span id="lblAdultCount"><?= (int)$booking['adults']; ?></span> Adult(s) &times; <span id="lblAdultUnit"><?= format_currency($booking['adult_price']); ?></span>:</span>
                                <span class="fw-semibold text-dark" id="lblAdultSubtotal">৳0.00</span>
                            </div>

                            <!-- Child Price Row -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted"><span id="lblChildCount"><?= (int)$booking['children']; ?></span> Child(ren) &times; <span id="lblChildUnit"><?= format_currency($booking['child_price']); ?></span>:</span>
                                <span class="fw-semibold text-dark" id="lblChildSubtotal">৳0.00</span>
                            </div>

                            <!-- Infants Row -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted"><span id="lblInfantCount"><?= (int)$booking['infants']; ?></span> Infant(s):</span>
                                <span class="badge bg-light text-secondary border">Complimentary</span>
                            </div>

                            <hr class="my-2">

                            <!-- Gross Subtotal -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold text-dark">Gross Subtotal:</span>
                                <span class="fw-semibold text-dark" id="lblGrossSubtotal">৳0.00</span>
                            </div>

                            <!-- Discount Row -->
                            <div class="d-flex justify-content-between align-items-center mb-3 text-success">
                                <span>Discount:</span>
                                <span class="fw-semibold" id="lblDiscountAmount">- ৳0.00</span>
                            </div>

                            <div class="p-3 bg-primary-subtle rounded border border-primary-subtle mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-6 fw-bold text-dark">Total Amount:</span>
                                    <span class="fs-4 fw-bold text-primary" id="lblTotalAmount">৳0.00</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-1"></i> Update Booking Reservation
                                </button>
                                <a href="<?= url('modules/bookings/view.php?id=' . $booking['id']); ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Pricing Engine JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const pkgSelect      = document.getElementById('tour_package_id');
        const adultsInput    = document.getElementById('adults');
        const childrenInput  = document.getElementById('children');
        const infantsInput   = document.getElementById('infants');
        const discTypeSelect = document.getElementById('discount_type');
        const discValInput   = document.getElementById('discount_value');

        const lblAdultCount    = document.getElementById('lblAdultCount');
        const lblAdultUnit     = document.getElementById('lblAdultUnit');
        const lblAdultSubtotal = document.getElementById('lblAdultSubtotal');
        const lblChildCount    = document.getElementById('lblChildCount');
        const lblChildUnit     = document.getElementById('lblChildUnit');
        const lblChildSubtotal = document.getElementById('lblChildSubtotal');
        const lblInfantCount   = document.getElementById('lblInfantCount');
        const lblGrossSubtotal = document.getElementById('lblGrossSubtotal');
        const lblDiscountAmount= document.getElementById('lblDiscountAmount');
        const lblTotalAmount   = document.getElementById('lblTotalAmount');

        function formatMoney(amount) {
            return '৳' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function recalculate() {
            const selectedOpt = pkgSelect.options[pkgSelect.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) return;

            const adultPrice = parseFloat(selectedOpt.getAttribute('data-price')) || 0;
            const childPrice = parseFloat(selectedOpt.getAttribute('data-child-price')) || 0;

            const adults   = Math.max(1, parseInt(adultsInput.value, 10) || 1);
            const children = Math.max(0, parseInt(childrenInput.value, 10) || 0);
            const infants  = Math.max(0, parseInt(infantsInput.value, 10) || 0);

            lblAdultCount.textContent = adults;
            lblAdultUnit.textContent  = formatMoney(adultPrice);
            const adultSubtotal       = adults * adultPrice;
            lblAdultSubtotal.textContent = formatMoney(adultSubtotal);

            lblChildCount.textContent = children;
            lblChildUnit.textContent  = formatMoney(childPrice);
            const childSubtotal       = children * childPrice;
            lblChildSubtotal.textContent = formatMoney(childSubtotal);

            lblInfantCount.textContent = infants;

            const grossSubtotal = adultSubtotal + childSubtotal;
            lblGrossSubtotal.textContent = formatMoney(grossSubtotal);

            const discType = discTypeSelect.value;
            const discVal  = Math.max(0, parseFloat(discValInput.value) || 0);
            let discountAmount = 0;

            if (discType === 'percentage' && discVal > 0) {
                discountAmount = (grossSubtotal * discVal) / 100;
            } else if (discType === 'fixed' && discVal > 0) {
                discountAmount = discVal;
            }

            discountAmount = Math.min(grossSubtotal, discountAmount);
            lblDiscountAmount.textContent = '- ' + formatMoney(discountAmount);

            const total = Math.max(0, grossSubtotal - discountAmount);
            lblTotalAmount.textContent = formatMoney(total);
        }

        pkgSelect.addEventListener('change', recalculate);
        document.querySelectorAll('.calc-trigger').forEach(el => {
            el.addEventListener('input', recalculate);
            el.addEventListener('change', recalculate);
        });

        recalculate();
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
