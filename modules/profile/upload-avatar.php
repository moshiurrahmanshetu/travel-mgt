<?php
/**
 * Upload User Avatar Handler
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

// 2. Validate uploaded file presence
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMessage = 'Please select a valid image file to upload.';

    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
        $errorMessage = 'The uploaded file exceeds the maximum allowed upload size.';
    }

    set_flash('error', $errorMessage);
    redirect('modules/profile/index.php');
}

$file = $_FILES['avatar'];

// 3. Validate File Size (Max 2MB)
$maxSize = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $maxSize) {
    set_flash('error', 'Avatar file size must not exceed 2MB.');
    redirect('modules/profile/index.php');
}

// 4. Validate File Extension
$originalName = $file['name'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($extension, $allowedExtensions, true)) {
    set_flash('error', 'Invalid file format. Allowed formats: JPG, JPEG, PNG, WEBP.');
    redirect('modules/profile/index.php');
}

// 5. Validate MIME Type securely using finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowedMimes = [
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png'  => ['png'],
    'image/webp' => ['webp']
];

if (!array_key_exists($mimeType, $allowedMimes) || !in_array($extension, $allowedMimes[$mimeType], true)) {
    set_flash('error', 'Uploaded file is not a valid image.');
    redirect('modules/profile/index.php');
}

// 6. Additional Image Verification using getimagesize
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    set_flash('error', 'Corrupt or unreadable image file.');
    redirect('modules/profile/index.php');
}

// 7. Ensure Upload Directory Exists
if (!is_dir(AVATAR_PATH)) {
    if (!mkdir(AVATAR_PATH, 0755, true) && !is_dir(AVATAR_PATH)) {
        set_flash('error', 'Failed to create upload directory on the server.');
        redirect('modules/profile/index.php');
    }
}

// 8. Generate safe, randomized filename
$newFileName = sprintf('avatar_%d_%s.%s', $userId, bin2hex(random_bytes(8)), $extension);
$destination = AVATAR_PATH . DIRECTORY_SEPARATOR . $newFileName;

// 9. Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    set_flash('error', 'Failed to save uploaded avatar. Please try again.');
    redirect('modules/profile/index.php');
}

try {
    $pdo = get_db_connection();

    // 10. Fetch and delete existing avatar file if safe
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $oldAvatar = $stmt->fetchColumn();

    if (!empty($oldAvatar)) {
        $oldFilePath = AVATAR_PATH . DIRECTORY_SEPARATOR . basename($oldAvatar);
        // Ensure path stays within avatar directory
        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }

    // 11. Update database record with new avatar filename
    $updateStmt = $pdo->prepare("UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE id = :id");
    $updateStmt->execute([
        'avatar' => $newFileName,
        'id'     => $userId
    ]);

    set_flash('success', 'Avatar updated successfully.');
    redirect('modules/profile/index.php');

} catch (PDOException $e) {
    error_log('Avatar Update Error: ' . $e->getMessage());
    // Cleanup newly uploaded file on database failure
    if (file_exists($destination)) {
        @unlink($destination);
    }
    set_flash('error', 'Failed to update avatar record in the database.');
    redirect('modules/profile/index.php');
}
