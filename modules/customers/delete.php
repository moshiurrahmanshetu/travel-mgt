<?php
/**
 * Soft Delete Customer Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('customers.delete');

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

    // 2. Fetch customer name & code for flash message
    $stmt = $pdo->prepare("SELECT customer_code, name FROM customers WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash('error', 'Customer not found or was already deleted.');
        redirect('modules/customers/index.php');
    }

    // 3. Soft delete customer record (Historical booking safety)
    $delStmt = $pdo->prepare("UPDATE customers SET deleted_at = NOW(), status = 'inactive' WHERE id = :id");
    $delStmt->execute(['id' => $id]);

    set_flash('success', "Customer {$customer['customer_code']} (\"{$customer['name']}\") deleted successfully.");
    redirect('modules/customers/index.php');

} catch (PDOException $e) {
    error_log('Customer Delete Error: ' . $e->getMessage());
    set_flash('error', 'Failed to delete customer due to a database error.');
    redirect('modules/customers/index.php');
}
