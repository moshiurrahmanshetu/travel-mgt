<?php
/**
 * Update Profile Information Handler
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/profile/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('error', 'Security token expired or invalid. Please try again.');
    redirect('modules/profile/index.php');
}

$userId = current_user_id();

// 2. Sanitize and collect inputs
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$name      = trim($_POST['name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');

// 3. Validation
$errors = [];

if (empty($firstName)) {
    $errors[] = 'First name is required.';
} elseif (mb_strlen($firstName) > 50) {
    $errors[] = 'First name cannot exceed 50 characters.';
}

if (empty($lastName)) {
    $errors[] = 'Last name is required.';
} elseif (mb_strlen($lastName) > 50) {
    $errors[] = 'Last name cannot exceed 50 characters.';
}

if (empty($name)) {
    $name = trim($firstName . ' ' . $lastName);
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Full display name cannot exceed 100 characters.';
}

if (empty($email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
} elseif (mb_strlen($email) > 100) {
    $errors[] = 'Email cannot exceed 100 characters.';
}

if (!empty($phone) && mb_strlen($phone) > 20) {
    $errors[] = 'Phone number cannot exceed 20 characters.';
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        set_flash('error', $err);
    }
    redirect('modules/profile/index.php');
}

try {
    $pdo = get_db_connection();

    // 4. Check email uniqueness against other users
    $stmt = $pdo->prepare("
        SELECT id 
        FROM users 
        WHERE email = :email AND id != :id AND deleted_at IS NULL 
        LIMIT 1
    ");
    $stmt->execute([
        'email' => $email,
        'id'    => $userId
    ]);

    if ($stmt->fetch()) {
        set_flash('error', 'The email address is already in use by another account.');
        redirect('modules/profile/index.php');
    }

    // 5. Update user information in database
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET 
            first_name = :first_name,
            last_name  = :last_name,
            name       = :name,
            email      = :email,
            phone      = :phone,
            updated_at = NOW()
        WHERE id = :id
    ");

    $updateStmt->execute([
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'name'       => $name,
        'email'      => $email,
        'phone'      => !empty($phone) ? $phone : null,
        'id'         => $userId
    ]);

    // 6. Update session name cache
    $_SESSION['user_name'] = $name;

    set_flash('success', 'Profile updated successfully.');
    redirect('modules/profile/index.php');

} catch (PDOException $e) {
    error_log('Profile Update Error: ' . $e->getMessage());
    set_flash('error', 'Failed to update profile due to a database error. Please try again.');
    redirect('modules/profile/index.php');
}
