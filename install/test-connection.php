<?php
/**
 * AJAX Database Connection Test Endpoint
 * Tour & Travel Booking Management System
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/functions.php';

// If already installed, reject all requests
if (is_installed()) {
    echo json_encode([
        'success' => false,
        'message' => 'Application is already installed.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Verify CSRF
if (!installer_verify_csrf()) {
    echo json_encode([
        'success' => false,
        'message' => 'Security token invalid or expired. Please refresh the page.'
    ]);
    exit;
}

$host     = trim($_POST['db_host'] ?? '127.0.0.1');
$port     = trim($_POST['db_port'] ?? '3306');
$dbname   = trim($_POST['db_name'] ?? '');
$user     = trim($_POST['db_user'] ?? 'root');
$pass     = (string)($_POST['db_pass'] ?? '');
$createDb = !empty($_POST['create_db']);

$result = test_db_connection($host, $port, $dbname, $user, $pass, $createDb);

echo json_encode([
    'success' => $result['success'],
    'message' => $result['message']
]);
exit;
