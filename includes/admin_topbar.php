<?php
/**
 * Admin Topbar Navigation
 * Tour & Travel Booking Management System
 */

$user = current_user();
$userName = $user['name'] ?? 'User';
$userRole = $user['role_name'] ?? 'Staff';
$userAvatarUrl = get_avatar_url($user['avatar'] ?? null);
$userInitials = get_user_initials($userName);
?>
<header id="admin-topbar">
    <!-- Topbar Left: Toggle & Page Title -->
    <div class="topbar-left">
        <button type="button" id="sidebar-toggle" class="sidebar-toggle-btn" aria-label="Toggle Sidebar" title="Toggle Navigation Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <h1 class="page-title"><?= isset($pageTitle) ? e($pageTitle) : 'Dashboard'; ?></h1>
    </div>

    <!-- Topbar Right: Notifications & User Profile Dropdown -->
    <div class="topbar-right">
        <!-- Notification Placeholder -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm p-2 rounded-circle border-0 text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <i class="bi bi-bell fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 260px; font-size: 0.875rem;">
                <li class="dropdown-header fw-bold text-dark border-bottom pb-2">Notifications</li>
                <li class="p-3 text-center text-muted">No new notifications</li>
            </ul>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="user-profile-btn dropdown-toggle" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if ($userAvatarUrl): ?>
                    <img src="<?= e($userAvatarUrl); ?>" alt="<?= e($userName); ?>" class="avatar-circle">
                <?php else: ?>
                    <span class="avatar-circle"><?= e($userInitials); ?></span>
                <?php endif; ?>
                
                <div class="user-meta-info">
                    <span class="user-meta-name"><?= e($userName); ?></span>
                    <span class="user-meta-role badge bg-secondary text-light fw-normal py-1 px-2 mt-1" style="font-size: 0.7rem;"><?= e($userRole); ?></span>
                </div>
            </button>
            
            <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-2" aria-labelledby="userMenuDropdown" style="min-width: 220px; font-size: 0.9rem;">
                <li class="px-3 py-2 border-bottom">
                    <div class="fw-bold text-dark"><?= e($userName); ?></div>
                    <div class="text-muted small"><?= e($user['email'] ?? ''); ?></div>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="<?= url('modules/profile/index.php'); ?>">
                        <i class="bi bi-person me-2 text-primary"></i> My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="<?= url('modules/profile/change-password.php'); ?>">
                        <i class="bi bi-key me-2 text-warning"></i> Change Password
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2 text-danger" href="<?= url('auth/logout.php'); ?>">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
