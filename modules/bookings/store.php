<?php
/**
 * Store Booking Processor
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('bookings.create');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/bookings/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/bookings/create.php');
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

// 3. Validate Inputs
if ($customerId <= 0) {
    $errors[] = 'Please select a valid customer.';
}

if ($tourPackageId <= 0) {
    $errors[] = 'Please select a valid tour package.';
}

if (empty($travelDate)) {
    $errors[] = 'Travel departure date is required.';
} else {
    $travelTimestamp = strtotime($travelDate);
    $todayTimestamp  = strtotime(date('Y-m-d'));
    if ($travelTimestamp === false) {
        $errors[] = 'Invalid travel date format.';
    } elseif ($travelTimestamp < $todayTimestamp) {
        $errors[] = 'Travel departure date cannot be in the past.';
    }
}

if ($adults < 1) {
    $errors[] = 'At least 1 adult passenger is required for a booking.';
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        set_flash('error', $err);
    }
    redirect('modules/bookings/create.php?customer_id=' . $customerId . '&package_id=' . $tourPackageId);
}

try {
    $pdo = get_db_connection();

    // 4. Verify Active Customer
    $cusStmt = $pdo->prepare("SELECT id, name FROM customers WHERE id = :id AND status = 'active' AND deleted_at IS NULL LIMIT 1");
    $cusStmt->execute(['id' => $customerId]);
    $customer = $cusStmt->fetch();

    if (!$customer) {
        set_flash('error', 'Selected customer was not found or is currently inactive.');
        redirect('modules/bookings/create.php');
    }

    // 5. Verify Active Tour Package & Fetch Authoritative Pricing
    $pkgStmt = $pdo->prepare("SELECT id, name, price, child_price, available_seats FROM tour_packages WHERE id = :id AND status = 'active' AND deleted_at IS NULL LIMIT 1");
    $pkgStmt->execute(['id' => $tourPackageId]);
    $package = $pkgStmt->fetch();

    if (!$package) {
        set_flash('error', 'Selected tour package is unavailable or inactive.');
        redirect('modules/bookings/create.php');
    }

    // 6. Check Capacity Warning (Initial booking status is Pending)
    $totalTravellers = $adults + $children + $infants;
    $capacityCheck = check_tour_capacity($tourPackageId, $totalTravellers);

    // 7. Calculate Authoritative Price Snapshot
    $adultPriceSnapshot = (float)$package['price'];
    $childPriceSnapshot = (float)($package['child_price'] ?? 0);

    $pricing = calculate_booking_pricing(
        $adults,
        $adultPriceSnapshot,
        $children,
        $childPriceSnapshot,
        $discountType,
        $discountValue,
        0.00 // initial paid amount
    );

    // 8. Generate Unique Booking Number
    $bookingNumber = generate_booking_number();
    $currentUserId = $_SESSION['user_id'] ?? null;

    // 9. Database Transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            `booking_number`,
            `customer_id`,
            `tour_package_id`,
            `travel_date`,
            `adults`,
            `children`,
            `infants`,
            `adult_price`,
            `child_price`,
            `subtotal`,
            `discount_type`,
            `discount_value`,
            `discount_amount`,
            `total_amount`,
            `paid_amount`,
            `due_amount`,
            `booking_status`,
            `payment_status`,
            `special_request`,
            `notes`,
            `created_by`,
            `created_at`,
            `updated_at`
        ) VALUES (
            :booking_number,
            :customer_id,
            :tour_package_id,
            :travel_date,
            :adults,
            :children,
            :infants,
            :adult_price,
            :child_price,
            :subtotal,
            :discount_type,
            :discount_value,
            :discount_amount,
            :total_amount,
            :paid_amount,
            :due_amount,
            'pending',
            'unpaid',
            :special_request,
            :notes,
            :created_by,
            NOW(),
            NOW()
        )
    ");

    $stmt->execute([
        'booking_number'  => $bookingNumber,
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
        'paid_amount'     => 0.00,
        'due_amount'      => $pricing['due_amount'],
        'special_request' => !empty($specialRequest) ? $specialRequest : null,
        'notes'           => !empty($notes) ? $notes : null,
        'created_by'      => $currentUserId
    ]);

    $newBookingId = (int)$pdo->lastInsertId();

    $pdo->commit();

    set_flash('success', "Booking reservation {$bookingNumber} created successfully.");
    redirect('modules/bookings/view.php?id=' . $newBookingId);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Booking Store Error: ' . $e->getMessage());
    set_flash('error', 'Failed to create booking reservation due to a database error.');
    redirect('modules/bookings/create.php');
}
