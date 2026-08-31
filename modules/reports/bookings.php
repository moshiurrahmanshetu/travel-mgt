<?php
/**
 * Detailed Booking Sales & Operations Report
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('reports.view');

$canExport = has_permission('reports.export');

// Filter Parameters
$dateBasis     = trim($_GET['date_basis'] ?? 'created_at'); // 'created_at' or 'travel_date'
$dateFrom      = trim($_GET['date_from'] ?? date('Y-m-01'));
$dateTo        = trim($_GET['date_to'] ?? date('Y-m-t'));
$bookingStatus = trim($_GET['booking_status'] ?? '');
$paymentStatus = trim($_GET['payment_status'] ?? '');
$packageId     = (int)($_GET['package_id'] ?? 0);
$customerId    = (int)($_GET['customer_id'] ?? 0);
$action        = trim($_GET['action'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 15;
$offset        = ($page - 1) * $perPage;

$dateField = ($dateBasis === 'travel_date') ? 'b.travel_date' : 'DATE(b.created_at)';

$bookings = [];
$totalRows = 0;
$totalTravellers = 0;
$totalActiveSales = 0.0;
$totalCancelledSales = 0.0;
$totalCollected = 0.0;
$totalDue = 0.0;

$tourPackages = [];
$customers = [];

try {
    $pdo = get_db_connection();

    // Query packages and customers for filter dropdowns
    $tourPackages = $pdo->query("SELECT id, package_code, name FROM tour_packages WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
    $customers = $pdo->query("SELECT id, customer_code, name FROM customers WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

    // Build WHERE conditions
    $where = ["b.deleted_at IS NULL"];
    $params = [];

    if (!empty($dateFrom)) {
        $where[] = "{$dateField} >= :date_from";
        $params['date_from'] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $where[] = "{$dateField} <= :date_to";
        $params['date_to'] = $dateTo;
    }

    if (!empty($bookingStatus) && in_array($bookingStatus, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $where[] = "b.booking_status = :b_status";
        $params['b_status'] = $bookingStatus;
    }

    if (!empty($paymentStatus) && in_array($paymentStatus, ['unpaid', 'partial', 'paid', 'refunded'], true)) {
        $where[] = "b.payment_status = :p_status";
        $params['p_status'] = $paymentStatus;
    }

    if ($packageId > 0) {
        $where[] = "b.tour_package_id = :pkg_id";
        $params['pkg_id'] = $packageId;
    }

    if ($customerId > 0) {
        $where[] = "b.customer_id = :cus_id";
        $params['cus_id'] = $customerId;
    }

    $whereSql = implode(' AND ', $where);

    // Summary Totals Query
    $summaryStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(b.adults + b.children + b.infants), 0) AS total_pax,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount ELSE 0 END), 0) AS active_sales,
            COALESCE(SUM(CASE WHEN b.booking_status = 'cancelled' THEN b.total_amount ELSE 0 END), 0) AS cancelled_sales,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.paid_amount ELSE 0 END), 0) AS total_paid,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.due_amount ELSE 0 END), 0) AS total_due
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages p ON b.tour_package_id = p.id
        WHERE {$whereSql}
    ");
    $summaryStmt->execute($params);
    $summaryData = $summaryStmt->fetch();

    $totalRows           = (int)($summaryData['total_count'] ?? 0);
    $totalTravellers     = (int)($summaryData['total_pax'] ?? 0);
    $totalActiveSales    = (float)($summaryData['active_sales'] ?? 0);
    $totalCancelledSales = (float)($summaryData['cancelled_sales'] ?? 0);
    $totalCollected      = (float)($summaryData['total_paid'] ?? 0);
    $totalDue            = (float)($summaryData['total_due'] ?? 0);

    // CSV Export Handler
    if ($action === 'export_csv' && $canExport) {
        $exportStmt = $pdo->prepare("
            SELECT 
                b.booking_number,
                c.customer_code,
                c.name AS customer_name,
                c.phone AS customer_phone,
                p.name AS package_name,
                p.package_code,
                b.travel_date,
                b.adults,
                b.children,
                b.infants,
                (b.adults + b.children + b.infants) AS total_pax,
                b.total_amount,
                b.paid_amount,
                b.due_amount,
                b.booking_status,
                b.payment_status,
                DATE(b.created_at) AS booking_date
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN tour_packages p ON b.tour_package_id = p.id
            WHERE {$whereSql}
            ORDER BY b.id DESC
        ");
        $exportStmt->execute($params);
        $exportRows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

        $csvHeaders = [
            'Booking Number', 'Customer Code', 'Customer Name', 'Phone',
            'Tour Package', 'Package Code', 'Travel Date', 'Adults', 'Children',
            'Infants', 'Total Pax', 'Total Amount', 'Paid Amount', 'Due Amount',
            'Booking Status', 'Payment Status', 'Booking Date'
        ];

        $filename = 'booking_report_' . date('Ymd_His') . '.csv';
        export_data_to_csv($filename, $csvHeaders, $exportRows);
        exit;
    }

    // Paginated Dataset Query
    $dataStmt = $pdo->prepare("
        SELECT 
            b.*,
            c.name AS customer_name,
            c.customer_code,
            c.phone AS customer_phone,
            p.name AS package_name,
            p.package_code
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages p ON b.tour_package_id = p.id
        WHERE {$whereSql}
        ORDER BY b.id DESC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
    ");
    $dataStmt->execute($params);
    $bookings = $dataStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Booking Report Query Error: ' . $e->getMessage());
}

$totalPages = ceil($totalRows / $perPage);
$pageTitle = 'Detailed Booking Report';

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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= url('modules/reports/index.php'); ?>">Reports</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Bookings Report</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Detailed Booking Sales & Operations Report</h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('modules/reports/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Reports Overview
                </a>
                <?php if ($canExport): ?>
                    <a href="<?= url('modules/reports/bookings.php?' . http_build_query(array_merge($_GET, ['action' => 'export_csv']))); ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/reports/bookings.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <!-- Date Basis -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Date Basis</label>
                        <select class="form-select form-select-sm" name="date_basis">
                            <option value="created_at" <?= $dateBasis === 'created_at' ? 'selected' : ''; ?>>Booking Date</option>
                            <option value="travel_date" <?= $dateBasis === 'travel_date' ? 'selected' : ''; ?>>Travel Departure Date</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($dateFrom); ?>">
                    </div>

                    <!-- Date To -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($dateTo); ?>">
                    </div>

                    <!-- Booking Status -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Booking Status</label>
                        <select class="form-select form-select-sm" name="booking_status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $bookingStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?= $bookingStatus === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?= $bookingStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?= $bookingStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <!-- Payment Status -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Payment Status</label>
                        <select class="form-select form-select-sm" name="payment_status">
                            <option value="">All Payments</option>
                            <option value="unpaid" <?= $paymentStatus === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="partial" <?= $paymentStatus === 'partial' ? 'selected' : ''; ?>>Partial</option>
                            <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="refunded" <?= $paymentStatus === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <!-- Tour Package Selector -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Tour Package</label>
                        <select class="form-select form-select-sm" name="package_id">
                            <option value="">All Tour Packages</option>
                            <?php foreach ($tourPackages as $pkg): ?>
                                <option value="<?= (int)$pkg['id']; ?>" <?= $packageId === (int)$pkg['id'] ? 'selected' : ''; ?>>
                                    <?= e($pkg['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Action Buttons -->
                    <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-funnel me-1"></i> Filter Results
                        </button>
                        <a href="<?= url('modules/reports/bookings.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filtered Dataset Summary KPI Banner -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 col-xl-2">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Filtered Orders</span>
                    <strong class="fs-5 text-dark"><?= $totalRows; ?></strong>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Total Pax (Travellers)</span>
                    <strong class="fs-5 text-primary"><?= $totalTravellers; ?> Pax</strong>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-3">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Active Invoiced Sales</span>
                    <strong class="fs-5 text-dark"><?= format_currency($totalActiveSales); ?></strong>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Total Paid</span>
                    <strong class="fs-5 text-success"><?= format_currency($totalCollected); ?></strong>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Outstanding Due</span>
                    <strong class="fs-5 text-danger"><?= format_currency($totalDue); ?></strong>
                </div>
            </div>
        </div>

        <!-- Report Table Card -->
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h3 class="admin-card-title"><i class="bi bi-table me-2 text-primary"></i> Booking Dataset</h3>
                <span class="badge bg-secondary"><?= $totalRows; ?> records found</span>
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
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($bookings)): ?>
                                <?php foreach ($bookings as $b): 
                                    $paxCount = (int)$b['adults'] + (int)$b['children'] + (int)$b['infants'];
                                    
                                    $bStatusClass = 'bg-secondary';
                                    if ($b['booking_status'] === 'pending') $bStatusClass = 'bg-warning text-dark';
                                    elseif ($b['booking_status'] === 'confirmed') $bStatusClass = 'bg-primary';
                                    elseif ($b['booking_status'] === 'completed') $bStatusClass = 'bg-success';
                                    elseif ($b['booking_status'] === 'cancelled') $bStatusClass = 'bg-danger';

                                    $pStatusClass = 'bg-secondary';
                                    if ($b['payment_status'] === 'paid') $pStatusClass = 'bg-success';
                                    elseif ($b['payment_status'] === 'partial') $pStatusClass = 'bg-warning text-dark';
                                    elseif ($b['payment_status'] === 'unpaid') $pStatusClass = 'bg-danger';
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <a href="<?= url('modules/bookings/view.php?id=' . $b['id']); ?>" class="fw-bold text-decoration-none">
                                                <code><?= e($b['booking_number']); ?></code>
                                            </a>
                                            <div class="text-muted small" style="font-size: 0.7rem;"><?= format_date($b['created_at'], 'M d, Y'); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($b['customer_name']); ?></div>
                                            <small class="text-muted"><?= e($b['customer_phone']); ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark small fw-semibold"><?= e($b['package_name']); ?></div>
                                            <small class="text-muted"><code><?= e($b['package_code']); ?></code></small>
                                        </td>
                                        <td>
                                            <span class="small text-dark fw-semibold"><?= format_date($b['travel_date'], 'M d, Y'); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= $paxCount; ?> Pax</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark"><?= format_currency($b['total_amount']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="text-success small fw-bold"><?= format_currency($b['paid_amount']); ?></span>
                                        </td>
                                        <td>
                                            <span class="text-danger small fw-bold"><?= format_currency($b['due_amount']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $bStatusClass; ?>">
                                                <?= ucfirst(e($b['booking_status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $pStatusClass; ?>">
                                                <?= ucfirst(e($b['payment_status'])); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="<?= url('modules/bookings/view.php?id=' . $b['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Booking">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                                        No bookings found matching your selected criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Footer -->
            <?php if ($totalPages > 1): ?>
                <div class="admin-card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                    <span class="small text-muted">Showing page <?= $page; ?> of <?= $totalPages; ?></span>
                    <nav aria-label="Bookings Report pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/reports/bookings.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= url('modules/reports/bookings.php?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/reports/bookings.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
