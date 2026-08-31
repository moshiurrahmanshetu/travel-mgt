<?php
/**
 * Payment Transactions Directory & Filter
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Payment Transactions';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('payments.view');

$canCreate = has_permission('payments.create');
$canEdit   = has_permission('payments.edit');
$canDelete = has_permission('payments.delete');

// Filter & Search Parameters
$search        = trim($_GET['search'] ?? '');
$paymentStatus = trim($_GET['status'] ?? '');
$paymentMethod = trim($_GET['method'] ?? '');
$bookingId     = (int)($_GET['booking_id'] ?? 0);
$dateFrom      = trim($_GET['date_from'] ?? '');
$dateTo        = trim($_GET['date_to'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 10;
$offset        = ($page - 1) * $perPage;

$payments = [];
$totalRows = 0;
$totalCompletedAmount = 0.0;

try {
    $pdo = get_db_connection();

    // Query Conditions
    $where = ["p.deleted_at IS NULL"];
    $params = [];

    if (!empty($search)) {
        $where[] = "(p.payment_number LIKE :search OR b.booking_number LIKE :search OR c.name LIKE :search OR c.phone LIKE :search OR p.transaction_id LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    if (!empty($paymentStatus) && in_array($paymentStatus, ['completed', 'pending', 'failed', 'refunded'], true)) {
        $where[] = "p.payment_status = :p_status";
        $params['p_status'] = $paymentStatus;
    }

    if (!empty($paymentMethod) && in_array($paymentMethod, ['cash', 'bank_transfer', 'card', 'mobile_banking', 'other'], true)) {
        $where[] = "p.payment_method = :p_method";
        $params['p_method'] = $paymentMethod;
    }

    if ($bookingId > 0) {
        $where[] = "p.booking_id = :b_id";
        $params['b_id'] = $bookingId;
    }

    if (!empty($dateFrom)) {
        $where[] = "p.payment_date >= :date_from";
        $params['date_from'] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $where[] = "p.payment_date <= :date_to";
        $params['date_to'] = $dateTo;
    }

    $whereSql = implode(' AND ', $where);

    // Count Total Rows & Sum Total Completed
    $countStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END), 0) AS total_sum
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        WHERE {$whereSql}
    ");
    $countStmt->execute($params);
    $countData = $countStmt->fetch();
    $totalRows = (int)($countData['total_count'] ?? 0);
    $totalCompletedAmount = (float)($countData['total_sum'] ?? 0);

    // Fetch Paginated Payments
    $sql = "
        SELECT 
            p.*,
            b.booking_number,
            b.total_amount AS booking_total,
            c.name AS customer_name,
            c.phone AS customer_phone,
            u.name AS collector_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        LEFT JOIN users u ON p.created_by = u.id
        WHERE {$whereSql}
        ORDER BY p.id DESC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Payments list error: ' . $e->getMessage());
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
                <h2 class="fs-4 fw-bold text-dark mb-1">Payment Transactions</h2>
                <p class="text-muted small mb-0">Track customer deposit records, payment methods, transaction receipts, and revenue collections.</p>
            </div>
            <?php if ($canCreate): ?>
                <a href="<?= url('modules/payments/create.php'); ?>" class="btn btn-primary">
                    <i class="bi bi-credit-card me-1"></i> Record Payment
                </a>
            <?php endif; ?>
        </div>

        <!-- Filter & Search Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/payments/index.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <!-- Search Field -->
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input 
                                type="text" 
                                class="form-control form-control-sm" 
                                name="search" 
                                placeholder="Search by payment #, booking #, client, or Trx ID..." 
                                value="<?= e($search); ?>"
                            >
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div class="col-6 col-md-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Statuses</option>
                            <option value="completed" <?= $paymentStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?= $paymentStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?= $paymentStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?= $paymentStatus === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-6 col-md-2">
                        <select class="form-select form-select-sm" name="method">
                            <option value="">All Methods</option>
                            <option value="cash" <?= $paymentMethod === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="bank_transfer" <?= $paymentMethod === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="card" <?= $paymentMethod === 'card' ? 'selected' : ''; ?>>Card</option>
                            <option value="mobile_banking" <?= $paymentMethod === 'mobile_banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                            <option value="other" <?= $paymentMethod === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <!-- Date Range: From -->
                    <div class="col-6 col-md-1">
                        <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($dateFrom); ?>" title="From Date">
                    </div>

                    <!-- Date Range: To -->
                    <div class="col-6 col-md-1">
                        <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($dateTo); ?>" title="To Date">
                    </div>

                    <!-- Submit & Clear Buttons -->
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-secondary btn-sm flex-fill" title="Filter">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <?php if (!empty($search) || !empty($paymentStatus) || !empty($paymentMethod) || $bookingId > 0 || !empty($dateFrom) || !empty($dateTo)): ?>
                            <a href="<?= url('modules/payments/index.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table Card -->
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h3 class="admin-card-title">
                    <i class="bi bi-wallet2 me-2 text-primary"></i> 
                    <?= !empty($paymentStatus) ? ucfirst(e($paymentStatus)) . ' Payments' : 'Payment History'; ?>
                </h3>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary"><?= $totalRows; ?> Payments</span>
                    <span class="badge bg-success">Collected: <?= format_currency($totalCompletedAmount); ?></span>
                </div>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Payment #</th>
                                <th>Booking #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($payments)): ?>
                                <?php foreach ($payments as $p): 
                                    // Status Badge Class
                                    $pStatusClass = 'bg-secondary';
                                    if ($p['payment_status'] === 'completed') $pStatusClass = 'bg-success';
                                    elseif ($p['payment_status'] === 'pending') $pStatusClass = 'bg-warning text-dark';
                                    elseif ($p['payment_status'] === 'failed') $pStatusClass = 'bg-danger';
                                    elseif ($p['payment_status'] === 'refunded') $pStatusClass = 'bg-secondary';

                                    // Method Label
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
                                        <!-- Payment Number -->
                                        <td class="ps-3">
                                            <a href="<?= url('modules/payments/view.php?id=' . $p['id']); ?>" class="fw-bold text-decoration-none">
                                                <code><?= e($p['payment_number']); ?></code>
                                            </a>
                                            <?php if (!empty($p['transaction_id'])): ?>
                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                    <i class="bi bi-hash"></i> <?= e($p['transaction_id']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Booking Number -->
                                        <td>
                                            <a href="<?= url('modules/bookings/view.php?id=' . $p['booking_id']); ?>" class="text-decoration-none">
                                                <code><?= e($p['booking_number']); ?></code>
                                            </a>
                                        </td>

                                        <!-- Customer -->
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($p['customer_name']); ?></div>
                                            <div class="text-muted small"><i class="bi bi-telephone me-1"></i> <?= e($p['customer_phone']); ?></div>
                                        </td>

                                        <!-- Amount -->
                                        <td>
                                            <div class="fs-6 fw-bold text-primary"><?= format_currency($p['amount']); ?></div>
                                        </td>

                                        <!-- Payment Method -->
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= e($methodName); ?>
                                            </span>
                                        </td>

                                        <!-- Payment Date -->
                                        <td>
                                            <span class="text-dark small"><?= format_date($p['payment_date'], 'M d, Y'); ?></span>
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <span class="badge <?= $pStatusClass; ?>">
                                                <?= ucfirst(e($p['payment_status'])); ?>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="pe-3 text-end">
                                            <a href="<?= url('modules/payments/view.php?id=' . $p['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Receipt">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($canEdit): ?>
                                                <a href="<?= url('modules/payments/edit.php?id=' . $p['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2 ms-1" title="Edit Remarks">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($canDelete): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-outline-danger btn-sm p-1 px-2 ms-1 btn-delete-payment" 
                                                    data-id="<?= (int)$p['id']; ?>"
                                                    data-number="<?= e($p['payment_number']); ?>"
                                                    data-amount="<?= format_currency($p['amount']); ?>"
                                                    title="Delete Payment"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-wallet2 fs-1 d-block mb-2 text-secondary"></i>
                                        No payment transactions found matching your criteria.
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
                    <nav aria-label="Payments pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/payments/index.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= url('modules/payments/index.php?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/payments/index.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Soft-Delete Payment Modal -->
    <?php if ($canDelete): ?>
        <div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-labelledby="deletePaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/payments/delete.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="delete_payment_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="deletePaymentModalLabel">Confirm Payment Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete payment receipt <strong id="delete_payment_number"></strong> (<span id="delete_payment_amount"></span>)?</p>
                            <p class="text-muted small mb-0">This transaction will be archived and the associated booking's collected/due balance will be automatically recalculated.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Delete Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.btn-delete-payment');
        const deleteModal = document.getElementById('deletePaymentModal');
        if (deleteModal) {
            const bsDeleteModal = new bootstrap.Modal(deleteModal);
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('delete_payment_id').value = this.getAttribute('data-id');
                    document.getElementById('delete_payment_number').textContent = this.getAttribute('data-number');
                    document.getElementById('delete_payment_amount').textContent = this.getAttribute('data-amount');
                    bsDeleteModal.show();
                });
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
