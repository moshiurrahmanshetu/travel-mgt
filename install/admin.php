<?php
/**
 * Installer Step 4 View: Primary Administrator Account
 * Tour & Travel Booking Management System
 */

$oldAdmin = $_SESSION['admin_input'] ?? [];
$adminName  = $oldAdmin['admin_name'] ?? 'System Administrator';
$adminEmail = $oldAdmin['admin_email'] ?? 'admin@example.com';
?>

<div class="mb-4">
    <h3 class="fs-5 fw-bold text-dark mb-1">Step 4: Create Administrator Account</h3>
    <p class="text-muted small mb-0">Set up the super administrator account for initial access to your CMS control panel.</p>
</div>

<form action="index.php?step=4" method="POST" id="adminForm">
    <input type="hidden" name="_csrf_token" value="<?= installer_csrf_token(); ?>">
    <input type="hidden" name="step_action" value="create_administrator_account">

    <div class="row g-3 mb-3">
        <!-- Administrator Full Name -->
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input 
                type="text" 
                class="form-control" 
                name="admin_name" 
                id="admin_name" 
                required 
                placeholder="e.g. Moshiur Rahman" 
                value="<?= htmlspecialchars($adminName); ?>"
            >
        </div>

        <!-- Administrator Email -->
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
            <input 
                type="email" 
                class="form-control" 
                name="admin_email" 
                id="admin_email" 
                required 
                placeholder="admin@example.com" 
                value="<?= htmlspecialchars($adminEmail); ?>"
            >
            <small class="text-muted">Used for dashboard login.</small>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Password -->
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
            <input 
                type="password" 
                class="form-control" 
                name="admin_password" 
                id="admin_password" 
                required 
                minlength="8" 
                placeholder="Minimum 8 characters"
            >
            <small class="text-muted">Must be at least 8 characters long.</small>
        </div>

        <!-- Confirm Password -->
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
            <input 
                type="password" 
                class="form-control" 
                name="admin_confirm_password" 
                id="admin_confirm_password" 
                required 
                minlength="8" 
                placeholder="Retype password"
            >
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
        <a href="index.php?step=3" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button type="submit" class="btn btn-primary px-4" id="btnSubmitAdmin">
            <i class="bi bi-person-check me-1"></i> Finalize Installation
        </button>
    </div>
</form>
