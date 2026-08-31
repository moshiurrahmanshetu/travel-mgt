<?php
/**
 * Update Customer Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('customers.edit');

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

// 2. Collect and Sanitize Inputs
$firstName      = trim($_POST['first_name'] ?? '');
$lastName       = trim($_POST['last_name'] ?? '');
$name           = trim($_POST['name'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$alternatePhone = trim($_POST['alternate_phone'] ?? '');
$email          = trim($_POST['email'] ?? '');
$gender         = in_array($_POST['gender'] ?? '', ['male', 'female', 'other'], true) ? $_POST['gender'] : null;
$dateOfBirth    = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;

$address        = trim($_POST['address'] ?? '');
$city           = trim($_POST['city'] ?? '');
$state          = trim($_POST['state'] ?? '');
$country        = trim($_POST['country'] ?? 'Bangladesh');
$postalCode     = trim($_POST['postal_code'] ?? '');

$passportNumber = trim($_POST['passport_number'] ?? '');
$passportExpiry = !empty($_POST['passport_expiry']) ? $_POST['passport_expiry'] : null;
$nationalId     = trim($_POST['national_id'] ?? '');

$notes          = trim($_POST['notes'] ?? '');
$status         = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

// If Full Name was empty, assemble from first + last name
if (empty($name) && (!empty($firstName) || !empty($lastName))) {
    $name = trim($firstName . ' ' . $lastName);
}

// 3. Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Full customer name is required.';
}

if (empty($phone)) {
    $errors[] = 'Primary phone number is required.';
} elseif (!preg_match('/^[+\d\s().-]{6,30}$/', $phone)) {
    $errors[] = 'Please enter a valid primary phone number.';
}

if (!empty($email)) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'The email address format is invalid.';
    }
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        set_flash('error', $err);
    }
    redirect('modules/customers/edit.php?id=' . $id);
}

try {
    $pdo = get_db_connection();

    // Verify existing record
    $stmtExisting = $pdo->prepare("SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmtExisting->execute(['id' => $id]);
    $existing = $stmtExisting->fetch();

    if (!$existing) {
        set_flash('error', 'Customer not found or was deleted.');
        redirect('modules/customers/index.php');
    }

    // Check duplicate active email excluding current customer
    if (!empty($email)) {
        $emailCheck = $pdo->prepare("SELECT id FROM customers WHERE email = :email AND id != :id AND deleted_at IS NULL LIMIT 1");
        $emailCheck->execute(['email' => $email, 'id' => $id]);
        if ($emailCheck->fetch()) {
            set_flash('error', 'Another customer with this email address already exists.');
            redirect('modules/customers/edit.php?id=' . $id);
        }
    }

    $profilePhotoFilename = $existing['profile_photo'];
    $oldPhotoToDelete = null;

    // 4. Handle Profile Photo Replacement via Reusable Validator
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = validate_uploaded_image($_FILES['profile_photo'], CUSTOMER_PATH, 'cus_', 2 * 1024 * 1024);
        if (!$uploadResult['success']) {
            set_flash('error', $uploadResult['error']);
            redirect('modules/customers/edit.php?id=' . $id);
        }
        $profilePhotoFilename = $uploadResult['filename'];
        $oldPhotoToDelete = $existing['profile_photo'];
    }

    // 5. Update Customer Record
    $stmt = $pdo->prepare("
        UPDATE customers 
        SET 
            `first_name`      = :first_name,
            `last_name`       = :last_name,
            `name`            = :name,
            `email`           = :email,
            `phone`           = :phone,
            `alternate_phone` = :alternate_phone,
            `gender`          = :gender,
            `date_of_birth`   = :date_of_birth,
            `address`         = :address,
            `city`            = :city,
            `state`           = :state,
            `country`         = :country,
            `postal_code`     = :postal_code,
            `passport_number` = :passport_number,
            `passport_expiry` = :passport_expiry,
            `national_id`     = :national_id,
            `profile_photo`   = :profile_photo,
            `notes`           = :notes,
            `status`          = :status,
            `updated_at`      = NOW()
        WHERE `id` = :id AND `deleted_at` IS NULL
    ");

    $stmt->execute([
        'first_name'      => !empty($firstName) ? $firstName : null,
        'last_name'       => !empty($lastName) ? $lastName : null,
        'name'            => $name,
        'email'           => !empty($email) ? $email : null,
        'phone'           => $phone,
        'alternate_phone' => !empty($alternatePhone) ? $alternatePhone : null,
        'gender'          => $gender,
        'date_of_birth'   => $dateOfBirth,
        'address'         => !empty($address) ? $address : null,
        'city'            => !empty($city) ? $city : null,
        'state'           => !empty($state) ? $state : null,
        'country'         => !empty($country) ? $country : 'Bangladesh',
        'postal_code'     => !empty($postalCode) ? $postalCode : null,
        'passport_number' => !empty($passportNumber) ? $passportNumber : null,
        'passport_expiry' => $passportExpiry,
        'national_id'     => !empty($nationalId) ? $nationalId : null,
        'profile_photo'   => $profilePhotoFilename,
        'notes'           => !empty($notes) ? $notes : null,
        'status'          => $status,
        'id'              => $id
    ]);

    // 6. Delete Old Photo Safely after DB update succeeds
    if (!empty($oldPhotoToDelete) && $oldPhotoToDelete !== $profilePhotoFilename) {
        $oldFilePath = CUSTOMER_PATH . DIRECTORY_SEPARATOR . basename($oldPhotoToDelete);
        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }

    set_flash('success', "Customer profile \"{$name}\" updated successfully.");
    redirect('modules/customers/view.php?id=' . $id);

} catch (PDOException $e) {
    error_log('Customer Update Error: ' . $e->getMessage());
    set_flash('error', 'Failed to update customer profile due to a database error.');
    redirect('modules/customers/edit.php?id=' . $id);
}
