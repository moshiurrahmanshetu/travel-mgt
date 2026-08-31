<?php
/**
 * User Management Directory
 * Tour & Travel Booking Management System
 */

$pageTitle = 'User Management';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('users.view');

$canCreate = has_permission('users.create');
$canEdit   = has_permission('users.edit');
$canDelete = has_permission('users.delete');

$currentUserId = current_user_id();

// Search & Filter Parameters
$search  = trim($_GET['search'] ?? '');
$roleId  = (int)($_GET['role_id'] ?? 0);
$status  = trim($_GET['status'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$users = [];
$roles = [];
$totalRows = 0;
$activeAdminCount = count_active_administrators();

try {
    $pdo = get_db_connection();

    // Query roles for filter dropdown
    $roles = $pdo->query("SELECT id, name, slug FROM roles ORDER BY id ASC")->fetchAll();

    // Build WHERE conditions
    $where = ["u.deleted_at IS NULL"];
    $params = [];

    if (!empty($search)) {
        $where[] = "(u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    if ($roleId > 0) {
        $where[] = "u.role_id = :role_id";
        $params['role_id'] = $roleId;
    }

    if (!empty($status) && in_array($status, ['active', 'inactive'], true)) {
        $where[] = "u.status = :status";
        $params['status'] = $status;
    }

    $whereSql = implode(' AND ', $where);

    // Count Total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    // Fetch Paginated Dataset
    $sql = "
        SELECT 
            u.id,
            u.role_id,
            u.first_name,
            u.last_name,
            u.name,
            u.email,
            u.phone,
            u.avatar,
            u.status,
            u.last_login,
            u.created_at,
            r.name AS role_name,
            r.slug AS role_slug
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE {$whereSql}
        ORDER BY u.id ASC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('User list query error: ' . $e->getMessage());
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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= url('modules/dashboard/index.php'); ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Users</li>
                    </ol>
                </nav>
                <h2 class="fs-4 fw-bold text-dark mb-0">System Users & Accounts</h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <?php if (has_permission('roles.view')): ?>
                    <a href="<?= url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-shield-lock me-1"></i> Manage Roles
                    </a>
                <?php endif; ?>
                <?php if ($canCreate): ?>
                    <a href="<?= url('modules/users/create.php'); ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-person-plus me-1"></i> Add New User
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body p-3">
                <form action="<?= url('modules/users/index.php'); ?>" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="search" 
                                placeholder="Search by name, email, or phone..." 
                                value="<?= e($search); ?>"
                            >
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm" name="role_id">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= (int)$r['id']; ?>" <?= $roleId === (int)$r['id'] ? 'selected' : ''; ?>>
                                    <?= e($r['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Statuses</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <?php if (!empty($search) || $roleId > 0 || !empty($status)): ?>
                            <a href="<?= url('modules/users/index.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h3 class="admin-card-title"><i class="bi bi-people me-2 text-primary"></i> System Accounts</h3>
                <span class="badge bg-secondary"><?= $totalRows; ?> Users</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Activity</th>
                                <th>Created Date</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $u): 
                                    $isLastAdmin = ($u['role_slug'] === 'administrator' && $u['status'] === 'active' && $activeAdminCount <= 1);
                                    $isSelf = ($u['id'] == $currentUserId);
                                    $avatarUrl = get_avatar_url($u['avatar'] ?? null);
                                    $initials = get_user_initials($u['name']);
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($avatarUrl): ?>
                                                    <img src="<?= e($avatarUrl); ?>" alt="<?= e($u['name']); ?>" class="avatar-circle" style="width:36px;height:36px;object-fit:cover;">
                                                <?php else: ?>
                                                    <span class="avatar-circle" style="width:36px;height:36px;font-size:0.85rem;"><?= e($initials); ?></span>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold text-dark">
                                                        <?= e($u['name']); ?>
                                                        <?php if ($isSelf): ?>
                                                            <span class="badge bg-light text-primary border ms-1" style="font-size:0.65rem;">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted"><?= e($u['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="small text-dark"><?= !empty($u['phone']) ? e($u['phone']) : '—'; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <i class="bi bi-shield-check me-1"></i> <?= e($u['role_name']); ?>
                                            </span>
                                            <?php if ($isLastAdmin): ?>
                                                <div class="text-danger small fw-semibold" style="font-size:0.65rem;">Primary Admin</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?= ucfirst(e($u['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small text-muted">
                                                <?= !empty($u['last_login']) ? format_date($u['last_login']) : 'Never logged in'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small text-muted"><?= format_date($u['created_at'], 'M d, Y'); ?></span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($canEdit): ?>
                                                    <a href="<?= url('modules/users/edit.php?id=' . $u['id']); ?>" class="btn btn-outline-secondary" title="Edit User">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($canDelete && !$isSelf && !$isLastAdmin): ?>
                                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?= (int)$u['id']; ?>" title="Delete User">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Soft Delete Confirmation Modal -->
                                            <?php if ($canDelete && !$isSelf && !$isLastAdmin): ?>
                                                <div class="modal fade" id="deleteUserModal<?= (int)$u['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered text-start">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fs-6 fw-bold text-dark">
                                                                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Confirm User Deletion
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="mb-1">Are you sure you want to remove user <strong><?= e($u['name']); ?></strong> (<code><?= e($u['email']); ?></code>)?</p>
                                                                <small class="text-muted">The user will no longer be able to log in or access the system.</small>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                <form action="<?= url('modules/users/delete.php'); ?>" method="POST" class="d-inline">
                                                                    <?= csrf_field(); ?>
                                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                                        <i class="bi bi-trash me-1"></i> Delete User
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                                        No users found matching your selected criteria.
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
                    <nav aria-label="Users pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/users/index.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= url('modules/users/index.php?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= url('modules/users/index.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
