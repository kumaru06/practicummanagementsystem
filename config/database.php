<?php
// Auto-detect local vs production environment
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '']);

if ($isLocal) {
    define('_DB_HOST', 'localhost');
    define('_DB_NAME', 'practicum_system');
    define('_DB_USER', 'root');
    define('_DB_PASS', '');
} else {
    require __DIR__ . '/database.production.php';

    if (!defined('_DB_NAME') || _DB_NAME === 'CHANGE_ME') {
        http_response_code(503);
        exit('Database not configured. Edit config/database.production.php with your Hostinger MySQL credentials from hPanel.');
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . _DB_HOST . ';dbname=' . _DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, _DB_USER, _DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        if (!in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', ''], true)) {
            http_response_code(503);
            exit(
                'Database connection failed. Edit config/database.production.php with your current '
                . 'Hostinger MySQL credentials from hPanel → Databases → MySQL Databases.'
            );
        }

        throw $e;
    }

    return $pdo;
}
