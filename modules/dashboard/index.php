<?php
/**
 * Admin Dashboard Module
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Dashboard Overview';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Fetch Live Stats
$totalPackages = 0;
$totalCategories = 0;
$totalDestinations = 0;
$totalCustomers = 0;
$totalBookings = 0;
$pendingBookings = 0;
$confirmedBookings = 0;
$recentPackages = [];
$recentBookings = [];

try {
    $pdo = get_db_connection();
    
    // Count active packages
    $stmtPkg = $pdo->query("SELECT COUNT(*) FROM tour_packages WHERE deleted_at IS NULL");
    $totalPackages = (int)$stmtPkg->fetchColumn();

    // Count destinations
    $stmtDest = $pdo->query("SELECT COUNT(*) FROM tour_destinations WHERE deleted_at IS NULL");
    $totalDestinations = (int)$stmtDest->fetchColumn();

    // Count categories
    $stmtCat = $pdo->query("SELECT COUNT(*) FROM tour_categories WHERE deleted_at IS NULL");
    $totalCategories = (int)$stmtCat->fetchColumn();

    // Count active customers
    $stmtCus = $pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL");
    $totalCustomers = (int)$stmtCus->fetchColumn();

    // Count active bookings (Phase 04)
    $stmtBk = $pdo->query("SELECT COUNT(*) FROM bookings WHERE deleted_at IS NULL");
    $totalBookings = (int)$stmtBk->fetchColumn();

    $stmtBkPending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending' AND deleted_at IS NULL");
    $pendingBookings = (int)$stmtBkPending->fetchColumn();

    $stmtBkConfirmed = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed' AND deleted_at IS NULL");
    $confirmedBookings = (int)$stmtBkConfirmed->fetchColumn();

    // Fetch recent packages
    $stmtRecent = $pdo->query("
        SELECT p.*, c.name AS category_name, d.name AS destination_name 
        FROM tour_packages p 
        LEFT JOIN tour_categories c ON p.category_id = c.id 
        LEFT JOIN tour_destinations d ON p.destination_id = d.id 
        WHERE p.deleted_at IS NULL 
        ORDER BY p.id DESC 
        LIMIT 5
    ");
    $recentPackages = $stmtRecent->fetchAll();

    // Fetch recent bookings (Phase 04)
    $stmtRecentBk = $pdo->query("
        SELECT 
            b.*,
            c.name AS customer_name,
            c.customer_code,
            p.name AS package_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages p ON b.tour_package_id = p.id
        WHERE b.deleted_at IS NULL
        ORDER BY b.id DESC
        LIMIT 5
    ");
    $recentBookings = $stmtRecentBk->fetchAll();

    // Financial Metrics (Phase 05)
    $stmtCol = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed' AND deleted_at IS NULL");
    $totalCollected = (float)$stmtCol->fetchColumn();

    $stmtOut = $pdo->query("SELECT COALESCE(SUM(due_amount), 0) FROM bookings WHERE deleted_at IS NULL AND booking_status != 'cancelled'");
    $totalOutstanding = (float)$stmtOut->fetchColumn();

    $stmtToday = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed' AND payment_date = CURRENT_DATE() AND deleted_at IS NULL");
    // Upcoming Confirmed Tours (Phase 06)
    $stmtUpcoming = $pdo->query("
        SELECT 
            b.*,
            c.name AS customer_name,
            c.phone AS customer_phone,
            p.name AS package_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages p ON b.tour_package_id = p.id
        WHERE b.deleted_at IS NULL 
          AND b.booking_status = 'confirmed' 
          AND b.travel_date >= CURRENT_DATE()
        ORDER BY b.travel_date ASC
        LIMIT 5
    ");
    $upcomingTours = $stmtUpcoming->fetchAll();

    // Recent Completed Payments (Phase 06)
    $stmtRecentPay = $pdo->query("
        SELECT 
            p.*,
            b.booking_number,
            c.name AS customer_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        WHERE p.deleted_at IS NULL AND p.payment_status = 'completed'
        ORDER BY p.payment_date DESC, p.id DESC
        LIMIT 5
    ");
    $recentPayments = $stmtRecentPay->fetchAll();

} catch (PDOException $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Welcome Banner Card with Quick Actions -->
        <div class="welcome-card mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h2 class="fs-4 fw-bold mb-0 text-dark">Welcome back, <?= e($currentUser['name']); ?>!</h2>
                        <span class="badge bg-primary px-2 py-1" style="font-size: 0.75rem;"><?= e($currentUser['role_name']); ?></span>
                    </div>
                    <p class="text-muted mb-0 small">
                        Here is the live operational summary of the Tour & Travel Management System.
                    </p>
                </div>
                <div class="text-md-end text-muted small">
                    <div><i class="bi bi-calendar-event me-1"></i> <?= date('l, F d, Y'); ?></div>
                    <div class="mt-1"><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> System Operational</span></div>
                </div>
            </div>

            <!-- Quick Action Shortcuts -->
            <div class="mt-3 pt-3 border-top d-flex gap-2 flex-wrap">
                <?php if (has_permission('bookings.create')): ?>
                    <a href="<?= url('modules/bookings/create.php'); ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> New Booking
                    </a>
                <?php endif; ?>
                <?php if (has_permission('customers.create')): ?>
                    <a href="<?= url('modules/customers/create.php'); ?>" class="btn btn-outline-secondary btn-sm bg-white">
                        <i class="bi bi-person-plus me-1"></i> Add Customer
                    </a>
                <?php endif; ?>
                <?php if (has_permission('tours.create')): ?>
                    <a href="<?= url('modules/tours/create.php'); ?>" class="btn btn-outline-secondary btn-sm bg-white">
                        <i class="bi bi-compass me-1"></i> Add Tour
                    </a>
                <?php endif; ?>
                <?php if (has_permission('payments.create')): ?>
                    <a href="<?= url('modules/payments/create.php'); ?>" class="btn btn-outline-success btn-sm bg-white">
                        <i class="bi bi-credit-card me-1"></i> Record Payment
                    </a>
                <?php endif; ?>
                <?php if (has_permission('reports.view')): ?>
                    <a href="<?= url('modules/reports/index.php'); ?>" class="btn btn-outline-dark btn-sm bg-white">
                        <i class="bi bi-bar-chart-line me-1"></i> Reports & Analytics
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Tour Packages (Live) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="<?= url('modules/tours/index.php'); ?>" class="text-decoration-none">
                    <div class="kpi-card">
                        <div class="kpi-icon-box kpi-icon-primary">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="kpi-info">
                            <div class="kpi-title">Tour Packages</div>
                            <div class="kpi-value"><?= $totalPackages; ?></div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Total Destinations (Live) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="<?= url('modules/tours/destinations.php'); ?>" class="text-decoration-none">
                    <div class="kpi-card">
                        <div class="kpi-icon-box kpi-icon-success">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div class="kpi-info">
                            <div class="kpi-title">Destinations</div>
                            <div class="kpi-value"><?= $totalDestinations; ?></div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Total Customers (Live Phase 03) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="<?= url('modules/customers/index.php'); ?>" class="text-decoration-none">
                    <div class="kpi-card">
                        <div class="kpi-icon-box kpi-icon-warning">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="kpi-info">
                            <div class="kpi-title">Total Customers</div>
                            <div class="kpi-value"><?= $totalCustomers; ?></div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Total Bookings (Live Phase 04) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="<?= url('modules/bookings/index.php'); ?>" class="text-decoration-none">
                    <div class="kpi-card">
                        <div class="kpi-icon-box kpi-icon-info">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="kpi-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="kpi-title">Total Bookings</div>
                            </div>
                            <div class="kpi-value"><?= $totalBookings; ?></div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Tour Packages & Recent Bookings -->
        <div class="row g-3">
            <!-- Left Column: Recent Bookings & Tour Packages -->
            <div class="col-12 col-lg-8">
                <!-- Recent Bookings Table (Phase 04 Integration) -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-calendar-check me-2 text-primary"></i> Recent Reservations
                        </h3>
                        <a href="<?= url('modules/bookings/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                            View All Bookings
                        </a>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Booking #</th>
                                        <th>Customer</th>
                                        <th>Tour Package</th>
                                        <th>Travel Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentBookings)): ?>
                                        <?php foreach ($recentBookings as $rb): 
                                            $rbStatusClass = 'bg-secondary';
                                            if ($rb['booking_status'] === 'pending') $rbStatusClass = 'bg-warning text-dark';
                                            elseif ($rb['booking_status'] === 'confirmed') $rbStatusClass = 'bg-primary';
                                            elseif ($rb['booking_status'] === 'completed') $rbStatusClass = 'bg-success';
                                            elseif ($rb['booking_status'] === 'cancelled') $rbStatusClass = 'bg-danger';
                                        ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <a href="<?= url('modules/bookings/view.php?id=' . $rb['id']); ?>" class="fw-bold text-decoration-none">
                                                        <code><?= e($rb['booking_number']); ?></code>
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($rb['customer_name']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="text-dark small" style="max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        <?= e($rb['package_name']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="small text-muted"><?= format_date($rb['travel_date'], 'M d, Y'); ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-dark small"><?= format_currency($rb['total_amount']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $rbStatusClass; ?>" style="font-size: 0.65rem;">
                                                        <?= ucfirst(e($rb['booking_status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="pe-3 text-end">
                                                    <a href="<?= url('modules/bookings/view.php?id=' . $rb['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Booking">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No booking reservations found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Tour Packages Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-compass me-2 text-primary"></i> Recent Tour Packages
                        </h3>
                        <a href="<?= url('modules/tours/create.php'); ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Add Package
                        </a>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Code</th>
                                        <th>Package Name</th>
                                        <th>Destination</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentPackages)): ?>
                                        <?php foreach ($recentPackages as $pkg): 
                                            $finalPrice = calculate_discounted_price($pkg['price'], $pkg['discount_type'], $pkg['discount_value']);
                                        ?>
                                            <tr>
                                                <td class="ps-3"><code><?= e($pkg['package_code']); ?></code></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($pkg['name']); ?></div>
                                                    <small class="text-muted"><?= e($pkg['category_name'] ?? 'Uncategorized'); ?></small>
                                                </td>
                                                <td><?= e($pkg['destination_name'] ?? '—'); ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= format_currency($finalPrice); ?></div>
                                                    <?php if ($pkg['discount_type'] !== 'none'): ?>
                                                        <small class="text-muted text-decoration-line-through"><?= format_currency($pkg['price']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $pkg['status'] === 'active' ? 'bg-success' : ($pkg['status'] === 'draft' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                                        <?= ucfirst(e($pkg['status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="pe-3 text-end">
                                                    <a href="<?= url('modules/tours/view.php?id=' . $pkg['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="<?= url('modules/tours/edit.php?id=' . $pkg['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2 ms-1" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No tour packages found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Tour Departures (Phase 06) -->
                <div class="admin-card mt-4">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h3 class="admin-card-title">
                            <i class="bi bi-calendar-event me-2 text-primary"></i> Upcoming Confirmed Departures
                        </h3>
                        <a href="<?= url('modules/bookings/index.php?status=confirmed'); ?>" class="btn btn-outline-secondary btn-sm">
                            All Confirmed
                        </a>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Booking #</th>
                                        <th>Customer</th>
                                        <th>Tour Package</th>
                                        <th>Travel Date</th>
                                        <th>Pax</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($upcomingTours)): ?>
                                        <?php foreach ($upcomingTours as $ut): 
                                            $utPax = (int)$ut['adults'] + (int)$ut['children'] + (int)$ut['infants'];
                                        ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <a href="<?= url('modules/bookings/view.php?id=' . $ut['id']); ?>" class="fw-bold text-decoration-none">
                                                        <code><?= e($ut['booking_number']); ?></code>
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($ut['customer_name']); ?></div>
                                                    <small class="text-muted"><?= e($ut['customer_phone']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="text-dark small fw-semibold"><?= e($ut['package_name']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= format_date($ut['travel_date'], 'M d, Y'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= $utPax; ?> Pax</span>
                                                </td>
                                                <td class="pe-3 text-end">
                                                    <a href="<?= url('modules/bookings/view.php?id=' . $ut['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Booking">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No upcoming confirmed departures found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Financial Overview & Recent Payments -->
            <div class="col-12 col-lg-4">
                <!-- Financial Overview Card (Phase 05 Integration) -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h3 class="admin-card-title">
                            <i class="bi bi-wallet2 me-2 text-primary"></i> Collections & Revenue
                        </h3>
                        <a href="<?= url('modules/payments/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                            Payments
                        </a>
                    </div>
                    <div class="admin-card-body p-3">
                        <div class="p-3 bg-light rounded border mb-3">
                            <span class="text-muted small d-block mb-1">Total Revenue Collected:</span>
                            <h4 class="fs-4 fw-bold text-success mb-0"><?= format_currency($totalCollected); ?></h4>
                        </div>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Total Outstanding Due:</span>
                                <strong class="<?= $totalOutstanding > 0 ? 'text-danger' : 'text-success'; ?>"><?= format_currency($totalOutstanding); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Collected Today:</span>
                                <strong class="text-primary"><?= format_currency($paymentsToday); ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Recent Payments Card (Phase 06) -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h3 class="admin-card-title">
                            <i class="bi bi-credit-card me-2 text-primary"></i> Recent Payments
                        </h3>
                        <a href="<?= url('modules/payments/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                            View All
                        </a>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Receipt</th>
                                        <th>Client</th>
                                        <th class="pe-3 text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentPayments)): ?>
                                        <?php foreach ($recentPayments as $rp): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <a href="<?= url('modules/payments/view.php?id=' . $rp['id']); ?>" class="fw-bold text-decoration-none">
                                                        <code><?= e($rp['payment_number']); ?></code>
                                                    </a>
                                                    <div class="text-muted" style="font-size: 0.7rem;"><?= format_date($rp['payment_date'], 'M d'); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 110px;"><?= e($rp['customer_name']); ?></div>
                                                </td>
                                                <td class="pe-3 text-end">
                                                    <strong class="text-success"><?= format_currency($rp['amount']); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted small">No payment transactions recorded.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Account Summary Card -->
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
