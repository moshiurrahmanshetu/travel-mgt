<?php
/**
 * Reports & Analytics Executive Dashboard
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Reports & Analytics Dashboard';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('reports.view');

$canExport = has_permission('reports.export');

// Period Presets
$period = trim($_GET['period'] ?? 'this_month');
$customDateFrom = trim($_GET['date_from'] ?? '');
$customDateTo   = trim($_GET['date_to'] ?? '');

$today = date('Y-m-d');
$dateFrom = '';
$dateTo   = '';

switch ($period) {
    case 'today':
        $dateFrom = $today;
        $dateTo   = $today;
        $periodLabel = 'Today (' . date('M d, Y') . ')';
        break;
    case 'this_week':
        $dateFrom = date('Y-m-d', strtotime('monday this week'));
        $dateTo   = date('Y-m-d', strtotime('sunday this week'));
        $periodLabel = 'This Week (' . date('M d', strtotime($dateFrom)) . ' – ' . date('M d, Y', strtotime($dateTo)) . ')';
        break;
    case 'this_month':
        $dateFrom = date('Y-m-01');
        $dateTo   = date('Y-m-t');
        $periodLabel = 'This Month (' . date('F Y') . ')';
        break;
    case 'this_year':
        $dateFrom = date('Y-01-01');
        $dateTo   = date('Y-12-31');
        $periodLabel = 'This Year (' . date('Y') . ')';
        break;
    case 'custom':
        if (!empty($customDateFrom) && !empty($customDateTo)) {
            if ($customDateFrom <= $customDateTo) {
                $dateFrom = $customDateFrom;
                $dateTo   = $customDateTo;
                $periodLabel = 'Custom Range (' . date('M d, Y', strtotime($dateFrom)) . ' – ' . date('M d, Y', strtotime($dateTo)) . ')';
            } else {
                set_flash('error', 'Invalid date range: Start date cannot be after end date.');
                $dateFrom = date('Y-m-01');
                $dateTo   = date('Y-m-t');
                $period = 'this_month';
                $periodLabel = 'This Month (' . date('F Y') . ')';
            }
        } else {
            $dateFrom = date('Y-m-01');
            $dateTo   = date('Y-m-t');
            $periodLabel = 'This Month (' . date('F Y') . ')';
        }
        break;
    case 'all_time':
    default:
        $dateFrom = '2000-01-01';
        $dateTo   = '2099-12-31';
        $periodLabel = 'All Time History';
        break;
}

$totalBookings      = 0;
$pendingBookings    = 0;
$confirmedBookings  = 0;
$completedBookings  = 0;
$cancelledBookings  = 0;
$activeBookingSales = 0.0;
$cancelledSales     = 0.0;
$totalCollected     = 0.0;
$totalOutstanding   = 0.0;
$methodStats        = [];
$topTours           = [];

try {
    $pdo = get_db_connection();

    // 1. Booking Metrics in Selected Period (Based on created_at date)
    $stmtBk = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
            COALESCE(SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END), 0) AS confirmed_count,
            COALESCE(SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_count,
            COALESCE(SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_count,
            COALESCE(SUM(CASE WHEN booking_status != 'cancelled' THEN total_amount ELSE 0 END), 0) AS active_sales,
            COALESCE(SUM(CASE WHEN booking_status = 'cancelled' THEN total_amount ELSE 0 END), 0) AS cancelled_sales
        FROM bookings
        WHERE deleted_at IS NULL
          AND DATE(created_at) >= :d_from
          AND DATE(created_at) <= :d_to
    ");
    $stmtBk->execute(['d_from' => $dateFrom, 'd_to' => $dateTo]);
    $bkMetrics = $stmtBk->fetch();

    $totalBookings      = (int)($bkMetrics['total_count'] ?? 0);
    $pendingBookings    = (int)($bkMetrics['pending_count'] ?? 0);
    $confirmedBookings  = (int)($bkMetrics['confirmed_count'] ?? 0);
    $completedBookings  = (int)($bkMetrics['completed_count'] ?? 0);
    $cancelledBookings  = (int)($bkMetrics['cancelled_count'] ?? 0);
    $activeBookingSales = (float)($bkMetrics['active_sales'] ?? 0);
    $cancelledSales     = (float)($bkMetrics['cancelled_sales'] ?? 0);

    // 2. Collected Payments in Selected Period (Based on payment_date)
    $stmtPay = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total_collected
        FROM payments
        WHERE payment_status = 'completed'
          AND deleted_at IS NULL
          AND payment_date >= :d_from
          AND payment_date <= :d_to
    ");
    $stmtPay->execute(['d_from' => $dateFrom, 'd_to' => $dateTo]);
    $totalCollected = (float)$stmtPay->fetchColumn();

    // Outstanding Balance (Active Bookings in period minus Collected Revenue)
    $totalOutstanding = max(0.0, round($activeBookingSales - $totalCollected, 2));

    // 3. Payment Method Breakdown in Selected Period
    $stmtMethods = $pdo->prepare("
        SELECT 
            payment_method,
            COUNT(*) AS trx_count,
            COALESCE(SUM(amount), 0) AS total_amount
        FROM payments
        WHERE payment_status = 'completed'
          AND deleted_at IS NULL
          AND payment_date >= :d_from
          AND payment_date <= :d_to
        GROUP BY payment_method
        ORDER BY total_amount DESC
    ");
    $stmtMethods->execute(['d_from' => $dateFrom, 'd_to' => $dateTo]);
    $methodStats = $stmtMethods->fetchAll();

    // 4. Top Performing Tour Packages in Selected Period
    $stmtTopTours = $pdo->prepare("
        SELECT 
            tp.id,
            tp.package_code,
            tp.name,
            COUNT(b.id) AS total_orders,
            COALESCE(SUM(CASE WHEN b.booking_status IN ('confirmed', 'completed') THEN 1 ELSE 0 END), 0) AS confirmed_orders,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN (b.adults + b.children + b.infants) ELSE 0 END), 0) AS total_pax,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount ELSE 0 END), 0) AS gross_sales
        FROM tour_packages tp
        JOIN bookings b ON b.tour_package_id = tp.id
        WHERE b.deleted_at IS NULL
          AND DATE(b.created_at) >= :d_from
          AND DATE(b.created_at) <= :d_to
        GROUP BY tp.id, tp.package_code, tp.name
        ORDER BY confirmed_orders DESC, gross_sales DESC
        LIMIT 5
    ");
    $stmtTopTours->execute(['d_from' => $dateFrom, 'd_to' => $dateTo]);
    $topTours = $stmtTopTours->fetchAll();

} catch (PDOException $e) {
    error_log('Reports Overview Error: ' . $e->getMessage());
}
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= url('modules/dashboard/index.php'); ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reports & Analytics</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Executive Reports & Business Analytics</h2>
                <div class="text-muted small mt-1">
                    <i class="bi bi-clock-history me-1"></i> Active Reporting Scope: <strong><?= e($periodLabel); ?></strong>
                </div>
            </div>

            <!-- Quick Navigation Report Links -->
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('modules/reports/bookings.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-journal-text me-1"></i> Bookings Report
                </a>
                <a href="<?= url('modules/reports/payments.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-credit-card me-1"></i> Payments Report
                </a>
                <a href="<?= url('modules/reports/tours.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-compass me-1"></i> Tour Performance
                </a>
                <a href="<?= url('modules/reports/customers.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-people me-1"></i> Customer Summary
                </a>
            </div>
        </div>

        <!-- Period Filter Bar Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/reports/index.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Reporting Period</label>
                        <select class="form-select form-select-sm" name="period" id="periodSelect">
                            <option value="today" <?= $period === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="this_week" <?= $period === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="this_month" <?= $period === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="this_year" <?= $period === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                            <option value="all_time" <?= $period === 'all_time' ? 'selected' : ''; ?>>All Time</option>
                            <option value="custom" <?= $period === 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 <?= $period === 'custom' ? '' : 'd-none'; ?>" id="customDateFromCol">
                        <label class="form-label small fw-semibold text-muted mb-1">Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($customDateFrom ?: date('Y-m-01')); ?>">
                    </div>

                    <div class="col-6 col-md-3 <?= $period === 'custom' ? '' : 'd-none'; ?>" id="customDateToCol">
                        <label class="form-label small fw-semibold text-muted mb-1">Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($customDateTo ?: date('Y-m-t')); ?>">
                    </div>

                    <div class="col-12 col-md-3 d-flex align-items-end gap-2 pt-md-4">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="bi bi-funnel me-1"></i> Apply Period
                        </button>
                        <a href="<?= url('modules/reports/index.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Revenue & Financial Summary Cards (Row 1) -->
        <div class="row g-3 mb-4">
            <!-- Active Invoiced Revenue -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-primary">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Booking Revenue</div>
                        <div class="kpi-value fs-5"><?= format_currency($activeBookingSales); ?></div>
                        <span class="text-muted" style="font-size: 0.7rem;">Active Valid Bookings</span>
                    </div>
                </div>
            </div>

            <!-- Collected Revenue -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-success">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Collected Revenue</div>
                        <div class="kpi-value fs-5 text-success"><?= format_currency($totalCollected); ?></div>
                        <span class="text-muted" style="font-size: 0.7rem;">Completed Payments</span>
                    </div>
                </div>
            </div>

            <!-- Outstanding Due Balance -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-danger">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Outstanding Balance</div>
                        <div class="kpi-value fs-5 text-danger"><?= format_currency($totalOutstanding); ?></div>
                        <span class="text-muted" style="font-size: 0.7rem;">Uncollected Receivables</span>
                    </div>
                </div>
            </div>

            <!-- Cancelled Sales -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-secondary">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Cancelled Value</div>
                        <div class="kpi-value fs-5 text-muted"><?= format_currency($cancelledSales); ?></div>
                        <span class="text-muted" style="font-size: 0.7rem;"><?= $cancelledBookings; ?> Cancelled Orders</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Status Metrics (Row 2) -->
        <div class="row g-3 mb-4">
            <!-- Total Reservations -->
            <div class="col-6 col-md-3">
                <div class="admin-card p-3 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Total Bookings</span>
                            <h4 class="fs-4 fw-bold text-dark mb-0"><?= $totalBookings; ?></h4>
                        </div>
                        <div class="fs-3 text-primary"><i class="bi bi-calendar-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Confirmed -->
            <div class="col-6 col-md-3">
                <div class="admin-card p-3 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Confirmed</span>
                            <h4 class="fs-4 fw-bold text-success mb-0"><?= $confirmedBookings; ?></h4>
                        </div>
                        <div class="fs-3 text-success"><i class="bi bi-check2-circle"></i></div>
                    </div>
                </div>
            </div>

            <!-- Pending -->
            <div class="col-6 col-md-3">
                <div class="admin-card p-3 border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Pending Approval</span>
                            <h4 class="fs-4 fw-bold text-warning text-dark mb-0"><?= $pendingBookings; ?></h4>
                        </div>
                        <div class="fs-3 text-warning"><i class="bi bi-clock-history"></i></div>
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="col-6 col-md-3">
                <div class="admin-card p-3 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Completed Tours</span>
                            <h4 class="fs-4 fw-bold text-dark mb-0"><?= $completedBookings; ?></h4>
                        </div>
                        <div class="fs-3 text-info"><i class="bi bi-trophy"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breakdown Section: Payment Methods & Top Packages -->
        <div class="row g-4 mb-4">
            <!-- Left Column: Payment Method Collections -->
            <div class="col-12 col-lg-6">
                <div class="admin-card h-100">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h3 class="admin-card-title">
                            <i class="bi bi-credit-card me-2 text-primary"></i> Collections by Payment Method
                        </h3>
                        <a href="<?= url('modules/reports/payments.php'); ?>" class="btn btn-outline-secondary btn-sm">
                            Detailed Report
                        </a>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Payment Method</th>
                                        <th class="text-center">Receipts</th>
                                        <th class="pe-3 text-end">Collected Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($methodStats)): ?>
                                        <?php 
                                        $methodLabels = [
                                            'cash'           => 'Cash Counter Deposit',
                                            'bank_transfer'  => 'Bank Transfer (EFT/NPSB)',
                                            'card'           => 'Credit / Debit Card',
                                            'mobile_banking' => 'Mobile Banking (bKash/Nagad)',
                                            'other'          => 'Other Methods'
                                        ];
                                        foreach ($methodStats as $ms): 
                                            $mName = $methodLabels[$ms['payment_method']] ?? ucfirst(str_replace('_', ' ', $ms['payment_method']));
                                        ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="fw-semibold text-dark"><?= e($mName); ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= (int)$ms['trx_count']; ?></span>
                                                </td>
                                                <td class="pe-3 text-end">
                                                    <strong class="text-success"><?= format_currency($ms['total_amount']); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">No completed payment receipts in this period.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Top Performing Tour Packages -->
            <div class="col-12 col-lg-6">
                <div class="admin-card h-100">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h3 class="admin-card-title">
                            <i class="bi bi-star me-2 text-primary"></i> Top Tour Packages (By Demand)
                        </h3>
                        <a href="<?= url('modules/reports/tours.php'); ?>" class="btn btn-outline-secondary btn-sm">
                            Tour Analytics
                        </a>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Package</th>
                                        <th class="text-center">Confirmed Pax</th>
                                        <th class="pe-3 text-end">Gross Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($topTours)): ?>
                                        <?php foreach ($topTours as $tt): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="fw-semibold text-dark"><?= e($tt['name']); ?></div>
                                                    <small class="text-muted"><code><?= e($tt['package_code']); ?></code></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= (int)$tt['total_pax']; ?> Pax</span>
                                                </td>
                                                <td class="pe-3 text-end">
                                                    <strong class="text-primary"><?= format_currency($tt['gross_sales']); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">No booking reservations recorded in this period.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Custom Date Fields -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const periodSelect = document.getElementById('periodSelect');
        const fromCol = document.getElementById('customDateFromCol');
        const toCol = document.getElementById('customDateToCol');

        if (periodSelect) {
            periodSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    fromCol.classList.remove('d-none');
                    toCol.classList.remove('d-none');
                } else {
                    fromCol.classList.add('d-none');
                    toCol.classList.add('d-none');
                }
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
