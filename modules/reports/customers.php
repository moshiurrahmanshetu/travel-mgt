<?php
/**
 * Customer Booking Summary & Value Report
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('reports.view');

$canExport = has_permission('reports.export');

// Search & Filter Parameters
$search = trim($_GET['search'] ?? '');
$action = trim($_GET['action'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$customerStats = [];
$totalRows = 0;

try {
    $pdo = get_db_connection();

    $where = ["c.deleted_at IS NULL"];
    $params = [];

    if (!empty($search)) {
        $where[] = "(c.name LIKE :search OR c.customer_code LIKE :search OR c.phone LIKE :search OR c.email LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    $whereSql = implode(' AND ', $where);

    // Count Customers
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c WHERE {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    $sql = "
        SELECT 
            c.id,
            c.customer_code,
            c.name,
            c.phone,
            c.email,
            c.city,
            COUNT(b.id) AS total_bookings,
            COALESCE(SUM(CASE WHEN b.booking_status = 'confirmed' THEN 1 ELSE 0 END), 0) AS confirmed_bookings,
            COALESCE(SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_bookings,
            COALESCE(SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_bookings,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount ELSE 0 END), 0) AS total_invoiced,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.paid_amount ELSE 0 END), 0) AS total_paid,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.due_amount ELSE 0 END), 0) AS total_due
        FROM customers c
        LEFT JOIN bookings b ON b.customer_id = c.id AND b.deleted_at IS NULL
        WHERE {$whereSql}
        GROUP BY c.id, c.customer_code, c.name, c.phone, c.email, c.city
        ORDER BY total_invoiced DESC, total_bookings DESC
    ";

    // CSV Export Handler
    if ($action === 'export_csv' && $canExport) {
        $exportStmt = $pdo->prepare($sql);
        $exportStmt->execute($params);
        $exportData = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

        $csvHeaders = [
            'Customer Code', 'Full Name', 'Phone', 'Email', 'City',
            'Total Bookings', 'Confirmed', 'Completed', 'Cancelled',
            'Total Invoiced (BDT)', 'Total Paid (BDT)', 'Total Due (BDT)'
        ];

        $csvRows = [];
        foreach ($exportData as $row) {
            $csvRows[] = [
                $row['customer_code'],
                $row['name'],
                $row['phone'],
                $row['email'] ?: '—',
                $row['city'] ?: '—',
                $row['total_bookings'],
                $row['confirmed_bookings'],
                $row['completed_bookings'],
                $row['cancelled_bookings'],
                $row['total_invoiced'],
                $row['total_paid'],
                $row['total_due']
            ];
        }

        $filename = 'customer_summary_report_' . date('Ymd_His') . '.csv';
        export_data_to_csv($filename, $csvHeaders, $csvRows);
        exit;
    }

    $paginatedSql = $sql . " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($paginatedSql);
    $stmt->execute($params);
    $customerStats = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Customer Report Query Error: ' . $e->getMessage());
}

$totalPages = ceil($totalRows / $perPage);
$pageTitle = 'Customer Booking & Value Summary Report';

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
                        <li class="breadcrumb-item active" aria-current="page">Customer Reports</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Customer Booking & Lifetime Value Report</h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('modules/reports/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Reports Overview
                </a>
                <?php if ($canExport): ?>
                    <a href="<?= url('modules/reports/customers.php?' . http_build_query(array_merge($_GET, ['action' => 'export_csv']))); ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/reports/customers.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="search" 
                                placeholder="Search by customer name, code, phone, or email..." 
                                value="<?= e($search); ?>"
                            >
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-search me-1"></i> Search Customers
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= url('modules/reports/customers.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Clear">
                                <i class="bi bi-x-lg me-1"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customer Summary Table Card -->
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h3 class="admin-card-title"><i class="bi bi-people me-2 text-primary"></i> Customer Value Rankings</h3>
                <span class="badge bg-secondary"><?= $totalRows; ?> Clients</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Customer Code</th>
                                <th>Full Name</th>
                                <th>Contact</th>
                                <th class="text-center">Total Orders</th>
                                <th class="text-center">Confirmed / Completed</th>
                                <th>Total Invoiced</th>
                                <th>Total Paid</th>
                                <th class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($customerStats)): ?>
                                <?php foreach ($customerStats as $cs): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <a href="<?= url('modules/customers/view.php?id=' . $cs['id']); ?>" class="fw-bold text-decoration-none">
                                                <code><?= e($cs['customer_code']); ?></code>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($cs['name']); ?></div>
                                            <small class="text-muted"><?= e($cs['city'] ?: '—'); ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark small"><i class="bi bi-telephone me-1 text-primary"></i> <?= e($cs['phone']); ?></div>
                                            <?php if (!empty($cs['email'])): ?>
                                                <small class="text-muted"><?= e($cs['email']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= (int)$cs['total_bookings']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= (int)$cs['confirmed_bookings'] + (int)$cs['completed_bookings']; ?></span>
                                            <?php if ((int)$cs['cancelled_bookings'] > 0): ?>
                                                <span class="badge bg-danger ms-1"><?= (int)$cs['cancelled_bookings']; ?> Can.</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-dark"><?= format_currency($cs['total_invoiced']); ?></strong>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?= format_currency($cs['total_paid']); ?></strong>
                                            <?php if ((float)$cs['total_due'] > 0): ?>
                                                <div class="text-danger small fw-semibold">Due: <?= format_currency($cs['total_due']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="<?= url('modules/customers/view.php?id=' . $cs['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Customer Profile">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                                        No customer records found matching your search query.
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
                    <nav aria-label="Customer Report pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/reports/customers.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= url('modules/reports/customers.php?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/reports/customers.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
