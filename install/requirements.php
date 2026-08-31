<?php
/**
 * Installer Step 1 View: Server Requirements
 * Tour & Travel Booking Management System
 */

$reqData = check_server_requirements();
$requirements = $reqData['requirements'];
$allPassed = $reqData['all_passed'];
?>

<div class="mb-4">
    <h3 class="fs-5 fw-bold text-dark mb-1">Step 1: Check Server Requirements</h3>
    <p class="text-muted small mb-0">Verify that your server environment satisfies the minimum prerequisites to run the application.</p>
</div>

<div class="table-responsive border rounded mb-4">
    <table class="table req-table align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Component</th>
                <th>Required</th>
                <th>Current Server</th>
                <th class="text-end">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requirements as $key => $req): ?>
                <tr>
                    <td class="fw-semibold text-dark">
                        <?= htmlspecialchars($req['name']); ?>
                    </td>
                    <td class="text-muted small">
                        <?= htmlspecialchars($req['required']); ?>
                    </td>
                    <td class="small">
                        <code><?= htmlspecialchars($req['current']); ?></code>
                    </td>
                    <td class="text-end">
                        <?php if ($req['passed']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> PASS</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> FAIL</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!$allPassed): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
        <div>
            <strong>Requirements Unmet:</strong> Your server environment does not satisfy all mandatory prerequisites. Please enable the missing PHP extensions or make the specified directories writable before continuing.
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 flex-shrink-0"></i>
        <div>
            <strong>Great news!</strong> Your server satisfies all prerequisites for hosting the Tour & Travel Booking Management System.
        </div>
    </div>
<?php endif; ?>

<form action="index.php?step=2" method="POST">
    <input type="hidden" name="_csrf_token" value="<?= installer_csrf_token(); ?>">
    <input type="hidden" name="step_action" value="requirements_confirmed">

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary px-4" <?= !$allPassed ? 'disabled' : ''; ?>>
            Continue to Database Setup <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</form>
