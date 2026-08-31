<?php
/**
 * Store Customer Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Enforce Permission
require_permission('customers.create');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/customers/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    flash_old_input($_POST);
    redirect('modules/customers/create.php');
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
    flash_old_input($_POST);
    redirect('modules/customers/create.php');
}

try {
    $pdo = get_db_connection();

    // Check duplicate active email if provided
    if (!empty($email)) {
        $emailCheck = $pdo->prepare("SELECT id FROM customers WHERE email = :email AND deleted_at IS NULL LIMIT 1");
        $emailCheck->execute(['email' => $email]);
        if ($emailCheck->fetch()) {
            set_flash('error', 'A customer with this email address already exists.');
            flash_old_input($_POST);
            redirect('modules/customers/create.php');
        }
    }

    // 4. Handle Profile Photo Upload via Reusable Validator
    $profilePhotoFilename = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = validate_uploaded_image($_FILES['profile_photo'], CUSTOMER_PATH, 'cus_', 2 * 1024 * 1024);
        if (!$uploadResult['success']) {
            set_flash('error', $uploadResult['error']);
            flash_old_input($_POST);
            redirect('modules/customers/create.php');
        }
        $profilePhotoFilename = $uploadResult['filename'];
    }

    // 5. Generate Unique Customer Code (e.g. CUS-00001)
    $maxIdStmt = $pdo->query("SELECT MAX(id) FROM customers");
    $nextId = (int)$maxIdStmt->fetchColumn() + 1;
    $customerCode = sprintf('CUS-%05d', $nextId);

    // Verify customer code uniqueness
    $codeCheck = $pdo->prepare("SELECT id FROM customers WHERE customer_code = :code LIMIT 1");
    $codeCheck->execute(['code' => $customerCode]);
    if ($codeCheck->fetch()) {
        $customerCode = sprintf('CUS-%05d-%s', $nextId, bin2hex(random_bytes(2)));
    }

    // 6. Insert Record into Database
    $stmt = $pdo->prepare("
        INSERT INTO customers (
            `customer_code`, `first_name`, `last_name`, `name`, `email`,
            `phone`, `alternate_phone`, `gender`, `date_of_birth`,
            `address`, `city`, `state`, `country`, `postal_code`,
            `passport_number`, `passport_expiry`, `national_id`,
            `profile_photo`, `notes`, `status`, `created_at`, `updated_at`
        ) VALUES (
            :customer_code, :first_name, :last_name, :name, :email,
            :phone, :alternate_phone, :gender, :date_of_birth,
            :address, :city, :state, :country, :postal_code,
            :passport_number, :passport_expiry, :national_id,
            :profile_photo, :notes, :status, NOW(), NOW()
        )
    ");

    $stmt->execute([
        'customer_code'   => $customerCode,
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
        'status'          => $status
    ]);

    $newCustomerId = (int)$pdo->lastInsertId();
    clear_old_input();

    set_flash('success', "Customer \"{$name}\" ({$customerCode}) registered successfully.");
    redirect('modules/customers/view.php?id=' . $newCustomerId);

} catch (PDOException $e) {
    error_log('Customer Store Error: ' . $e->getMessage());
    // If photo was saved, cleanup to prevent orphan files
    if ($profilePhotoFilename) {
        $uploadedFilePath = CUSTOMER_PATH . DIRECTORY_SEPARATOR . $profilePhotoFilename;
        if (file_exists($uploadedFilePath) && is_file($uploadedFilePath)) {
            @unlink($uploadedFilePath);
        }
    }

    set_flash('error', 'Failed to register customer due to a database error.');
    flash_old_input($_POST);
    redirect('modules/customers/create.php');
}
