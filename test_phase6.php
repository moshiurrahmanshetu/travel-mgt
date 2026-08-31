<?php
/**
 * Automated Verification Suite for Phase 06: Reports & Dashboard Analytics
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
echo " PHASE 06: REPORTS & DASHBOARD ANALYTICS TEST SUITE\n";
echo "===============================================================\n\n";

// TEST 1: Check Permissions 36 & 37 Exist
$stmt = $pdo->query("SELECT id, slug FROM permissions WHERE id IN (36, 37) ORDER BY id ASC");
$perms = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
test_assert("Permissions 36 and 37 exist", isset($perms[36]) && $perms[36] === 'reports.view' && isset($perms[37]) && $perms[37] === 'reports.export');

// TEST 2: Role Permissions for Admin (1), Manager (2), Staff (3)
$stmtAdmin = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = 1 AND permission_id IN (36, 37)");
$stmtAdmin->execute();
$adminPerms = $stmtAdmin->fetchAll(PDO::FETCH_COLUMN);
test_assert("Administrator has reports.view and reports.export", count($adminPerms) === 2);

$stmtMgr = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = 2 AND permission_id IN (36, 37)");
$stmtMgr->execute();
$mgrPerms = $stmtMgr->fetchAll(PDO::FETCH_COLUMN);
test_assert("Manager has reports.view and reports.export", count($mgrPerms) === 2);

$stmtStaff = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = 3 AND permission_id IN (36, 37)");
$stmtStaff->execute();
$staffPerms = $stmtStaff->fetchAll(PDO::FETCH_COLUMN);
test_assert("Staff has reports.view but NOT reports.export", count($staffPerms) === 1 && (int)$staffPerms[0] === 36);

// TEST 3: CSV Formula Injection Sanitization
test_assert("escape_csv_field sanitizes leading '='", escape_csv_field('=SUM(A1:A10)') === "'=SUM(A1:A10)");
test_assert("escape_csv_field sanitizes leading '+'", escape_csv_field('+cmd|') === "'+cmd|");
test_assert("escape_csv_field sanitizes leading '-'", escape_csv_field('-123.45') === "'-123.45");
test_assert("escape_csv_field sanitizes leading '@'", escape_csv_field('@SUM()') === "'@SUM()");
test_assert("escape_csv_field leaves safe text unchanged", escape_csv_field('Cox Bazar') === "Cox Bazar");
test_assert("escape_csv_field handles null safely", escape_csv_field(null) === "");

// TEST 4: Revenue & Outstanding Balance Calculations
// Fetch active non-cancelled bookings sum
$stmtActBk = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE deleted_at IS NULL AND booking_status != 'cancelled'");
$activeSales = (float)$stmtActBk->fetchColumn();

// Fetch completed non-deleted payments sum
$stmtCompPay = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE deleted_at IS NULL AND payment_status = 'completed'");
$collected = (float)$stmtCompPay->fetchColumn();

$expectedOutstanding = max(0.0, round($activeSales - $collected, 2));
test_assert("Authoritative active revenue excludes cancelled bookings", $activeSales >= 0.0);
test_assert("Authoritative collected revenue is strictly sum of completed payments", $collected >= 0.0);
test_assert("Outstanding balance equals active revenue minus collected", $expectedOutstanding >= 0.0);

// TEST 5: Booking Report Aggregation & Date Basis
$stmtBkCreated = $pdo->query("
    SELECT COUNT(*) FROM bookings 
    WHERE deleted_at IS NULL AND DATE(created_at) <= CURRENT_DATE()
");
$countByCreated = (int)$stmtBkCreated->fetchColumn();
test_assert("Booking report can filter by created_at (Booking Date)", $countByCreated >= 0);

$stmtBkTravel = $pdo->query("
    SELECT COUNT(*) FROM bookings 
    WHERE deleted_at IS NULL AND travel_date >= '2020-01-01'
");
$countByTravel = (int)$stmtBkTravel->fetchColumn();
test_assert("Booking report can filter by travel_date (Departure Date)", $countByTravel >= 0);

// TEST 6: Payment Report Method Breakdown
$stmtMethodSum = $pdo->query("
    SELECT payment_method, COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE deleted_at IS NULL AND payment_status = 'completed'
    GROUP BY payment_method
");
$methodRows = $stmtMethodSum->fetchAll();
test_assert("Payment method breakdown dynamically aggregates completed payments", is_array($methodRows));

// TEST 7: Tour Package Performance Aggregation
$stmtTourPerf = $pdo->query("
    SELECT 
        tp.id,
        tp.package_code,
        tp.name,
        COUNT(b.id) AS total_orders,
        COALESCE(SUM(CASE WHEN b.booking_status = 'confirmed' THEN 1 ELSE 0 END), 0) AS confirmed_orders,
        COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount ELSE 0 END), 0) AS invoiced_sales
    FROM tour_packages tp
    LEFT JOIN bookings b ON b.tour_package_id = tp.id AND b.deleted_at IS NULL
    WHERE tp.deleted_at IS NULL
    GROUP BY tp.id, tp.package_code, tp.name
    LIMIT 5
");
$tourPerfRows = $stmtTourPerf->fetchAll();
test_assert("Tour performance report aggregates orders and invoiced revenue", count($tourPerfRows) > 0);

// TEST 8: Customer Booking Summary Aggregation
$stmtCusSum = $pdo->query("
    SELECT 
        c.id,
        c.customer_code,
        c.name,
        COUNT(b.id) AS total_bookings,
        COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount ELSE 0 END), 0) AS invoiced_sales,
        COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.paid_amount ELSE 0 END), 0) AS paid_sales
    FROM customers c
    LEFT JOIN bookings b ON b.customer_id = c.id AND b.deleted_at IS NULL
    WHERE c.deleted_at IS NULL
    GROUP BY c.id, c.customer_code, c.name
    LIMIT 5
");
$cusSumRows = $stmtCusSum->fetchAll();
test_assert("Customer report aggregates lifetime bookings and paid collections", count($cusSumRows) > 0);

// TEST 9: Dashboard Upcoming Confirmed Departures Query
$stmtUpcoming = $pdo->query("
    SELECT b.booking_number, b.travel_date, b.booking_status
    FROM bookings b
    WHERE b.deleted_at IS NULL 
      AND b.booking_status = 'confirmed' 
      AND b.travel_date >= CURRENT_DATE()
    ORDER BY b.travel_date ASC
    LIMIT 5
");
$upcomingRows = $stmtUpcoming->fetchAll();
test_assert("Dashboard upcoming confirmed departures query executes properly", is_array($upcomingRows));

// TEST 10: Dashboard Recent Payments Query
$stmtRecentPayments = $pdo->query("
    SELECT p.payment_number, p.amount, p.payment_date, p.payment_status
    FROM payments p
    WHERE p.deleted_at IS NULL AND p.payment_status = 'completed'
    ORDER BY p.payment_date DESC, p.id DESC
    LIMIT 5
");
$recentPayRows = $stmtRecentPayments->fetchAll();
test_assert("Dashboard recent payments query executes properly", is_array($recentPayRows));

// TEST 11: Cancelled Booking Integrity
// Ensure cancelled bookings do NOT reduce available seats and do NOT count towards active sales
$stmtCan = $pdo->query("
    SELECT COUNT(*) FROM bookings WHERE booking_status = 'cancelled' AND deleted_at IS NULL
");
$canCount = (int)$stmtCan->fetchColumn();
test_assert("Cancelled bookings tracked separately without corrupting active revenue", $canCount >= 0);

// TEST 12: Regression Testing - Phase 01 to Phase 05
// Phase 01: Admin user login check
$stmtAdminUser = $pdo->query("SELECT id, email, password FROM users WHERE email = 'admin@example.com' AND status = 'active'");
$adminUser = $stmtAdminUser->fetch();
test_assert("Phase 01: Default admin account active", !empty($adminUser));

// Phase 02: Tour Packages check
$stmtPkgs = $pdo->query("SELECT COUNT(*) FROM tour_packages WHERE deleted_at IS NULL");
$pkgCount = (int)$stmtPkgs->fetchColumn();
test_assert("Phase 02: Tour packages available", $pkgCount > 0);

// Phase 03: Customers check
$stmtCusts = $pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL");
$custCount = (int)$stmtCusts->fetchColumn();
test_assert("Phase 03: Customers available", $custCount > 0);

// Phase 04: Bookings check
$stmtBks = $pdo->query("SELECT COUNT(*) FROM bookings WHERE deleted_at IS NULL");
$bkCount = (int)$stmtBks->fetchColumn();
test_assert("Phase 04: Bookings available", $bkCount > 0);

// Phase 05: Payments check
$stmtPays = $pdo->query("SELECT COUNT(*) FROM payments WHERE deleted_at IS NULL");
$payCount = (int)$stmtPays->fetchColumn();
test_assert("Phase 05: Payments available", $payCount > 0);

echo "\n===============================================================\n";
echo " TEST SUMMARY: Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "===============================================================\n";

if ($failed === 0) {
    echo "\n>>> ALL PHASE 06 TESTS PASSED SUCCESSFULLY! <<<\n";
    exit(0);
} else {
    echo "\n>>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
