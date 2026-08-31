<?php
/**
 * Update Tour Destination Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('destinations.edit');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/tours/destinations.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/tours/destinations.php');
}

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$country     = trim($_POST['country'] ?? 'Bangladesh');
$description = trim($_POST['description'] ?? '');
$status      = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

if ($id <= 0 || empty($name)) {
    set_flash('error', 'Destination ID and Name are required.');
    redirect('modules/tours/destinations.php');
}

$baseSlug = slugify($name);
$slug = $baseSlug;

try {
    $pdo = get_db_connection();

    // Fetch existing destination for image management
    $stmtExisting = $pdo->prepare("SELECT image FROM tour_destinations WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmtExisting->execute(['id' => $id]);
    $existingImage = $stmtExisting->fetchColumn();

    $imageFilename = $existingImage;

    // Handle new Cover Image Upload
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
                    $newImage = sprintf('dest_%s.%s', bin2hex(random_bytes(10)), $ext);
                    $destinationPath = DESTINATION_PATH . DIRECTORY_SEPARATOR . $newImage;
                    if (move_uploaded_file($tmpPath, $destinationPath)) {
                        // Unlink old image safely
                        if (!empty($existingImage)) {
                            $oldFile = DESTINATION_PATH . DIRECTORY_SEPARATOR . basename($existingImage);
                            if (file_exists($oldFile) && is_file($oldFile)) {
                                @unlink($oldFile);
                            }
                        }
                        $imageFilename = $newImage;
                    }
                }
            }
        }
    }

    // Check slug uniqueness excluding current destination
    $stmtCheck = $pdo->prepare("SELECT id FROM tour_destinations WHERE slug = :slug AND id != :id AND deleted_at IS NULL LIMIT 1");
    $stmtCheck->execute(['slug' => $slug, 'id' => $id]);
    if ($stmtCheck->fetch()) {
        $slug = $baseSlug . '-' . $id;
    }

    $stmt = $pdo->prepare("
        UPDATE tour_destinations 
        SET 
            `name`        = :name,
            `slug`        = :slug,
            `country`     = :country,
            `description` = :description,
            `image`       = :image,
            `status`      = :status,
            `updated_at`  = NOW()
        WHERE `id` = :id AND `deleted_at` IS NULL
    ");

    $stmt->execute([
        'name'        => $name,
        'slug'        => $slug,
        'country'     => !empty($country) ? $country : 'Bangladesh',
        'description' => !empty($description) ? $description : null,
        'image'       => $imageFilename,
        'status'      => $status,
        'id'          => $id
    ]);

    set_flash('success', 'Destination updated successfully.');
    redirect('modules/tours/destinations.php');

} catch (PDOException $e) {
    error_log('Destination Update Error: ' . $e->getMessage());
    set_flash('error', 'Failed to update destination due to a database error.');
    redirect('modules/tours/destinations.php');
}
