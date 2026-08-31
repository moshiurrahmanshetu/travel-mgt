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
$recentPackages = [];

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

    // Count active customers (Phase 03)
    $stmtCus = $pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL");
    $totalCustomers = (int)$stmtCus->fetchColumn();

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

        <!-- Welcome Banner Card -->
        <div class="welcome-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h2 class="fs-4 fw-bold mb-0 text-dark">Welcome back, <?= e($currentUser['name']); ?>!</h2>
                        <span class="badge bg-primary px-2 py-1" style="font-size: 0.75rem;"><?= e($currentUser['role_name']); ?></span>
                    </div>
                    <p class="text-muted mb-0 small">
                        Here is an overview of the Tour & Travel Management System.
                    </p>
                </div>
                <div class="text-md-end text-muted small">
                    <div><i class="bi bi-calendar-event me-1"></i> <?= date('l, F d, Y'); ?></div>
                    <div class="mt-1"><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> System Operational</span></div>
                </div>
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

            <!-- Total Bookings (Phase 04 Placeholder) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-info">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-title">Total Bookings</div>
                        <div class="kpi-value">0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tour Packages & Quick Actions -->
        <div class="row g-3">
            <!-- Left Column: Recent Tour Packages Table -->
            <div class="col-12 col-lg-8">
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
            </div>

            <!-- Right Column: Account Quick Summary -->
            <div class="col-12 col-lg-4">
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
