<?php
/**
 * Installer Step 3 View: Database Import
 * Tour & Travel Booking Management System
 */

$defaultSqlPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sql';
$defaultSqlExists = file_exists($defaultSqlPath);
$defaultSqlSize = $defaultSqlExists ? round(filesize($defaultSqlPath) / 1024, 1) . ' KB' : 'Missing';
$dbConfig = $_SESSION['db_config'] ?? [];
?>

<div class="mb-4">
    <h3 class="fs-5 fw-bold text-dark mb-1">Step 3: Database Schema & Seed Data Import</h3>
    <p class="text-muted small mb-0">Select your database installation source file to initialize tables and default system configurations.</p>
</div>

<div class="card bg-light border p-3 mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <span class="text-muted small d-block">Target Database:</span>
            <strong class="text-dark"><code><?= htmlspecialchars($dbConfig['db_name'] ?? 'travel_mgt_db'); ?></code></strong>
            <span class="text-muted small ms-2">(Host: <?= htmlspecialchars($dbConfig['db_host'] ?? '127.0.0.1'); ?>:<?= htmlspecialchars($dbConfig['db_port'] ?? '3306'); ?>)</span>
        </div>
        <span class="badge bg-primary"><i class="bi bi-hdd me-1"></i> Connected</span>
    </div>
</div>

<form action="index.php?step=3" method="POST" enctype="multipart/form-data" id="importForm">
    <input type="hidden" name="_csrf_token" value="<?= installer_csrf_token(); ?>">
    <input type="hidden" name="step_action" value="execute_database_import">

    <div class="mb-4">
        <label class="form-label fw-semibold text-dark">Select SQL Source File <span class="text-danger">*</span></label>

        <!-- Option 1: Default Master SQL -->
        <div class="form-check border rounded p-3 mb-2 <?= $defaultSqlExists ? 'bg-white' : 'bg-light'; ?>">
            <input 
                class="form-check-input" 
                type="radio" 
                name="sql_source" 
                id="sql_default" 
                value="default" 
                checked
                <?= !$defaultSqlExists ? 'disabled' : ''; ?>
            >
            <label class="form-check-label w-100" for="sql_default">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="text-dark">Default Master SQL</strong>
                        <div class="text-muted small"><code>database/database.sql</code> (Core schema, permissions, roles & starter catalog)</div>
                    </div>
                    <?php if ($defaultSqlExists): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><?= $defaultSqlSize; ?></span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">File Not Found</span>
                    <?php endif; ?>
                </div>
            </label>
        </div>

        <!-- Option 2: Custom SQL Upload -->
        <div class="form-check border rounded p-3 bg-white">
            <input 
                class="form-check-input" 
                type="radio" 
                name="sql_source" 
                id="sql_custom" 
                value="custom"
            >
            <label class="form-check-label w-100" for="sql_custom">
                <strong class="text-dark">Upload Custom SQL File (.sql)</strong>
                <div class="text-muted small">Select a custom SQL database schema from your local drive.</div>
            </label>
            <div id="customSqlWrapper" class="mt-3 ps-4 d-none">
                <input 
                    type="file" 
                    class="form-control" 
                    name="custom_sql_file" 
                    id="custom_sql_file" 
                    accept=".sql"
                >
                <small class="text-muted">Maximum allowed file upload size: <?= ini_get('upload_max_filesize'); ?></small>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
        <a href="index.php?step=2" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button type="submit" class="btn btn-primary px-4" id="btnSubmitImport">
            <i class="bi bi-database-down me-1"></i> Import Database & Continue
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioDefault = document.getElementById('sql_default');
    const radioCustom = document.getElementById('sql_custom');
    const customWrapper = document.getElementById('customSqlWrapper');
    const customInput = document.getElementById('custom_sql_file');
    const importForm = document.getElementById('importForm');
    const btnSubmit = document.getElementById('btnSubmitImport');

    function toggleCustomUpload() {
        if (radioCustom.checked) {
            customWrapper.classList.remove('d-none');
            customInput.required = true;
        } else {
            customWrapper.classList.add('d-none');
            customInput.required = false;
        }
    }

    if (radioDefault) radioDefault.addEventListener('change', toggleCustomUpload);
    if (radioCustom) radioCustom.addEventListener('change', toggleCustomUpload);

    if (importForm && btnSubmit) {
        importForm.addEventListener('submit', function() {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing Database...';
        });
    }
});
</script>
