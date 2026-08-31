<?php
/**
 * Create Customer Form
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Add New Customer';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('customers.create');
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
                <h2 class="fs-4 fw-bold text-dark mb-1">Add New Customer</h2>
                <p class="text-muted small mb-0">Register a new client profile with contact, address, and travel documents.</p>
            </div>
            <a href="<?= url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Directory
            </a>
        </div>

        <form action="<?= url('modules/customers/store.php'); ?>" method="POST" enctype="multipart/form-data" autocomplete="off" id="customerCreateForm">
            <?= csrf_field(); ?>

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
                                        placeholder="e.g. Tanvir" 
                                        value="<?= e(old('first_name')); ?>"
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
                                        placeholder="e.g. Ahmed" 
                                        value="<?= e(old('last_name')); ?>"
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
                                    placeholder="e.g. Tanvir Ahmed" 
                                    value="<?= e(old('name')); ?>" 
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
                                            placeholder="e.g. +8801711000000" 
                                            value="<?= e(old('phone')); ?>" 
                                            required
                                        >
                                    </div>
                                    <div class="form-text small text-muted">Primary contact for booking alerts and tickets.</div>
                                </div>

                                <!-- Alternate Phone -->
                                <div class="col-12 col-sm-6">
                                    <label for="alternate_phone" class="form-label">Alternate Phone (Optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="alternate_phone" 
                                            name="alternate_phone" 
                                            placeholder="e.g. +8801811000000" 
                                            value="<?= e(old('alternate_phone')); ?>"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Email -->
                                <div class="col-12 col-sm-6">
                                    <label for="email" class="form-label">Email Address (Optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input 
                                            type="email" 
                                            class="form-control" 
                                            id="email" 
                                            name="email" 
                                            placeholder="e.g. tanvir@example.com" 
                                            value="<?= e(old('email')); ?>"
                                        >
                                    </div>
                                </div>

                                <!-- Gender -->
                                <div class="col-12 col-sm-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select</option>
                                        <option value="male" <?= old('gender') === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?= old('gender') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?= old('gender') === 'other' ? 'selected' : ''; ?>>Other</option>
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
                                        value="<?= e(old('date_of_birth')); ?>"
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
                                <textarea class="form-control" id="address" name="address" rows="2" placeholder="House, Road, Area..."><?= e(old('address')); ?></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" placeholder="e.g. Dhaka" value="<?= e(old('city')); ?>">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="state" class="form-label">State / Division</label>
                                    <input type="text" class="form-control" id="state" name="state" placeholder="e.g. Dhaka Division" value="<?= e(old('state')); ?>">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" placeholder="e.g. Bangladesh" value="<?= e(old('country', 'Bangladesh')); ?>">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="e.g. 1209" value="<?= e(old('postal_code')); ?>">
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
                                        placeholder="e.g. A01234567" 
                                        value="<?= e(old('passport_number')); ?>"
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
                                        value="<?= e(old('passport_expiry')); ?>"
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
                                        placeholder="e.g. 19901234567890123" 
                                        value="<?= e(old('national_id')); ?>"
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
                                    <option value="active" <?= old('status') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div class="form-text small text-muted">Inactive clients cannot be booked into upcoming tours.</div>
                            </div>

                            <hr class="my-3">

                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-check2-circle me-1"></i> Save Customer Profile
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
                            <!-- Preview Box -->
                            <div class="mb-3">
                                <div id="photo_preview_container" class="d-inline-block">
                                    <div id="photo_placeholder" class="rounded-circle bg-light text-primary border d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 2.25rem;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <img id="photo_preview" src="" alt="Profile Preview" class="rounded-circle border mx-auto d-none" style="width: 100px; height: 100px; object-fit: cover;">
                                </div>
                            </div>

                            <div class="mb-0 text-start">
                                <input 
                                    type="file" 
                                    class="form-control form-control-sm" 
                                    id="profile_photo" 
                                    name="profile_photo" 
                                    accept="image/jpeg,image/png,image/webp"
                                >
                                <div class="form-text small text-muted">Allowed: JPG, PNG, WebP (Max 2MB).</div>
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
                                <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Special requirements, dietary needs, preferred seats, VIP status..."><?= e(old('notes')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Name Sync & Image Preview JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Auto-sync first_name and last_name to full name if full name hasn't been manually detached
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const nameInput = document.getElementById('name');
        let userManuallyChangedName = false;

        nameInput.addEventListener('input', function() {
            userManuallyChangedName = true;
        });

        function syncFullName() {
            if (!userManuallyChangedName) {
                const f = firstNameInput.value.trim();
                const l = lastNameInput.value.trim();
                nameInput.value = (f + ' ' + l).trim();
            }
        }

        firstNameInput.addEventListener('input', syncFullName);
        lastNameInput.addEventListener('input', syncFullName);

        // 2. Profile Photo Live Preview
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
                        photoPlaceholder.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                } else {
                    photoPreview.classList.add('d-none');
                    photoPlaceholder.classList.remove('d-none');
                }
            });
        }
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
