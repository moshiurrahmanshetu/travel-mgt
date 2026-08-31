<?php
/**
 * Commercial Installer Helper Functions
 * Tour & Travel Booking Management System
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

/**
 * Check if the application is installed (via storage/install.lock)
 * 
 * @return bool
 */
function is_installed(): bool
{
    $lockFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'install.lock';
    return file_exists($lockFile);
}

/**
 * Generate or retrieve installer CSRF token
 * 
 * @return string
 */
function installer_csrf_token(): string
{
    if (empty($_SESSION['_installer_csrf_token'])) {
        $_SESSION['_installer_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_installer_csrf_token'];
}

/**
 * Verify installer CSRF token
 * 
 * @param string|null $token
 * @return bool
 */
function installer_verify_csrf(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['_installer_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['_installer_csrf_token'], $token);
}

/**
 * Check system & server requirements
 * 
 * @return array
 */
function check_server_requirements(): array
{
    $phpMin = '8.0.0';
    $phpCurrent = PHP_VERSION;
    $phpPass = version_compare($phpCurrent, $phpMin, '>=');

    $storageDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0755, true);
    }
    $storageWritable = is_dir($storageDir) && is_writable($storageDir);

    $uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }
    $uploadsWritable = is_dir($uploadsDir) && is_writable($uploadsDir);

    $requirements = [
        'php_version' => [
            'name'     => 'PHP Version',
            'required' => '>= ' . $phpMin,
            'current'  => $phpCurrent,
            'passed'   => $phpPass
        ],
        'pdo' => [
            'name'     => 'PDO Extension',
            'required' => 'Enabled',
            'current'  => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
            'passed'   => extension_loaded('pdo')
        ],
        'pdo_mysql' => [
            'name'     => 'PDO MySQL Driver',
            'required' => 'Enabled',
            'current'  => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
            'passed'   => extension_loaded('pdo_mysql')
        ],
        'json' => [
            'name'     => 'JSON Extension',
            'required' => 'Enabled',
            'current'  => extension_loaded('json') ? 'Enabled' : 'Disabled',
            'passed'   => extension_loaded('json')
        ],
        'fileinfo' => [
            'name'     => 'Fileinfo Extension',
            'required' => 'Enabled',
            'current'  => extension_loaded('fileinfo') ? 'Enabled' : 'Disabled',
            'passed'   => extension_loaded('fileinfo')
        ],
        'session' => [
            'name'     => 'Session Support',
            'required' => 'Enabled',
            'current'  => session_status() !== PHP_SESSION_DISABLED ? 'Enabled' : 'Disabled',
            'passed'   => session_status() !== PHP_SESSION_DISABLED
        ],
        'storage_writable' => [
            'name'     => 'storage/ Directory',
            'required' => 'Writable',
            'current'  => $storageWritable ? 'Writable' : 'Not Writable',
            'passed'   => $storageWritable
        ],
        'uploads_writable' => [
            'name'     => 'uploads/ Directory',
            'required' => 'Writable',
            'current'  => $uploadsWritable ? 'Writable' : 'Not Writable',
            'passed'   => $uploadsWritable
        ]
    ];

    $allPassed = true;
    foreach ($requirements as $req) {
        if (!$req['passed']) {
            $allPassed = false;
            break;
        }
    }

    return [
        'requirements' => $requirements,
        'all_passed'   => $allPassed
    ];
}

/**
 * Test Database Connection
 * 
 * @param string $host
 * @param string|int $port
 * @param string $dbname
 * @param string $user
 * @param string $pass
 * @param bool $createIfNotExists
 * @return array ['success' => bool, 'message' => string, 'pdo' => PDO|null]
 */
function test_db_connection(string $host, $port, string $dbname, string $user, string $pass, bool $createIfNotExists = false): array
{
    $host = trim($host) ?: '127.0.0.1';
    $port = (int)$port > 0 ? (int)$port : 3306;
    $dbname = trim($dbname);
    $user = trim($user);

    // Validate database name format (alphanumeric, underscores, hyphens)
    if (!empty($dbname) && !preg_match('/^[a-zA-Z0-9_\-]+$/', $dbname)) {
        return [
            'success' => false,
            'message' => 'Database name contains invalid characters. Use letters, numbers, hyphens, and underscores only.',
            'pdo'     => null
        ];
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5
    ];

    try {
        if (!empty($dbname)) {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
                $pdo->exec("SET NAMES utf8mb4");
                return [
                    'success' => true,
                    'message' => 'Database connection successful.',
                    'pdo'     => $pdo
                ];
            } catch (PDOException $e) {
                // If database does not exist and createIfNotExists is enabled, try creating it
                if ($createIfNotExists && $e->getCode() == 1049) {
                    $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $serverPdo = new PDO($serverDsn, $user, $pass, $options);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    $pdo = new PDO($dsn, $user, $pass, $options);
                    $pdo->exec("SET NAMES utf8mb4");
                    return [
                        'success' => true,
                        'message' => "Database '{$dbname}' created and connected successfully.",
                        'pdo'     => $pdo
                    ];
                }
                throw $e;
            }
        } else {
            $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($serverDsn, $user, $pass, $options);
            $pdo->exec("SET NAMES utf8mb4");
            return [
                'success' => true,
                'message' => 'Database server connection successful.',
                'pdo'     => $pdo
            ];
        }
    } catch (PDOException $e) {
        error_log('Installer DB Connection Error: ' . $e->getMessage());

        $friendlyMessage = 'Unable to connect to the MySQL database server. Please verify your host, port, username, password, and database name.';
        if ($e->getCode() == 1045) {
            $friendlyMessage = 'Access denied for user. Please check your database username and password.';
        } elseif ($e->getCode() == 1049) {
            $friendlyMessage = "Database '{$dbname}' does not exist. Please create it in cPanel/MySQL or enable 'Create Database'.";
        } elseif ($e->getCode() == 2002) {
            $friendlyMessage = "Could not connect to database host '{$host}:{$port}'. Ensure MySQL server is running.";
        }

        return [
            'success' => false,
            'message' => $friendlyMessage,
            'pdo'     => null
        ];
    }
}

/**
 * Execute SQL file with comment stripping and robust statement parser
 * 
 * @param PDO $pdo
 * @param string $sqlFilePath
 * @return array ['success' => bool, 'statements_executed' => int, 'message' => string]
 */
function execute_sql_file(PDO $pdo, string $sqlFilePath): array
{
    if (!file_exists($sqlFilePath) || !is_readable($sqlFilePath)) {
        return [
            'success' => false,
            'statements_executed' => 0,
            'message' => 'SQL file not found or is unreadable.'
        ];
    }

    $content = file_get_contents($sqlFilePath);
    if ($content === false || trim($content) === '') {
        return [
            'success' => false,
            'statements_executed' => 0,
            'message' => 'SQL file is empty.'
        ];
    }

    try {
        // Temporarily disable foreign key checks during import
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Parse SQL statements safely
        $statements = split_sql_statements($content);
        $executedCount = 0;

        foreach ($statements as $statement) {
            $stmt = trim($statement);
            if (!empty($stmt)) {
                $pdo->exec($stmt);
                $executedCount++;
            }
        }

        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        return [
            'success' => true,
            'statements_executed' => $executedCount,
            'message' => "Database imported successfully ({$executedCount} statements executed)."
        ];

    } catch (PDOException $e) {
        // Ensure foreign key checks are re-enabled
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (Exception $ignored) {}

        error_log('Installer SQL Import Error: ' . $e->getMessage());

        return [
            'success' => false,
            'statements_executed' => 0,
            'message' => 'Database import encountered an error: ' . preg_replace('/(SQLSTATE\[\w+\]:\s*)/', '', $e->getMessage())
        ];
    }
}

/**
 * Robust SQL Statement Splitter
 * Correctly handles multiline queries, single/double quoted strings, backticks, and comment blocks.
 * 
 * @param string $sql
 * @return array
 */
function split_sql_statements(string $sql): array
{
    $queries = [];
    $query = '';
    $len = strlen($sql);
    $inString = false;
    $stringChar = '';
    $inComment = false;
    $commentType = ''; // '--' or '/*'

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        // 1. Inside comment block
        if ($inComment) {
            if ($commentType === '--' && ($char === "\n" || $char === "\r")) {
                $inComment = false;
            } elseif ($commentType === '/*' && $char === '*' && $next === '/') {
                $inComment = false;
                $i++; // skip /
            }
            continue;
        }

        // 2. Inside quoted string or identifier
        if ($inString) {
            $query .= $char;
            if ($char === '\\' && $inString) {
                // Escaped character inside string
                $i++;
                if ($i < $len) {
                    $query .= $sql[$i];
                }
            } elseif ($char === $stringChar) {
                $inString = false;
            }
            continue;
        }

        // 3. Comment start detection
        if ($char === '-' && $next === '-') {
            $inComment = true;
            $commentType = '--';
            $i++;
            continue;
        }
        if ($char === '#' && ($i === 0 || $sql[$i-1] === "\n" || $sql[$i-1] === "\r" || $sql[$i-1] === ' ')) {
            $inComment = true;
            $commentType = '--';
            continue;
        }
        if ($char === '/' && $next === '*') {
            $inComment = true;
            $commentType = '/*';
            $i++;
            continue;
        }

        // 4. String start detection
        if ($char === "'" || $char === '"' || $char === '`') {
            $inString = true;
            $stringChar = $char;
            $query .= $char;
            continue;
        }

        // 5. Statement delimiter
        if ($char === ';') {
            $trimmed = trim($query);
            if (!empty($trimmed)) {
                $queries[] = $trimmed;
            }
            $query = '';
            continue;
        }

        $query .= $char;
    }

    $trailing = trim($query);
    if (!empty($trailing)) {
        $queries[] = $trailing;
    }

    return $queries;
}

/**
 * Verify essential application tables exist in database
 * 
 * @param PDO $pdo
 * @return array ['success' => bool, 'missing_tables' => array]
 */
function verify_installation_tables(PDO $pdo): array
{
    $requiredTables = [
        'roles',
        'permissions',
        'role_permissions',
        'users',
        'tour_categories',
        'tour_destinations',
        'tour_packages',
        'customers',
        'bookings',
        'payments',
        'settings'
    ];

    try {
        $stmt = $pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $existingTablesLower = array_map('strtolower', $existingTables);

        $missing = [];
        foreach ($requiredTables as $t) {
            if (!in_array(strtolower($t), $existingTablesLower, true)) {
                $missing[] = $t;
            }
        }

        return [
            'success'        => empty($missing),
            'missing_tables' => $missing
        ];
    } catch (PDOException $e) {
        error_log('Installer Table Verification Error: ' . $e->getMessage());
        return [
            'success'        => false,
            'missing_tables' => $requiredTables
        ];
    }
}

/**
 * Create Primary Administrator User Account
 * 
 * @param PDO $pdo
 * @param string $fullName
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'user_id' => int|null, 'message' => string]
 */
function create_admin_user(PDO $pdo, string $fullName, string $email, string $password): array
{
    $fullName = trim($fullName);
    $email = trim(strtolower($email));

    if (empty($fullName)) {
        return ['success' => false, 'user_id' => null, 'message' => 'Administrator full name is required.'];
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'user_id' => null, 'message' => 'A valid administrator email address is required.'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'user_id' => null, 'message' => 'Password must be at least 8 characters long.'];
    }

    try {
        // 1. Locate Administrator role
        $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE slug = 'administrator' LIMIT 1");
        $roleStmt->execute();
        $adminRoleId = (int)$roleStmt->fetchColumn();

        if ($adminRoleId <= 0) {
            // Fallback: create administrator role if somehow missing
            $pdo->exec("INSERT INTO roles (name, slug, description, is_system, created_at, updated_at) VALUES ('Administrator', 'administrator', 'Full system access', 1, NOW(), NOW())");
            $adminRoleId = (int)$pdo->lastInsertId();
        }

        // Split name into first and last name
        $nameParts = explode(' ', $fullName, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 2. Transactional insert
        $pdo->beginTransaction();

        // Check if user already exists
        $userCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $userCheck->execute(['email' => $email]);
        $existingUserId = $userCheck->fetchColumn();

        if ($existingUserId) {
            $updateStmt = $pdo->prepare("
                UPDATE users SET
                    role_id = :role_id,
                    first_name = :first_name,
                    last_name = :last_name,
                    name = :name,
                    password = :password,
                    status = 'active',
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([
                'role_id'    => $adminRoleId,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'name'       => $fullName,
                'password'   => $passwordHash,
                'id'         => $existingUserId
            ]);
            $userId = (int)$existingUserId;
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO users (
                    role_id, first_name, last_name, name, email, password, status, created_at, updated_at
                ) VALUES (
                    :role_id, :first_name, :last_name, :name, :email, :password, 'active', NOW(), NOW()
                )
            ");
            $insertStmt->execute([
                'role_id'    => $adminRoleId,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'name'       => $fullName,
                'email'      => $email,
                'password'   => $passwordHash
            ]);
            $userId = (int)$pdo->lastInsertId();
        }

        $pdo->commit();

        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Administrator account created successfully.'
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Installer Admin Creation Error: ' . $e->getMessage());
        return [
            'success' => false,
            'user_id' => null,
            'message' => 'Could not create administrator user: ' . $e->getMessage()
        ];
    }
}

/**
 * Save Database Configuration to config/database.php
 * 
 * @param string $host
 * @param string|int $port
 * @param string $dbname
 * @param string $user
 * @param string $pass
 * @return bool
 */
function save_database_config(string $host, $port, string $dbname, string $user, string $pass): bool
{
    $configFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

    $hostCode = var_export((string)$host, true);
    $portCode = var_export((string)$port, true);
    $dbnameCode = var_export((string)$dbname, true);
    $userCode = var_export((string)$user, true);
    $passCode = var_export((string)$pass, true);

    $content = <<<PHP
<?php
/**
 * Database Connection Configuration (PDO)
 * Tour & Travel Booking Management System
 * Auto-generated by Installer
 */

require_once __DIR__ . '/config.php';

// Database Connection Constants
if (!defined('DB_HOST')) define('DB_HOST', {$hostCode});
if (!defined('DB_PORT')) define('DB_PORT', {$portCode});
if (!defined('DB_NAME')) define('DB_NAME', {$dbnameCode});
if (!defined('DB_USER')) define('DB_USER', {$userCode});
if (!defined('DB_PASS')) define('DB_PASS', {$passCode});
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Get centralized PDO database connection (Singleton pattern)
 * 
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO
{
    static \$pdo = null;

    if (\$pdo === null) {
        \$dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        \$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
        ];

        try {
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            error_log('Database Connection Error: ' . \$e->getMessage());

            if (defined('APP_ENV') && APP_ENV === 'development') {
                die('<div style="font-family: sans-serif; padding: 20px; background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; border-radius: 8px; margin: 20px;">'
                    . '<h3 style="margin-top: 0;">Database Connection Failed</h3>'
                    . '<p>' . htmlspecialchars(\$e->getMessage()) . '</p>'
                    . '<p><small>Ensure MySQL is running and the database <code>' . htmlspecialchars(DB_NAME) . '</code> exists.</small></p>'
                    . '</div>');
            } else {
                die('<div style="font-family: sans-serif; text-align: center; padding: 50px;">'
                    . '<h2>Service Temporarily Unavailable</h2>'
                    . '<p>We are currently experiencing database connection difficulties. Please try again shortly.</p>'
                    . '</div>');
            }
        }
    }

    return \$pdo;
}
PHP;

    return (bool)file_put_contents($configFile, $content, LOCK_EX);
}

/**
 * Create atomic installation lock in storage/install.lock
 * 
 * @return bool
 */
function create_install_lock(): bool
{
    $storageDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0755, true);
    }

    $lockFile = $storageDir . DIRECTORY_SEPARATOR . 'install.lock';
    $tmpFile = $storageDir . DIRECTORY_SEPARATOR . 'install.lock.tmp';

    $data = json_encode([
        'installed_at' => date('c'),
        'app_name'     => 'Tour & Travel Booking Management System',
        'app_version'  => '1.0.0',
        'status'       => 'installed'
    ], JSON_PRETTY_PRINT);

    if (file_put_contents($tmpFile, $data, LOCK_EX) !== false) {
        return rename($tmpFile, $lockFile);
    }

    return false;
}

/**
 * Destroy installer session data
 * 
 * @return void
 */
function clean_installer_session(): void
{
    unset(
        $_SESSION['install_step'],
        $_SESSION['db_config'],
        $_SESSION['_installer_csrf_token'],
        $_SESSION['installer_flash']
    );
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Set installer flash message
 * 
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 * @return void
 */
function set_installer_flash(string $type, string $message): void
{
    $_SESSION['installer_flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Display installer flash message
 * 
 * @return string HTML
 */
function display_installer_flash(): string
{
    if (empty($_SESSION['installer_flash'])) {
        return '';
    }
    $f = $_SESSION['installer_flash'];
    unset($_SESSION['installer_flash']);

    $type = htmlspecialchars($f['type']);
    $msg = $f['message'];

    return "<div class=\"alert alert-{$type} alert-dismissible fade show shadow-sm mb-4\" role=\"alert\">
        {$msg}
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
    </div>";
}
