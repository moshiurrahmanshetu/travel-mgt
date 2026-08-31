<?php
/**
 * Installer Step 2 View: Database Credentials
 * Tour & Travel Booking Management System
 */

$savedConfig = $_SESSION['db_config'] ?? [];
$dbHost = $savedConfig['db_host'] ?? '127.0.0.1';
$dbPort = $savedConfig['db_port'] ?? '3306';
$dbName = $savedConfig['db_name'] ?? 'travel_mgt_db';
$dbUser = $savedConfig['db_user'] ?? 'root';
$dbPass = $savedConfig['db_pass'] ?? '';
$createDb = !empty($savedConfig['create_db']);
?>

<div class="mb-4">
    <h3 class="fs-5 fw-bold text-dark mb-1">Step 2: Database Configuration</h3>
    <p class="text-muted small mb-0">Enter your MySQL / MariaDB database server connection credentials.</p>
</div>

<!-- AJAX Test Alert Container -->
<div id="testConnAlert" class="d-none alert mb-4" role="alert"></div>

<form action="index.php?step=2" method="POST" id="dbConfigForm">
    <input type="hidden" name="_csrf_token" value="<?= installer_csrf_token(); ?>">
    <input type="hidden" name="step_action" value="save_database_credentials">

    <div class="row g-3 mb-3">
        <!-- Database Host -->
        <div class="col-12 col-md-8">
            <label class="form-label fw-semibold">Database Host <span class="text-danger">*</span></label>
            <input 
                type="text" 
                class="form-control" 
                name="db_host" 
                id="db_host" 
                required 
                placeholder="127.0.0.1 or localhost" 
                value="<?= htmlspecialchars($dbHost); ?>"
            >
            <small class="text-muted">Usually <code>127.0.0.1</code> or <code>localhost</code>.</small>
        </div>

        <!-- Database Port -->
        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Port <span class="text-danger">*</span></label>
            <input 
                type="number" 
                class="form-control" 
                name="db_port" 
                id="db_port" 
                required 
                placeholder="3306" 
                value="<?= htmlspecialchars($dbPort); ?>"
            >
            <small class="text-muted">Default is <code>3306</code>.</small>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- Database Name -->
        <div class="col-12">
            <label class="form-label fw-semibold">Database Name <span class="text-danger">*</span></label>
            <input 
                type="text" 
                class="form-control" 
                name="db_name" 
                id="db_name" 
                required 
                placeholder="e.g. travel_mgt_db" 
                value="<?= htmlspecialchars($dbName); ?>"
            >
            <small class="text-muted">Database name created in cPanel/phpMyAdmin.</small>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- Database Username -->
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Database Username <span class="text-danger">*</span></label>
            <input 
                type="text" 
                class="form-control" 
                name="db_user" 
                id="db_user" 
                required 
                placeholder="e.g. root" 
                value="<?= htmlspecialchars($dbUser); ?>"
            >
        </div>

        <!-- Database Password -->
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Database Password</label>
            <input 
                type="password" 
                class="form-control" 
                name="db_pass" 
                id="db_pass" 
                placeholder="Enter password (leave blank if empty)" 
                value="<?= htmlspecialchars($dbPass); ?>"
            >
        </div>
    </div>

    <!-- Optional Create Database Checkbox -->
    <div class="form-check mb-4">
        <input 
            class="form-check-input" 
            type="checkbox" 
            name="create_db" 
            id="create_db" 
            value="1"
            <?= $createDb ? 'checked' : ''; ?>
        >
        <label class="form-check-label small text-dark" for="create_db">
            Attempt to create database automatically if it does not exist (requires database user permissions)
        </label>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
        <a href="index.php?step=1" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="btnTestConn">
                <i class="bi bi-broadcast me-1"></i> Test Connection
            </button>
            <button type="submit" class="btn btn-primary px-4">
                Continue to Database Import <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</form>

<!-- AJAX Test Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnTest = document.getElementById('btnTestConn');
    const alertBox = document.getElementById('testConnAlert');

    if (btnTest && alertBox) {
        btnTest.addEventListener('click', function() {
            btnTest.disabled = true;
            btnTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing...';
            alertBox.className = 'd-none alert mb-4';

            const formData = new FormData();
            formData.append('_csrf_token', '<?= installer_csrf_token(); ?>');
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_port', document.getElementById('db_port').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);
            formData.append('create_db', document.getElementById('create_db').checked ? '1' : '0');

            fetch('test-connection.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnTest.disabled = false;
                btnTest.innerHTML = '<i class="bi bi-broadcast me-1"></i> Test Connection';
                
                alertBox.classList.remove('d-none');
                if (data.success) {
                    alertBox.className = 'alert alert-success d-flex align-items-center mb-4';
                    alertBox.innerHTML = '<i class="bi bi-check-circle-fill fs-5 me-2 flex-shrink-0"></i> <div>' + data.message + '</div>';
                } else {
                    alertBox.className = 'alert alert-danger d-flex align-items-center mb-4';
                    alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i> <div>' + data.message + '</div>';
                }
            })
            .catch(err => {
                btnTest.disabled = false;
                btnTest.innerHTML = '<i class="bi bi-broadcast me-1"></i> Test Connection';
                alertBox.className = 'alert alert-danger d-flex align-items-center mb-4';
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i> <div>Unable to test database connection. Please verify web server status.</div>';
            });
        });
    }
});
</script>
