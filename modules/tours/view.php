<?php
/**
 * View Tour Package Details
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('tours.view');

$canEdit   = has_permission('tours.edit');
$canDelete = has_permission('tours.delete');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Tour package not found.');
    redirect('modules/tours/index.php');
}

$package = null;
$galleryImages = [];
$itineraries = [];

try {
    $pdo = get_db_connection();

    // Fetch Package details
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            c.name AS category_name, 
            d.name AS destination_name,
            d.country AS destination_country
        FROM tour_packages p
        LEFT JOIN tour_categories c ON p.category_id = c.id
        LEFT JOIN tour_destinations d ON p.destination_id = d.id
        WHERE p.id = :id AND p.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    $package = $stmt->fetch();

    if (!$package) {
        set_flash('error', 'Tour package does not exist or has been removed.');
        redirect('modules/tours/index.php');
    }

    // Fetch Gallery images
    $imgStmt = $pdo->prepare("SELECT * FROM tour_package_images WHERE tour_package_id = :id ORDER BY sort_order ASC, id ASC");
    $imgStmt->execute(['id' => $id]);
    $galleryImages = $imgStmt->fetchAll();

    // Fetch Itineraries
    $itinStmt = $pdo->prepare("SELECT * FROM tour_package_itineraries WHERE tour_package_id = :id ORDER BY day_number ASC");
    $itinStmt->execute(['id' => $id]);
    $itineraries = $itinStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Tour View Error: ' . $e->getMessage());
    set_flash('error', 'Failed to load tour package details.');
    redirect('modules/tours/index.php');
}

$pageTitle = $package['name'];
$featuredImgUrl = get_tour_image_url($package['featured_image'] ?? null);
$finalPrice = calculate_discounted_price($package['price'], $package['discount_type'], $package['discount_value']);

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <!-- Top Header Navigation Bar -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning"><code class="text-black"><?= e($package['package_code']); ?></code></span>
                    <span class="badge <?= $package['status'] === 'active' ? 'bg-success' : ($package['status'] === 'draft' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                        <?= ucfirst(e($package['status'])); ?>
                    </span>
                    <?php if ($package['featured']): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Featured</span>
                    <?php endif; ?>
                </div>
                <h2 class="fs-4 fw-bold text-dark mb-0"><?= e($package['name']); ?></h2>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('modules/tours/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <?php if ($canEdit): ?>
                    <a href="<?= url('modules/tours/edit.php?id=' . $package['id']); ?>" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> Edit Package
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Media, Overview, Itinerary (Col-8) -->
            <div class="col-12 col-lg-8">
                <!-- Featured Image & Gallery Card -->
                <div class="admin-card mb-4 overflow-hidden">
                    <?php if ($featuredImgUrl): ?>
                        <img src="<?= e($featuredImgUrl); ?>" alt="<?= e($package['name']); ?>" class="w-100" style="max-height: 380px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light text-muted d-flex flex-column align-items-center justify-content-center p-5 border-bottom">
                            <i class="bi bi-image fs-1 mb-2"></i>
                            <span class="small">No featured cover photo uploaded</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($galleryImages)): ?>
                        <div class="p-3 bg-light border-top">
                            <h4 class="fs-6 fw-bold text-dark mb-2">Gallery Photos</h4>
                            <div class="row g-2">
                                <?php foreach ($galleryImages as $gImg): 
                                    $gUrl = get_tour_image_url($gImg['image']);
                                    if ($gUrl):
                                ?>
                                    <div class="col-4 col-sm-3 col-md-2">
                                        <a href="<?= e($gUrl); ?>" target="_blank">
                                            <img src="<?= e($gUrl); ?>" alt="Gallery Image" class="rounded border w-100" style="height: 70px; object-fit: cover;">
                                        </a>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Package Overview & Descriptions -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-text-paragraph me-2 text-primary"></i> Tour Description
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (!empty($package['short_description'])): ?>
                            <div class="p-3 bg-light border rounded mb-3">
                                <strong class="text-dark d-block mb-1">Highlights Summary:</strong>
                                <p class="text-muted mb-0"><?= nl2br(e($package['short_description'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="text-dark" style="line-height: 1.7;">
                            <?= !empty($package['description']) ? nl2br(e($package['description'])) : '<p class="text-muted">No detailed description provided.</p>'; ?>
                        </div>
                    </div>
                </div>

                <!-- Itinerary Timeline -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-calendar-check me-2 text-primary"></i> Detailed Daily Itinerary
                        </h3>
                        <span class="badge bg-secondary"><?= count($itineraries); ?> Days Scheduled</span>
                    </div>
                    <div class="admin-card-body">
                        <?php if (!empty($itineraries)): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($itineraries as $itin): ?>
                                    <div class="border rounded p-3 bg-light">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary px-2 py-1">Day <?= (int)$itin['day_number']; ?></span>
                                            <h5 class="fs-6 fw-bold text-dark mb-0"><?= e($itin['title']); ?></h5>
                                        </div>
                                        <?php if (!empty($itin['description'])): ?>
                                            <p class="text-muted small mb-0 ps-1"><?= nl2br(e($itin['description'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No day-by-day itinerary defined for this package.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Services Included & Excluded -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-card-checklist me-2 text-primary"></i> Services & Conditions
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="row g-4 mb-3">
                            <!-- Included -->
                            <div class="col-12 col-md-6">
                                <h4 class="fs-6 fw-bold text-success mb-2">
                                    <i class="bi bi-check-circle-fill me-1"></i> Included Services
                                </h4>
                                <?php if (!empty($package['included_services'])): ?>
                                    <div class="p-3 border border-success-subtle rounded bg-success-subtle text-dark small" style="white-space: pre-line;">
                                        <?= e($package['included_services']); ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small">None specified.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Excluded -->
                            <div class="col-12 col-md-6">
                                <h4 class="fs-6 fw-bold text-danger mb-2">
                                    <i class="bi bi-x-circle-fill me-1"></i> Excluded Services
                                </h4>
                                <?php if (!empty($package['excluded_services'])): ?>
                                    <div class="p-3 border border-danger-subtle rounded bg-danger-subtle text-dark small" style="white-space: pre-line;">
                                        <?= e($package['excluded_services']); ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small">None specified.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Terms & Policies -->
                        <?php if (!empty($package['terms_conditions'])): ?>
                            <div class="pt-3 border-top">
                                <h4 class="fs-6 fw-bold text-dark mb-2">Booking Terms & Policies</h4>
                                <p class="text-muted small mb-0"><?= nl2br(e($package['terms_conditions'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Logistics, Hotel, Price Card (Col-4) -->
            <div class="col-12 col-lg-4">
                <!-- Pricing Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-currency-dollar me-2 text-primary"></i> Pricing Breakdown
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="text-center p-3 bg-light rounded border mb-3">
                            <span class="text-muted small d-block mb-1">Starting Price Per Adult</span>
                            <h3 class="fs-2 fw-bold text-primary mb-0"><?= format_currency($finalPrice); ?></h3>
                            <?php if ($package['discount_type'] !== 'none' && (float)$package['discount_value'] > 0): ?>
                                <div class="mt-1">
                                    <span class="text-muted text-decoration-line-through small"><?= format_currency($package['price']); ?></span>
                                    <span class="badge bg-danger ms-1">
                                        <?= $package['discount_type'] === 'percentage' ? '-' . (float)$package['discount_value'] . '%' : '-' . format_currency($package['discount_value']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <ul class="list-group list-group-flush small mb-3">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Child Price:</span>
                                <span class="fw-semibold"><?= $package['child_price'] ? format_currency($package['child_price']) : 'N/A'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Available Capacity:</span>
                                <span class="d-flex align-items-center badge <?= (int)$package['available_seats'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                    <?= (int)$package['available_seats']; ?> Seats Available
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Duration:</span>
                                <span class="fw-semibold"><?= (int)$package['duration_days']; ?> Days / <?= (int)$package['duration_nights']; ?> Nights</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Departure From:</span>
                                <span class="fw-semibold"><?= e($package['departure_location'] ?: '—'); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Destination & Category Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-geo-alt me-2 text-primary"></i> Destination & Category
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Primary Destination:</span>
                                <span class="fw-bold text-dark"><?= e($package['destination_name'] ?? '—'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Country:</span>
                                <span class="fw-semibold"><?= e($package['destination_country'] ?? 'Bangladesh'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Tour Category:</span>
                                <span class="d-flex align-items-center badge bg-primary"><?= e($package['category_name'] ?? 'Uncategorized'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Package Slug:</span>
                                <code><?= e($package['slug']); ?></code>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Logistics & Hotel Card -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-building me-2 text-primary"></i> Hotel & Transport
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="mb-3">
                            <strong class="small text-dark d-block mb-1">Accommodation:</strong>
                            <p class="text-muted small mb-0"><?= !empty($package['hotel_information']) ? nl2br(e($package['hotel_information'])) : '—'; ?></p>
                        </div>
                        <div class="mb-3">
                            <strong class="small text-dark d-block mb-1">Transportation:</strong>
                            <p class="text-muted small mb-0"><?= !empty($package['transportation']) ? nl2br(e($package['transportation'])) : '—'; ?></p>
                        </div>
                        <div>
                            <strong class="small text-dark d-block mb-1">Meal Plan:</strong>
                            <p class="text-muted small mb-0"><?= !empty($package['meal_information']) ? nl2br(e($package['meal_information'])) : '—'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
