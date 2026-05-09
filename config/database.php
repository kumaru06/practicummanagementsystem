<?php
// Auto-detect local vs production environment
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '']);

if ($isLocal) {
    define('_DB_HOST', 'localhost');
    define('_DB_NAME', 'practicum_system');
    define('_DB_USER', 'root');
    define('_DB_PASS', '');
} else {
    define('_DB_HOST', 'sql309.infinityfree.com');
    define('_DB_NAME', 'if0_41872185_practicummanagementsystem');
    define('_DB_USER', 'if0_41872185');
    define('_DB_PASS', '0twdhr9utirPSQ');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . _DB_HOST . ';dbname=' . _DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, _DB_USER, _DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
