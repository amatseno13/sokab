<?php
/**
 * SOKAB - Database Configuration (Localhost)
 * Struktur identik dengan SAMAWA
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sokab_db');
define('DB_CHARSET', 'utf8mb4');

// Session Configuration
define('SESSION_TIMEOUT', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);

// Application Configuration
define('APP_NAME', 'SOKAB');
define('APP_URL', 'http://localhost/sokab/');

// Security
define('HASH_COST', 10);

// Timezone WITA
date_default_timezone_set('Asia/Makassar');

/**
 * Get Database Connection (PDO - identik dengan SAMAWA)
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Check your configuration.");
        }
    }

    return $pdo;
}
?>
