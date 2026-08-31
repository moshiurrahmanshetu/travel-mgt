<?php
/**
 * Login Page
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// If not yet installed, redirect to installer
if (!is_installed()) {
    redirect('install/');
}

// If user is already logged in, redirect straight to dashboard
if (is_logged_in()) {
    redirect('modules/dashboard/index.php');
}

$pageTitle = 'Sign In';
$bodyClass = 'auth-body';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-card">
    <!-- Header -->
    <div class="auth-header">
        <div class="auth-logo-icon">
            <i class="bi bi-compass"></i>
        </div>
        <h2 class="auth-title"><?= e(APP_SHORT_NAME); ?></h2>
        <p class="auth-subtitle">Tour & Travel Management System</p>
    </div>

    <!-- Body -->
    <div class="auth-body-content">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <form action="<?= url('auth/process-login.php'); ?>" method="POST" autocomplete="off">
            <?= csrf_field(); ?>

            <!-- Email Field -->
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        placeholder="name@example.com" 
                        value="<?= e(old('email')); ?>" 
                        required 
                        autofocus
                    >
                </div>
            </div>

            <!-- Password Field -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                    >
                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="mb-4 d-flex align-items-center justify-content-between">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me" value="1">
                    <label class="form-check-label text-muted small" for="remember_me">
                        Remember me
                    </label>
                </div>
                <span class="text-muted small">v<?= e(APP_VERSION); ?></span>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>
    </div>

    <!-- Footer -->
    <div class="auth-footer">
        &copy; <?= date('Y'); ?> <?= e(APP_NAME); ?>
    </div>
</div>

<?php 
// Clear flashed input after form is rendered
clear_old_input();
require_once __DIR__ . '/../includes/footer.php'; 
?>
