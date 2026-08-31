<?php
/**
 * Database Connection Configuration (PDO)
 * Tour & Travel Booking Management System
 */

require_once __DIR__ . '/config.php';

/**
 * Get centralized PDO database connection (Singleton pattern)
 * 
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error internally
            error_log('Database Connection Error: ' . $e->getMessage());

            if (defined('APP_ENV') && APP_ENV === 'development') {
                die('<div style="font-family: sans-serif; padding: 20px; background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; border-radius: 8px; margin: 20px;">'
                    . '<h3 style="margin-top: 0;">Database Connection Failed</h3>'
                    . '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
                    . '<p><small>Ensure MySQL is running in XAMPP and the database <code>' . htmlspecialchars(DB_NAME) . '</code> has been imported.</small></p>'
                    . '</div>');
            } else {
                die('<div style="font-family: sans-serif; text-align: center; padding: 50px;">'
                    . '<h2>Service Temporarily Unavailable</h2>'
                    . '<p>We are currently experiencing database connection difficulties. Please try again shortly.</p>'
                    . '</div>');
            }
        }
    }

    return $pdo;
}
