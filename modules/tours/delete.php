<?php
/**
 * Soft Delete Tour Package Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('tours.delete');

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

try {
    $pdo = get_db_connection();

    // 2. Fetch package name & code for friendly message
    $stmt = $pdo->prepare("SELECT package_code, name FROM tour_packages WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $package = $stmt->fetch();

    if (!$package) {
        set_flash('error', 'Tour package does not exist or was already deleted.');
        redirect('modules/tours/index.php');
    }

    // 3. Soft delete package
    $delStmt = $pdo->prepare("UPDATE tour_packages SET deleted_at = NOW(), status = 'inactive' WHERE id = :id");
    $delStmt->execute(['id' => $id]);

    set_flash('success', "Tour package {$package['package_code']} (\"{$package['name']}\") deleted successfully.");
    redirect('modules/tours/index.php');

} catch (PDOException $e) {
    error_log('Tour Delete Error: ' . $e->getMessage());
    set_flash('error', 'Failed to delete tour package due to a database error.');
    redirect('modules/tours/index.php');
}
