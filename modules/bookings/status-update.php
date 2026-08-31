<?php
/**
 * Status Transition Processor
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

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
$newStatus = trim($_POST['new_status'] ?? '');

if ($id <= 0 || !in_array($newStatus, ['confirmed', 'completed'], true)) {
    set_flash('error', 'Invalid status transition request.');
    redirect('modules/bookings/index.php');
}

// 2. Enforce Permissions
if ($newStatus === 'confirmed') {
    require_permission('bookings.confirm');
} elseif ($newStatus === 'completed') {
    require_permission('bookings.complete');
}

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        set_flash('error', 'Booking record not found or was deleted.');
        redirect('modules/bookings/index.php');
    }

    $currentStatus = $booking['booking_status'];

    // 3. Verify Allowed Status Transition
    if (!can_transition_booking_status($currentStatus, $newStatus)) {
        set_flash('error', "Invalid status transition from {$currentStatus} to {$newStatus}.");
        redirect('modules/bookings/view.php?id=' . $id);
    }

    // 4. Capacity Re-Check for Confirmation
    if ($newStatus === 'confirmed') {
        $totalPax = (int)$booking['adults'] + (int)$booking['children'] + (int)$booking['infants'];
        $capacity = check_tour_capacity((int)$booking['tour_package_id'], $totalPax, $id);

        if (!$capacity['has_capacity']) {
            set_flash('error', "Cannot confirm booking. Tour package only has {$capacity['remaining']} available seat(s) remaining.");
            redirect('modules/bookings/view.php?id=' . $id);
        }
    }

    // 5. Update Status
    $updateStmt = $pdo->prepare("UPDATE bookings SET booking_status = :status, updated_at = NOW() WHERE id = :id");
    $updateStmt->execute(['status' => $newStatus, 'id' => $id]);

    $statusLabel = ucfirst($newStatus);
    set_flash('success', "Booking {$booking['booking_number']} marked as {$statusLabel} successfully.");
    redirect('modules/bookings/view.php?id=' . $id);

} catch (PDOException $e) {
    error_log('Booking Status Update Error: ' . $e->getMessage());
    set_flash('error', 'Failed to update booking status due to a database error.');
    redirect('modules/bookings/view.php?id=' . $id);
}
