<?php
/**
 * Flash Message System
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Set a flash message
 * 
 * @param string $type ('success', 'error', 'warning', 'info')
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void
{
    $validTypes = ['success', 'error', 'warning', 'info'];
    if (!in_array($type, $validTypes, true)) {
        $type = 'info';
    }

    if (!isset($_SESSION['_flash'][$type])) {
        $_SESSION['_flash'][$type] = [];
    }

    $_SESSION['_flash'][$type][] = $message;
}

/**
 * Check if any flash message or specific type exists
 * 
 * @param string|null $type
 * @return bool
 */
function has_flash(?string $type = null): bool
{
    if ($type !== null) {
        return !empty($_SESSION['_flash'][$type]);
    }
    return !empty($_SESSION['_flash']);
}

/**
 * Get flash messages and clear them from session
 * 
 * @param string|null $type
 * @return array
 */
function get_flash(?string $type = null): array
{
    if ($type !== null) {
        $messages = $_SESSION['_flash'][$type] ?? [];
        unset($_SESSION['_flash'][$type]);
        return $messages;
    }

    $all = $_SESSION['_flash'] ?? [];
    $_SESSION['_flash'] = [];
    return $all;
}

/**
 * Render flash messages as Bootstrap 5 alerts
 * 
 * @return string HTML output
 */
function display_flash(): string
{
    if (!has_flash()) {
        return '';
    }

    $allMessages = get_flash();
    $html = '<div class="flash-messages mb-4">';

    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'error'   => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info'    => 'bi-info-circle-fill'
    ];

    $bsClassMap = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info'
    ];

    foreach ($allMessages as $type => $messages) {
        $alertClass = $bsClassMap[$type] ?? 'alert-info';
        $iconClass = $iconMap[$type] ?? 'bi-info-circle-fill';

        foreach ($messages as $msg) {
            $html .= sprintf(
                '<div class="alert %s alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">' .
                '  <i class="bi %s me-2 flex-shrink-0 fs-5"></i>' .
                '  <div class="flex-grow-1">%s</div>' .
                '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                '</div>',
                htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')
            );
        }
    }

    $html .= '</div>';
    return $html;
}
