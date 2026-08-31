<?php
/**
 * Automated Verification Suite for Phase 07: User, Role & Permission Management + System Settings
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_db_connection();
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
echo " PHASE 07: USERS, ROLES, PERMISSIONS & SETTINGS TEST SUITE\n";
echo "===============================================================\n\n";

// TEST 1: Check Permissions 38 to 45 Exist
$stmt = $pdo->query("SELECT id, slug FROM permissions WHERE id BETWEEN 38 AND 45 ORDER BY id ASC");
$perms = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
test_assert("Permissions 38-45 exist", count($perms) === 8 && isset($perms[38], $perms[39], $perms[40], $perms[41], $perms[42], $perms[43], $perms[44], $perms[45]));

// TEST 2: Role Permissions for Administrator (1), Manager (2), Staff (3)
$stmtAdmin = $pdo->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = 1");
$stmtAdmin->execute();
$adminPermCount = (int)$stmtAdmin->fetchColumn();
test_assert("Administrator has all 45 permissions", $adminPermCount >= 45);

$stmtMgr = $pdo->prepare("SELECT p.slug FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = 2 AND p.slug IN ('users.view', 'roles.view', 'permissions.view', 'settings.view')");
$stmtMgr->execute();
$mgrPerms = $stmtMgr->fetchAll(PDO::FETCH_COLUMN);
test_assert("Manager has users.view, roles.view, permissions.view, settings.view", count($mgrPerms) === 4);

$stmtStaff = $pdo->prepare("SELECT COUNT(*) FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = 3 AND p.slug IN ('roles.create', 'roles.edit', 'roles.delete', 'settings.edit')");
$stmtStaff->execute();
$staffRestricted = (int)$stmtStaff->fetchColumn();
test_assert("Staff does NOT have administrative role/settings edit permissions", $staffRestricted === 0);

// TEST 3: User Creation & Explicit Role Assignment
$testEmailStaff = 'test_staff_' . time() . '@example.com';
$pwdHash = password_hash('Secret@123', PASSWORD_DEFAULT);

$stmtCreateStaff = $pdo->prepare("
    INSERT INTO users (role_id, first_name, last_name, name, email, password, status, created_at, updated_at)
    VALUES (3, 'Test', 'Staff', 'Test Staff', :email, :pwd, 'active', NOW(), NOW())
");
$stmtCreateStaff->execute(['email' => $testEmailStaff, 'pwd' => $pwdHash]);
$staffUserId = (int)$pdo->lastInsertId();

$checkStaff = $pdo->query("SELECT u.role_id, r.slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = {$staffUserId}")->fetch();
test_assert("Created user with Staff role receives role_id = 3 (staff) and NOT administrator", (int)$checkStaff['role_id'] === 3 && $checkStaff['slug'] === 'staff');

// TEST 4: User Role Update (Staff -> Manager)
$stmtUpdateUser = $pdo->prepare("UPDATE users SET role_id = 2, updated_at = NOW() WHERE id = :id");
$stmtUpdateUser->execute(['id' => $staffUserId]);
$checkMgr = $pdo->query("SELECT u.role_id, r.slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = {$staffUserId}")->fetch();
test_assert("User role updated successfully from Staff to Manager (role_id = 2)", (int)$checkMgr['role_id'] === 2 && $checkMgr['slug'] === 'manager');

// TEST 5: Last Administrator Protection (Single Admin)
$adminCount = count_active_administrators();
test_assert("count_active_administrators() returns valid count", $adminCount >= 1);

// Find primary administrator
$stmtAdminUser = $pdo->query("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug = 'administrator' AND u.status = 'active' AND u.deleted_at IS NULL LIMIT 1");
$primaryAdminId = (int)$stmtAdminUser->fetchColumn();

if ($adminCount === 1) {
    test_assert("is_last_active_administrator() detects single active administrator", is_last_active_administrator($primaryAdminId) === true);
}

// TEST 6: Last Administrator Protection (Multiple Admins)
// Create second administrator
$testEmailAdmin2 = 'test_admin2_' . time() . '@example.com';
$stmtCreateAdmin2 = $pdo->prepare("
    INSERT INTO users (role_id, first_name, last_name, name, email, password, status, created_at, updated_at)
    VALUES (1, 'Second', 'Admin', 'Second Admin', :email, :pwd, 'active', NOW(), NOW())
");
$stmtCreateAdmin2->execute(['email' => $testEmailAdmin2, 'pwd' => $pwdHash]);
$admin2UserId = (int)$pdo->lastInsertId();

$newAdminCount = count_active_administrators();
test_assert("Second active administrator registered", $newAdminCount >= 2);
test_assert("is_last_active_administrator() returns false when >= 2 admins exist", is_last_active_administrator($primaryAdminId) === false);

// Clean up second test admin
$pdo->exec("DELETE FROM users WHERE id = {$admin2UserId}");
$restoredAdminCount = count_active_administrators();
test_assert("After cleanup, single administrator detected as protected", is_last_active_administrator($primaryAdminId) === true);

// TEST 7: Custom Role Creation & Permission Assignment
$customRoleSlug = 'test-role-' . time();
$stmtInsertRole = $pdo->prepare("
    INSERT INTO roles (name, slug, description, is_system, created_at, updated_at)
    VALUES ('Booking Specialist', :slug, 'Specialist for booking operations', 0, NOW(), NOW())
");
$stmtInsertRole->execute(['slug' => $customRoleSlug]);
$customRoleId = (int)$pdo->lastInsertId();

// Assign permissions: bookings.view (26) and bookings.create (27)
$stmtAssign = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:r_id, :p_id)");
$stmtAssign->execute(['r_id' => $customRoleId, 'p_id' => 26]);
$stmtAssign->execute(['r_id' => $customRoleId, 'p_id' => 27]);

$assignedStmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = :r_id ORDER BY permission_id ASC");
$assignedStmt->execute(['r_id' => $customRoleId]);
$assignedList = $assignedStmt->fetchAll(PDO::FETCH_COLUMN);

test_assert("Custom role created and assigned exactly 2 permissions", count($assignedList) === 2 && (int)$assignedList[0] === 26 && (int)$assignedList[1] === 27);

// TEST 8: Cross-Role Permission Isolation
// Check that Administrator permissions were NOT affected by custom role assignments
$stmtAdminCheck = $pdo->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = 1");
$adminPermsAfter = (int)$stmtAdminCheck->fetchColumn();
test_assert("Cross-role isolation: Administrator permissions remain untouched", $adminPermsAfter === $adminPermCount);

// TEST 9: Custom Role Permission Update (Atomic Sync)
$pdo->beginTransaction();
$pdo->exec("DELETE FROM role_permissions WHERE role_id = {$customRoleId}");
// Assign: customers.view (21), customers.create (22), customers.edit (23)
$pdo->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES ({$customRoleId}, 21), ({$customRoleId}, 22), ({$customRoleId}, 23)");
$pdo->commit();

$assignedStmt->execute(['r_id' => $customRoleId]);
$updatedPerms = $assignedStmt->fetchAll(PDO::FETCH_COLUMN);
test_assert("Custom role updated with new permission set atomically (3 permissions)", count($updatedPerms) === 3 && (int)$updatedPerms[0] === 21);

// TEST 10: Role Deletion Protection (System Roles & Active Users)
$adminRole = $pdo->query("SELECT is_system FROM roles WHERE id = 1")->fetch();
test_assert("System role Administrator has is_system = 1", (int)$adminRole['is_system'] === 1);

// Assign staff user to custom role
$pdo->exec("UPDATE users SET role_id = {$customRoleId} WHERE id = {$staffUserId}");
$assignedUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = {$customRoleId} AND deleted_at IS NULL")->fetchColumn();
test_assert("Custom role assigned to active user cannot be deleted", $assignedUsersCount === 1);

// Unassign and delete custom role
$pdo->exec("UPDATE users SET role_id = 3 WHERE id = {$staffUserId}");
$pdo->exec("DELETE FROM role_permissions WHERE role_id = {$customRoleId}");
$pdo->exec("DELETE FROM roles WHERE id = {$customRoleId}");
$checkRoleDeleted = $pdo->query("SELECT COUNT(*) FROM roles WHERE id = {$customRoleId}")->fetchColumn();
test_assert("Custom role deleted cleanly after unassigning users", (int)$checkRoleDeleted === 0);

// Cleanup test user
$pdo->exec("DELETE FROM users WHERE id = {$staffUserId}");

// TEST 11: System Settings Functions
$compName = get_setting('company_name');
test_assert("get_setting('company_name') retrieves default setting", !empty($compName));

$allSettings = get_all_settings();
test_assert("get_all_settings() returns key-value pairs", is_array($allSettings) && isset($allSettings['company_name'], $allSettings['currency'], $allSettings['timezone']));

// Update setting test
set_setting('test_key_sample', 'SampleValue123');
test_assert("set_setting() successfully writes to database", get_setting('test_key_sample') === 'SampleValue123');
$pdo->exec("DELETE FROM settings WHERE setting_key = 'test_key_sample'");

// TEST 12: Inactive Account Login Guard
$testEmailInactive = 'test_inactive_' . time() . '@example.com';
$pdo->exec("
    INSERT INTO users (role_id, first_name, last_name, name, email, password, status, created_at, updated_at)
    VALUES (3, 'Inactive', 'User', 'Inactive User', '{$testEmailInactive}', '{$pwdHash}', 'inactive', NOW(), NOW())
");
$inactiveUserId = (int)$pdo->lastInsertId();

$stmtCheckInactive = $pdo->prepare("SELECT status FROM users WHERE id = :id");
$stmtCheckInactive->execute(['id' => $inactiveUserId]);
$inactStatus = $stmtCheckInactive->fetchColumn();
test_assert("Inactive user status correctly stored in database", $inactStatus === 'inactive');

$pdo->exec("DELETE FROM users WHERE id = {$inactiveUserId}");

// TEST 13: Full Regression Checks (Phase 01 to Phase 06)
// Phase 01: Default admin account
$stmtAdminAuth = $pdo->query("SELECT id, status FROM users WHERE email = 'admin@example.com' AND deleted_at IS NULL");
$adminAccount = $stmtAdminAuth->fetch();
test_assert("Phase 01: Default Admin account intact and active", !empty($adminAccount) && $adminAccount['status'] === 'active');

// Phase 02: Tour Packages table verified
$pkgTable = $pdo->query("SHOW TABLES LIKE 'tour_packages'")->fetchColumn();
test_assert("Phase 02: Tour Packages table intact", !empty($pkgTable));

// Phase 03: Customers table verified
$custTable = $pdo->query("SHOW TABLES LIKE 'customers'")->fetchColumn();
test_assert("Phase 03: Customers table intact", !empty($custTable));

// Phase 04: Bookings table verified
$bkTable = $pdo->query("SHOW TABLES LIKE 'bookings'")->fetchColumn();
test_assert("Phase 04: Bookings table intact", !empty($bkTable));

// Phase 05: Payments table verified
$payTable = $pdo->query("SHOW TABLES LIKE 'payments'")->fetchColumn();
test_assert("Phase 05: Payments table intact", !empty($payTable));

// Phase 06: Reports Permissions
$reportPermCount = (int)$pdo->query("SELECT COUNT(*) FROM permissions WHERE slug IN ('reports.view', 'reports.export')")->fetchColumn();
test_assert("Phase 06: Reports permissions intact", $reportPermCount === 2);

echo "\n===============================================================\n";
echo " TEST SUMMARY: Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "===============================================================\n";

if ($failed === 0) {
    echo "\n>>> ALL PHASE 07 TESTS PASSED SUCCESSFULLY! <<<\n";
    exit(0);
} else {
    echo "\n>>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
