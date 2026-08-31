<?php
/**
 * Update Payment Processor
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('payments.edit');

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

// 2. Collect Inputs
$paymentDate   = trim($_POST['payment_date'] ?? '');
$paymentMethod = in_array($_POST['payment_method'] ?? '', ['cash', 'bank_transfer', 'card', 'mobile_banking', 'other'], true) ? $_POST['payment_method'] : 'cash';
$transactionId = trim($_POST['transaction_id'] ?? '');
$paymentStatus = in_array($_POST['payment_status'] ?? '', ['completed', 'pending', 'failed', 'refunded'], true) ? $_POST['payment_status'] : 'completed';
$notes         = trim($_POST['notes'] ?? '');

$errors = [];

if (empty($paymentDate)) {
    $errors[] = 'Payment date is required.';
} else {
    $payTimestamp   = strtotime($paymentDate);
    $todayTimestamp = strtotime(date('Y-m-d'));
    if ($payTimestamp === false) {
        $errors[] = 'Invalid payment date format.';
    } elseif ($payTimestamp > $todayTimestamp) {
        $errors[] = 'Payment date cannot be in the future.';
    }
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        set_flash('error', $err);
    }
    redirect('modules/payments/edit.php?id=' . $id);
}

try {
    $pdo = get_db_connection();

    // 3. Load Existing Payment
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        set_flash('error', 'Payment record not found.');
        redirect('modules/payments/index.php');
    }

    $bookingId = (int)$existing['booking_id'];

    // 4. Update Payment Record (amount is immutable)
    $updateStmt = $pdo->prepare("
        UPDATE payments 
        SET 
            `payment_date`   = :payment_date,
            `payment_method` = :payment_method,
            `transaction_id` = :transaction_id,
            `payment_status` = :payment_status,
            `notes`          = :notes,
            `updated_at`     = NOW()
        WHERE `id` = :id AND `deleted_at` IS NULL
    ");

    $updateStmt->execute([
        'payment_date'   => $paymentDate,
        'payment_method' => $paymentMethod,
        'transaction_id' => !empty($transactionId) ? $transactionId : null,
        'payment_status' => $paymentStatus,
        'notes'          => !empty($notes) ? $notes : null,
        'id'             => $id
    ]);

    // 5. Re-synchronize Booking Payment Summary
    recalculate_booking_payment_summary($bookingId);

    set_flash('success', "Payment receipt {$existing['payment_number']} updated successfully.");
    redirect('modules/payments/view.php?id=' . $id);

} catch (PDOException $e) {
    error_log('Payment Update Error: ' . $e->getMessage());
    set_flash('error', 'Failed to update payment due to a database error.');
    redirect('modules/payments/edit.php?id=' . $id);
}
