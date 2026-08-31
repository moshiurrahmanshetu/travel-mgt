<?php
/**
 * Tour Destinations Management
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Tour Destinations';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('destinations.view');

$canCreate = has_permission('destinations.create');
$canEdit   = has_permission('destinations.edit');
$canDelete = has_permission('destinations.delete');

$destinations = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("
        SELECT d.*, 
               (SELECT COUNT(*) FROM tour_packages p WHERE p.destination_id = d.id AND p.deleted_at IS NULL) AS total_packages
        FROM tour_destinations d
        WHERE d.deleted_at IS NULL
        ORDER BY d.id DESC
    ");
    $destinations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Destinations list error: ' . $e->getMessage());
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
                <h2 class="fs-4 fw-bold text-dark mb-1">Tour Destinations</h2>
                <p class="text-muted small mb-0">Manage popular travel spots, regions, and international destinations.</p>
            </div>
            <?php if ($canCreate): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDestinationModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Destination
                </button>
            <?php endif; ?>
        </div>

        <!-- Destinations Table Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="bi bi-geo-alt me-2 text-primary"></i> All Destinations
                </h3>
                <span class="badge bg-secondary"><?= count($destinations); ?> Destinations</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Destination</th>
                                <th>Country</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th class="text-center">Packages</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($destinations)): ?>
                                <?php foreach ($destinations as $dest): 
                                    $destImgUrl = get_destination_image_url($dest['image'] ?? null);
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($destImgUrl): ?>
                                                    <img src="<?= e($destImgUrl); ?>" alt="<?= e($dest['name']); ?>" class="rounded" style="width: 48px; height: 36px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded bg-light text-muted d-flex align-items-center justify-content-center border" style="width: 48px; height: 36px; font-size: 1rem;">
                                                        <i class="bi bi-geo-alt"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= e($dest['name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= e($dest['country'] ?: 'Bangladesh'); ?></span></td>
                                        <td><code><?= e($dest['slug']); ?></code></td>
                                        <td class="text-muted small" style="max-width: 260px;">
                                            <?= e($dest['description'] ?: '—'); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <?= (int)$dest['total_packages']; ?> Packages
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $dest['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?= ucfirst(e($dest['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <?php if ($canEdit): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-outline-secondary btn-sm p-1 px-2 btn-edit-dest" 
                                                    data-id="<?= (int)$dest['id']; ?>"
                                                    data-name="<?= e($dest['name']); ?>"
                                                    data-country="<?= e($dest['country'] ?? ''); ?>"
                                                    data-description="<?= e($dest['description'] ?? ''); ?>"
                                                    data-status="<?= e($dest['status']); ?>"
                                                    data-image="<?= e($destImgUrl ?: ''); ?>"
                                                    title="Edit Destination"
                                                >
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($canDelete): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-outline-danger btn-sm p-1 px-2 ms-1 btn-delete-dest" 
                                                    data-id="<?= (int)$dest['id']; ?>"
                                                    data-name="<?= e($dest['name']); ?>"
                                                    title="Delete Destination"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No tour destinations found. Click "Add Destination" to create one.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Destination Modal -->
    <?php if ($canCreate): ?>
        <div class="modal fade" id="addDestinationModal" tabindex="-1" aria-labelledby="addDestinationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/tours/destination-store.php'); ?>" method="POST" enctype="multipart/form-data">
                        <?= csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="addDestinationModalLabel">Add New Destination</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="add_dest_name" class="form-label">Destination Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_dest_name" name="name" placeholder="e.g. Cox's Bazar" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_dest_country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="add_dest_country" name="country" placeholder="e.g. Bangladesh" value="Bangladesh">
                            </div>
                            <div class="mb-3">
                                <label for="add_dest_image" class="form-label">Cover Image</label>
                                <input type="file" class="form-control form-control-sm" id="add_dest_image" name="image" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text small text-muted">Allowed: JPG, PNG, WEBP (Max 2MB).</div>
                            </div>
                            <div class="mb-3">
                                <label for="add_dest_description" class="form-label">Description</label>
                                <textarea class="form-control" id="add_dest_description" name="description" rows="3" placeholder="Brief summary of this destination"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="add_dest_status" class="form-label">Status</label>
                                <select class="form-select" id="add_dest_status" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Destination</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Destination Modal -->
    <?php if ($canEdit): ?>
        <div class="modal fade" id="editDestinationModal" tabindex="-1" aria-labelledby="editDestinationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/tours/destination-update.php'); ?>" method="POST" enctype="multipart/form-data">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="edit_dest_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="editDestinationModalLabel">Edit Destination</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_dest_name" class="form-label">Destination Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_dest_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_dest_country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="edit_dest_country" name="country">
                            </div>
                            <div class="mb-3">
                                <label for="edit_dest_image" class="form-label">Cover Image (Optional)</label>
                                <div id="edit_dest_preview_container" class="mb-2 d-none">
                                    <img src="" id="edit_dest_preview_img" alt="Destination Preview" class="rounded border" style="max-height: 80px; object-fit: cover;">
                                </div>
                                <input type="file" class="form-control form-control-sm" id="edit_dest_image" name="image" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text small text-muted">Leave empty to keep existing image.</div>
                            </div>
                            <div class="mb-3">
                                <label for="edit_dest_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_dest_description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_dest_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_dest_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Destination</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Delete Destination Modal -->
    <?php if ($canDelete): ?>
        <div class="modal fade" id="deleteDestinationModal" tabindex="-1" aria-labelledby="deleteDestinationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= url('modules/tours/destination-delete.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="id" id="delete_dest_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="deleteDestinationModalLabel">Confirm Destination Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete destination <strong id="delete_dest_name"></strong>?</p>
                            <p class="text-muted small mb-0">Note: A destination cannot be deleted if active tour packages are assigned to it.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Destination</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Edit Destination Button
        const editButtons = document.querySelectorAll('.btn-edit-dest');
        const editModal = document.getElementById('editDestinationModal');
        if (editModal) {
            const bsEditModal = new bootstrap.Modal(editModal);
            editButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_dest_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_dest_name').value = this.getAttribute('data-name');
                    document.getElementById('edit_dest_country').value = this.getAttribute('data-country');
                    document.getElementById('edit_dest_description').value = this.getAttribute('data-description');
                    document.getElementById('edit_dest_status').value = this.getAttribute('data-status');
                    
                    const imgUrl = this.getAttribute('data-image');
                    const previewContainer = document.getElementById('edit_dest_preview_container');
                    const previewImg = document.getElementById('edit_dest_preview_img');
                    if (imgUrl) {
                        previewImg.src = imgUrl;
                        previewContainer.classList.remove('d-none');
                    } else {
                        previewContainer.classList.add('d-none');
                    }

                    bsEditModal.show();
                });
            });
        }

        // Handle Delete Destination Button
        const deleteButtons = document.querySelectorAll('.btn-delete-dest');
        const deleteModal = document.getElementById('deleteDestinationModal');
        if (deleteModal) {
            const bsDeleteModal = new bootstrap.Modal(deleteModal);
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('delete_dest_id').value = this.getAttribute('data-id');
                    document.getElementById('delete_dest_name').textContent = this.getAttribute('data-name');
                    bsDeleteModal.show();
                });
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
