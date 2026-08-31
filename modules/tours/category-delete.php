<?php
/**
 * Soft Delete Tour Category Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('categories.delete');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/tours/categories.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/tours/categories.php');
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'Invalid category identifier.');
    redirect('modules/tours/categories.php');
}

try {
    $pdo = get_db_connection();

    // 2. Safe FK dependency check: Prevent deleting if referenced by active packages
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM tour_packages 
        WHERE category_id = :id AND deleted_at IS NULL
    ");
    $checkStmt->execute(['id' => $id]);
    $assignedCount = (int)$checkStmt->fetchColumn();

    if ($assignedCount > 0) {
        set_flash('error', "Category cannot be deleted because it is currently assigned to {$assignedCount} active tour package(s).");
        redirect('modules/tours/categories.php');
    }

    // 3. Soft delete category
    $deleteStmt = $pdo->prepare("
        UPDATE tour_categories 
        SET deleted_at = NOW(), status = 'inactive' 
        WHERE id = :id AND deleted_at IS NULL
    ");
    $deleteStmt->execute(['id' => $id]);

    set_flash('success', 'Tour category deleted successfully.');
    redirect('modules/tours/categories.php');

} catch (PDOException $e) {
    error_log('Category Delete Error: ' . $e->getMessage());
    set_flash('error', 'Failed to delete category due to a database error.');
    redirect('modules/tours/categories.php');
}
