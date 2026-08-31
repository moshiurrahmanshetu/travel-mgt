<?php
/**
 * Update Tour Package Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('tours.edit');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/tours/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/tours/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid tour package ID.');
    redirect('modules/tours/index.php');
}

// 2. Collect and Sanitize Inputs
$name               = trim($_POST['name'] ?? '');
$categoryId         = (int)($_POST['category_id'] ?? 0);
$destinationId      = (int)($_POST['destination_id'] ?? 0);
$departureLocation  = trim($_POST['departure_location'] ?? '');
$availableSeats     = max(0, (int)($_POST['available_seats'] ?? 0));
$durationDays       = max(1, (int)($_POST['duration_days'] ?? 1));
$durationNights      = max(0, (int)($_POST['duration_nights'] ?? 0));
$price              = max(0.0, (float)($_POST['price'] ?? 0));
$childPrice         = isset($_POST['child_price']) && $_POST['child_price'] !== '' ? max(0.0, (float)$_POST['child_price']) : null;
$discountType       = in_array($_POST['discount_type'] ?? '', ['none', 'percentage', 'fixed'], true) ? $_POST['discount_type'] : 'none';
$discountValue      = max(0.0, (float)($_POST['discount_value'] ?? 0));
$status             = in_array($_POST['status'] ?? '', ['active', 'draft', 'inactive'], true) ? $_POST['status'] : 'active';
$featured           = !empty($_POST['featured']) ? 1 : 0;

$shortDescription   = trim($_POST['short_description'] ?? '');
$description        = trim($_POST['description'] ?? '');
$hotelInformation   = trim($_POST['hotel_information'] ?? '');
$transportation     = trim($_POST['transportation'] ?? '');
$mealInformation    = trim($_POST['meal_information'] ?? '');
$includedServices   = trim($_POST['included_services'] ?? '');
$excludedServices   = trim($_POST['excluded_services'] ?? '');
$termsConditions    = trim($_POST['terms_conditions'] ?? '');

$itineraryTitles       = $_POST['itinerary_title'] ?? [];
$itineraryDescriptions = $_POST['itinerary_description'] ?? [];

// 3. Validation
$errors = [];
if (empty($name)) {
    $errors[] = 'Package name is required.';
}
if ($categoryId <= 0) {
    $errors[] = 'Please select a valid tour category.';
}
if ($destinationId <= 0) {
    $errors[] = 'Please select a valid destination.';
}
if ($price <= 0) {
    $errors[] = 'Base adult price must be greater than 0.';
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        set_flash('error', $err);
    }
    redirect('modules/tours/edit.php?id=' . $id);
}

try {
    $pdo = get_db_connection();

    // Fetch existing package
    $stmtExisting = $pdo->prepare("SELECT * FROM tour_packages WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmtExisting->execute(['id' => $id]);
    $existing = $stmtExisting->fetch();

    if (!$existing) {
        set_flash('error', 'Tour package does not exist.');
        redirect('modules/tours/index.php');
    }

    $featuredImageFilename = $existing['featured_image'];

    // 4. Handle Featured Cover Image Replacement
    $allowedImageTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
    ];
    if (defined('IMAGETYPE_WEBP')) {
        $allowedImageTypes[IMAGETYPE_WEBP] = 'webp';
    }

    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['featured_image'];
        $tmpPath = $file['tmp_name'];

        if (is_uploaded_file($tmpPath) && $file['size'] <= 5 * 1024 * 1024) {
            $imageInfo = @getimagesize($tmpPath);
            if ($imageInfo !== false && array_key_exists($imageInfo[2], $allowedImageTypes)) {
                if (!is_dir(TOUR_PATH)) {
                    @mkdir(TOUR_PATH, 0755, true);
                }
                $ext = $allowedImageTypes[$imageInfo[2]];
                $newFeatured = sprintf('tour_cover_%s.%s', bin2hex(random_bytes(10)), $ext);
                $destination = TOUR_PATH . DIRECTORY_SEPARATOR . $newFeatured;

                if (move_uploaded_file($tmpPath, $destination)) {
                    // Delete old featured image safely
                    if (!empty($existing['featured_image'])) {
                        $oldPath = TOUR_PATH . DIRECTORY_SEPARATOR . basename($existing['featured_image']);
                        if (file_exists($oldPath) && is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $featuredImageFilename = $newFeatured;
                }
            }
        }
    }

    // 5. Handle New Gallery Uploads
    $newGalleryFilenames = [];
    if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
        $count = count($_FILES['gallery_images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['gallery_images']['tmp_name'][$i];
                $fileSize = $_FILES['gallery_images']['size'][$i];

                if (is_uploaded_file($tmpPath) && $fileSize <= 5 * 1024 * 1024) {
                    $imageInfo = @getimagesize($tmpPath);
                    if ($imageInfo !== false && array_key_exists($imageInfo[2], $allowedImageTypes)) {
                        if (!is_dir(TOUR_PATH)) {
                            @mkdir(TOUR_PATH, 0755, true);
                        }
                        $ext = $allowedImageTypes[$imageInfo[2]];
                        $galleryName = sprintf('tour_gallery_%s.%s', bin2hex(random_bytes(10)), $ext);
                        $destination = TOUR_PATH . DIRECTORY_SEPARATOR . $galleryName;
                        if (move_uploaded_file($tmpPath, $destination)) {
                            $newGalleryFilenames[] = $galleryName;
                        }
                    }
                }
            }
        }
    }

    // 6. Update Slug if Name changed
    $slug = $existing['slug'];
    if ($name !== $existing['name']) {
        $baseSlug = slugify($name);
        $slug = $baseSlug;
        $slugCheck = $pdo->prepare("SELECT id FROM tour_packages WHERE slug = :slug AND id != :id AND deleted_at IS NULL LIMIT 1");
        $slugCheck->execute(['slug' => $slug, 'id' => $id]);
        if ($slugCheck->fetch()) {
            $slug = $baseSlug . '-' . $id;
        }
    }

    // 7. Transaction for Package Update & Itinerary Sync
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE tour_packages 
        SET 
            `category_id`        = :category_id,
            `destination_id`     = :destination_id,
            `name`               = :name,
            `slug`               = :slug,
            `short_description`  = :short_description,
            `description`        = :description,
            `duration_days`      = :duration_days,
            `duration_nights`     = :duration_nights,
            `price`              = :price,
            `child_price`        = :child_price,
            `discount_type`      = :discount_type,
            `discount_value`     = :discount_value,
            `available_seats`    = :available_seats,
            `departure_location` = :departure_location,
            `featured_image`     = :featured_image,
            `hotel_information`  = :hotel_information,
            `transportation`     = :transportation,
            `meal_information`   = :meal_information,
            `included_services`  = :included_services,
            `excluded_services`  = :excluded_services,
            `terms_conditions`   = :terms_conditions,
            `status`             = :status,
            `featured`           = :featured,
            `updated_at`         = NOW()
        WHERE `id` = :id AND `deleted_at` IS NULL
    ");

    $stmt->execute([
        'category_id'        => $categoryId,
        'destination_id'     => $destinationId,
        'name'               => $name,
        'slug'               => $slug,
        'short_description'  => !empty($shortDescription) ? $shortDescription : null,
        'description'        => !empty($description) ? $description : null,
        'duration_days'      => $durationDays,
        'duration_nights'    => $durationNights,
        'price'              => $price,
        'child_price'        => $childPrice,
        'discount_type'      => $discountType,
        'discount_value'     => $discountValue,
        'available_seats'    => $availableSeats,
        'departure_location' => !empty($departureLocation) ? $departureLocation : null,
        'featured_image'     => $featuredImageFilename,
        'hotel_information'  => !empty($hotelInformation) ? $hotelInformation : null,
        'transportation'     => !empty($transportation) ? $transportation : null,
        'meal_information'   => !empty($mealInformation) ? $mealInformation : null,
        'included_services'  => !empty($includedServices) ? $includedServices : null,
        'excluded_services'  => !empty($excludedServices) ? $excludedServices : null,
        'terms_conditions'   => !empty($termsConditions) ? $termsConditions : null,
        'status'             => $status,
        'featured'           => $featured,
        'id'                 => $id
    ]);

    // 8. Insert new gallery images if any
    if (!empty($newGalleryFilenames)) {
        $imgStmt = $pdo->prepare("
            INSERT INTO tour_package_images (`tour_package_id`, `image`, `sort_order`, `created_at`)
            VALUES (:pkg_id, :image, :sort_order, NOW())
        ");
        foreach ($newGalleryFilenames as $order => $gImage) {
            $imgStmt->execute([
                'pkg_id'     => $id,
                'image'      => $gImage,
                'sort_order' => $order + 10
            ]);
        }
    }

    // 9. Sync Itineraries (Delete existing and insert updated list)
    $delItin = $pdo->prepare("DELETE FROM tour_package_itineraries WHERE tour_package_id = :id");
    $delItin->execute(['id' => $id]);

    if (!empty($itineraryTitles) && is_array($itineraryTitles)) {
        $itinStmt = $pdo->prepare("
            INSERT INTO tour_package_itineraries (`tour_package_id`, `day_number`, `title`, `description`, `created_at`, `updated_at`)
            VALUES (:pkg_id, :day_number, :title, :description, NOW(), NOW())
        ");

        $dayIndex = 1;
        foreach ($itineraryTitles as $key => $titleText) {
            $t = trim($titleText);
            if (!empty($t)) {
                $d = trim($itineraryDescriptions[$key] ?? '');
                $itinStmt->execute([
                    'pkg_id'      => $id,
                    'day_number'  => $dayIndex,
                    'title'       => $t,
                    'description' => !empty($d) ? $d : null
                ]);
                $dayIndex++;
            }
        }
    }

    $pdo->commit();

    set_flash('success', "Tour package \"{$name}\" updated successfully.");
    redirect('modules/tours/view.php?id=' . $id);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Tour Update Database Error: ' . $e->getMessage());
    set_flash('error', 'Failed to update tour package due to a database error: ' . $e->getMessage());
    redirect('modules/tours/edit.php?id=' . $id);
}
