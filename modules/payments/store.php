<?php
/**
 * Store Payment Processor
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('payments.create');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/payments/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/payments/create.php');
}

// 2. Collect Inputs
$bookingId     = (int)($_POST['booking_id'] ?? 0);
$paymentDate   = trim($_POST['payment_date'] ?? '');
$amount        = max(0.0, round((float)($_POST['amount'] ?? 0), 2));
$paymentMethod = in_array($_POST['payment_method'] ?? '', ['cash', 'bank_transfer', 'card', 'mobile_banking', 'other'], true) ? $_POST['payment_method'] : 'cash';
$transactionId = trim($_POST['transaction_id'] ?? '');
$paymentStatus = in_array($_POST['payment_status'] ?? '', ['completed', 'pending', 'failed'], true) ? $_POST['payment_status'] : 'completed';
$notes         = trim($_POST['notes'] ?? '');

$errors = [];

// 3. Validate Inputs
if ($bookingId <= 0) {
    $errors[] = 'Please select a valid booking reservation.';
}

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

if ($amount <= 0.0) {
    $errors[] = 'Payment amount must be greater than zero.';
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        set_flash('error', $err);
    }
    redirect('modules/payments/create.php?booking_id=' . $bookingId);
}

try {
    $pdo = get_db_connection();

    // 4. Begin Database Transaction with Row-Level Lock
    $pdo->beginTransaction();

    // Lock booking record for update to prevent concurrent overpayments
    $stmtBk = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND deleted_at IS NULL FOR UPDATE");
    $stmtBk->execute(['id' => $bookingId]);
    $booking = $stmtBk->fetch();

    if (!$booking) {
        $pdo->rollBack();
        set_flash('error', 'The selected booking was not found or has been removed.');
        redirect('modules/payments/create.php');
    }

    if ($booking['booking_status'] === 'cancelled') {
        $pdo->rollBack();
        set_flash('error', 'Payments cannot be added to a cancelled booking reservation.');
        redirect('modules/payments/create.php?booking_id=' . $bookingId);
    }

    // 5. Authoritatively Calculate Current Due Balance
    $stmtSum = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total_paid
        FROM payments
        WHERE booking_id = :b_id 
          AND payment_status = 'completed' 
          AND deleted_at IS NULL
    ");
    $stmtSum->execute(['b_id' => $bookingId]);
    $existingPaid = round((float)$stmtSum->fetchColumn(), 2);
    $bookingTotal = (float)$booking['total_amount'];
    $currentDue   = max(0.0, round($bookingTotal - $existingPaid, 2));

    // 6. Overpayment Protection: Reject if completed payment exceeds current remaining balance
    if ($paymentStatus === 'completed') {
        if ($amount > ($currentDue + 0.01)) {
            $pdo->rollBack();
            set_flash('error', "Payment amount (" . format_currency($amount) . ") exceeds the current remaining balance (" . format_currency($currentDue) . ").");
            redirect('modules/payments/create.php?booking_id=' . $bookingId);
        }
    }

    // 7. Generate Unique Payment Receipt Number
    $paymentNumber = generate_payment_number();
    $currentUserId = $_SESSION['user_id'] ?? null;

    // 8. Insert Payment Transaction
    $insertStmt = $pdo->prepare("
        INSERT INTO payments (
            `payment_number`,
            `booking_id`,
            `payment_date`,
            `amount`,
            `payment_method`,
            `transaction_id`,
            `payment_status`,
            `notes`,
            `created_by`,
            `created_at`,
            `updated_at`
        ) VALUES (
            :payment_number,
            :booking_id,
            :payment_date,
            :amount,
            :payment_method,
            :transaction_id,
            :payment_status,
            :notes,
            :created_by,
            NOW(),
            NOW()
        )
    ");

    $insertStmt->execute([
        'payment_number' => $paymentNumber,
        'booking_id'     => $bookingId,
        'payment_date'   => $paymentDate,
        'amount'         => $amount,
        'payment_method' => $paymentMethod,
        'transaction_id' => !empty($transactionId) ? $transactionId : null,
        'payment_status' => $paymentStatus,
        'notes'          => !empty($notes) ? $notes : null,
        'created_by'     => $currentUserId
    ]);

    $newPaymentId = (int)$pdo->lastInsertId();

    // 9. Synchronize Booking Payment Summary inside Transaction
    recalculate_booking_payment_summary($bookingId);

    $pdo->commit();

    set_flash('success', "Payment receipt {$paymentNumber} for " . format_currency($amount) . " recorded successfully.");
    redirect('modules/bookings/view.php?id=' . $bookingId);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Payment Store Error: ' . $e->getMessage());
    set_flash('error', 'Failed to record payment transaction due to a database error.');
    redirect('modules/payments/create.php?booking_id=' . $bookingId);
}
