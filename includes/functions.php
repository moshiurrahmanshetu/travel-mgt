<?php
/**
 * Global Common Helper Functions
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/flash.php';

/**
 * Escape HTML output safely
 * 
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate full application URL
 * 
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    if (empty($cleanPath)) {
        return APP_URL;
    }
    return APP_URL . '/' . $cleanPath;
}

/**
 * Generate full asset URL
 * 
 * @param string $path
 * @return string
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Perform HTTP redirect and terminate script
 * 
 * @param string $path URL or relative path
 * @return void
 */
function redirect(string $path): void
{
    // If it's not a full http:// or https:// URL, prepend application URL
    if (!preg_match('#^https?://#i', $path)) {
        $target = url($path);
    } else {
        $target = $path;
    }

    header("Location: " . $target);
    exit;
}

/**
 * Retrieve old input value from session or request
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function old(string $key, $default = '')
{
    if (isset($_SESSION['_old_input'][$key])) {
        $val = $_SESSION['_old_input'][$key];
        return $val;
    }

    if (isset($_POST[$key])) {
        return $_POST[$key];
    }

    if (isset($_GET[$key])) {
        return $_GET[$key];
    }

    return $default;
}

/**
 * Flash input data to session for the next request
 * 
 * @param array $input
 * @return void
 */
function flash_old_input(array $input): void
{
    // Remove sensitive fields
    unset($input['password'], $input['password_confirmation'], $input['current_password'], $input['csrf_token']);
    $_SESSION['_old_input'] = $input;
}

/**
 * Clear flashed input from session
 * 
 * @return void
 */
function clear_old_input(): void
{
    unset($_SESSION['_old_input']);
}

/**
 * Check if a user is currently authenticated
 * 
 * @return bool
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user's ID
 * 
 * @return int|null
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current authenticated user details from database with request-level caching
 * 
 * @return array|null
 */
function current_user(): ?array
{
    static $cachedUser = null;
    static $cachedUserId = null;

    $userId = current_user_id();
    if (!$userId) {
        return null;
    }

    if ($cachedUser !== null && $cachedUserId === $userId) {
        return $cachedUser;
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.role_id,
                u.first_name,
                u.last_name,
                u.name,
                u.email,
                u.phone,
                u.avatar,
                u.status,
                u.last_login,
                u.created_at,
                u.updated_at,
                r.name AS role_name,
                r.slug AS role_slug
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user) {
            $cachedUser = $user;
            $cachedUserId = $userId;
            return $user;
        }
    } catch (PDOException $e) {
        error_log('current_user query error: ' . $e->getMessage());
    }

    return null;
}

/**
 * Check if the authenticated user has a specific role slug
 * 
 * @param string|array $roles Single role slug or array of role slugs
 * @return bool
 */
function has_role($roles): bool
{
    $user = current_user();
    if (!$user || empty($user['role_slug'])) {
        return false;
    }

    if (is_array($roles)) {
        return in_array($user['role_slug'], $roles, true);
    }

    return $user['role_slug'] === $roles;
}

/**
 * Check if the authenticated user's role has a specific permission slug
 * 
 * @param string $permissionSlug
 * @return bool
 */
function has_permission(string $permissionSlug): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    // Administrators always have full permission
    if ($user['role_slug'] === 'administrator') {
        return true;
    }

    static $userPermissions = null;

    if ($userPermissions === null) {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("
                SELECT p.slug
                FROM role_permissions rp
                JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id
            ");
            $stmt->execute(['role_id' => $user['role_id']]);
            $userPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('has_permission query error: ' . $e->getMessage());
            $userPermissions = [];
        }
    }

    return in_array($permissionSlug, $userPermissions, true);
}

/**
 * Enforce that the user must be authenticated
 * 
 * @return void
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access this page.');
        redirect('auth/login.php');
    }

    // Verify user account is still active and valid in DB
    $user = current_user();
    if (!$user || $user['status'] !== 'active') {
        // Account disabled or soft deleted
        $_SESSION = [];
        if (session_id() !== '' || headers_sent() === false) {
            session_destroy();
        }
        session_start();
        set_flash('error', 'Your account is inactive or has been suspended. Please contact the administrator.');
        redirect('auth/login.php');
    }
}

/**
 * Enforce that the user has a specific role or abort
 * 
 * @param string|array $roles
 * @return void
 */
function require_role($roles): void
{
    require_login();

    if (!has_role($roles)) {
        set_flash('error', 'Access Denied: You do not have permission to access this resource.');
        redirect('modules/dashboard/index.php');
    }
}

/**
 * Enforce that the user has a specific permission or abort
 * 
 * @param string $permission
 * @return void
 */
function require_permission(string $permission): void
{
    require_login();

    if (!has_permission($permission)) {
        set_flash('error', 'Access Denied: You lack the required permission.');
        redirect('modules/dashboard/index.php');
    }
}

/**
 * Format a datetime string into readable text
 * 
 * @param string|null $datetime
 * @param string $format
 * @return string
 */
function format_date(?string $datetime, string $format = 'M d, Y h:i A'): string
{
    if (empty($datetime)) {
        return '—';
    }

    try {
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Extract initials from a user's full name
 * 
 * @param string $name
 * @return string
 */
function get_user_initials(string $name): string
{
    $words = explode(' ', trim($name));
    $initials = '';
    if (!empty($words[0])) {
        $initials .= mb_substr($words[0], 0, 1, 'UTF-8');
    }
    if (count($words) > 1 && !empty($words[count($words) - 1])) {
        $initials .= mb_substr($words[count($words) - 1], 0, 1, 'UTF-8');
    }
    return strtoupper($initials ?: 'U');
}

/**
 * Get user avatar URL or fallback
 * 
 * @param string|null $avatarFilename
 * @return string|null
 */
function get_avatar_url(?string $avatarFilename): ?string
{
    if (empty($avatarFilename)) {
        return null;
    }

    $filePath = AVATAR_PATH . DIRECTORY_SEPARATOR . $avatarFilename;
    if (file_exists($filePath)) {
        return AVATAR_URL . '/' . $avatarFilename;
    }

    return null;
}

/**
 * Generate a clean URL-friendly slug
 * 
 * @param string $text
 * @return string
 */
function slugify(string $text): string
{
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);

    return empty($text) ? 'n-a-' . time() : $text;
}

/**
 * Calculate discounted final price safely (ensuring never below 0)
 * 
 * @param float|int|string $basePrice
 * @param string $discountType ('none', 'percentage', 'fixed')
 * @param float|int|string $discountValue
 * @return float
 */
function calculate_discounted_price($basePrice, string $discountType = 'none', $discountValue = 0): float
{
    $price = (float)$basePrice;
    $val = (float)$discountValue;

    if ($discountType === 'percentage' && $val > 0) {
        $discountAmount = ($price * $val) / 100;
        $price = $price - $discountAmount;
    } elseif ($discountType === 'fixed' && $val > 0) {
        $price = $price - $val;
    }

    return max(0.0, round($price, 2));
}

/**
 * Format a monetary amount with currency symbol
 * 
 * @param float|int|string|null $amount
 * @param int $decimals
 * @return string
 */
function format_currency($amount, int $decimals = 2): string
{
    $val = (float)($amount ?? 0);
    return APP_CURRENCY_SYMBOL . number_format($val, $decimals);
}

/**
 * Get tour image URL or placeholder
 * 
 * @param string|null $filename
 * @return string|null
 */
function get_tour_image_url(?string $filename): ?string
{
    if (empty($filename)) {
        return null;
    }

    $filePath = TOUR_PATH . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($filePath)) {
        return TOUR_URL . '/' . $filename;
    }

    return null;
}

/**
 * Get destination image URL or placeholder
 * 
 * @param string|null $filename
 * @return string|null
 */
function get_destination_image_url(?string $filename): ?string
{
    if (empty($filename)) {
        return null;
    }

    $filePath = DESTINATION_PATH . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($filePath)) {
        return DESTINATION_URL . '/' . $filename;
    }

    return null;
}

/**
 * Get customer profile photo URL or placeholder
 * 
 * @param string|null $filename
 * @return string|null
 */
function get_customer_avatar_url(?string $filename): ?string
{
    if (empty($filename)) {
        return null;
    }

    $filePath = CUSTOMER_PATH . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($filePath)) {
        return CUSTOMER_URL . '/' . $filename;
    }

    return null;
}

/**
 * Extract initials from a customer's full name
 * 
 * @param string $name
 * @return string
 */
function get_customer_initials(string $name): string
{
    $words = explode(' ', trim($name));
    $initials = '';
    if (!empty($words[0])) {
        $initials .= mb_substr($words[0], 0, 1, 'UTF-8');
    }
    if (count($words) > 1 && !empty($words[count($words) - 1])) {
        $initials .= mb_substr($words[count($words) - 1], 0, 1, 'UTF-8');
    }
    return strtoupper($initials ?: 'C');
}

/**
 * Validate and process an uploaded image file securely
 * 
 * @param array $file Single file element from $_FILES array
 * @param string $destinationDir Target absolute folder path
 * @param string $filePrefix Prefix for randomized filename (e.g. 'cus_', 'avatar_')
 * @param int $maxBytes Max allowable file size in bytes (default 2MB)
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function validate_uploaded_image(array $file, string $destinationDir, string $filePrefix = 'img_', int $maxBytes = 2097152): array
{
    // 1. Check Upload Error Codes
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was selected for upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
        ];
        return [
            'success'  => false,
            'filename' => null,
            'error'    => $errorMessages[$errorCode] ?? 'An unknown upload error occurred.'
        ];
    }

    $tmpPath = $file['tmp_name'] ?? '';

    // 2. Validate Temporary File Origin
    if (empty($tmpPath) || !is_uploaded_file($tmpPath)) {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => 'Security check failed: File was not uploaded via a valid HTTP POST request.'
        ];
    }

    // 3. Validate File Size
    if ($file['size'] > $maxBytes) {
        $maxMb = round($maxBytes / (1024 * 1024), 1);
        return [
            'success'  => false,
            'filename' => null,
            'error'    => "File size exceeds the allowable limit of {$maxMb}MB."
        ];
    }

    // 4. Validate Binary Image Signature & Dimensions via getimagesize
    $imageInfo = @getimagesize($tmpPath);
    if ($imageInfo === false) {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => 'Uploaded file is not a valid image.'
        ];
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
        return [
            'success'  => false,
            'filename' => null,
            'error'    => 'Unsupported image format. Allowed formats: JPG, PNG, WebP.'
        ];
    }

    // 5. Validate MIME type via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = strtolower($finfo->file($tmpPath));
    $allowedMimes = [
        'image/jpeg', 'image/pjpeg', 'image/jpg',
        'image/png', 'image/x-png',
        'image/webp', 'image/x-webp'
    ];

    if (!in_array($detectedMime, $allowedMimes, true)) {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => 'Invalid image MIME type: ' . e($detectedMime)
        ];
    }

    // 6. Ensure Destination Directory Exists
    if (!is_dir($destinationDir)) {
        if (!@mkdir($destinationDir, 0755, true)) {
            return [
                'success'  => false,
                'filename' => null,
                'error'    => 'Failed to initialize destination upload directory.'
            ];
        }
    }

    // 7. Generate Secure Randomized Filename
    $extension = $allowedImageTypes[$detectedImageType];
    $newFilename = sprintf('%s%s.%s', $filePrefix, bin2hex(random_bytes(10)), $extension);
    $destinationPath = rtrim($destinationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFilename;

    // 8. Move File to Final Location
    if (!move_uploaded_file($tmpPath, $destinationPath)) {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => 'Failed to move uploaded file to permanent storage.'
        ];
    }

    return [
        'success'  => true,
        'filename' => $newFilename,
        'error'    => null
    ];
}

/**
 * Generate a unique booking number in the format BK-YYYY-XXXXX
 * 
 * @return string
 */
function generate_booking_number(): string
{
    $pdo = get_db_connection();
    $currentYear = date('Y');
    
    // Find highest ID in bookings
    $maxIdStmt = $pdo->query("SELECT MAX(id) FROM bookings");
    $nextId = (int)$maxIdStmt->fetchColumn() + 1;
    $bookingNumber = sprintf('BK-%s-%05d', $currentYear, $nextId);

    // Verify collision safety
    $checkStmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_number = :code LIMIT 1");
    $checkStmt->execute(['code' => $bookingNumber]);
    if ($checkStmt->fetch()) {
        $bookingNumber = sprintf('BK-%s-%05d-%s', $currentYear, $nextId, bin2hex(random_bytes(2)));
    }

    return $bookingNumber;
}

/**
 * Calculate authoritative booking pricing breakdown server-side
 * 
 * @param int $adults
 * @param float|int $adultPrice
 * @param int $children
 * @param float|int $childPrice
 * @param string $discountType ('none', 'percentage', 'fixed')
 * @param float|int $discountValue
 * @param float|int $paidAmount
 * @return array
 */
function calculate_booking_pricing($adults, $adultPrice, $children = 0, $childPrice = 0, string $discountType = 'none', $discountValue = 0, $paidAmount = 0): array
{
    $adultCount = max(1, (int)$adults);
    $childCount = max(0, (int)$children);
    $priceAdult = max(0.0, (float)$adultPrice);
    $priceChild = max(0.0, (float)$childPrice);
    $discVal    = max(0.0, (float)$discountValue);
    $paid       = max(0.0, (float)$paidAmount);

    $adultSubtotal = round($adultCount * $priceAdult, 2);
    $childSubtotal = round($childCount * $priceChild, 2);
    $subtotal      = round($adultSubtotal + $childSubtotal, 2);

    $discountAmount = 0.0;
    if ($discountType === 'percentage' && $discVal > 0) {
        $discountAmount = round(($subtotal * $discVal) / 100, 2);
    } elseif ($discountType === 'fixed' && $discVal > 0) {
        $discountAmount = round($discVal, 2);
    }

    // Never allow discount to exceed subtotal
    $discountAmount = min($subtotal, $discountAmount);
    $totalAmount    = max(0.0, round($subtotal - $discountAmount, 2));
    $dueAmount      = max(0.0, round($totalAmount - $paid, 2));

    return [
        'adults'          => $adultCount,
        'adult_price'     => $priceAdult,
        'adult_subtotal'  => $adultSubtotal,
        'children'        => $childCount,
        'child_price'     => $priceChild,
        'child_subtotal'  => $childSubtotal,
        'subtotal'        => $subtotal,
        'discount_type'   => in_array($discountType, ['none', 'percentage', 'fixed'], true) ? $discountType : 'none',
        'discount_value'  => $discVal,
        'discount_amount' => $discountAmount,
        'total_amount'    => $totalAmount,
        'paid_amount'     => $paid,
        'due_amount'      => $dueAmount
    ];
}

/**
 * Get total confirmed travellers (adults + children + infants) for a tour package
 * 
 * @param int $tourPackageId
 * @param int|null $excludeBookingId
 * @return int
 */
function get_tour_confirmed_travellers(int $tourPackageId, ?int $excludeBookingId = null): int
{
    try {
        $pdo = get_db_connection();
        $sql = "
            SELECT COALESCE(SUM(adults + children + infants), 0) AS total_travellers
            FROM bookings
            WHERE tour_package_id = :pkg_id 
              AND booking_status = 'confirmed' 
              AND deleted_at IS NULL
        ";
        $params = ['pkg_id' => $tourPackageId];

        if ($excludeBookingId !== null && $excludeBookingId > 0) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeBookingId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('get_tour_confirmed_travellers error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Check whether a tour package has sufficient capacity for requested travellers
 * 
 * @param int $tourPackageId
 * @param int $requestedTravellers
 * @param int|null $excludeBookingId
 * @return array ['has_capacity' => bool, 'capacity' => int, 'confirmed' => int, 'remaining' => int, 'requested' => int]
 */
function check_tour_capacity(int $tourPackageId, int $requestedTravellers, ?int $excludeBookingId = null): array
{
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT available_seats FROM tour_packages WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $tourPackageId]);
        $capacity = (int)$stmt->fetchColumn();

        $confirmed = get_tour_confirmed_travellers($tourPackageId, $excludeBookingId);
        $remaining = max(0, $capacity - $confirmed);
        $req = max(1, $requestedTravellers);

        return [
            'has_capacity' => ($req <= $remaining),
            'capacity'     => $capacity,
            'confirmed'    => $confirmed,
            'remaining'    => $remaining,
            'requested'    => $req
        ];
    } catch (PDOException $e) {
        error_log('check_tour_capacity error: ' . $e->getMessage());
        return [
            'has_capacity' => false,
            'capacity'     => 0,
            'confirmed'    => 0,
            'remaining'    => 0,
            'requested'    => $requestedTravellers
        ];
    }
}

/**
 * Validate allowed booking status transitions
 * 
 * @param string $currentStatus
 * @param string $newStatus
 * @return bool
 */
function can_transition_booking_status(string $currentStatus, string $newStatus): bool
{
    if ($currentStatus === $newStatus) {
        return true;
    }

    $allowedTransitions = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => []
    ];

    return isset($allowedTransitions[$currentStatus]) && in_array($newStatus, $allowedTransitions[$currentStatus], true);
}

/**
 * Generate a unique payment receipt number in the format PAY-YYYY-XXXXX
 * 
 * @return string
 */
function generate_payment_number(): string
{
    $pdo = get_db_connection();
    $currentYear = date('Y');
    
    // Find highest ID in payments
    $maxIdStmt = $pdo->query("SELECT MAX(id) FROM payments");
    $nextId = (int)$maxIdStmt->fetchColumn() + 1;
    $paymentNumber = sprintf('PAY-%s-%05d', $currentYear, $nextId);

    // Verify collision safety
    $checkStmt = $pdo->prepare("SELECT id FROM payments WHERE payment_number = :code LIMIT 1");
    $checkStmt->execute(['code' => $paymentNumber]);
    if ($checkStmt->fetch()) {
        $paymentNumber = sprintf('PAY-%s-%05d-%s', $currentYear, $nextId, bin2hex(random_bytes(2)));
    }

    return $paymentNumber;
}

/**
 * Authoritatively recalculate and synchronize booking payment summary
 * 
 * @param int $bookingId
 * @return array ['total_amount', 'paid_amount', 'due_amount', 'payment_status']
 */
function recalculate_booking_payment_summary(int $bookingId): array
{
    try {
        $pdo = get_db_connection();

        // 1. Fetch Booking Total Amount
        $stmtBk = $pdo->prepare("SELECT total_amount FROM bookings WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmtBk->execute(['id' => $bookingId]);
        $bookingTotal = (float)$stmtBk->fetchColumn();

        // 2. Sum only completed and non-deleted payments
        $stmtPay = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total_paid
            FROM payments
            WHERE booking_id = :booking_id
              AND payment_status = 'completed'
              AND deleted_at IS NULL
        ");
        $stmtPay->execute(['booking_id' => $bookingId]);
        $paidAmount = round((float)$stmtPay->fetchColumn(), 2);

        // 3. Compute Due & Booking-Level Payment Status
        $dueAmount = max(0.0, round($bookingTotal - $paidAmount, 2));

        $paymentStatus = 'unpaid';
        if ($paidAmount <= 0.0) {
            $paymentStatus = 'unpaid';
        } elseif ($paidAmount < $bookingTotal) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'paid';
        }

        // 4. Synchronize Booking Record
        $updateStmt = $pdo->prepare("
            UPDATE bookings 
            SET 
                `paid_amount`    = :paid,
                `due_amount`     = :due,
                `payment_status` = :status,
                `updated_at`     = NOW()
            WHERE `id` = :id
        ");
        $updateStmt->execute([
            'paid'   => $paidAmount,
            'due'    => $dueAmount,
            'status' => $paymentStatus,
            'id'     => $bookingId
        ]);

        return [
            'total_amount'   => $bookingTotal,
            'paid_amount'    => $paidAmount,
            'due_amount'     => $dueAmount,
            'payment_status' => $paymentStatus
        ];

    } catch (PDOException $e) {
        error_log('recalculate_booking_payment_summary error: ' . $e->getMessage());
        return [
            'total_amount'   => 0.0,
            'paid_amount'    => 0.0,
            'due_amount'     => 0.0,
            'payment_status' => 'unpaid'
        ];
    }
}

/**
 * Get live payment summary breakdown for a booking
 * 
 * @param int $bookingId
 * @return array
 */
function get_booking_payment_summary(int $bookingId): array
{
    try {
        $pdo = get_db_connection();

        $stmtBk = $pdo->prepare("SELECT total_amount, paid_amount, due_amount, payment_status FROM bookings WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmtBk->execute(['id' => $bookingId]);
        $bk = $stmtBk->fetch();

        if (!$bk) {
            return ['total_amount' => 0.0, 'paid_amount' => 0.0, 'due_amount' => 0.0, 'payment_status' => 'unpaid'];
        }

        $stmtPay = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total_paid
            FROM payments
            WHERE booking_id = :booking_id
              AND payment_status = 'completed'
              AND deleted_at IS NULL
        ");
        $stmtPay->execute(['booking_id' => $bookingId]);
        $paidAmount = round((float)$stmtPay->fetchColumn(), 2);
        $totalAmount = (float)$bk['total_amount'];
        $dueAmount = max(0.0, round($totalAmount - $paidAmount, 2));

        $paymentStatus = 'unpaid';
        if ($paidAmount <= 0.0) {
            $paymentStatus = 'unpaid';
        } elseif ($paidAmount < $totalAmount) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'paid';
        }

        return [
            'total_amount'   => $totalAmount,
            'paid_amount'    => $paidAmount,
            'due_amount'     => $dueAmount,
            'payment_status' => $paymentStatus
        ];
    } catch (PDOException $e) {
        error_log('get_booking_payment_summary error: ' . $e->getMessage());
        return ['total_amount' => 0.0, 'paid_amount' => 0.0, 'due_amount' => 0.0, 'payment_status' => 'unpaid'];
    }
}



