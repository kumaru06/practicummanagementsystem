<?php
if (!function_exists('env')) {
    require_once __DIR__ . '/../bootstrap/env.php';
}

// Auto-detect local vs production environment
$host = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
define('APP_IS_LOCAL', in_array($host, ['localhost', '127.0.0.1', ''], true)
    || str_ends_with($host, '.test')
    || str_ends_with($host, '.loc')
    || str_ends_with($host, '.localhost'));

if (APP_IS_LOCAL) {
    // Laragon defaults — do not use production DB_* from .env on .test / localhost.
    define('_DB_HOST', 'localhost');
    define('_DB_NAME', 'practicum_system');
    define('_DB_USER', 'root');
    define('_DB_PASS', '');
} else {
    $dbName = env('DB_NAME');
    if ($dbName !== null && $dbName !== '') {
        define('_DB_HOST', env('DB_HOST', 'localhost') ?? 'localhost');
        define('_DB_NAME', $dbName);
        define('_DB_USER', env('DB_USER', '') ?? '');
        define('_DB_PASS', env('DB_PASS', '') ?? '');
    } elseif (is_file(__DIR__ . '/database.production.php')) {
        require __DIR__ . '/database.production.php';
    } else {
        http_response_code(503);
        exit('Database not configured. Copy .env.example to .env and set DB_* values, or create config/database.production.php.');
    }

    if (!defined('_DB_NAME') || _DB_NAME === 'CHANGE_ME' || _DB_NAME === '') {
        http_response_code(503);
        exit('Database not configured. Set DB_NAME in .env or edit config/database.production.php.');
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
        if (!APP_IS_LOCAL) {
            http_response_code(503);
            exit(
                'Database connection failed. Check DB_* values in .env or config/database.production.php '
                . '(hPanel → Databases → MySQL Databases).'
            );
        }

        http_response_code(503);
        exit('Database connection failed. Make sure Laragon MySQL is running and the database "practicum_system" exists, then refresh this page.');
    }

    return $pdo;
}
