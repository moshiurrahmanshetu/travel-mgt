<?php
/**
 * Create Tour Category Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('categories.create');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/tours/categories.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/tours/categories.php');
}

// 2. Validate Inputs
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status      = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

if (empty($name)) {
    set_flash('error', 'Category name is required.');
    redirect('modules/tours/categories.php');
}

$baseSlug = slugify($name);
$slug = $baseSlug;

try {
    $pdo = get_db_connection();

    // Ensure unique slug
    $stmtCheck = $pdo->prepare("SELECT id FROM tour_categories WHERE slug = :slug AND deleted_at IS NULL LIMIT 1");
    $stmtCheck->execute(['slug' => $slug]);
    if ($stmtCheck->fetch()) {
        $slug = $baseSlug . '-' . time();
    }

    $stmt = $pdo->prepare("
        INSERT INTO tour_categories (`name`, `slug`, `description`, `status`, `created_at`, `updated_at`)
        VALUES (:name, :slug, :description, :status, NOW(), NOW())
    ");

    $stmt->execute([
        'name'        => $name,
        'slug'        => $slug,
        'description' => !empty($description) ? $description : null,
        'status'      => $status
    ]);

    set_flash('success', 'Tour category "' . $name . '" created successfully.');
    redirect('modules/tours/categories.php');

} catch (PDOException $e) {
    error_log('Category Store Error: ' . $e->getMessage());
    set_flash('error', 'Failed to create category due to a database error.');
    redirect('modules/tours/categories.php');
}
