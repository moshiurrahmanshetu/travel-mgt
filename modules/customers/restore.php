<?php
/**
 * Restore Soft-Deleted Customer Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('customers.restore');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/customers/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/customers/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid customer identifier.');
    redirect('modules/customers/index.php');
}

try {
    $pdo = get_db_connection();

    // 2. Fetch archived customer details
    $stmt = $pdo->prepare("SELECT customer_code, name FROM customers WHERE id = :id AND deleted_at IS NOT NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash('error', 'Archived customer not found or is already active.');
        redirect('modules/customers/index.php');
    }

    // 3. Restore customer
    $restoreStmt = $pdo->prepare("UPDATE customers SET deleted_at = NULL, status = 'active' WHERE id = :id");
    $restoreStmt->execute(['id' => $id]);

    set_flash('success', "Customer {$customer['customer_code']} (\"{$customer['name']}\") restored successfully.");
    redirect('modules/customers/index.php');

} catch (PDOException $e) {
    error_log('Customer Restore Error: ' . $e->getMessage());
    set_flash('error', 'Failed to restore customer due to a database error.');
    redirect('modules/customers/index.php');
}
