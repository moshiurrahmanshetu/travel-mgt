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
