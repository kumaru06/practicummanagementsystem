<?php
if (!function_exists('env')) {
    require_once __DIR__ . '/../bootstrap/env.php';
}

/**
 * Load production database credentials.
 * Returns: [source, host, name, user, pass]
 */
function load_database_config(): array
{
    $dbName = env('DB_NAME');
    if ($dbName !== null && $dbName !== '') {
        return [
            'source' => resolve_env_file() ?? '.env',
            'host' => env('DB_HOST', 'localhost') ?? 'localhost',
            'name' => $dbName,
            'user' => env('DB_USER', '') ?? '',
            'pass' => env('DB_PASS', '') ?? '',
        ];
    }

    $productionFile = __DIR__ . '/database.production.php';
    if (is_file($productionFile)) {
        require $productionFile;

        if (defined('_DB_NAME') && _DB_NAME !== '' && _DB_NAME !== 'CHANGE_ME') {
            return [
                'source' => 'config/database.production.php',
                'host' => defined('_DB_HOST') ? (string)_DB_HOST : 'localhost',
                'name' => (string)_DB_NAME,
                'user' => defined('_DB_USER') ? (string)_DB_USER : '',
                'pass' => defined('_DB_PASS') ? (string)_DB_PASS : '',
            ];
        }
    }

    return [
        'source' => '',
        'host' => 'localhost',
        'name' => '',
        'user' => '',
        'pass' => '',
    ];
}

function database_connection_error_message(array $config, PDOException $exception): string
{
    $code = (int)$exception->getCode();
    $source = $config['source'] !== '' ? $config['source'] : 'missing config';
    $details = 'host=' . ($config['host'] ?: 'localhost')
        . ', database=' . ($config['name'] ?: '(empty)')
        . ', user=' . ($config['user'] ?: '(empty)');

    if ($config['name'] === '' || $config['user'] === '') {
        return 'Database not configured. Create config/database.production.php on the server '
            . '(recommended for Hostinger) or set DB_* in .env. See HOSTINGER_DEPLOY.md.';
    }

    if ($code === 1045) {
        return 'Database login failed (wrong MySQL password). In hPanel → Databases → Management, '
            . 'reset the password for user "' . $config['user'] . '", then update '
            . $source . '. ' . $details;
    }

    if ($code === 1049) {
        return 'Database "' . $config['name'] . '" was not found. Use the exact MySQL database name '
            . 'from hPanel → Databases → Management and update ' . $source . '.';
    }

    if ($code === 2002) {
        return 'Could not reach MySQL on host "' . $config['host'] . '". '
            . 'On Hostinger use DB_HOST=localhost in ' . $source . '.';
    }

    return 'Database connection failed using ' . $source . '. ' . $details
        . '. In hPanel → Databases → Management, confirm the database name, user, and password match '
        . 'config/database.production.php (or .env), then reset the MySQL password if needed.';
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
    define('_DB_CONFIG_SOURCE', 'local');
} else {
    $dbConfig = load_database_config();
    define('_DB_HOST', $dbConfig['host'] ?: 'localhost');
    define('_DB_NAME', $dbConfig['name']);
    define('_DB_USER', $dbConfig['user']);
    define('_DB_PASS', $dbConfig['pass']);
    define('_DB_CONFIG_SOURCE', $dbConfig['source']);

    if (_DB_NAME === '' || _DB_USER === '') {
        http_response_code(503);
        exit('Database not configured. Create config/database.production.php on the server '
            . '(copy from config/database.production.php.example) or set DB_* in .env.');
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $hosts = array_values(array_unique([_DB_HOST, 'localhost', '127.0.0.1']));
    $config = [
        'source' => defined('_DB_CONFIG_SOURCE') ? (string)_DB_CONFIG_SOURCE : '',
        'host' => _DB_HOST,
        'name' => _DB_NAME,
        'user' => _DB_USER,
        'pass' => _DB_PASS,
    ];
    $lastError = null;

    foreach ($hosts as $dbHost) {
        $dsn = 'mysql:host=' . $dbHost . ';dbname=' . _DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, _DB_USER, _DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '+08:00'");
            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e;
            $config['host'] = $dbHost;
        }
    }

    http_response_code(503);
    if (APP_IS_LOCAL) {
        exit('Database connection failed. Make sure Laragon MySQL is running and the database "practicum_system" exists, then refresh this page.');
    }

    exit(database_connection_error_message($config, $lastError ?? new PDOException('Connection failed')));
}
