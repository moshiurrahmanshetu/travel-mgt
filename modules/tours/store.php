<?php
/**
 * Store Tour Package Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('tours.create');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/tours/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/tours/create.php');
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
    flash_old_input($_POST);
    redirect('modules/tours/create.php');
}

// 4. Handle Featured Cover Image Upload
$featuredImageFilename = null;
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
            $featuredImageFilename = sprintf('tour_cover_%s.%s', bin2hex(random_bytes(10)), $ext);
            $destination = TOUR_PATH . DIRECTORY_SEPARATOR . $featuredImageFilename;
            if (!move_uploaded_file($tmpPath, $destination)) {
                $featuredImageFilename = null;
            }
        }
    }
}

// 5. Handle Additional Gallery Images Upload
$galleryFilenames = [];
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
                        $galleryFilenames[] = $galleryName;
                    }
                }
            }
        }
    }
}

try {
    $pdo = get_db_connection();

    // 6. Generate Unique Package Code (e.g. TP-00001)
    $maxIdStmt = $pdo->query("SELECT MAX(id) FROM tour_packages");
    $nextId = (int)$maxIdStmt->fetchColumn() + 1;
    $packageCode = sprintf('TP-%05d', $nextId);

    // Verify package code uniqueness
    $codeCheck = $pdo->prepare("SELECT id FROM tour_packages WHERE package_code = :code LIMIT 1");
    $codeCheck->execute(['code' => $packageCode]);
    if ($codeCheck->fetch()) {
        $packageCode = sprintf('TP-%05d-%s', $nextId, bin2hex(random_bytes(2)));
    }

    // 7. Generate Unique Slug
    $baseSlug = slugify($name);
    $slug = $baseSlug;
    $slugCheck = $pdo->prepare("SELECT id FROM tour_packages WHERE slug = :slug AND deleted_at IS NULL LIMIT 1");
    $slugCheck->execute(['slug' => $slug]);
    if ($slugCheck->fetch()) {
        $slug = $baseSlug . '-' . time();
    }

    // 8. Execute Database Insertion in a Transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO tour_packages (
            `category_id`, `destination_id`, `package_code`, `name`, `slug`,
            `short_description`, `description`, `duration_days`, `duration_nights`,
            `price`, `child_price`, `discount_type`, `discount_value`, `available_seats`,
            `departure_location`, `featured_image`, `hotel_information`, `transportation`,
            `meal_information`, `included_services`, `excluded_services`, `terms_conditions`,
            `status`, `featured`, `created_at`, `updated_at`
        ) VALUES (
            :category_id, :destination_id, :package_code, :name, :slug,
            :short_description, :description, :duration_days, :duration_nights,
            :price, :child_price, :discount_type, :discount_value, :available_seats,
            :departure_location, :featured_image, :hotel_information, :transportation,
            :meal_information, :included_services, :excluded_services, :terms_conditions,
            :status, :featured, NOW(), NOW()
        )
    ");

    $stmt->execute([
        'category_id'        => $categoryId,
        'destination_id'     => $destinationId,
        'package_code'       => $packageCode,
        'name'               => $name,
        'slug'               => $slug,
        'short_description'  => !empty($shortDescription) ? $shortDescription : null,
        'description'        => !empty($description) ? $description : null,
        'duration_days'      => $durationDays,
        'duration_nights'     => $durationNights,
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
        'featured'           => $featured
    ]);

    $packageId = (int)$pdo->lastInsertId();

    // 9. Insert Gallery Images
    if (!empty($galleryFilenames)) {
        $imgStmt = $pdo->prepare("
            INSERT INTO tour_package_images (`tour_package_id`, `image`, `sort_order`, `created_at`)
            VALUES (:pkg_id, :image, :sort_order, NOW())
        ");
        foreach ($galleryFilenames as $order => $gImage) {
            $imgStmt->execute([
                'pkg_id'     => $packageId,
                'image'      => $gImage,
                'sort_order' => $order + 1
            ]);
        }
    }

    // 10. Insert Itinerary Days
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
                    'pkg_id'      => $packageId,
                    'day_number'  => $dayIndex,
                    'title'       => $t,
                    'description' => !empty($d) ? $d : null
                ]);
                $dayIndex++;
            }
        }
    }

    $pdo->commit();
    clear_old_input();

    set_flash('success', "Tour package \"{$name}\" ({$packageCode}) created successfully.");
    redirect('modules/tours/index.php');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Tour Store Database Error: ' . $e->getMessage());
    set_flash('error', 'Failed to create tour package due to a database error.');
    flash_old_input($_POST);
    redirect('modules/tours/create.php');
}
