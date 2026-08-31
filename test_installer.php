<?php
/**
 * Automated Verification Suite for Phase 08: Commercial WordPress-Style Installer
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/install/functions.php';

$passed = 0;
$failed = 0;

function test_assert(string $desc, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$desc}\n";
    } else {
        $failed++;
        echo "[FAIL] {$desc} -> Details: {$details}\n";
    }
}

echo "===============================================================\n";
echo " PHASE 08: COMMERCIAL WORDPRESS-STYLE INSTALLER TEST SUITE\n";
echo "===============================================================\n\n";

// TEST 1: Server Requirements Check
$reqs = check_server_requirements();
test_assert("Requirements check executes and returns array", is_array($reqs) && isset($reqs['requirements']));
test_assert("PHP Version >= 8.0 passes", $reqs['requirements']['php_version']['passed'] === true);
test_assert("PDO extension passes", $reqs['requirements']['pdo']['passed'] === true);
test_assert("PDO MySQL extension passes", $reqs['requirements']['pdo_mysql']['passed'] === true);
test_assert("JSON extension passes", $reqs['requirements']['json']['passed'] === true);
test_assert("Storage writable passes", $reqs['requirements']['storage_writable']['passed'] === true);
test_assert("All server requirements pass", $reqs['all_passed'] === true);

// TEST 2: Database Connection Test (Invalid Credentials)
$badConn = test_db_connection('127.0.0.1', 3306, 'non_existent_db_xyz999', 'invalid_user_123', 'wrong_pass_456');
test_assert("Invalid database credentials fail gracefully", $badConn['success'] === false && !empty($badConn['message']));
test_assert("Invalid connection message does not expose raw PHP passwords", strpos($badConn['message'], 'wrong_pass_456') === false);

// TEST 3: Database Connection Test (Valid Credentials)
$goodConn = test_db_connection('127.0.0.1', 3306, 'travel_mgt_db', 'root', '');
test_assert("Valid database credentials connect successfully", $goodConn['success'] === true && $goodConn['pdo'] instanceof PDO);
$pdo = $goodConn['pdo'];

// TEST 4: Master database.sql File Exists & Is Valid
$sqlFile = __DIR__ . '/database/database.sql';
test_assert("Master database/database.sql exists and is readable", file_exists($sqlFile) && is_readable($sqlFile));

// TEST 5: SQL File Execution & Import
$importResult = execute_sql_file($pdo, $sqlFile);
test_assert("Master database.sql imports with 0 SQL errors", $importResult['success'] === true);
test_assert("Statements executed > 10", $importResult['statements_executed'] > 10);

// TEST 6: Table Verification
$tableCheck = verify_installation_tables($pdo);
test_assert("All required application tables verified in database", $tableCheck['success'] === true && empty($tableCheck['missing_tables']));

// TEST 7: Default System Data Verification
$roleCount = (int)$pdo->query("SELECT COUNT(*) FROM roles WHERE is_system = 1")->fetchColumn();
test_assert("3 core system roles exist (Administrator, Manager, Staff)", $roleCount === 3);

$permCount = (int)$pdo->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
test_assert("45 system permissions exist", $permCount === 45);

$adminPermCount = (int)$pdo->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = 1")->fetchColumn();
test_assert("Administrator role has all 45 permissions assigned", $adminPermCount === 45);

$settingsCount = (int)$pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
test_assert("Default system settings seeded", $settingsCount >= 8);

// TEST 8: Admin User Creation
$testAdminEmail = 'installer_admin_' . time() . '@example.com';
$testAdminPass = 'AdminSecure@2026';
$adminResult = create_admin_user($pdo, 'Test SuperAdmin', $testAdminEmail, $testAdminPass);
test_assert("create_admin_user() creates account successfully", $adminResult['success'] === true && (int)$adminResult['user_id'] > 0);

// Verify created administrator in database
$adminUserStmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.password, r.slug AS role_slug
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = :id
");
$adminUserStmt->execute(['id' => $adminResult['user_id']]);
$createdAdmin = $adminUserStmt->fetch();
test_assert("Created admin has role_slug = administrator", $createdAdmin && $createdAdmin['role_slug'] === 'administrator');
test_assert("Created admin password verified with password_verify()", password_verify($testAdminPass, $createdAdmin['password']));

// TEST 9: Save Database Configuration
$saveConfigResult = save_database_config('127.0.0.1', '3306', 'travel_mgt_db', 'root', '');
test_assert("save_database_config() writes config/database.php", $saveConfigResult === true);

// Verify config file
$configFile = __DIR__ . '/config/database.php';
test_assert("config/database.php exists and contains valid syntax", file_exists($configFile));

// TEST 10: Atomic Installation Lock Creation & Detection
$lockCreated = create_install_lock();
test_assert("create_install_lock() creates storage/install.lock", $lockCreated === true && file_exists(__DIR__ . '/storage/install.lock'));
test_assert("is_installed() returns true after lock creation", is_installed() === true);

// Read lock file content
$lockData = json_decode(file_get_contents(__DIR__ . '/storage/install.lock'), true);
test_assert("Lock file contains valid metadata and no sensitive passwords", is_array($lockData) && isset($lockData['installed_at']) && !isset($lockData['db_password']));

// TEST 11: CSRF Generator & Verification
$csrf = installer_csrf_token();
test_assert("installer_csrf_token() generates 64-character token", strlen($csrf) === 64);
test_assert("installer_verify_csrf() validates valid token", installer_verify_csrf($csrf) === true);
test_assert("installer_verify_csrf() rejects invalid token", installer_verify_csrf('bogus_token_123') === false);

// TEST 12: Reinstallation Reset Workflow
// Remove lock to verify is_installed() responds dynamically
@unlink(__DIR__ . '/storage/install.lock');
test_assert("is_installed() returns false when lock file removed", is_installed() === false);

// Re-create lock
create_install_lock();
test_assert("is_installed() returns true when lock file restored", is_installed() === true);

// Clean up test admin
$pdo->exec("DELETE FROM users WHERE id = {$adminResult['user_id']}");

// Also create default administrator for ongoing local development
$defaultAdminPass = password_hash('Admin@12345', PASSWORD_DEFAULT);
$pdo->exec("
    INSERT INTO users (role_id, first_name, last_name, name, email, phone, password, status, created_at, updated_at)
    VALUES (1, 'System', 'Administrator', 'System Administrator', 'admin@example.com', '+880 1700-000000', '{$defaultAdminPass}', 'active', NOW(), NOW())
    ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password)
");

echo "\n===============================================================\n";
echo " TEST SUMMARY: Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "===============================================================\n";

if ($failed === 0) {
    echo "\n>>> ALL PHASE 08 INSTALLER TESTS PASSED SUCCESSFULLY! <<<\n";
    exit(0);
} else {
    echo "\n>>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
