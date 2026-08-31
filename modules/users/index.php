<?php
/**
 * Users & Roles Foundation Module
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Users & Roles';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

try {
    $pdo = get_db_connection();
    $rolesStmt = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
    $roles = $rolesStmt->fetchAll();

    $usersStmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at, r.name AS role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.deleted_at IS NULL 
        ORDER BY u.id ASC
    ");
    $users = $usersStmt->fetchAll();
} catch (PDOException $e) {
    $roles = [];
    $users = [];
}
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Phase Foundation Notice -->
        <div class="alert alert-info d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0"></i>
            <div>
                <strong>Phase 01 Foundation:</strong> Role and permission infrastructure is initialized. Complete User CRUD and Role assignment management will be expanded in accordance with the project roadmap.
            </div>
        </div>

        <div class="row g-4">
            <!-- System Users Table -->
            <div class="col-12 col-xl-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-people-fill me-2 text-primary"></i> Active System Users
                        </h3>
                        <span class="badge bg-secondary"><?= count($users); ?> Total</span>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($users)): ?>
                                        <?php foreach ($users as $u): ?>
                                            <tr>
                                                <td class="ps-3 fw-semibold"><?= e($u['name']); ?></td>
                                                <td><?= e($u['email']); ?></td>
                                                <td><span class="badge bg-primary"><?= e($u['role_name']); ?></span></td>
                                                <td>
                                                    <span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?= ucfirst(e($u['status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="pe-3 text-end text-muted small"><?= format_date($u['created_at'], 'M d, Y'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No users found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Defined Roles Card -->
            <div class="col-12 col-xl-4">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-shield-check me-2 text-primary"></i> Defined System Roles
                        </h3>
                    </div>
                    <div class="admin-card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($roles as $r): ?>
                                <li class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h5 class="fs-6 fw-bold mb-0 text-dark"><?= e($r['name']); ?></h5>
                                        <code><?= e($r['slug']); ?></code>
                                    </div>
                                    <p class="text-muted small mb-0"><?= e($r['description']); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
