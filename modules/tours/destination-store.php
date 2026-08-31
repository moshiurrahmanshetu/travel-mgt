<?php
/**
 * Create Tour Destination Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('destinations.create');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/tours/destinations.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/tours/destinations.php');
}

// 2. Validate Inputs
$name        = trim($_POST['name'] ?? '');
$country     = trim($_POST['country'] ?? 'Bangladesh');
$description = trim($_POST['description'] ?? '');
$status      = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

if (empty($name)) {
    set_flash('error', 'Destination name is required.');
    redirect('modules/tours/destinations.php');
}

$baseSlug = slugify($name);
$slug = $baseSlug;
$imageFilename = null;

// 3. Handle Cover Image Upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $tmpPath = $file['tmp_name'];

    if (is_uploaded_file($tmpPath) && $file['size'] <= 2 * 1024 * 1024) {
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo !== false) {
            $detectedType = $imageInfo[2];
            $allowedTypes = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG  => 'png',
            ];
            if (defined('IMAGETYPE_WEBP')) {
                $allowedTypes[IMAGETYPE_WEBP] = 'webp';
            }

            if (array_key_exists($detectedType, $allowedTypes)) {
                if (!is_dir(DESTINATION_PATH)) {
                    @mkdir(DESTINATION_PATH, 0755, true);
                }

                $ext = $allowedTypes[$detectedType];
                $imageFilename = sprintf('dest_%s.%s', bin2hex(random_bytes(10)), $ext);
                $destinationPath = DESTINATION_PATH . DIRECTORY_SEPARATOR . $imageFilename;
                if (!move_uploaded_file($tmpPath, $destinationPath)) {
                    $imageFilename = null;
                }
            }
        }
    }
}

try {
    $pdo = get_db_connection();

    // Check slug uniqueness
    $stmtCheck = $pdo->prepare("SELECT id FROM tour_destinations WHERE slug = :slug AND deleted_at IS NULL LIMIT 1");
    $stmtCheck->execute(['slug' => $slug]);
    if ($stmtCheck->fetch()) {
        $slug = $baseSlug . '-' . time();
    }

    $stmt = $pdo->prepare("
        INSERT INTO tour_destinations (`name`, `slug`, `country`, `description`, `image`, `status`, `created_at`, `updated_at`)
        VALUES (:name, :slug, :country, :description, :image, :status, NOW(), NOW())
    ");

    $stmt->execute([
        'name'        => $name,
        'slug'        => $slug,
        'country'     => !empty($country) ? $country : 'Bangladesh',
        'description' => !empty($description) ? $description : null,
        'image'       => $imageFilename,
        'status'      => $status
    ]);

    set_flash('success', 'Destination "' . $name . '" created successfully.');
    redirect('modules/tours/destinations.php');

} catch (PDOException $e) {
    error_log('Destination Store Error: ' . $e->getMessage());
    set_flash('error', 'Failed to create destination due to a database error.');
    redirect('modules/tours/destinations.php');
}
