<?php
/**
 * Admin Sidebar Navigation
 * Tour & Travel Booking Management System
 */

$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$isDashboard = strpos($currentScript, '/modules/dashboard/') !== false;
$isProfile = strpos($currentScript, '/modules/profile/') !== false;
$isUsers = strpos($currentScript, '/modules/users/') !== false;
$isSettings = strpos($currentScript, '/modules/settings/') !== false;
$isTourPackages = strpos($currentScript, '/modules/tours/index.php') !== false || strpos($currentScript, '/modules/tours/create.php') !== false || strpos($currentScript, '/modules/tours/edit.php') !== false || strpos($currentScript, '/modules/tours/view.php') !== false;
$isCategories = strpos($currentScript, '/modules/tours/categories.php') !== false;
$isDestinations = strpos($currentScript, '/modules/tours/destinations.php') !== false;
$isCustomers = strpos($currentScript, '/modules/customers/') !== false;
$isBookings = strpos($currentScript, '/modules/bookings/') !== false;
$statusParam = $_GET['status'] ?? '';
$isBookingsAll = $isBookings && empty($statusParam) && (strpos($currentScript, 'create.php') === false && strpos($currentScript, 'edit.php') === false && strpos($currentScript, 'view.php') === false);
$isBookingsPending = $isBookings && $statusParam === 'pending';
$isBookingsConfirmed = $isBookings && $statusParam === 'confirmed';
$isBookingsCancelled = $isBookings && $statusParam === 'cancelled';
$isPayments = strpos($currentScript, '/modules/payments/') !== false;
$isReports = strpos($currentScript, '/modules/reports/') !== false;
$isReportsOverview = $isReports && (strpos($currentScript, 'index.php') !== false);
$isReportsBookings = $isReports && (strpos($currentScript, 'bookings.php') !== false);
$isReportsPayments = $isReports && (strpos($currentScript, 'payments.php') !== false);
$isReportsTours = $isReports && (strpos($currentScript, 'tours.php') !== false);
$isReportsCustomers = $isReports && (strpos($currentScript, 'customers.php') !== false);
$isRoles = strpos($currentScript, '/modules/roles/') !== false;
?>
<!-- Sidebar Navigation -->
<aside id="admin-sidebar">
    <!-- Brand Logo -->
    <a href="<?= url('modules/dashboard/index.php'); ?>" class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="bi bi-compass"></i>
        </div>
        <div class="sidebar-brand-text">
            <span><?= e(APP_SHORT_NAME); ?></span>
        </div>
    </a>

    <!-- Navigation Menu Items -->
    <div class="sidebar-nav-container">
        <ul class="nav flex-column mb-0">
            <!-- Main Section -->
            <li class="nav-item">
                <a href="<?= url('modules/dashboard/index.php'); ?>" class="sidebar-nav-link <?= $isDashboard ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 nav-icon"></i>
                    <span class="nav-link-text">Dashboard</span>
                </a>
            </li>

            <!-- TOURS Section -->
            <li class="sidebar-header">
                <span>Tours</span>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/tours/index.php'); ?>" class="sidebar-nav-link <?= $isTourPackages ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam nav-icon"></i>
                    <span class="nav-link-text">Tour Packages</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/tours/categories.php'); ?>" class="sidebar-nav-link <?= $isCategories ? 'active' : ''; ?>">
                    <i class="bi bi-tags nav-icon"></i>
                    <span class="nav-link-text">Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/tours/destinations.php'); ?>" class="sidebar-nav-link <?= $isDestinations ? 'active' : ''; ?>">
                    <i class="bi bi-geo-alt nav-icon"></i>
                    <span class="nav-link-text">Destinations</span>
                </a>
            </li>

            <!-- CUSTOMERS Section -->
            <li class="sidebar-header">
                <span>Customers</span>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/customers/index.php'); ?>" class="sidebar-nav-link <?= $isCustomers ? 'active' : ''; ?>">
                    <i class="bi bi-people nav-icon"></i>
                    <span class="nav-link-text">Customers</span>
                </a>
            </li>

            <!-- BOOKINGS Section -->
            <li class="sidebar-header">
                <span>Bookings</span>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/bookings/index.php'); ?>" class="sidebar-nav-link <?= $isBookingsAll ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check nav-icon"></i>
                    <span class="nav-link-text">All Bookings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/bookings/index.php?status=pending'); ?>" class="sidebar-nav-link <?= $isBookingsPending ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history nav-icon"></i>
                    <span class="nav-link-text">Pending</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/bookings/index.php?status=confirmed'); ?>" class="sidebar-nav-link <?= $isBookingsConfirmed ? 'active' : ''; ?>">
                    <i class="bi bi-check2-circle nav-icon"></i>
                    <span class="nav-link-text">Confirmed</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/bookings/index.php?status=cancelled'); ?>" class="sidebar-nav-link <?= $isBookingsCancelled ? 'active' : ''; ?>">
                    <i class="bi bi-x-circle nav-icon"></i>
                    <span class="nav-link-text">Cancelled</span>
                </a>
            </li>

            <!-- PAYMENTS Section -->
            <li class="sidebar-header">
                <span>Payments</span>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/payments/index.php'); ?>" class="sidebar-nav-link <?= $isPayments ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card nav-icon"></i>
                    <span class="nav-link-text">Payments</span>
                </a>
            </li>

            <!-- REPORTS Section -->
            <li class="sidebar-header">
                <span>Reports & Analytics</span>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/reports/index.php'); ?>" class="sidebar-nav-link <?= $isReportsOverview ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer nav-icon"></i>
                    <span class="nav-link-text">Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/reports/bookings.php'); ?>" class="sidebar-nav-link <?= $isReportsBookings ? 'active' : ''; ?>">
                    <i class="bi bi-journal-text nav-icon"></i>
                    <span class="nav-link-text">Booking Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/reports/payments.php'); ?>" class="sidebar-nav-link <?= $isReportsPayments ? 'active' : ''; ?>">
                    <i class="bi bi-cash-stack nav-icon"></i>
                    <span class="nav-link-text">Payment Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/reports/tours.php'); ?>" class="sidebar-nav-link <?= $isReportsTours ? 'active' : ''; ?>">
                    <i class="bi bi-compass nav-icon"></i>
                    <span class="nav-link-text">Tour Performance</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/reports/customers.php'); ?>" class="sidebar-nav-link <?= $isReportsCustomers ? 'active' : ''; ?>">
                    <i class="bi bi-people nav-icon"></i>
                    <span class="nav-link-text">Customer Summary</span>
                </a>
            </li>

            <!-- USER MANAGEMENT Section -->
            <?php if (has_permission('users.view') || has_permission('roles.view')): ?>
                <li class="sidebar-header">
                    <span>User Management</span>
                </li>
                <?php if (has_permission('users.view')): ?>
                    <li class="nav-item">
                        <a href="<?= url('modules/users/index.php'); ?>" class="sidebar-nav-link <?= $isUsers ? 'active' : ''; ?>">
                            <i class="bi bi-people nav-icon"></i>
                            <span class="nav-link-text">Users</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (has_permission('roles.view')): ?>
                    <li class="nav-item">
                        <a href="<?= url('modules/roles/index.php'); ?>" class="sidebar-nav-link <?= $isRoles ? 'active' : ''; ?>">
                            <i class="bi bi-shield-lock nav-icon"></i>
                            <span class="nav-link-text">Roles & Permissions</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- SYSTEM Section -->
            <?php if (has_permission('settings.view')): ?>
                <li class="sidebar-header">
                    <span>System</span>
                </li>
                <li class="nav-item">
                    <a href="<?= url('modules/settings/index.php'); ?>" class="sidebar-nav-link <?= $isSettings ? 'active' : ''; ?>">
                        <i class="bi bi-gear nav-icon"></i>
                        <span class="nav-link-text">Settings</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</aside>
