<?php
// ============================================================
//  MediCare+ — Database Configuration
//  Using PDO for secure, prepared-statement-based queries
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'medicare_db');
define('DB_USER', 'root');
define('DB_PASS', '');           // XAMPP default: empty password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log this rather than displaying it
            die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;">
                  <h2>Database Connection Error</h2>
                  <p>Could not connect to the database. Please ensure XAMPP MySQL is running and the database <strong>medicare_db</strong> has been imported.</p>
                  <code>' . htmlspecialchars($e->getMessage()) . '</code>
                 </div>');
        }
    }
    return $pdo;
}
