<?php
/**
 * Soft Delete Payment Processor
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('payments.delete');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/payments/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/payments/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid payment identifier.');
    redirect('modules/payments/index.php');
}

try {
    $pdo = get_db_connection();

    // 2. Fetch Payment Record
    $stmt = $pdo->prepare("SELECT payment_number, booking_id, amount FROM payments WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        set_flash('error', 'Payment transaction not found or was already deleted.');
        redirect('modules/payments/index.php');
    }

    $bookingId = (int)$payment['booking_id'];

    // 3. Soft Delete Payment Record
    $delStmt = $pdo->prepare("UPDATE payments SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id");
    $delStmt->execute(['id' => $id]);

    // 4. Re-synchronize Booking Payment Summary
    recalculate_booking_payment_summary($bookingId);

    set_flash('success', "Payment receipt {$payment['payment_number']} (" . format_currency($payment['amount']) . ") deleted successfully.");
    redirect('modules/payments/index.php');

} catch (PDOException $e) {
    error_log('Payment Delete Error: ' . $e->getMessage());
    set_flash('error', 'Failed to delete payment transaction due to a database error.');
    redirect('modules/payments/index.php');
}
