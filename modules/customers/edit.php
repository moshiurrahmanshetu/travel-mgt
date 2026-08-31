<?php
/**
 * Edit Customer Form
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Edit Customer';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('customers.edit');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid customer identifier.');
    redirect('modules/customers/index.php');
}

$customer = null;
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash('error', 'Customer not found or was deleted.');
        redirect('modules/customers/index.php');
    }
} catch (PDOException $e) {
    error_log('Customer Edit Load Error: ' . $e->getMessage());
    set_flash('error', 'Failed to load customer record.');
    redirect('modules/customers/index.php');
}

$avatarUrl = get_customer_avatar_url($customer['profile_photo'] ?? null);
$initials = get_customer_initials($customer['name']);
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
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-secondary"><code><?= e($customer['customer_code']); ?></code></span>
                    <h2 class="fs-4 fw-bold text-dark mb-0">Edit Customer Profile</h2>
                </div>
                <p class="text-muted small mb-0">Update contact information, address details, or passport credentials.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('modules/customers/view.php?id=' . $customer['id']); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-eye me-1"></i> View Profile
                </a>
                <a href="<?= url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Directory
                </a>
            </div>
        </div>

        <form action="<?= url('modules/customers/update.php'); ?>" method="POST" enctype="multipart/form-data" autocomplete="off" id="customerEditForm">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$customer['id']; ?>">

            <div class="row g-4">
                <!-- Left Column: Personal, Address & Travel Docs (Col-8) -->
                <div class="col-12 col-lg-8">
                    <!-- Section 1: Basic & Contact Information -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-person me-2 text-primary"></i> Basic & Contact Information
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3 mb-3">
                                <!-- First Name -->
                                <div class="col-12 col-sm-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="first_name" 
                                        name="first_name" 
                                        value="<?= e($customer['first_name'] ?? ''); ?>"
                                    >
                                </div>

                                <!-- Last Name -->
                                <div class="col-12 col-sm-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="last_name" 
                                        name="last_name" 
                                        value="<?= e($customer['last_name'] ?? ''); ?>"
                                    >
                                </div>
                            </div>

                            <!-- Full Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Display Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="name" 
                                    name="name" 
                                    value="<?= e($customer['name']); ?>" 
                                    required
                                >
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Phone -->
                                <div class="col-12 col-sm-6">
                                    <label for="phone" class="form-label">Primary Phone <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="phone" 
                                            name="phone" 
                                            value="<?= e($customer['phone']); ?>" 
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Alternate Phone -->
                                <div class="col-12 col-sm-6">
                                    <label for="alternate_phone" class="form-label">Alternate Phone</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="alternate_phone" 
                                            name="alternate_phone" 
                                            value="<?= e($customer['alternate_phone'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Email -->
                                <div class="col-12 col-sm-6">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input 
                                            type="email" 
                                            class="form-control" 
                                            id="email" 
                                            name="email" 
                                            value="<?= e($customer['email'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>

                                <!-- Gender -->
                                <div class="col-12 col-sm-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select</option>
                                        <option value="male" <?= $customer['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?= $customer['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?= $customer['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <!-- Date of Birth -->
                                <div class="col-12 col-sm-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        id="date_of_birth" 
                                        name="date_of_birth" 
                                        value="<?= e($customer['date_of_birth'] ?? ''); ?>"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Address & Location -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-geo-alt me-2 text-primary"></i> Residential & Mailing Address
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?= e($customer['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?= e($customer['city'] ?? ''); ?>">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="state" class="form-label">State / Division</label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?= e($customer['state'] ?? ''); ?>">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" value="<?= e($customer['country'] ?? 'Bangladesh'); ?>">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?= e($customer['postal_code'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Travel Documents & Credentials -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-passport me-2 text-primary"></i> Travel Documents & Identity
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <!-- Passport Number -->
                                <div class="col-12 col-sm-4">
                                    <label for="passport_number" class="form-label">Passport Number</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="passport_number" 
                                        name="passport_number" 
                                        value="<?= e($customer['passport_number'] ?? ''); ?>"
                                    >
                                </div>

                                <!-- Passport Expiry -->
                                <div class="col-12 col-sm-4">
                                    <label for="passport_expiry" class="form-label">Passport Expiry Date</label>
                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        id="passport_expiry" 
                                        name="passport_expiry" 
                                        value="<?= e($customer['passport_expiry'] ?? ''); ?>"
                                    >
                                </div>

                                <!-- National ID -->
                                <div class="col-12 col-sm-4">
                                    <label for="national_id" class="form-label">National ID / Smart Card</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="national_id" 
                                        name="national_id" 
                                        value="<?= e($customer['national_id'] ?? ''); ?>"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Profile Photo, Notes & Publishing (Col-4) -->
                <div class="col-12 col-lg-4">
                    <!-- Status & Submission Card -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-sliders me-2 text-primary"></i> Account Status
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="mb-3">
                                <label for="status" class="form-label">Customer Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= $customer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?= $customer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <hr class="my-3">

                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-check2-circle me-1"></i> Update Customer Profile
                            </button>
                        </div>
                    </div>

                    <!-- Profile Photo Card -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-image me-2 text-primary"></i> Profile Photo
                            </h3>
                        </div>
                        <div class="admin-card-body text-center">
                            <!-- Existing / Preview Box -->
                            <div class="mb-3">
                                <?php if ($avatarUrl): ?>
                                    <img id="photo_preview" src="<?= e($avatarUrl); ?>" alt="<?= e($customer['name']); ?>" class="rounded-circle border mx-auto" style="width: 100px; height: 100px; object-fit: cover;">
                                    <div id="photo_placeholder" class="rounded-circle bg-light text-primary border d-none align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 2.25rem;">
                                        <?= e($initials); ?>
                                    </div>
                                <?php else: ?>
                                    <div id="photo_placeholder" class="rounded-circle bg-light text-primary border d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 2.25rem;">
                                        <?= e($initials); ?>
                                    </div>
                                    <img id="photo_preview" src="" alt="Profile Preview" class="rounded-circle border mx-auto d-none" style="width: 100px; height: 100px; object-fit: cover;">
                                <?php endif; ?>
                            </div>

                            <div class="mb-0 text-start">
                                <input 
                                    type="file" 
                                    class="form-control form-control-sm" 
                                    id="profile_photo" 
                                    name="profile_photo" 
                                    accept="image/jpeg,image/png,image/webp"
                                >
                                <div class="form-text small text-muted">Leave empty to keep existing photo. Allowed: JPG, PNG, WebP (Max 2MB).</div>
                            </div>
                        </div>
                    </div>

                    <!-- Administrative Notes Card -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-journal-text me-2 text-primary"></i> Special Notes / CRM
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="mb-0">
                                <label for="notes" class="form-label">Client Preferences & Internal Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"><?= e($customer['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Profile Photo Live Preview
        const photoInput = document.getElementById('profile_photo');
        const photoPreview = document.getElementById('photo_preview');
        const photoPlaceholder = document.getElementById('photo_placeholder');

        if (photoInput) {
            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        photoPreview.src = evt.target.result;
                        photoPreview.classList.remove('d-none');
                        if (photoPlaceholder) {
                            photoPlaceholder.classList.remove('d-flex');
                            photoPlaceholder.classList.add('d-none');
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
