<?php
/**
 * Detailed Payment & Collection Report
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('reports.view');

$canExport = has_permission('reports.export');

// Filter Parameters
$dateFrom      = trim($_GET['date_from'] ?? date('Y-m-01'));
$dateTo        = trim($_GET['date_to'] ?? date('Y-m-t'));
$paymentStatus = trim($_GET['payment_status'] ?? '');
$paymentMethod = trim($_GET['payment_method'] ?? '');
$action        = trim($_GET['action'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 15;
$offset        = ($page - 1) * $perPage;

$payments = [];
$totalRows = 0;
$totalCompletedCount = 0;
$totalCompletedSum = 0.0;
$totalPendingSum = 0.0;
$totalPendingCount = 0;
$totalFailedSum = 0.0;
$methodStats = [];

try {
    $pdo = get_db_connection();

    // Build WHERE conditions
    $where = ["p.deleted_at IS NULL"];
    $params = [];

    if (!empty($dateFrom)) {
        $where[] = "p.payment_date >= :date_from";
        $params['date_from'] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $where[] = "p.payment_date <= :date_to";
        $params['date_to'] = $dateTo;
    }

    if (!empty($paymentStatus) && in_array($paymentStatus, ['completed', 'pending', 'failed', 'refunded'], true)) {
        $where[] = "p.payment_status = :p_status";
        $params['p_status'] = $paymentStatus;
    }

    if (!empty($paymentMethod) && in_array($paymentMethod, ['cash', 'bank_transfer', 'card', 'mobile_banking', 'other'], true)) {
        $where[] = "p.payment_method = :p_method";
        $params['p_method'] = $paymentMethod;
    }

    $whereSql = implode(' AND ', $where);

    // Summary Totals Query
    $summaryStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(CASE WHEN p.payment_status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_count,
            COALESCE(SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END), 0) AS completed_sum,
            COALESCE(SUM(CASE WHEN p.payment_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
            COALESCE(SUM(CASE WHEN p.payment_status = 'pending' THEN p.amount ELSE 0 END), 0) AS pending_sum,
            COALESCE(SUM(CASE WHEN p.payment_status IN ('failed', 'refunded') THEN p.amount ELSE 0 END), 0) AS failed_sum
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        WHERE {$whereSql}
    ");
    $summaryStmt->execute($params);
    $summaryData = $summaryStmt->fetch();

    $totalRows           = (int)($summaryData['total_count'] ?? 0);
    $totalCompletedCount = (int)($summaryData['completed_count'] ?? 0);
    $totalCompletedSum   = (float)($summaryData['completed_sum'] ?? 0);
    $totalPendingCount   = (int)($summaryData['pending_count'] ?? 0);
    $totalPendingSum     = (float)($summaryData['pending_sum'] ?? 0);
    $totalFailedSum      = (float)($summaryData['failed_sum'] ?? 0);

    // Method Breakdown Query for the filtered period
    $methodStmt = $pdo->prepare("
        SELECT 
            p.payment_method,
            COUNT(*) AS trx_count,
            COALESCE(SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END), 0) AS total_amount
        FROM payments p
        WHERE {$whereSql}
        GROUP BY p.payment_method
        ORDER BY total_amount DESC
    ");
    $methodStmt->execute($params);
    $methodStats = $methodStmt->fetchAll();

    // CSV Export Handler
    if ($action === 'export_csv' && $canExport) {
        $exportStmt = $pdo->prepare("
            SELECT 
                p.payment_number,
                b.booking_number,
                c.customer_code,
                c.name AS customer_name,
                c.phone AS customer_phone,
                p.payment_date,
                p.amount,
                p.payment_method,
                p.transaction_id,
                p.payment_status,
                u.name AS collector_name,
                p.notes
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN customers c ON b.customer_id = c.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE {$whereSql}
            ORDER BY p.id DESC
        ");
        $exportStmt->execute($params);
        $exportRows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

        $csvHeaders = [
            'Payment Receipt #', 'Booking Number', 'Customer Code', 'Customer Name',
            'Customer Phone', 'Payment Date', 'Amount (BDT)', 'Payment Method',
            'Transaction Reference ID', 'Payment Status', 'Collected By', 'Remarks'
        ];

        $filename = 'payments_report_' . date('Ymd_His') . '.csv';
        export_data_to_csv($filename, $csvHeaders, $exportRows);
        exit;
    }

    // Paginated Dataset Query
    $dataStmt = $pdo->prepare("
        SELECT 
            p.*,
            b.booking_number,
            c.name AS customer_name,
            c.customer_code,
            c.phone AS customer_phone,
            u.name AS collector_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        LEFT JOIN users u ON p.created_by = u.id
        WHERE {$whereSql}
        ORDER BY p.id DESC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
    ");
    $dataStmt->execute($params);
    $payments = $dataStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Payments Report Query Error: ' . $e->getMessage());
}

$totalPages = ceil($totalRows / $perPage);
$pageTitle = 'Detailed Payments & Collections Report';

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
                        <li class="breadcrumb-item active" aria-current="page">Payments Report</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Payments & Revenue Collections Report</h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('modules/reports/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Reports Overview
                </a>
                <?php if ($canExport): ?>
                    <a href="<?= url('modules/reports/payments.php?' . http_build_query(array_merge($_GET, ['action' => 'export_csv']))); ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/reports/payments.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <!-- Date From -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Payment Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($dateFrom); ?>">
                    </div>

                    <!-- Date To -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Payment Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($dateTo); ?>">
                    </div>

                    <!-- Payment Status -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Payment Status</label>
                        <select class="form-select form-select-sm" name="payment_status">
                            <option value="">All Statuses</option>
                            <option value="completed" <?= $paymentStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?= $paymentStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?= $paymentStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?= $paymentStatus === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Payment Method</label>
                        <select class="form-select form-select-sm" name="payment_method">
                            <option value="">All Payment Methods</option>
                            <option value="cash" <?= $paymentMethod === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="bank_transfer" <?= $paymentMethod === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="card" <?= $paymentMethod === 'card' ? 'selected' : ''; ?>>Card</option>
                            <option value="mobile_banking" <?= $paymentMethod === 'mobile_banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                            <option value="other" <?= $paymentMethod === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <!-- Filter Action Buttons -->
                    <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-funnel me-1"></i> Filter Results
                        </button>
                        <a href="<?= url('modules/reports/payments.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filtered Summary KPI Banner -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Filtered Transactions</span>
                    <strong class="fs-5 text-dark"><?= $totalRows; ?> Receipts</strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Total Revenue Collected</span>
                    <strong class="fs-5 text-success"><?= format_currency($totalCompletedSum); ?></strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Pending Verification</span>
                    <strong class="fs-5 text-warning text-dark"><?= format_currency($totalPendingSum); ?> (<?= $totalPendingCount; ?>)</strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="admin-card p-3">
                    <span class="text-muted small d-block">Failed / Refunded</span>
                    <strong class="fs-5 text-muted"><?= format_currency($totalFailedSum); ?></strong>
                </div>
            </div>
        </div>

        <!-- Report Table Card -->
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h3 class="admin-card-title"><i class="bi bi-wallet2 me-2 text-primary"></i> Payment Receipts</h3>
                <span class="badge bg-secondary"><?= $totalRows; ?> records found</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Payment #</th>
                                <th>Booking #</th>
                                <th>Customer</th>
                                <th>Payment Date</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Trx ID</th>
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
                                        <td>
                                            <a href="<?= url('modules/bookings/view.php?id=' . $p['booking_id']); ?>" class="text-decoration-none">
                                                <code><?= e($p['booking_number']); ?></code>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($p['customer_name']); ?></div>
                                            <small class="text-muted"><?= e($p['customer_phone']); ?></small>
                                        </td>
                                        <td>
                                            <span class="small text-dark fw-semibold"><?= format_date($p['payment_date'], 'M d, Y'); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($methodName); ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-primary fs-6"><?= format_currency($p['amount']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="small text-muted"><?= !empty($p['transaction_id']) ? e($p['transaction_id']) : '—'; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $pStatusClass; ?>">
                                                <?= ucfirst(e($p['payment_status'])); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="<?= url('modules/payments/view.php?id=' . $p['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Receipt">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-credit-card-2-front fs-1 d-block mb-2 text-secondary"></i>
                                        No payment transactions found matching your selected criteria.
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
                    <nav aria-label="Payments Report pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/reports/payments.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= url('modules/reports/payments.php?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/reports/payments.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
