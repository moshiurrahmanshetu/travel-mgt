<?php
/**
 * Tour Categories Management
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Tour Categories';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('categories.view');

$canCreate = has_permission('categories.create');
$canEdit   = has_permission('categories.edit');
$canDelete = has_permission('categories.delete');

$categories = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM tour_packages p WHERE p.category_id = c.id AND p.deleted_at IS NULL) AS total_packages
        FROM tour_categories c
        WHERE c.deleted_at IS NULL
        ORDER BY c.id DESC
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Categories list error: ' . $e->getMessage());
}
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Header Bar -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h2 class="fs-4 fw-bold text-dark mb-1">Tour Categories</h2>
                <p class="text-muted small mb-0">Organize tour packages into logical themes and travel categories.</p>
            </div>
            <?php if ($canCreate): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Category
                </button>
            <?php endif; ?>
        </div>

        <!-- Categories Table Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="bi bi-tags me-2 text-primary"></i> All Categories
                </h3>
                <span class="badge bg-secondary"><?= count($categories); ?> Categories</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Category Name</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th class="text-center">Packages</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $index => $cat): ?>
                                    <tr>
                                        <td class="ps-3 text-muted"><?= $index + 1; ?></td>
                                        <td class="fw-bold text-dark"><?= e($cat['name']); ?></td>
                                        <td><code><?= e($cat['slug']); ?></code></td>
                                        <td class="text-muted small" style="max-width: 280px;">
                                            <?= e($cat['description'] ?: '—'); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <?= (int)$cat['total_packages']; ?> Packages
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $cat['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?= ucfirst(e($cat['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <?php if ($canEdit): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-outline-secondary btn-sm p-1 px-2 btn-edit-category" 
                                                    data-id="<?= (int)$cat['id']; ?>"
                                                    data-name="<?= e($cat['name']); ?>"
                                                    data-slug="<?= e($cat['slug']); ?>"
                                                    data-description="<?= e($cat['description'] ?? ''); ?>"
                                                    data-status="<?= e($cat['status']); ?>"
                                                    title="Edit Category"
                                                >
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($canDelete): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-outline-danger btn-sm p-1 px-2 ms-1 btn-delete-category" 
                                                    data-id="<?= (int)$cat['id']; ?>"
                                                    data-name="<?= e($cat['name']); ?>"
                                                    title="Delete Category"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No tour categories found. Click "Add Category" to create one.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <?php if ($canCreate): ?>
        <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/tours/category-store.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="addCategoryModalLabel">Add New Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="add_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_name" name="name" placeholder="e.g. Adventure & Trekking" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_description" class="form-label">Description</label>
                                <textarea class="form-control" id="add_description" name="description" rows="3" placeholder="Short description of this category"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="add_status" class="form-label">Status</label>
                                <select class="form-select" id="add_status" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Category Modal -->
    <?php if ($canEdit): ?>
        <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/tours/category-update.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="edit_category_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="editCategoryModalLabel">Edit Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Delete Category Modal -->
    <?php if ($canDelete): ?>
        <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/tours/category-delete.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="delete_category_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="deleteCategoryModalLabel">Confirm Category Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete category <strong id="delete_category_name"></strong>?</p>
                            <p class="text-muted small mb-0">Note: A category cannot be deleted if active tour packages are assigned to it.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Edit Category Button
        const editButtons = document.querySelectorAll('.btn-edit-category');
        const editModal = document.getElementById('editCategoryModal');
        if (editModal) {
            const bsEditModal = new bootstrap.Modal(editModal);
            editButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_category_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_name').value = this.getAttribute('data-name');
                    document.getElementById('edit_description').value = this.getAttribute('data-description');
                    document.getElementById('edit_status').value = this.getAttribute('data-status');
                    bsEditModal.show();
                });
            });
        }

        // Handle Delete Category Button
        const deleteButtons = document.querySelectorAll('.btn-delete-category');
        const deleteModal = document.getElementById('deleteCategoryModal');
        if (deleteModal) {
            const bsDeleteModal = new bootstrap.Modal(deleteModal);
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('delete_category_id').value = this.getAttribute('data-id');
                    document.getElementById('delete_category_name').textContent = this.getAttribute('data-name');
                    bsDeleteModal.show();
                });
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
