<?php
/**
 * Edit Tour Package Page
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Edit Tour Package';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Enforce Permission
require_permission('tours.edit');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid tour package ID.');
    redirect('modules/tours/index.php');
}

$package = null;
$categories = [];
$destinations = [];
$galleryImages = [];
$itineraries = [];

try {
    $pdo = get_db_connection();

    // Fetch Package
    $stmt = $pdo->prepare("SELECT * FROM tour_packages WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $package = $stmt->fetch();

    if (!$package) {
        set_flash('error', 'Tour package does not exist.');
        redirect('modules/tours/index.php');
    }

    // Fetch Categories & Destinations
    $catStmt = $pdo->query("SELECT id, name FROM tour_categories WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
    $categories = $catStmt->fetchAll();

    $destStmt = $pdo->query("SELECT id, name, country FROM tour_destinations WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
    $destinations = $destStmt->fetchAll();

    // Fetch Gallery Images
    $imgStmt = $pdo->prepare("SELECT * FROM tour_package_images WHERE tour_package_id = :id ORDER BY sort_order ASC, id ASC");
    $imgStmt->execute(['id' => $id]);
    $galleryImages = $imgStmt->fetchAll();

    // Fetch Itineraries
    $itinStmt = $pdo->prepare("SELECT * FROM tour_package_itineraries WHERE tour_package_id = :id ORDER BY day_number ASC");
    $itinStmt->execute(['id' => $id]);
    $itineraries = $itinStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Tour Edit Error: ' . $e->getMessage());
    set_flash('error', 'Failed to load tour package for editing.');
    redirect('modules/tours/index.php');
}

$featuredImgUrl = get_tour_image_url($package['featured_image'] ?? null);
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
                    <span class="badge bg-warning"><code class="text-black" ><?= e($package['package_code']); ?></code></span>
                    <h2 class="fs-4 fw-bold text-dark mb-0">Edit Tour Package</h2>
                </div>
                <p class="text-muted small mb-0">Update package details, schedule, pricing, or media.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('modules/tours/view.php?id=' . $package['id']); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-eye me-1"></i> View Details
                </a>
                <a href="<?= url('modules/tours/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>

        <form action="<?= url('modules/tours/update.php'); ?>" method="POST" enctype="multipart/form-data" autocomplete="off" id="tourPackageEditForm">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$package['id']; ?>">

            <div class="row g-4">
                <!-- Left Column: Details & Itinerary (Col-8) -->
                <div class="col-12 col-lg-8">
                    <!-- Section 1: Basic Information -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-info-circle me-2 text-primary"></i> Basic Package Information
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <!-- Package Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Package Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="name" 
                                    name="name" 
                                    value="<?= e($package['name']); ?>" 
                                    required
                                >
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Category -->
                                <div class="col-12 col-sm-6">
                                    <label for="category_id" class="form-label">Tour Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= (int)$cat['id']; ?>" <?= (int)$package['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                                                <?= e($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Destination -->
                                <div class="col-12 col-sm-6">
                                    <label for="destination_id" class="form-label">Primary Destination <span class="text-danger">*</span></label>
                                    <select class="form-select" id="destination_id" name="destination_id" required>
                                        <option value="">Select Destination</option>
                                        <?php foreach ($destinations as $dest): ?>
                                            <option value="<?= (int)$dest['id']; ?>" <?= (int)$package['destination_id'] === (int)$dest['id'] ? 'selected' : ''; ?>>
                                                <?= e($dest['name']) . ' (' . e($dest['country']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Departure Location -->
                                <div class="col-12 col-sm-6">
                                    <label for="departure_location" class="form-label">Departure Location</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="departure_location" 
                                        name="departure_location" 
                                        value="<?= e($package['departure_location'] ?? ''); ?>"
                                    >
                                </div>

                                <!-- Available Seats -->
                                <div class="col-12 col-sm-6">
                                    <label for="available_seats" class="form-label">Available Capacity / Seats <span class="text-danger">*</span></label>
                                    <input 
                                        type="number" 
                                        class="form-control" 
                                        id="available_seats" 
                                        name="available_seats" 
                                        min="0" 
                                        value="<?= (int)$package['available_seats']; ?>" 
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Short Description -->
                            <div class="mb-3">
                                <label for="short_description" class="form-label">Short Summary (Highlights)</label>
                                <textarea class="form-control" id="short_description" name="short_description" rows="2"><?= e($package['short_description'] ?? ''); ?></textarea>
                            </div>

                            <!-- Full Description -->
                            <div class="mb-0">
                                <label for="description" class="form-label">Full Package Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?= e($package['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Itinerary Timeline Builder -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header d-flex justify-content-between align-items-center">
                            <h3 class="admin-card-title">
                                <i class="bi bi-calendar-range me-2 text-primary"></i> Tour Itinerary (Day-by-Day)
                            </h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddItineraryDay">
                                <i class="bi bi-plus-circle me-1"></i> Add Day
                            </button>
                        </div>
                        <div class="admin-card-body">
                            <p class="text-muted small mb-3">Add or modify scheduled activities for each day of the tour.</p>
                            
                            <div id="itineraryDaysContainer" class="d-flex flex-column gap-3">
                                <?php if (!empty($itineraries)): ?>
                                    <?php foreach ($itineraries as $index => $itin): ?>
                                        <div class="itinerary-day-row border rounded p-3 bg-light" data-day="<?= $index + 1; ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-primary px-2 py-1 day-badge">Day <?= $index + 1; ?></span>
                                                <button type="button" class="btn btn-outline-danger btn-sm p-0 px-2 btn-remove-day" title="Remove this day" <?= count($itineraries) === 1 ? 'style="display: none;"' : ''; ?>>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Day <?= $index + 1; ?> Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm itinerary-title-input" name="itinerary_title[]" value="<?= e($itin['title']); ?>" required>
                                            </div>
                                            <div>
                                                <label class="form-label small fw-semibold">Activity Details</label>
                                                <textarea class="form-control form-control-sm" name="itinerary_description[]" rows="2"><?= e($itin['description'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="itinerary-day-row border rounded p-3 bg-light" data-day="1">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-primary px-2 py-1 day-badge">Day 1</span>
                                            <button type="button" class="btn btn-outline-danger btn-sm p-0 px-2 btn-remove-day" title="Remove this day" style="display: none;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Day 1 Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm itinerary-title-input" name="itinerary_title[]" placeholder="e.g. Arrival & Hotel Check-in" required>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-semibold">Activity Details</label>
                                            <textarea class="form-control form-control-sm" name="itinerary_description[]" rows="2"></textarea>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Inclusions, Logistics & Terms -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-card-checklist me-2 text-primary"></i> Logistics, Inclusions & Policies
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-6">
                                    <label for="included_services" class="form-label">Included Services</label>
                                    <textarea class="form-control" id="included_services" name="included_services" rows="3"><?= e($package['included_services'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="excluded_services" class="form-label">Excluded Services</label>
                                    <textarea class="form-control" id="excluded_services" name="excluded_services" rows="3"><?= e($package['excluded_services'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-6">
                                    <label for="hotel_information" class="form-label">Hotel & Accommodation Info</label>
                                    <textarea class="form-control" id="hotel_information" name="hotel_information" rows="2"><?= e($package['hotel_information'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="transportation" class="form-label">Transportation Details</label>
                                    <textarea class="form-control" id="transportation" name="transportation" rows="2"><?= e($package['transportation'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label for="meal_information" class="form-label">Meal Information</label>
                                    <textarea class="form-control" id="meal_information" name="meal_information" rows="2"><?= e($package['meal_information'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="terms_conditions" class="form-label">Booking Terms & Cancellation Policy</label>
                                    <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="2"><?= e($package['terms_conditions'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Pricing, Timing, Media & Publishing (Col-4) -->
                <div class="col-12 col-lg-4">
                    <!-- Publishing Card -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-sliders me-2 text-primary"></i> Publishing Options
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <!-- Status -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Publication Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= $package['status'] === 'active' ? 'selected' : ''; ?>>Active (Published)</option>
                                    <option value="draft" <?= $package['status'] === 'draft' ? 'selected' : ''; ?>>Draft (Hidden)</option>
                                    <option value="inactive" <?= $package['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <!-- Featured Flag -->
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1" <?= $package['featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="featured">
                                    Mark as Featured Tour
                                </label>
                            </div>

                            <hr class="my-3">

                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-check2-circle me-1"></i> Update Tour Package
                            </button>
                        </div>
                    </div>

                    <!-- Duration Card -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-hourglass-split me-2 text-primary"></i> Duration
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="duration_days" class="form-label">Days <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="duration_days" name="duration_days" min="1" value="<?= (int)$package['duration_days']; ?>" required>
                                </div>
                                <div class="col-6">
                                    <label for="duration_nights" class="form-label">Nights <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="duration_nights" name="duration_nights" min="0" value="<?= (int)$package['duration_nights']; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Card -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-currency-dollar me-2 text-primary"></i> Pricing & Discounts
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <!-- Regular Adult Price -->
                            <div class="mb-3">
                                <label for="price" class="form-label">Base Adult Price (<?= e(APP_CURRENCY_SYMBOL); ?>) <span class="text-danger">*</span></label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    class="form-control price-calc-trigger" 
                                    id="price" 
                                    name="price" 
                                    value="<?= (float)$package['price']; ?>" 
                                    required
                                >
                            </div>

                            <!-- Child Price -->
                            <div class="mb-3">
                                <label for="child_price" class="form-label">Child Price (Optional)</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    class="form-control" 
                                    id="child_price" 
                                    name="child_price" 
                                    value="<?= $package['child_price'] !== null ? (float)$package['child_price'] : ''; ?>"
                                >
                            </div>

                            <!-- Discount Type -->
                            <div class="mb-3">
                                <label for="discount_type" class="form-label">Discount Type</label>
                                <select class="form-select price-calc-trigger" id="discount_type" name="discount_type">
                                    <option value="none" <?= $package['discount_type'] === 'none' ? 'selected' : ''; ?>>No Discount</option>
                                    <option value="percentage" <?= $package['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                    <option value="fixed" <?= $package['discount_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount (<?= e(APP_CURRENCY_SYMBOL); ?>)</option>
                                </select>
                            </div>

                            <!-- Discount Value -->
                            <div class="mb-3">
                                <label for="discount_value" class="form-label">Discount Value</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    class="form-control price-calc-trigger" 
                                    id="discount_value" 
                                    name="discount_value" 
                                    value="<?= (float)$package['discount_value']; ?>"
                                >
                            </div>

                            <!-- Live Price Calculation Box -->
                            <div class="p-3 border rounded bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Final Selling Price:</span>
                                    <strong class="fs-5 text-primary" id="calculatedFinalPrice"><?= e(APP_CURRENCY_SYMBOL); ?>0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-muted small">
                                    <span>Discount Applied:</span>
                                    <span id="calculatedDiscountSummary">None</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Media Assets Card -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="bi bi-images me-2 text-primary"></i> Media Assets
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <!-- Featured Image -->
                            <div class="mb-4">
                                <label for="featured_image" class="form-label">Featured Cover Image</label>
                                <?php if ($featuredImgUrl): ?>
                                    <div class="mb-2 position-relative">
                                        <img src="<?= e($featuredImgUrl); ?>" id="featured_image_preview_el" alt="Featured Cover" class="rounded w-100 border" style="max-height: 140px; object-fit: cover;">
                                    </div>
                                <?php else: ?>
                                    <div id="featured_image_preview_box" class="mb-2 d-none">
                                        <img src="" id="featured_image_preview_el" alt="Featured Preview" class="rounded w-100 border" style="max-height: 140px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <input 
                                    type="file" 
                                    class="form-control form-control-sm" 
                                    id="featured_image" 
                                    name="featured_image" 
                                    accept="image/jpeg,image/png,image/webp"
                                >
                                <div class="form-text small text-muted">Leave empty to keep existing cover photo.</div>
                            </div>

                            <!-- Existing Gallery Photos -->
                            <?php if (!empty($galleryImages)): ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark">Current Gallery Photos</label>
                                    <div class="row g-2">
                                        <?php foreach ($galleryImages as $gImg): 
                                            $gUrl = get_tour_image_url($gImg['image']);
                                            if ($gUrl):
                                        ?>
                                            <div class="col-4 position-relative">
                                                <img src="<?= e($gUrl); ?>" alt="Gallery Image" class="rounded border w-100" style="height: 60px; object-fit: cover;">
                                                <button 
                                                    type="button" 
                                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 px-1 m-1 btn-delete-gallery-img" 
                                                    data-id="<?= (int)$gImg['id']; ?>"
                                                    data-pkg-id="<?= (int)$package['id']; ?>"
                                                    title="Remove Image"
                                                >
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Upload More Gallery Images -->
                            <div class="mb-0">
                                <label for="gallery_images" class="form-label">Add More Gallery Photos</label>
                                <input 
                                    type="file" 
                                    class="form-control form-control-sm" 
                                    id="gallery_images" 
                                    name="gallery_images[]" 
                                    accept="image/jpeg,image/png,image/webp" 
                                    multiple
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Hidden Form for Deleting Individual Gallery Images -->
    <form action="<?= url('modules/tours/delete-image.php'); ?>" method="POST" id="deleteGalleryImageForm" class="d-none">
        <?= csrf_field(); ?>
        <input type="hidden" name="image_id" id="delete_gallery_image_id">
        <input type="hidden" name="package_id" id="delete_gallery_package_id">
    </form>

    <!-- Dynamic Itinerary Builder & Price Calculator JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Live Price Calculation Logic
        const priceInput = document.getElementById('price');
        const discountTypeSelect = document.getElementById('discount_type');
        const discountValueInput = document.getElementById('discount_value');
        const calculatedFinalPriceEl = document.getElementById('calculatedFinalPrice');
        const calculatedDiscountSummaryEl = document.getElementById('calculatedDiscountSummary');
        const currencySymbol = '<?= e(APP_CURRENCY_SYMBOL); ?>';

        function updatePriceCalculation() {
            const basePrice = parseFloat(priceInput.value) || 0;
            const discountType = discountTypeSelect.value;
            const discountVal = parseFloat(discountValueInput.value) || 0;

            let finalPrice = basePrice;
            let summaryText = 'None';

            if (discountType === 'percentage' && discountVal > 0) {
                const discountAmount = (basePrice * discountVal) / 100;
                finalPrice = Math.max(0, basePrice - discountAmount);
                summaryText = `-${discountVal}% (-${currencySymbol}${discountAmount.toFixed(2)})`;
            } else if (discountType === 'fixed' && discountVal > 0) {
                finalPrice = Math.max(0, basePrice - discountVal);
                summaryText = `-${currencySymbol}${discountVal.toFixed(2)}`;
            }

            calculatedFinalPriceEl.textContent = `${currencySymbol}${finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            calculatedDiscountSummaryEl.textContent = summaryText;
        }

        document.querySelectorAll('.price-calc-trigger').forEach(el => {
            el.addEventListener('input', updatePriceCalculation);
            el.addEventListener('change', updatePriceCalculation);
        });

        updatePriceCalculation();

        // 2. Featured Image Preview
        const featuredImageInput = document.getElementById('featured_image');
        const previewEl = document.getElementById('featured_image_preview_el');
        const previewBox = document.getElementById('featured_image_preview_box');

        if (featuredImageInput) {
            featuredImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        if (previewEl) {
                            previewEl.src = evt.target.result;
                        }
                        if (previewBox) {
                            previewBox.classList.remove('d-none');
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // 3. Dynamic Itinerary Builder
        const container = document.getElementById('itineraryDaysContainer');
        const btnAddDay = document.getElementById('btnAddItineraryDay');
        const durationDaysInput = document.getElementById('duration_days');

        function reindexItineraryDays() {
            const rows = container.querySelectorAll('.itinerary-day-row');
            rows.forEach((row, idx) => {
                const dayNum = idx + 1;
                row.setAttribute('data-day', dayNum);
                const badge = row.querySelector('.day-badge');
                if (badge) badge.textContent = `Day ${dayNum}`;

                const titleLabel = row.querySelector('label');
                if (titleLabel) titleLabel.innerHTML = `Day ${dayNum} Title <span class="text-danger">*</span>`;

                const removeBtn = row.querySelector('.btn-remove-day');
                if (removeBtn) {
                    removeBtn.style.display = rows.length > 1 ? 'inline-block' : 'none';
                }
            });

            if (durationDaysInput && rows.length > 0) {
                durationDaysInput.value = rows.length;
            }
        }

        if (btnAddDay) {
            btnAddDay.addEventListener('click', function() {
                const currentCount = container.querySelectorAll('.itinerary-day-row').length;
                const nextDay = currentCount + 1;

                const newRow = document.createElement('div');
                newRow.className = 'itinerary-day-row border rounded p-3 bg-light';
                newRow.setAttribute('data-day', nextDay);
                newRow.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary px-2 py-1 day-badge">Day ${nextDay}</span>
                        <button type="button" class="btn btn-outline-danger btn-sm p-0 px-2 btn-remove-day" title="Remove this day">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Day ${nextDay} Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm itinerary-title-input" name="itinerary_title[]" placeholder="e.g. Sightseeing & Activities" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Activity Details</label>
                        <textarea class="form-control form-control-sm" name="itinerary_description[]" rows="2" placeholder="Details of visits, meals, and leisure..."></textarea>
                    </div>
                `;

                container.appendChild(newRow);
                reindexItineraryDays();
            });
        }

        container.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.btn-remove-day');
            if (removeBtn) {
                const row = removeBtn.closest('.itinerary-day-row');
                if (row && container.querySelectorAll('.itinerary-day-row').length > 1) {
                    row.remove();
                    reindexItineraryDays();
                }
            }
        });

        // 4. Delete Gallery Image Button Handler
        const deleteImgButtons = document.querySelectorAll('.btn-delete-gallery-img');
        deleteImgButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this gallery image?')) {
                    const imgId = this.getAttribute('data-id');
                    const pkgId = this.getAttribute('data-pkg-id');
                    document.getElementById('delete_gallery_image_id').value = imgId;
                    document.getElementById('delete_gallery_package_id').value = pkgId;
                    document.getElementById('deleteGalleryImageForm').submit();
                }
            });
        });
    });
    </script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
