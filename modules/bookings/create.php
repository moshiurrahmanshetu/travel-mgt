<?php
/**
 * Create New Booking View
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Create New Booking';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('bookings.create');

// Optional pre-selected IDs from query string
$preSelectedCustomerId = (int)($_GET['customer_id'] ?? 0);
$preSelectedPackageId  = (int)($_GET['package_id'] ?? 0);

$customers = [];
$packages = [];

try {
    $pdo = get_db_connection();

    // Fetch active customers
    $cusStmt = $pdo->query("
        SELECT id, customer_code, name, phone, email 
        FROM customers 
        WHERE status = 'active' AND deleted_at IS NULL 
        ORDER BY name ASC
    ");
    $customers = $cusStmt->fetchAll();

    // Fetch active tour packages
    $pkgStmt = $pdo->query("
        SELECT 
            p.id, p.package_code, p.name, p.price, p.child_price, 
            p.duration_days, p.duration_nights, p.available_seats,
            d.name AS destination_name
        FROM tour_packages p
        LEFT JOIN tour_destinations d ON p.destination_id = d.id
        WHERE p.status = 'active' AND p.deleted_at IS NULL 
        ORDER BY p.name ASC
    ");
    $packages = $pkgStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Booking create query error: ' . $e->getMessage());
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
                        <li class="breadcrumb-item active" aria-current="page">New Booking</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Create New Tour Reservation</h2>
            </div>
            <div>
                <a href="<?= url('modules/bookings/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Bookings
                </a>
            </div>
        </div>

        <form action="<?= url('modules/bookings/store.php'); ?>" method="POST" id="bookingForm">
            <?= csrf_field(); ?>

            <div class="row g-4">
                <!-- Left Column: Booking Form Details -->
                <div class="col-12 col-lg-8">
                    <!-- Step 1: Customer & Tour Package Selection -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title"><i class="bi bi-person-check me-2 text-primary"></i> 1. Customer & Tour Selection</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <div class="row g-3">
                                <!-- Customer Select -->
                                <div class="col-12 col-md-6">
                                    <label for="customer_id" class="form-label fw-semibold">Select Customer <span class="text-danger">*</span></label>
                                    <select class="form-select" id="customer_id" name="customer_id" required>
                                        <option value="">-- Choose a Customer --</option>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?= (int)$c['id']; ?>" <?= $preSelectedCustomerId === (int)$c['id'] ? 'selected' : ''; ?>>
                                                <?= e($c['customer_code']); ?> — <?= e($c['name']); ?> (<?= e($c['phone']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Client must be registered. <a href="<?= url('modules/customers/create.php'); ?>" target="_blank">+ Add New Customer</a></div>
                                </div>

                                <!-- Tour Package Select -->
                                <div class="col-12 col-md-6">
                                    <label for="tour_package_id" class="form-label fw-semibold">Select Tour Package <span class="text-danger">*</span></label>
                                    <select class="form-select" id="tour_package_id" name="tour_package_id" required>
                                        <option value="">-- Choose a Tour Package --</option>
                                        <?php foreach ($packages as $p): 
                                            $childPr = (float)($p['child_price'] ?? 0);
                                        ?>
                                            <option 
                                                value="<?= (int)$p['id']; ?>" 
                                                data-price="<?= (float)$p['price']; ?>"
                                                data-child-price="<?= $childPr; ?>"
                                                data-seats="<?= (int)$p['available_seats']; ?>"
                                                data-destination="<?= e($p['destination_name'] ?? ''); ?>"
                                                data-duration="<?= (int)$p['duration_days']; ?>D / <?= (int)$p['duration_nights']; ?>N"
                                                <?= $preSelectedPackageId === (int)$p['id'] ? 'selected' : ''; ?>
                                            >
                                                <?= e($p['package_code']); ?> — <?= e($p['name']); ?> (৳<?= number_format((float)$p['price'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Package Details Card (Dynamic JS) -->
                                <div class="col-12 d-none" id="packageInfoCard">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="row g-2 text-dark small">
                                            <div class="col-6 col-sm-3">
                                                <span class="text-muted d-block">Destination:</span>
                                                <strong id="infoDestination">—</strong>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <span class="text-muted d-block">Duration:</span>
                                                <strong id="infoDuration">—</strong>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <span class="text-muted d-block">Base Adult Price:</span>
                                                <strong id="infoAdultPrice" class="text-primary">—</strong>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <span class="text-muted d-block">Child Price:</span>
                                                <strong id="infoChildPrice" class="text-primary">—</strong>
                                            </div>
                                        </div>
                                    </div>
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
                                        min="<?= date('Y-m-d'); ?>" 
                                        value="<?= date('Y-m-d', strtotime('+7 days')); ?>" 
                                        required
                                    >
                                    <div class="form-text">Must be today or a future departure date.</div>
                                </div>

                                <div class="col-12 col-md-6"></div>

                                <!-- Adults Count -->
                                <div class="col-4">
                                    <label for="adults" class="form-label fw-semibold">Adults <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control calc-trigger" id="adults" name="adults" min="1" max="100" value="1" required>
                                    <div class="form-text small">Age 12+ (Min 1)</div>
                                </div>

                                <!-- Children Count -->
                                <div class="col-4">
                                    <label for="children" class="form-label fw-semibold">Children</label>
                                    <input type="number" class="form-control calc-trigger" id="children" name="children" min="0" max="50" value="0">
                                    <div class="form-text small">Age 3-11</div>
                                </div>

                                <!-- Infants Count -->
                                <div class="col-4">
                                    <label for="infants" class="form-label fw-semibold">Infants</label>
                                    <input type="number" class="form-control calc-trigger" id="infants" name="infants" min="0" max="20" value="0">
                                    <div class="form-text small">Under 3 (Free)</div>
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
                                        <option value="none">No Discount</option>
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount (৳)</option>
                                    </select>
                                </div>

                                <!-- Discount Value -->
                                <div class="col-12 col-md-6">
                                    <label for="discount_value" class="form-label fw-semibold">Discount Value</label>
                                    <input type="number" step="0.01" class="form-control calc-trigger" id="discount_value" name="discount_value" min="0" value="0.00">
                                </div>

                                <!-- Special Requests -->
                                <div class="col-12">
                                    <label for="special_request" class="form-label fw-semibold">Special Client Requests</label>
                                    <textarea class="form-control" id="special_request" name="special_request" rows="2" placeholder="e.g. Sea view room, airport pickup, dietary requirements..."></textarea>
                                </div>

                                <!-- Internal Notes -->
                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold">Internal Booking Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Internal remarks for staff..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Live Price Breakdown Voucher Preview -->
                <div class="col-12 col-lg-4">
                    <div class="admin-card sticky-top" style="top: 80px;">
                        <div class="admin-card-header bg-dark text-white">
                            <h3 class="admin-card-title text-white"><i class="bi bi-receipt me-2"></i> Price Breakdown (Preview)</h3>
                        </div>
                        <div class="admin-card-body p-4">
                            <!-- Adult Price Row -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted"><span id="lblAdultCount">1</span> Adult(s) &times; <span id="lblAdultUnit">৳0.00</span>:</span>
                                <span class="fw-semibold text-dark" id="lblAdultSubtotal">৳0.00</span>
                            </div>

                            <!-- Child Price Row -->
                            <div class="d-flex justify-content-between align-items-center mb-2" id="rowChildSubtotal">
                                <span class="text-muted"><span id="lblChildCount">0</span> Child(ren) &times; <span id="lblChildUnit">৳0.00</span>:</span>
                                <span class="fw-semibold text-dark" id="lblChildSubtotal">৳0.00</span>
                            </div>

                            <!-- Infants Row -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted"><span id="lblInfantCount">0</span> Infant(s):</span>
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
                                <div class="text-muted small mt-1">Payment Status will be initialized as <strong>Unpaid</strong>.</div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-1"></i> Confirm & Create Booking
                                </button>
                                <a href="<?= url('modules/bookings/index.php'); ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Pricing Vanilla JS Engine -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const pkgSelect      = document.getElementById('tour_package_id');
        const pkgInfoCard    = document.getElementById('packageInfoCard');
        const infoDest       = document.getElementById('infoDestination');
        const infoDur        = document.getElementById('infoDuration');
        const infoAdultPr    = document.getElementById('infoAdultPrice');
        const infoChildPr    = document.getElementById('infoChildPrice');

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
            if (!selectedOpt || !selectedOpt.value) {
                pkgInfoCard.classList.add('d-none');
                lblAdultUnit.textContent     = '৳0.00';
                lblAdultSubtotal.textContent = '৳0.00';
                lblChildUnit.textContent     = '৳0.00';
                lblChildSubtotal.textContent = '৳0.00';
                lblGrossSubtotal.textContent = '৳0.00';
                lblDiscountAmount.textContent= '- ৳0.00';
                lblTotalAmount.textContent   = '৳0.00';
                return;
            }

            // Extract package data attributes
            const adultPrice = parseFloat(selectedOpt.getAttribute('data-price')) || 0;
            const childPrice = parseFloat(selectedOpt.getAttribute('data-child-price')) || 0;
            const dest       = selectedOpt.getAttribute('data-destination') || '—';
            const dur        = selectedOpt.getAttribute('data-duration') || '—';

            // Show package card
            pkgInfoCard.classList.remove('d-none');
            infoDest.textContent    = dest;
            infoDur.textContent     = dur;
            infoAdultPr.textContent = formatMoney(adultPrice);
            infoChildPr.textContent = formatMoney(childPrice);

            // Passenger counts
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

            // Subtotal
            const grossSubtotal = adultSubtotal + childSubtotal;
            lblGrossSubtotal.textContent = formatMoney(grossSubtotal);

            // Discount
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

            // Final Total
            const total = Math.max(0, grossSubtotal - discountAmount);
            lblTotalAmount.textContent = formatMoney(total);
        }

        // Attach event listeners
        pkgSelect.addEventListener('change', recalculate);
        document.querySelectorAll('.calc-trigger').forEach(el => {
            el.addEventListener('input', recalculate);
            el.addEventListener('change', recalculate);
        });

        // Trigger on initial page load if pre-selected
        recalculate();
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
