<?php
/**
 * Tour Packages List & Filter
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Tour Packages';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('tours.view');

$canCreate = has_permission('tours.create');
$canEdit   = has_permission('tours.edit');
$canDelete = has_permission('tours.delete');

// Filter parameters
$search      = trim($_GET['search'] ?? '');
$categoryId  = (int)($_GET['category'] ?? 0);
$destId      = (int)($_GET['destination'] ?? 0);
$status      = trim($_GET['status'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 10;
$offset      = ($page - 1) * $perPage;

$packages = [];
$totalRows = 0;
$categories = [];
$destinations = [];

try {
    $pdo = get_db_connection();

    // Fetch categories and destinations for filter dropdowns
    $catStmt = $pdo->query("SELECT id, name FROM tour_categories WHERE deleted_at IS NULL ORDER BY name ASC");
    $categories = $catStmt->fetchAll();

    $destStmt = $pdo->query("SELECT id, name FROM tour_destinations WHERE deleted_at IS NULL ORDER BY name ASC");
    $destinations = $destStmt->fetchAll();

    // Build query conditions
    $where = ["p.deleted_at IS NULL"];
    $params = [];

    if (!empty($search)) {
        $where[] = "(p.package_code LIKE :search OR p.name LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    if ($categoryId > 0) {
        $where[] = "p.category_id = :category_id";
        $params['category_id'] = $categoryId;
    }

    if ($destId > 0) {
        $where[] = "p.destination_id = :dest_id";
        $params['dest_id'] = $destId;
    }

    if (!empty($status) && in_array($status, ['active', 'inactive', 'draft'], true)) {
        $where[] = "p.status = :status";
        $params['status'] = $status;
    }

    $whereSql = implode(' AND ', $where);

    // Count Total Matching Rows
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tour_packages p WHERE {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    // Fetch Paginated Packages
    $sql = "
        SELECT 
            p.*, 
            c.name AS category_name, 
            d.name AS destination_name
        FROM tour_packages p
        LEFT JOIN tour_categories c ON p.category_id = c.id
        LEFT JOIN tour_destinations d ON p.destination_id = d.id
        WHERE {$whereSql}
        ORDER BY p.id DESC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $packages = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Tour Packages List Error: ' . $e->getMessage());
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
                <h2 class="fs-4 fw-bold text-dark mb-1">Tour Packages</h2>
                <p class="text-muted small mb-0">Manage complete holiday itineraries, pricing, media, and availability.</p>
            </div>
            <?php if ($canCreate): ?>
                <a href="<?= url('modules/tours/create.php'); ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Create Package
                </a>
            <?php endif; ?>
        </div>

        <!-- Filter & Search Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/tours/index.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <!-- Search Field -->
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input 
                                type="text" 
                                class="form-control form-control-sm" 
                                name="search" 
                                placeholder="Search by name or code..." 
                                value="<?= e($search); ?>"
                            >
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id']; ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?= e($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Destination Filter -->
                    <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm" name="destination">
                            <option value="">All Destinations</option>
                            <?php foreach ($destinations as $dest): ?>
                                <option value="<?= (int)$dest['id']; ?>" <?= $destId === (int)$dest['id'] ? 'selected' : ''; ?>>
                                    <?= e($dest['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter & Submit -->
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="draft" <?= $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm px-3" title="Apply Filter">
                            <i class="bi bi-funnel"></i>
                        </button>
                        <?php if (!empty($search) || $categoryId > 0 || $destId > 0 || !empty($status)): ?>
                            <a href="<?= url('modules/tours/index.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Packages Table Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="bi bi-box-seam me-2 text-primary"></i> Packages List
                </h3>
                <span class="badge bg-secondary"><?= $totalRows; ?> Packages Found</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Code</th>
                                <th>Package Details</th>
                                <th>Category & Destination</th>
                                <th>Pricing</th>
                                <th class="text-center">Seats</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($packages)): ?>
                                <?php foreach ($packages as $pkg): 
                                    $finalPrice = calculate_discounted_price($pkg['price'], $pkg['discount_type'], $pkg['discount_value']);
                                    $imgUrl = get_tour_image_url($pkg['featured_image'] ?? null);
                                ?>
                                    <tr>
                                        <!-- Code -->
                                        <td class="ps-3">
                                            <code><?= e($pkg['package_code']); ?></code>
                                            <?php if ($pkg['featured']): ?>
                                                <div class="mt-1"><span class="badge bg-warning text-dark" style="font-size: 0.65rem;"><i class="bi bi-star-fill me-1"></i>Featured</span></div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Details & Thumbnail -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($imgUrl): ?>
                                                    <img src="<?= e($imgUrl); ?>" alt="<?= e($pkg['name']); ?>" class="rounded" style="width: 54px; height: 42px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded bg-light text-muted d-flex align-items-center justify-content-center border" style="width: 54px; height: 42px; font-size: 1.15rem;">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="<?= url('modules/tours/view.php?id=' . $pkg['id']); ?>" class="fw-bold text-dark text-decoration-none">
                                                        <?= e($pkg['name']); ?>
                                                    </a>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-clock me-1"></i> <?= (int)$pkg['duration_days']; ?>D / <?= (int)$pkg['duration_nights']; ?>N
                                                        <?php if (!empty($pkg['departure_location'])): ?>
                                                            &bull; <i class="bi bi-geo-alt"></i> <?= e($pkg['departure_location']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Category & Destination -->
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($pkg['destination_name'] ?? '—'); ?></div>
                                            <span class="badge bg-light text-dark border"><?= e($pkg['category_name'] ?? 'Uncategorized'); ?></span>
                                        </td>

                                        <!-- Pricing -->
                                        <td>
                                            <div class="fw-bold text-dark"><?= format_currency($finalPrice); ?></div>
                                            <?php if ($pkg['discount_type'] !== 'none' && (float)$pkg['discount_value'] > 0): ?>
                                                <div class="small">
                                                    <span class="text-muted text-decoration-line-through"><?= format_currency($pkg['price']); ?></span>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 0.65rem;">
                                                        <?= $pkg['discount_type'] === 'percentage' ? '-' . (float)$pkg['discount_value'] . '%' : '-' . format_currency($pkg['discount_value']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Available Seats -->
                                        <td class="text-center">
                                            <span class="badge <?= (int)$pkg['available_seats'] > 0 ? 'bg-light text-dark border' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?>">
                                                <?= (int)$pkg['available_seats']; ?> Seats
                                            </span>
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <span class="badge <?= $pkg['status'] === 'active' ? 'bg-success' : ($pkg['status'] === 'draft' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                                <?= ucfirst(e($pkg['status'])); ?>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="pe-3 text-end">
                                            <a href="<?= url('modules/tours/view.php?id=' . $pkg['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($canEdit): ?>
                                                <a href="<?= url('modules/tours/edit.php?id=' . $pkg['id']); ?>" class="btn btn-outline-secondary btn-sm p-1 px-2 ms-1" title="Edit Package">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($canDelete): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-outline-danger btn-sm p-1 px-2 ms-1 btn-delete-package" 
                                                    data-id="<?= (int)$pkg['id']; ?>"
                                                    data-name="<?= e($pkg['name']); ?>"
                                                    data-code="<?= e($pkg['package_code']); ?>"
                                                    title="Delete Package"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary"></i>
                                        No tour packages matched your search criteria.
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
                    <nav aria-label="Tour Packages pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/tours/index.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= url('modules/tours/index.php?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/tours/index.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Package Confirmation Modal -->
    <?php if ($canDelete): ?>
        <div class="modal fade" id="deletePackageModal" tabindex="-1" aria-labelledby="deletePackageModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/tours/delete.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="delete_pkg_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="deletePackageModalLabel">Confirm Package Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete tour package <strong id="delete_pkg_code"></strong> — <span id="delete_pkg_name"></span>?</p>
                            <p class="text-muted small mb-0">The package will be safely soft-deleted from the active listings.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Package</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.btn-delete-package');
        const deleteModal = document.getElementById('deletePackageModal');
        if (deleteModal) {
            const bsDeleteModal = new bootstrap.Modal(deleteModal);
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('delete_pkg_id').value = this.getAttribute('data-id');
                    document.getElementById('delete_pkg_code').textContent = this.getAttribute('data-code');
                    document.getElementById('delete_pkg_name').textContent = this.getAttribute('data-name');
                    bsDeleteModal.show();
                });
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
