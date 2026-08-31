<?php
/**
 * Bookings List & Filter
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Tour Bookings';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('bookings.view');

$canCreate   = has_permission('bookings.create');
$canEdit     = has_permission('bookings.edit');
$canCancel   = has_permission('bookings.cancel');
$canConfirm  = has_permission('bookings.confirm');
$canComplete = has_permission('bookings.complete');

// Filter & Search Parameters
$search        = trim($_GET['search'] ?? '');
$bookingStatus = trim($_GET['status'] ?? '');
$paymentStatus = trim($_GET['payment_status'] ?? '');
$packageId     = (int)($_GET['package_id'] ?? 0);
$customerId    = (int)($_GET['customer_id'] ?? 0);
$dateFrom      = trim($_GET['date_from'] ?? '');
$dateTo        = trim($_GET['date_to'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 10;
$offset        = ($page - 1) * $perPage;

$bookings = [];
$totalRows = 0;
$packages = [];
$customers = [];

try {
    $pdo = get_db_connection();

    // Fetch Packages and Customers for Filter Selects
    $pkgStmt = $pdo->query("SELECT id, package_code, name FROM tour_packages WHERE deleted_at IS NULL ORDER BY name ASC");
    $packages = $pkgStmt->fetchAll();

    $cusStmt = $pdo->query("SELECT id, customer_code, name, phone FROM customers WHERE deleted_at IS NULL ORDER BY name ASC");
    $customers = $cusStmt->fetchAll();

    // Query Conditions
    $where = ["b.deleted_at IS NULL"];
    $params = [];

    if (!empty($search)) {
        $where[] = "(b.booking_number LIKE :search OR c.name LIKE :search OR c.phone LIKE :search OR p.name LIKE :search OR p.package_code LIKE :search)";
        $params['search'] = '%' . $search . '%';
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

    if (!empty($dateFrom)) {
        $where[] = "b.travel_date >= :date_from";
        $params['date_from'] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $where[] = "b.travel_date <= :date_to";
        $params['date_to'] = $dateTo;
    }

    $whereSql = implode(' AND ', $where);

    // Count Total Matching Rows
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages p ON b.tour_package_id = p.id
        WHERE {$whereSql}
    ");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    // Fetch Paginated Bookings
    $sql = "
        SELECT 
            b.*,
            c.customer_code,
            c.name AS customer_name,
            c.phone AS customer_phone,
            c.profile_photo AS customer_avatar,
            p.package_code,
            p.name AS package_name,
            d.name AS destination_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tour_packages p ON b.tour_package_id = p.id
        LEFT JOIN tour_destinations d ON p.destination_id = d.id
        WHERE {$whereSql}
        ORDER BY b.id DESC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Bookings list error: ' . $e->getMessage());
}

$totalPages = ceil($totalRows / $perPage);
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
                <h2 class="fs-4 fw-bold text-dark mb-1">Booking Management</h2>
                <p class="text-muted small mb-0">Track tour reservations, passenger capacities, pricing snapshots, and travel dates.</p>
            </div>
            <?php if ($canCreate): ?>
                <a href="<?= url('modules/bookings/create.php'); ?>" class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-1"></i> Create Booking
                </a>
            <?php endif; ?>
        </div>

        <!-- Filter & Search Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/bookings/index.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <!-- Search Field -->
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input 
                                type="text" 
                                class="form-control form-control-sm" 
                                name="search" 
                                placeholder="Search by booking #, client, or package..." 
                                value="<?= e($search); ?>"
                            >
                        </div>
                    </div>

                    <!-- Booking Status -->
                    <div class="col-6 col-md-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $bookingStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?= $bookingStatus === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?= $bookingStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?= $bookingStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <!-- Payment Status -->
                    <div class="col-6 col-md-2">
                        <select class="form-select form-select-sm" name="payment_status">
                            <option value="">All Payments</option>
                            <option value="unpaid" <?= $paymentStatus === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="partial" <?= $paymentStatus === 'partial' ? 'selected' : ''; ?>>Partial</option>
                            <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="refunded" <?= $paymentStatus === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <!-- Tour Package -->
                    <div class="col-6 col-md-2">
                        <select class="form-select form-select-sm" name="package_id">
                            <option value="">All Packages</option>
                            <?php foreach ($packages as $pkg): ?>
                                <option value="<?= (int)$pkg['id']; ?>" <?= $packageId === (int)$pkg['id'] ? 'selected' : ''; ?>>
                                    <?= e($pkg['package_code']) . ' - ' . e($pkg['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Submit & Clear Buttons -->
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-secondary btn-sm flex-fill" title="Filter">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <?php if (!empty($search) || !empty($bookingStatus) || !empty($paymentStatus) || $packageId > 0 || !empty($dateFrom) || !empty($dateTo)): ?>
                            <a href="<?= url('modules/bookings/index.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Table Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="bi bi-calendar-check me-2 text-primary"></i> 
                    <?= !empty($bookingStatus) ? ucfirst(e($bookingStatus)) . ' Bookings' : 'All Tour Bookings'; ?>
                </h3>
                <span class="badge bg-secondary"><?= $totalRows; ?> Bookings</span>
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
                                <th class="text-center">Travellers</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($bookings)): ?>
                                <?php foreach ($bookings as $b): 
                                    $totalTravellers = (int)$b['adults'] + (int)$b['children'] + (int)$b['infants'];
                                    
                                    // Status Badge Class
                                    $bStatusClass = 'bg-secondary';
                                    if ($b['booking_status'] === 'pending') $bStatusClass = 'bg-warning text-dark';
                                    elseif ($b['booking_status'] === 'confirmed') $bStatusClass = 'bg-primary';
                                    elseif ($b['booking_status'] === 'completed') $bStatusClass = 'bg-success';
                                    elseif ($b['booking_status'] === 'cancelled') $bStatusClass = 'bg-danger';

                                    // Payment Badge Class
                                    $pStatusClass = 'bg-secondary';
                                    if ($b['payment_status'] === 'unpaid') $pStatusClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                    elseif ($b['payment_status'] === 'partial') $pStatusClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    elseif ($b['payment_status'] === 'paid') $pStatusClass = 'bg-success-subtle text-success border border-success-subtle';
                                    elseif ($b['payment_status'] === 'refunded') $pStatusClass = 'bg-secondary-subtle text-secondary border';
                                ?>
                                    <tr>
                                        <!-- Booking Number -->
                                        <td class="ps-3">
                                            <a href="<?= url('modules/bookings/view.php?id=' . $b['id']); ?>" class="fw-bold text-decoration-none">
                                                <code><?= e($b['booking_number']); ?></code>
                                            </a>
                                            <div class="text-muted small"><?= format_date($b['created_at'], 'M d, Y'); ?></div>
                                        </td>

                                        <!-- Customer -->
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($b['customer_name']); ?></div>
                                            <div class="text-muted small"><i class="bi bi-telephone me-1"></i> <?= e($b['customer_phone']); ?></div>
                                        </td>

                                        <!-- Tour Package -->
                                        <td>
                                            <div class="fw-semibold text-dark" style="max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= e($b['package_name']); ?>
                                            </div>
                                            <span class="badge bg-light text-dark border"><?= e($b['destination_name'] ?? '—'); ?></span>
                                        </td>

                                        <!-- Travel Date -->
                                        <td>
                                            <div class="text-dark fw-semibold"><?= format_date($b['travel_date'], 'M d, Y'); ?></div>
                                        </td>

                                        <!-- Travellers -->
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <?= $totalTravellers; ?> Pax
                                            </span>
                                            <div class="text-muted" style="font-size: 0.7rem;">
                                                <?= (int)$b['adults']; ?>A <?= (int)$b['children'] > 0 ? '/ ' . (int)$b['children'] . 'C' : ''; ?>
                                            </div>
                                        </td>

                                        <!-- Total & Payment -->
                                        <td>
                                            <div class="fw-bold text-dark"><?= format_currency($b['total_amount']); ?></div>
                                            <span class="badge <?= $pStatusClass; ?>" style="font-size: 0.65rem;">
                                                <?= ucfirst(e($b['payment_status'])); ?>
                                            </span>
                                        </td>

                                        <!-- Booking Status -->
                                        <td>
                                            <span class="badge <?= $bStatusClass; ?>">
                                                <?= ucfirst(e($b['booking_status'])); ?>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="pe-3 text-end">
                                            <a href="<?= url('modules/bookings/view.php?id=' . $b['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Reservation Voucher">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($b['booking_status'] !== 'cancelled' && $b['booking_status'] !== 'completed'): ?>
                                                <?php if ($canEdit): ?>
                                                    <a href="<?= url('modules/bookings/edit.php?id=' . $b['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2 ms-1" title="Edit Booking">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($canCancel): ?>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-outline-danger btn-sm p-1 px-2 ms-1 btn-cancel-booking" 
                                                        data-id="<?= (int)$b['id']; ?>"
                                                        data-number="<?= e($b['booking_number']); ?>"
                                                        title="Cancel Booking"
                                                    >
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                                        No bookings found matching your search or filter criteria.
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
                    <nav aria-label="Bookings pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/bookings/index.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= url('modules/bookings/index.php?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/bookings/index.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <?php if ($canCancel): ?>
        <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/bookings/cancel.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="cancel_booking_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="cancelBookingModalLabel">Confirm Booking Cancellation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to cancel booking <strong id="cancel_booking_number"></strong>?</p>
                            <p class="text-muted small mb-0">This booking will be marked as Cancelled. Any occupied capacity will be released immediately for other reservations.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Cancel Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cancel modal trigger
        const cancelButtons = document.querySelectorAll('.btn-cancel-booking');
        const cancelModal = document.getElementById('cancelBookingModal');
        if (cancelModal) {
            const bsCancelModal = new bootstrap.Modal(cancelModal);
            cancelButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('cancel_booking_id').value = this.getAttribute('data-id');
                    document.getElementById('cancel_booking_number').textContent = this.getAttribute('data-number');
                    bsCancelModal.show();
                });
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
