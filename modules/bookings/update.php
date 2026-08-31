<?php
/**
 * Update Booking Processor
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('bookings.edit');

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

// 2. Collect Inputs
$customerId     = (int)($_POST['customer_id'] ?? 0);
$tourPackageId  = (int)($_POST['tour_package_id'] ?? 0);
$travelDate     = trim($_POST['travel_date'] ?? '');
$adults         = max(1, (int)($_POST['adults'] ?? 1));
$children       = max(0, (int)($_POST['children'] ?? 0));
$infants        = max(0, (int)($_POST['infants'] ?? 0));
$discountType   = in_array($_POST['discount_type'] ?? '', ['none', 'percentage', 'fixed'], true) ? $_POST['discount_type'] : 'none';
$discountValue  = max(0.0, (float)($_POST['discount_value'] ?? 0));
$specialRequest = trim($_POST['special_request'] ?? '');
$notes          = trim($_POST['notes'] ?? '');

$errors = [];

if ($customerId <= 0) {
    $errors[] = 'Please select a valid customer.';
}

if ($tourPackageId <= 0) {
    $errors[] = 'Please select a valid tour package.';
}

if (empty($travelDate)) {
    $errors[] = 'Travel departure date is required.';
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        set_flash('error', $err);
    }
    redirect('modules/bookings/edit.php?id=' . $id);
}

try {
    $pdo = get_db_connection();

    // 3. Load Existing Booking
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        set_flash('error', 'Booking record not found.');
        redirect('modules/bookings/index.php');
    }

    if ($existing['booking_status'] === 'cancelled') {
        set_flash('warning', 'Cancelled bookings cannot be modified.');
        redirect('modules/bookings/view.php?id=' . $id);
    }

    // 4. Verify Customer & Tour Package
    $cusCheck = $pdo->prepare("SELECT id FROM customers WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $cusCheck->execute(['id' => $customerId]);
    if (!$cusCheck->fetch()) {
        set_flash('error', 'Selected customer does not exist.');
        redirect('modules/bookings/edit.php?id=' . $id);
    }

    $pkgCheck = $pdo->prepare("SELECT id, price, child_price FROM tour_packages WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $pkgCheck->execute(['id' => $tourPackageId]);
    $package = $pkgCheck->fetch();

    if (!$package) {
        set_flash('error', 'Selected tour package does not exist.');
        redirect('modules/bookings/edit.php?id=' . $id);
    }

    // 5. Determine Price Snapshot: If package changed, use new package price; otherwise preserve existing snapshot
    if ((int)$existing['tour_package_id'] === $tourPackageId) {
        $adultPriceSnapshot = (float)$existing['adult_price'];
        $childPriceSnapshot = (float)$existing['child_price'];
    } else {
        $adultPriceSnapshot = (float)$package['price'];
        $childPriceSnapshot = (float)($package['child_price'] ?? 0);
    }

    // 6. Calculate Authoritative Pricing
    $pricing = calculate_booking_pricing(
        $adults,
        $adultPriceSnapshot,
        $children,
        $childPriceSnapshot,
        $discountType,
        $discountValue,
        (float)$existing['paid_amount']
    );

    // 7. Capacity Validation if Confirmed
    $totalPax = $adults + $children + $infants;
    if ($existing['booking_status'] === 'confirmed') {
        $capacity = check_tour_capacity($tourPackageId, $totalPax, $id);
        if (!$capacity['has_capacity']) {
            set_flash('error', "Cannot update booking. Tour package only has {$capacity['remaining']} seat(s) remaining.");
            redirect('modules/bookings/edit.php?id=' . $id);
        }
    }

    // 8. Update Record
    $updateStmt = $pdo->prepare("
        UPDATE bookings 
        SET 
            `customer_id`     = :customer_id,
            `tour_package_id` = :tour_package_id,
            `travel_date`     = :travel_date,
            `adults`          = :adults,
            `children`        = :children,
            `infants`         = :infants,
            `adult_price`     = :adult_price,
            `child_price`     = :child_price,
            `subtotal`        = :subtotal,
            `discount_type`   = :discount_type,
            `discount_value`  = :discount_value,
            `discount_amount` = :discount_amount,
            `total_amount`    = :total_amount,
            `due_amount`      = :due_amount,
            `special_request` = :special_request,
            `notes`           = :notes,
            `updated_at`      = NOW()
        WHERE `id` = :id AND `deleted_at` IS NULL
    ");

    $updateStmt->execute([
        'customer_id'     => $customerId,
        'tour_package_id' => $tourPackageId,
        'travel_date'     => $travelDate,
        'adults'          => $pricing['adults'],
        'children'        => $pricing['children'],
        'infants'         => $infants,
        'adult_price'     => $pricing['adult_price'],
        'child_price'     => $pricing['child_price'],
        'subtotal'        => $pricing['subtotal'],
        'discount_type'   => $pricing['discount_type'],
        'discount_value'  => $pricing['discount_value'],
        'discount_amount' => $pricing['discount_amount'],
        'total_amount'    => $pricing['total_amount'],
        'due_amount'      => $pricing['due_amount'],
        'special_request' => !empty($specialRequest) ? $specialRequest : null,
        'notes'           => !empty($notes) ? $notes : null,
        'id'              => $id
    ]);

    set_flash('success', "Booking {$existing['booking_number']} updated successfully.");
    redirect('modules/bookings/view.php?id=' . $id);

} catch (PDOException $e) {
    error_log('Booking Update Error: ' . $e->getMessage());
    set_flash('error', 'Failed to update booking due to a database error.');
    redirect('modules/bookings/edit.php?id=' . $id);
}
