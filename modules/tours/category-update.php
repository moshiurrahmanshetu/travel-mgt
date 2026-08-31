<?php
/**
 * Update Tour Category Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('categories.edit');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/tours/categories.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/tours/categories.php');
}

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status      = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

if ($id <= 0 || empty($name)) {
    set_flash('error', 'Category ID and Name are required.');
    redirect('modules/tours/categories.php');
}

$baseSlug = slugify($name);
$slug = $baseSlug;

try {
    $pdo = get_db_connection();

    // Check slug uniqueness excluding current category
    $stmtCheck = $pdo->prepare("SELECT id FROM tour_categories WHERE slug = :slug AND id != :id AND deleted_at IS NULL LIMIT 1");
    $stmtCheck->execute(['slug' => $slug, 'id' => $id]);
    if ($stmtCheck->fetch()) {
        $slug = $baseSlug . '-' . $id;
    }

    $stmt = $pdo->prepare("
        UPDATE tour_categories 
        SET 
            `name`        = :name,
            `slug`        = :slug,
            `description` = :description,
            `status`      = :status,
            `updated_at`  = NOW()
        WHERE `id` = :id AND `deleted_at` IS NULL
    ");

    $stmt->execute([
        'name'        => $name,
        'slug'        => $slug,
        'description' => !empty($description) ? $description : null,
        'status'      => $status,
        'id'          => $id
    ]);

    set_flash('success', 'Tour category updated successfully.');
    redirect('modules/tours/categories.php');

} catch (PDOException $e) {
    error_log('Category Update Error: ' . $e->getMessage());
    set_flash('error', 'Failed to update category due to a database error.');
    redirect('modules/tours/categories.php');
}
