<?php
/**
 * Customer Directory & Filter
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Customers';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('customers.view');

$canCreate  = has_permission('customers.create');
$canEdit    = has_permission('customers.edit');
$canDelete  = has_permission('customers.delete');
$canRestore = has_permission('customers.restore');

// Filter & Search Parameters
$search  = trim($_GET['search'] ?? '');
$status  = trim($_GET['status'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$customers = [];
$totalRows = 0;

try {
    $pdo = get_db_connection();

    // Query Conditions
    $where = [];
    $params = [];

    if ($status === 'deleted' && $canRestore) {
        $where[] = "c.deleted_at IS NOT NULL";
    } else {
        $where[] = "c.deleted_at IS NULL";
        if (!empty($status) && in_array($status, ['active', 'inactive'], true)) {
            $where[] = "c.status = :status";
            $params['status'] = $status;
        }
    }

    if (!empty($search)) {
        $where[] = "(c.customer_code LIKE :search OR c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    $whereSql = implode(' AND ', $where);

    // Count Matching Customers
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c WHERE {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    // Fetch Paginated Customers
    $sql = "
        SELECT c.* 
        FROM customers c 
        WHERE {$whereSql} 
        ORDER BY c.id DESC 
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Customers list error: ' . $e->getMessage());
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
                <h2 class="fs-4 fw-bold text-dark mb-1">Customer Management</h2>
                <p class="text-muted small mb-0">Manage customer records, passport details, contact information, and travel profiles.</p>
            </div>
            <?php if ($canCreate): ?>
                <a href="<?= url('modules/customers/create.php'); ?>" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Add Customer
                </a>
            <?php endif; ?>
        </div>

        <!-- Filter & Search Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/customers/index.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <!-- Search Field -->
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input 
                                type="text" 
                                class="form-control form-control-sm" 
                                name="search" 
                                placeholder="Search by name, code, phone, or email..." 
                                value="<?= e($search); ?>"
                            >
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-8 col-md-4">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Status (Active & Inactive)</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                            <?php if ($canRestore): ?>
                                <option value="deleted" <?= $status === 'deleted' ? 'selected' : ''; ?>>Archived / Soft-Deleted</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Submit & Clear Buttons -->
                    <div class="col-4 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-secondary btn-sm flex-fill" title="Filter">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <?php if (!empty($search) || !empty($status)): ?>
                            <a href="<?= url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customers Table Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="bi bi-people me-2 text-primary"></i> 
                    <?= $status === 'deleted' ? 'Archived Customers' : 'Customer Directory'; ?>
                </h3>
                <span class="badge bg-secondary"><?= $totalRows; ?> Customers</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Customer</th>
                                <th>Contact Information</th>
                                <th>Location</th>
                                <th>Travel Documents</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($customers)): ?>
                                <?php foreach ($customers as $cus): 
                                    $avatarUrl = get_customer_avatar_url($cus['profile_photo'] ?? null);
                                    $initials = get_customer_initials($cus['name']);
                                    $isDeleted = !empty($cus['deleted_at']);
                                ?>
                                    <tr>
                                        <!-- Customer Profile Thumbnail & Name -->
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($avatarUrl): ?>
                                                    <img src="<?= e($avatarUrl); ?>" alt="<?= e($cus['name']); ?>" class="rounded-circle border" style="width: 38px; height: 38px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-light text-primary border d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                                        <?= e($initials); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="<?= url('modules/customers/view.php?id=' . $cus['id']); ?>" class="fw-bold text-dark text-decoration-none">
                                                        <?= e($cus['name']); ?>
                                                    </a>
                                                    <div>
                                                        <code><?= e($cus['customer_code']); ?></code>
                                                        <?php if ($cus['gender']): ?>
                                                            <span class="text-muted small">&bull; <?= ucfirst(e($cus['gender'])); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Contact Info -->
                                        <td>
                                            <div><i class="bi bi-telephone me-1 text-muted"></i> <a href="tel:<?= e($cus['phone']); ?>" class="text-dark text-decoration-none"><?= e($cus['phone']); ?></a></div>
                                            <?php if (!empty($cus['email'])): ?>
                                                <small class="text-muted"><i class="bi bi-envelope me-1"></i> <?= e($cus['email']); ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Location -->
                                        <td>
                                            <div class="text-dark"><?= e($cus['city'] ?: '—'); ?></div>
                                            <small class="text-muted"><?= e($cus['country'] ?: 'Bangladesh'); ?></small>
                                        </td>

                                        <!-- Travel Documents -->
                                        <td>
                                            <?php if (!empty($cus['passport_number'])): ?>
                                                <div><span class="badge bg-light text-dark border"><i class="bi bi-passport me-1"></i> <?= e($cus['passport_number']); ?></span></div>
                                            <?php elseif (!empty($cus['national_id'])): ?>
                                                <div><span class="badge bg-light text-dark border"><i class="bi bi-card-text me-1"></i> NID: <?= e($cus['national_id']); ?></span></div>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <?php if ($isDeleted): ?>
                                                <span class="badge bg-danger">Archived</span>
                                            <?php else: ?>
                                                <span class="badge <?= $cus['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?= ucfirst(e($cus['status'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Created Date -->
                                        <td>
                                            <small class="text-muted"><?= format_date($cus['created_at'], 'M d, Y'); ?></small>
                                        </td>

                                        <!-- Actions -->
                                        <td class="pe-3 text-end">
                                            <a href="<?= url('modules/customers/view.php?id=' . $cus['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Profile">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if (!$isDeleted): ?>
                                                <?php if ($canEdit): ?>
                                                    <a href="<?= url('modules/customers/edit.php?id=' . $cus['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2 ms-1" title="Edit Customer">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($canDelete): ?>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-outline-danger btn-sm p-1 px-2 ms-1 btn-delete-customer" 
                                                        data-id="<?= (int)$cus['id']; ?>"
                                                        data-code="<?= e($cus['customer_code']); ?>"
                                                        data-name="<?= e($cus['name']); ?>"
                                                        title="Delete Customer"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($canRestore): ?>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-outline-success btn-sm p-1 px-2 ms-1 btn-restore-customer" 
                                                        data-id="<?= (int)$cus['id']; ?>"
                                                        data-code="<?= e($cus['customer_code']); ?>"
                                                        data-name="<?= e($cus['name']); ?>"
                                                        title="Restore Customer"
                                                    >
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                                        No customer records found matching your criteria.
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
                    <nav aria-label="Customer pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/customers/index.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= url('modules/customers/index.php?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/customers/index.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Soft Delete Customer Modal -->
    <?php if ($canDelete): ?>
        <div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/customers/delete.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="delete_customer_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="deleteCustomerModalLabel">Confirm Customer Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete customer <strong id="delete_customer_code"></strong> (<span id="delete_customer_name"></span>)?</p>
                            <p class="text-muted small mb-0">This is a safe soft-delete. The customer record and its booking history will remain intact in the database archive.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Restore Customer Modal -->
    <?php if ($canRestore): ?>
        <div class="modal fade" id="restoreCustomerModal" tabindex="-1" aria-labelledby="restoreCustomerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/customers/restore.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="restore_customer_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-success" id="restoreCustomerModalLabel">Confirm Customer Restoration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Restore customer <strong id="restore_customer_code"></strong> (<span id="restore_customer_name"></span>) back to active directory?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Restore Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete Modal trigger
        const deleteButtons = document.querySelectorAll('.btn-delete-customer');
        const deleteModal = document.getElementById('deleteCustomerModal');
        if (deleteModal) {
            const bsDeleteModal = new bootstrap.Modal(deleteModal);
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('delete_customer_id').value = this.getAttribute('data-id');
                    document.getElementById('delete_customer_code').textContent = this.getAttribute('data-code');
                    document.getElementById('delete_customer_name').textContent = this.getAttribute('data-name');
                    bsDeleteModal.show();
                });
            });
        }

        // Restore Modal trigger
        const restoreButtons = document.querySelectorAll('.btn-restore-customer');
        const restoreModal = document.getElementById('restoreCustomerModal');
        if (restoreModal) {
            const bsRestoreModal = new bootstrap.Modal(restoreModal);
            restoreButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('restore_customer_id').value = this.getAttribute('data-id');
                    document.getElementById('restore_customer_code').textContent = this.getAttribute('data-code');
                    document.getElementById('restore_customer_name').textContent = this.getAttribute('data-name');
                    bsRestoreModal.show();
                });
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
