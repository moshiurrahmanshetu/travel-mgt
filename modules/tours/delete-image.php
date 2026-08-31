<?php
/**
 * Delete Tour Gallery Image Handler
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

$imageId   = (int)($_POST['image_id'] ?? 0);
$packageId = (int)($_POST['package_id'] ?? 0);

if ($imageId <= 0 || $packageId <= 0) {
    set_flash('error', 'Invalid image or package identifier.');
    redirect('modules/tours/index.php');
}

try {
    $pdo = get_db_connection();

    // 2. Fetch image filename
    $stmt = $pdo->prepare("SELECT image FROM tour_package_images WHERE id = :id AND tour_package_id = :pkg_id LIMIT 1");
    $stmt->execute([
        'id'     => $imageId,
        'pkg_id' => $packageId
    ]);
    $filename = $stmt->fetchColumn();

    if ($filename) {
        // Delete database record
        $delStmt = $pdo->prepare("DELETE FROM tour_package_images WHERE id = :id AND tour_package_id = :pkg_id");
        $delStmt->execute([
            'id'     => $imageId,
            'pkg_id' => $packageId
        ]);

        // Unlink physical file safely
        $filePath = TOUR_PATH . DIRECTORY_SEPARATOR . basename($filename);
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }

        set_flash('success', 'Gallery image removed successfully.');
    } else {
        set_flash('error', 'Gallery image not found.');
    }

    redirect('modules/tours/edit.php?id=' . $packageId);

} catch (PDOException $e) {
    error_log('Gallery Image Delete Error: ' . $e->getMessage());
    set_flash('error', 'Failed to remove image due to a database error.');
    redirect('modules/tours/edit.php?id=' . $packageId);
}
