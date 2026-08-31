<?php
/**
 * Cancel Booking Processor
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('bookings.cancel');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/bookings/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/bookings/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid booking identifier.');
    redirect('modules/bookings/index.php');
}

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("SELECT booking_number, booking_status FROM bookings WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        set_flash('error', 'Booking record not found.');
        redirect('modules/bookings/index.php');
    }

    if ($booking['booking_status'] === 'cancelled') {
        set_flash('info', "Booking {$booking['booking_number']} is already cancelled.");
        redirect('modules/bookings/index.php');
    }

    // Update to Cancelled (Releasing capacity)
    $cancelStmt = $pdo->prepare("
        UPDATE bookings 
        SET 
            booking_status = 'cancelled', 
            cancelled_at   = NOW(), 
            updated_at     = NOW() 
        WHERE id = :id
    ");
    $cancelStmt->execute(['id' => $id]);

    set_flash('success', "Booking {$booking['booking_number']} has been cancelled successfully.");
    redirect('modules/bookings/index.php');

} catch (PDOException $e) {
    error_log('Booking Cancel Error: ' . $e->getMessage());
    set_flash('error', 'Failed to cancel booking due to a database error.');
    redirect('modules/bookings/index.php');
}
