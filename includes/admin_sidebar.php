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
                <a href="javascript:void(0)" class="sidebar-nav-link disabled" title="Available in Phase 04">
                    <i class="bi bi-calendar-check nav-icon"></i>
                    <span class="nav-link-text">All Bookings</span>
                    <span class="badge badge-coming-soon ms-auto">Soon</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" class="sidebar-nav-link disabled" title="Available in Phase 04">
                    <i class="bi bi-clock-history nav-icon"></i>
                    <span class="nav-link-text">Pending</span>
                    <span class="badge badge-coming-soon ms-auto">Soon</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" class="sidebar-nav-link disabled" title="Available in Phase 04">
                    <i class="bi bi-check2-circle nav-icon"></i>
                    <span class="nav-link-text">Confirmed</span>
                    <span class="badge badge-coming-soon ms-auto">Soon</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" class="sidebar-nav-link disabled" title="Available in Phase 04">
                    <i class="bi bi-x-circle nav-icon"></i>
                    <span class="nav-link-text">Cancelled</span>
                    <span class="badge badge-coming-soon ms-auto">Soon</span>
                </a>
            </li>

            <!-- PAYMENTS Section -->
            <li class="sidebar-header">
                <span>Payments</span>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" class="sidebar-nav-link disabled" title="Available in Phase 05">
                    <i class="bi bi-credit-card nav-icon"></i>
                    <span class="nav-link-text">Payments</span>
                    <span class="badge badge-coming-soon ms-auto">Soon</span>
                </a>
            </li>

            <!-- REPORTS Section -->
            <li class="sidebar-header">
                <span>Reports</span>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" class="sidebar-nav-link disabled" title="Available in Phase 06">
                    <i class="bi bi-bar-chart-line nav-icon"></i>
                    <span class="nav-link-text">Reports</span>
                    <span class="badge badge-coming-soon ms-auto">Soon</span>
                </a>
            </li>

            <!-- SYSTEM Section -->
            <li class="sidebar-header">
                <span>System</span>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/users/index.php'); ?>" class="sidebar-nav-link <?= $isUsers ? 'active' : ''; ?>">
                    <i class="bi bi-shield-lock nav-icon"></i>
                    <span class="nav-link-text">Users & Roles</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" class="sidebar-nav-link disabled" title="Available in Phase 06">
                    <i class="bi bi-journal-text nav-icon"></i>
                    <span class="nav-link-text">Activity Logs</span>
                    <span class="badge badge-coming-soon ms-auto">Soon</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('modules/settings/index.php'); ?>" class="sidebar-nav-link <?= $isSettings ? 'active' : ''; ?>">
                    <i class="bi bi-gear nav-icon"></i>
                    <span class="nav-link-text">Settings</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
