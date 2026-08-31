<?php
/**
 * Tour Package Performance Report
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('reports.view');

$canExport = has_permission('reports.export');

// Filter Parameters
$dateFrom      = trim($_GET['date_from'] ?? '');
$dateTo        = trim($_GET['date_to'] ?? '');
$categoryId    = (int)($_GET['category_id'] ?? 0);
$destinationId = (int)($_GET['destination_id'] ?? 0);
$action        = trim($_GET['action'] ?? '');

$tourPerformance = [];
$categories = [];
$destinations = [];

try {
    $pdo = get_db_connection();

    // Query dropdown options
    $categories   = $pdo->query("SELECT id, name FROM tour_categories WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
    $destinations = $pdo->query("SELECT id, name FROM tour_destinations WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

    // Build WHERE conditions for tour packages
    $where = ["tp.deleted_at IS NULL"];
    $params = [];

    if ($categoryId > 0) {
        $where[] = "tp.category_id = :cat_id";
        $params['cat_id'] = $categoryId;
    }

    if ($destinationId > 0) {
        $where[] = "tp.destination_id = :dest_id";
        $params['dest_id'] = $destinationId;
    }

    $whereSql = implode(' AND ', $where);

    // Date subquery conditions for bookings
    $bookingDateCond = "b.deleted_at IS NULL";
    if (!empty($dateFrom)) {
        $bookingDateCond .= " AND DATE(b.created_at) >= " . $pdo->quote($dateFrom);
    }
    if (!empty($dateTo)) {
        $bookingDateCond .= " AND DATE(b.created_at) <= " . $pdo->quote($dateTo);
    }

    $sql = "
        SELECT 
            tp.id,
            tp.package_code,
            tp.name,
            tp.price,
            tp.available_seats,
            tc.name AS category_name,
            td.name AS destination_name,
            COALESCE(SUM(CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END), 0) AS total_orders,
            COALESCE(SUM(CASE WHEN b.booking_status = 'confirmed' THEN 1 ELSE 0 END), 0) AS confirmed_orders,
            COALESCE(SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_orders,
            COALESCE(SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_orders,
            COALESCE(SUM(CASE WHEN b.booking_status IN ('confirmed', 'completed') THEN (b.adults + b.children + b.infants) ELSE 0 END), 0) AS total_travellers,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount ELSE 0 END), 0) AS invoiced_revenue,
            COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.paid_amount ELSE 0 END), 0) AS collected_revenue
        FROM tour_packages tp
        LEFT JOIN tour_categories tc ON tp.category_id = tc.id
        LEFT JOIN tour_destinations td ON tp.destination_id = td.id
        LEFT JOIN bookings b ON b.tour_package_id = tp.id AND {$bookingDateCond}
        WHERE {$whereSql}
        GROUP BY tp.id, tp.package_code, tp.name, tp.price, tp.available_seats, tc.name, td.name
        ORDER BY confirmed_orders DESC, invoiced_revenue DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tourPerformance = $stmt->fetchAll();

    // CSV Export Handler
    if ($action === 'export_csv' && $canExport) {
        $csvHeaders = [
            'Package Code', 'Package Name', 'Category', 'Destination', 'Base Price (BDT)',
            'Total Bookings', 'Confirmed', 'Completed', 'Cancelled', 'Confirmed Pax',
            'Invoiced Revenue (BDT)', 'Collected Revenue (BDT)'
        ];

        $csvRows = [];
        foreach ($tourPerformance as $tp) {
            $csvRows[] = [
                $tp['package_code'],
                $tp['name'],
                $tp['category_name'] ?? 'Uncategorized',
                $tp['destination_name'] ?? '—',
                $tp['price'],
                $tp['total_orders'],
                $tp['confirmed_orders'],
                $tp['completed_orders'],
                $tp['cancelled_orders'],
                $tp['total_travellers'],
                $tp['invoiced_revenue'],
                $tp['collected_revenue']
            ];
        }

        $filename = 'tour_performance_report_' . date('Ymd_His') . '.csv';
        export_data_to_csv($filename, $csvHeaders, $csvRows);
        exit;
    }

} catch (PDOException $e) {
    error_log('Tour Performance Report Query Error: ' . $e->getMessage());
}

$pageTitle = 'Tour Package Performance Report';

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
                        <li class="breadcrumb-item active" aria-current="page">Tour Performance</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">Tour Package Performance & Demand Analytics</h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('modules/reports/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Reports Overview
                </a>
                <?php if ($canExport): ?>
                    <a href="<?= url('modules/reports/tours.php?' . http_build_query(array_merge($_GET, ['action' => 'export_csv']))); ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/reports/tours.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <!-- Date From -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Booking Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($dateFrom); ?>">
                    </div>

                    <!-- Date To -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Booking Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($dateTo); ?>">
                    </div>

                    <!-- Category -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Tour Category</label>
                        <select class="form-select form-select-sm" name="category_id">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id']; ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?= e($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Destination -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Destination</label>
                        <select class="form-select form-select-sm" name="destination_id">
                            <option value="">All Destinations</option>
                            <?php foreach ($destinations as $dest): ?>
                                <option value="<?= (int)$dest['id']; ?>" <?= $destinationId === (int)$dest['id'] ? 'selected' : ''; ?>>
                                    <?= e($dest['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-funnel me-1"></i> Apply Filter
                        </button>
                        <a href="<?= url('modules/reports/tours.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Performance Table Card -->
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h3 class="admin-card-title"><i class="bi bi-compass me-2 text-primary"></i> Tour Performance Breakdown</h3>
                <span class="badge bg-secondary"><?= count($tourPerformance); ?> Packages Analyzed</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Package Code</th>
                                <th>Package Name</th>
                                <th>Category / Destination</th>
                                <th class="text-center">Total Orders</th>
                                <th class="text-center">Confirmed Orders</th>
                                <th class="text-center">Confirmed Pax</th>
                                <th>Invoiced Revenue</th>
                                <th class="pe-3">Collected Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tourPerformance)): ?>
                                <?php foreach ($tourPerformance as $tp): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <a href="<?= url('modules/tours/view.php?id=' . $tp['id']); ?>" class="fw-bold text-decoration-none">
                                                <code><?= e($tp['package_code']); ?></code>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($tp['name']); ?></div>
                                            <small class="text-muted">Base: <?= format_currency($tp['price']); ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark small"><?= e($tp['destination_name'] ?? '—'); ?></div>
                                            <small class="text-muted"><?= e($tp['category_name'] ?? 'Uncategorized'); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= (int)$tp['total_orders']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= (int)$tp['confirmed_orders'] + (int)$tp['completed_orders']; ?></span>
                                            <?php if ((int)$tp['cancelled_orders'] > 0): ?>
                                                <span class="badge bg-danger ms-1" title="Cancelled"><?= (int)$tp['cancelled_orders']; ?> Can.</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <strong class="text-primary"><?= (int)$tp['total_travellers']; ?> Pax</strong>
                                        </td>
                                        <td>
                                            <strong class="text-dark"><?= format_currency($tp['invoiced_revenue']); ?></strong>
                                        </td>
                                        <td class="pe-3">
                                            <strong class="text-success"><?= format_currency($tp['collected_revenue']); ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-compass fs-1 d-block mb-2 text-secondary"></i>
                                        No tour package data found matching your criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
