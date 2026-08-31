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

// 2. Validate $_FILES existence and upload error codes
if (!isset($_FILES['avatar'])) {
    set_flash('error', 'Please select an image file to upload.');
    redirect('modules/profile/index.php');
}

$file = $_FILES['avatar'];
$errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

if ($errorCode !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded image exceeds the server upload limit (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded image exceeds the maximum allowed form size.',
        UPLOAD_ERR_PARTIAL    => 'The image was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE    => 'Please select an image file to upload.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server error: Temporary directory is missing.',
        UPLOAD_ERR_CANT_WRITE => 'Server error: Failed to write uploaded file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
    ];

    $message = $errorMessages[$errorCode] ?? 'An error occurred during file upload. (Code: ' . $errorCode . ')';
    set_flash('error', $message);
    redirect('modules/profile/index.php');
}

// 3. Verify uploaded temporary file
$tmpPath = $file['tmp_name'] ?? '';
if (empty($tmpPath) || !is_uploaded_file($tmpPath)) {
    set_flash('error', 'Invalid upload attempt or temporary file not found.');
    redirect('modules/profile/index.php');
}

// 4. Validate File Size (Max 2MB)
$maxSize = 2 * 1024 * 1024; // 2 MB
if ($file['size'] > $maxSize) {
    set_flash('error', 'Avatar file size must not exceed 2MB.');
    redirect('modules/profile/index.php');
}

// 5. Validate Actual Image Content & Dimensions using getimagesize()
$imageInfo = @getimagesize($tmpPath);
if ($imageInfo === false) {
    set_flash('error', 'Uploaded file is not a valid image or is corrupt.');
    redirect('modules/profile/index.php');
}

$detectedImageType = $imageInfo[2] ?? 0;
$allowedImageTypes = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
];

if (defined('IMAGETYPE_WEBP')) {
    $allowedImageTypes[IMAGETYPE_WEBP] = 'webp';
}

if (!array_key_exists($detectedImageType, $allowedImageTypes)) {
    set_flash('error', 'Invalid image format. Supported formats: JPG, JPEG, PNG, WEBP.');
    redirect('modules/profile/index.php');
}

// Safe canonical extension derived from validated image content
$safeExtension = $allowedImageTypes[$detectedImageType];

// 6. Validate MIME Type using finfo
$allowedMimes = [
    'image/jpeg',
    'image/pjpeg',
    'image/jpg',
    'image/png',
    'image/x-png',
    'image/webp',
    'image/x-webp'
];

if (extension_loaded('fileinfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($tmpPath);
    if (!in_array(strtolower($detectedMime), $allowedMimes, true)) {
        set_flash('error', 'Invalid image MIME type (' . htmlspecialchars($detectedMime) . ').');
        redirect('modules/profile/index.php');
    }
}

// 7. Ensure Avatar Upload Directory Exists and is Writable
if (!is_dir(AVATAR_PATH)) {
    if (!mkdir(AVATAR_PATH, 0755, true) && !is_dir(AVATAR_PATH)) {
        set_flash('error', 'Server error: Unable to create avatar upload directory.');
        redirect('modules/profile/index.php');
    }
}

if (!is_writable(AVATAR_PATH)) {
    set_flash('error', 'Server error: Avatar upload directory is not writable.');
    redirect('modules/profile/index.php');
}

// 8. Generate safe, randomized filename (never use user-supplied filename)
$newFileName = sprintf('avatar_%d_%s.%s', $userId, bin2hex(random_bytes(10)), $safeExtension);
$destination = AVATAR_PATH . DIRECTORY_SEPARATOR . $newFileName;

// 9. Move uploaded file to target location
if (!move_uploaded_file($tmpPath, $destination)) {
    set_flash('error', 'Failed to save uploaded avatar to destination.');
    redirect('modules/profile/index.php');
}

try {
    $pdo = get_db_connection();

    // 10. Fetch current avatar filename for cleanup
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $oldAvatar = $stmt->fetchColumn();

    // 11. Update database record with new avatar filename
    $updateStmt = $pdo->prepare("UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE id = :id");
    $updateStmt->execute([
        'avatar' => $newFileName,
        'id'     => $userId
    ]);

    // 12. Safely remove previous avatar file after database update succeeds
    if (!empty($oldAvatar)) {
        $oldFilePath = AVATAR_PATH . DIRECTORY_SEPARATOR . basename($oldAvatar);
        // Ensure path remains inside AVATAR_PATH and is a valid file
        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }

    set_flash('success', 'Avatar updated successfully.');
    redirect('modules/profile/index.php');

} catch (PDOException $e) {
    error_log('Avatar Database Update Error: ' . $e->getMessage());
    // Cleanup newly moved file on DB failure
    if (file_exists($destination) && is_file($destination)) {
        @unlink($destination);
    }
    set_flash('error', 'Failed to update avatar record in the database.');
    redirect('modules/profile/index.php');
}
