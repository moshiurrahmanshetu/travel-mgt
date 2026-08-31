<?php
/**
 * Commercial Web Installer Controller & Shell
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/functions.php';

// --------------------------------------------------------------------------
// 1. Installation Lock Guard
// --------------------------------------------------------------------------
$alreadyInstalled = is_installed();
$currentStep = (int)($_GET['step'] ?? 1);

// If already installed and not on the completion screen, block access
if ($alreadyInstalled && $currentStep !== 5) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(403);
        die('Forbidden: Application is already installed.');
    }
    // Render Locked Screen
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation Locked — Tour & Travel Booking Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="assets/css/installer.css" rel="stylesheet">
    </head>
    <body class="installer-body">
        <div class="installer-container" style="max-width: 540px;">
            <div class="installer-card text-center p-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-danger text-white rounded-circle" style="width: 56px; height: 56px; font-size: 28px;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-2">Application Already Installed</h4>
                <p class="text-muted small mb-4">
                    The Tour & Travel Booking Management System has already been installed on this server. The installer is permanently locked to prevent unauthorized changes.
                </p>
                <div class="d-grid gap-2">
                    <a href="../auth/login.php" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Proceed to Login
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --------------------------------------------------------------------------
// 2. Handle POST Actions for Multi-step Wizard
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['step_action'] ?? '';

    // Verify CSRF
    if (!installer_verify_csrf()) {
        set_installer_flash('danger', 'Security token invalid or expired. Please try again.');
        header('Location: index.php?step=' . $currentStep);
        exit;
    }

    // ACTION: Step 1 -> Step 2
    if ($action === 'requirements_confirmed') {
        $reqs = check_server_requirements();
        if (!$reqs['all_passed']) {
            set_installer_flash('danger', 'Cannot proceed. Please satisfy all server requirements.');
            header('Location: index.php?step=1');
            exit;
        }
        $_SESSION['step1_passed'] = true;
        header('Location: index.php?step=2');
        exit;
    }

    // ACTION: Step 2 -> Step 3
    if ($action === 'save_database_credentials') {
        $host     = trim($_POST['db_host'] ?? '127.0.0.1');
        $port     = trim($_POST['db_port'] ?? '3306');
        $dbname   = trim($_POST['db_name'] ?? '');
        $user     = trim($_POST['db_user'] ?? 'root');
        $pass     = (string)($_POST['db_pass'] ?? '');
        $createDb = !empty($_POST['create_db']);

        $test = test_db_connection($host, $port, $dbname, $user, $pass, $createDb);
        if (!$test['success']) {
            set_installer_flash('danger', $test['message']);
            $_SESSION['db_config'] = [
                'db_host'   => $host,
                'db_port'   => $port,
                'db_name'   => $dbname,
                'db_user'   => $user,
                'db_pass'   => $pass,
                'create_db' => $createDb
            ];
            header('Location: index.php?step=2');
            exit;
        }

        $_SESSION['db_config'] = [
            'db_host'   => $host,
            'db_port'   => $port,
            'db_name'   => $dbname,
            'db_user'   => $user,
            'db_pass'   => $pass,
            'create_db' => $createDb
        ];
        $_SESSION['step2_passed'] = true;
        set_installer_flash('success', 'Database connection verified successfully.');
        header('Location: index.php?step=3');
        exit;
    }

    // ACTION: Step 3 -> Step 4
    if ($action === 'execute_database_import') {
        $dbConfig = $_SESSION['db_config'] ?? [];
        if (empty($dbConfig['db_name'])) {
            set_installer_flash('danger', 'Database credentials missing. Please configure database first.');
            header('Location: index.php?step=2');
            exit;
        }

        $test = test_db_connection(
            $dbConfig['db_host'],
            $dbConfig['db_port'],
            $dbConfig['db_name'],
            $dbConfig['db_user'],
            $dbConfig['db_pass'],
            !empty($dbConfig['create_db'])
        );

        if (!$test['success'] || !$test['pdo']) {
            set_installer_flash('danger', 'Database connection failed: ' . $test['message']);
            header('Location: index.php?step=3');
            exit;
        }

        $pdo = $test['pdo'];
        $sqlSource = $_POST['sql_source'] ?? 'default';
        $sqlFilePath = '';
        $isTempFile = false;

        if ($sqlSource === 'custom' && !empty($_FILES['custom_sql_file']['name'])) {
            $file = $_FILES['custom_sql_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($ext !== 'sql') {
                set_installer_flash('danger', 'Invalid file type. Only .sql files are supported.');
                header('Location: index.php?step=3');
                exit;
            }

            if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                set_installer_flash('danger', 'File upload failed. Please try again.');
                header('Location: index.php?step=3');
                exit;
            }

            $sqlFilePath = $file['tmp_name'];
            $isTempFile = true;
        } else {
            $sqlFilePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sql';
        }

        // Execute SQL import
        $importResult = execute_sql_file($pdo, $sqlFilePath);

        if ($isTempFile && file_exists($sqlFilePath)) {
            @unlink($sqlFilePath);
        }

        if (!$importResult['success']) {
            set_installer_flash('danger', $importResult['message']);
            header('Location: index.php?step=3');
            exit;
        }

        // Verify tables
        $tableCheck = verify_installation_tables($pdo);
        if (!$tableCheck['success']) {
            set_installer_flash('danger', 'Database import was incomplete. Missing required tables: ' . implode(', ', $tableCheck['missing_tables']));
            header('Location: index.php?step=3');
            exit;
        }

        // Save DB config to config/database.php
        save_database_config(
            $dbConfig['db_host'],
            $dbConfig['db_port'],
            $dbConfig['db_name'],
            $dbConfig['db_user'],
            $dbConfig['db_pass']
        );

        $_SESSION['db_imported'] = true;
        $_SESSION['step3_passed'] = true;
        set_installer_flash('success', $importResult['message']);
        header('Location: index.php?step=4');
        exit;
    }

    // ACTION: Step 4 -> Step 5 (Finalize)
    if ($action === 'create_administrator_account') {
        $dbConfig = $_SESSION['db_config'] ?? [];
        $adminName  = trim($_POST['admin_name'] ?? '');
        $adminEmail = trim(strtolower($_POST['admin_email'] ?? ''));
        $adminPass  = (string)($_POST['admin_password'] ?? '');
        $adminConf  = (string)($_POST['admin_confirm_password'] ?? '');

        $_SESSION['admin_input'] = [
            'admin_name'  => $adminName,
            'admin_email' => $adminEmail
        ];

        if (empty($adminName)) {
            set_installer_flash('danger', 'Administrator full name is required.');
            header('Location: index.php?step=4');
            exit;
        }

        if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            set_installer_flash('danger', 'A valid administrator email address is required.');
            header('Location: index.php?step=4');
            exit;
        }

        if (strlen($adminPass) < 8) {
            set_installer_flash('danger', 'Password must be at least 8 characters long.');
            header('Location: index.php?step=4');
            exit;
        }

        if ($adminPass !== $adminConf) {
            set_installer_flash('danger', 'Password confirmation does not match.');
            header('Location: index.php?step=4');
            exit;
        }

        $test = test_db_connection(
            $dbConfig['db_host'] ?? '127.0.0.1',
            $dbConfig['db_port'] ?? '3306',
            $dbConfig['db_name'] ?? 'travel_mgt_db',
            $dbConfig['db_user'] ?? 'root',
            $dbConfig['db_pass'] ?? ''
        );

        if (!$test['success'] || !$test['pdo']) {
            set_installer_flash('danger', 'Database connection failed: ' . $test['message']);
            header('Location: index.php?step=4');
            exit;
        }

        $pdo = $test['pdo'];

        // Create Admin Account
        $adminResult = create_admin_user($pdo, $adminName, $adminEmail, $adminPass);
        if (!$adminResult['success']) {
            set_installer_flash('danger', $adminResult['message']);
            header('Location: index.php?step=4');
            exit;
        }

        // Final verification & atomic lock creation
        $lockCreated = create_install_lock();
        if (!$lockCreated) {
            set_installer_flash('danger', 'Unable to create installation lock file in storage/ directory. Check directory permissions.');
            header('Location: index.php?step=4');
            exit;
        }

        $_SESSION['completed_admin_email'] = $adminEmail;
        clean_installer_session();

        header('Location: index.php?step=5');
        exit;
    }
}

// Ensure step boundary
if ($currentStep < 1 || $currentStep > 5) {
    $currentStep = 1;
}

// Step labels
$stepLabels = [
    1 => 'Requirements',
    2 => 'Database',
    3 => 'Import',
    4 => 'Administrator',
    5 => 'Complete'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install — Tour & Travel Booking Management System</title>
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Solid Installer Styling (Zero Gradients) -->
    <link href="assets/css/installer.css" rel="stylesheet">
</head>
<body class="installer-body">
    <div class="installer-container">
        <div class="installer-card">
            <!-- Installer Header -->
            <div class="installer-header">
                <div class="installer-icon">
                    <i class="bi bi-compass"></i>
                </div>
                <h1 class="installer-title">Tour & Travel Booking Management System</h1>
                <p class="installer-subtitle">Commercial System Installation Wizard</p>
            </div>

            <!-- Steps Progress Bar -->
            <div class="installer-steps">
                <?php for ($i = 1; $i <= 5; $i++): 
                    $isCompleted = ($i < $currentStep);
                    $isActive = ($i === $currentStep);
                    $stepClass = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
                ?>
                    <div class="step-item <?= $stepClass; ?>">
                        <span class="step-badge">
                            <?php if ($isCompleted): ?>
                                <i class="bi bi-check-lg"></i>
                            <?php else: ?>
                                <?= $i; ?>
                            <?php endif; ?>
                        </span>
                        <span class="step-label"><?= $stepLabels[$i]; ?></span>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- Step Body Content -->
            <div class="installer-body-content">
                <!-- Flash Alerts -->
                <?= display_installer_flash(); ?>

                <?php
                switch ($currentStep) {
                    case 1:
                        require __DIR__ . '/requirements.php';
                        break;
                    case 2:
                        require __DIR__ . '/database.php';
                        break;
                    case 3:
                        require __DIR__ . '/import.php';
                        break;
                    case 4:
                        require __DIR__ . '/admin.php';
                        break;
                    case 5:
                        require __DIR__ . '/complete.php';
                        break;
                    default:
                        require __DIR__ . '/requirements.php';
                        break;
                }
                ?>
            </div>
        </div>

        <div class="text-center text-muted small mt-3">
            &copy; <?= date('Y'); ?> Tour & Travel Booking Management System. All rights reserved.
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
