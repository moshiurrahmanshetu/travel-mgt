<?php
/**
 * Change Password Page & Handler
 * Tour & Travel Booking Management System
 */

$pageTitle = 'Change Password';

require_once __DIR__ . '/../../includes/admin_header.php';
require_once __DIR__ . '/../../includes/admin_sidebar.php';

// Handle POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token()) {
        set_flash('error', 'Security token expired or invalid. Please try again.');
        redirect('modules/profile/change-password.php');
    }

    $userId = current_user_id();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 2. Validation
    $errors = [];

    if (empty($currentPassword)) {
        $errors[] = 'Current password is required.';
    }

    if (empty($newPassword)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (!empty($errors)) {
        foreach ($errors as $err) {
            set_flash('error', $err);
        }
        redirect('modules/profile/change-password.php');
    }

    try {
        $pdo = get_db_connection();

        // 3. Fetch current password hash from DB
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $existingHash = $stmt->fetchColumn();

        if (!$existingHash || !password_verify($currentPassword, $existingHash)) {
            set_flash('error', 'Your current password does not match our records.');
            redirect('modules/profile/change-password.php');
        }

        // 4. Hash new password and update in DB
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
        $updateStmt->execute([
            'password' => $newHash,
            'id'       => $userId
        ]);

        set_flash('success', 'Your password has been changed successfully.');
        redirect('modules/profile/change-password.php');

    } catch (PDOException $e) {
        error_log('Change Password Error: ' . $e->getMessage());
        set_flash('error', 'Failed to change password due to a system error.');
        redirect('modules/profile/change-password.php');
    }
}
?>

<!-- Main Content Area -->
<main id="admin-main">
    <?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>

    <div class="admin-content-body">
        <!-- Flash Messages -->
        <?= display_flash(); ?>

        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="bi bi-key-fill me-2 text-primary"></i> Change Account Password
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <form action="<?= url('modules/profile/change-password.php'); ?>" method="POST" autocomplete="off">
                            <?= csrf_field(); ?>

                            <!-- Current Password -->
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="current_password" 
                                        name="current_password" 
                                        placeholder="Enter your current password" 
                                        required
                                    >
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <hr class="my-4 text-muted">

                            <!-- New Password -->
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="new_password" 
                                        name="new_password" 
                                        placeholder="Minimum 8 characters" 
                                        required
                                    >
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text small text-muted">Must be at least 8 characters long.</div>
                            </div>

                            <!-- Confirm New Password -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        placeholder="Re-enter new password" 
                                        required
                                    >
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="<?= url('modules/profile/index.php'); ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Back to Profile
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2-circle me-1"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
