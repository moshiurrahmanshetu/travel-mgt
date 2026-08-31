<?php
/**
 * Installer Step 5 View: Installation Complete
 * Tour & Travel Booking Management System
 */

$adminEmail = $_SESSION['completed_admin_email'] ?? 'admin@example.com';
?>

<div class="text-center py-4">
    <div class="mb-3">
        <span class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle" style="width: 64px; height: 64px; font-size: 32px;">
            <i class="bi bi-check-lg"></i>
        </span>
    </div>

    <h3 class="fs-4 fw-bold text-dark mb-2">Installation Completed Successfully!</h3>
    <p class="text-muted small mb-4 mx-auto" style="max-width: 500px;">
        Your Tour & Travel Booking Management System is now fully configured and ready for production use.
    </p>

    <div class="card bg-light border text-start p-3 mb-4 mx-auto" style="max-width: 500px;">
        <div class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted small">Administrator Email:</span>
            <strong class="text-dark small"><?= htmlspecialchars($adminEmail); ?></strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted small">Installation Status:</span>
            <span class="badge bg-success"><i class="bi bi-lock-fill me-1"></i> Locked & Protected</span>
        </div>
        <div class="d-flex justify-content-between py-2">
            <span class="text-muted small">Lock File:</span>
            <code class="small">storage/install.lock</code>
        </div>
    </div>

    <div class="alert alert-info text-start small mb-4 mx-auto" style="max-width: 500px;">
        <i class="bi bi-info-circle-fill me-1"></i> <strong>Security Notice:</strong> The installer has been permanently locked to prevent unauthorized changes. You may now proceed to log in to your management dashboard.
    </div>

    <div class="d-grid gap-2 mx-auto" style="max-width: 320px;">
        <a href="../auth/login.php" class="btn btn-primary btn-lg fs-6">
            <i class="bi bi-box-arrow-in-right me-1"></i> Go to Admin Login
        </a>
    </div>
</div>
