<?php
if (!function_exists('env') || !function_exists('app_is_local_host')) {
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

function database_driver_error_code(PDOException $exception): int
{
    $code = $exception->getCode();
    if (is_numeric($code) && (int)$code > 0) {
        return (int)$code;
    }

    $driverCode = $exception->errorInfo[1] ?? null;
    if (is_numeric($driverCode) && (int)$driverCode > 0) {
        return (int)$driverCode;
    }

    if (preg_match('/\[(\d+)\]/', $exception->getMessage(), $matches)) {
        return (int)$matches[1];
    }

    return 0;
}

function database_is_transient_connect_error(PDOException $exception): bool
{
    return in_array(database_driver_error_code($exception), [2002, 2003, 2006, 2013, 1040, 1203], true);
}

function database_unix_socket_path(): ?string
{
    $candidates = [];
    foreach (['pdo_mysql.default_socket', 'mysqli.default_socket'] as $iniKey) {
        $value = trim((string)ini_get($iniKey));
        if ($value !== '') {
            $candidates[] = $value;
        }
    }
    $candidates[] = '/var/run/mysqld/mysqld.sock';
    $candidates[] = '/tmp/mysql.sock';
    $candidates[] = '/var/lib/mysql/mysql.sock';

    foreach (array_unique($candidates) as $path) {
        if (@is_readable($path)) {
            return $path;
        }
    }

    return null;
}

function database_pdo_options(): array
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 4,
    ];
    if (defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')) {
        $options[PDO::MYSQL_ATTR_CONNECT_TIMEOUT] = 4;
    }

    return $options;
}

/**
 * Ordered connection targets. Unix socket first on live (avoids localhost→::1 IPv6 2002),
 * then TCP hosts. 127.0.0.1 is a fallback only — Hostinger prefers the socket.
 *
 * @return list<array{host: string, dsn: string}>
 */
function database_connection_targets(): array
{
    $targets = [];
    $seen = [];

    $add = static function (string $host, string $dsn) use (&$targets, &$seen): void {
        if (isset($seen[$dsn])) {
            return;
        }
        $seen[$dsn] = true;
        $targets[] = ['host' => $host, 'dsn' => $dsn];
    };

    $socket = database_unix_socket_path();
    if ($socket !== null) {
        $add('unix:' . $socket, 'mysql:unix_socket=' . $socket . ';dbname=' . _DB_NAME . ';charset=utf8mb4');
    }

    $hosts = APP_IS_LOCAL
        ? array_values(array_unique([_DB_HOST, 'localhost', '127.0.0.1']))
        : array_values(array_unique([_DB_HOST, 'localhost', '127.0.0.1']));

    foreach ($hosts as $host) {
        $add((string)$host, 'mysql:host=' . $host . ';dbname=' . _DB_NAME . ';charset=utf8mb4');
    }

    return $targets;
}

function database_try_connect(string $dsn): PDO
{
    $pdo = new PDO($dsn, _DB_USER, _DB_PASS, database_pdo_options());
    $pdo->exec("SET time_zone = '+08:00'");

    return $pdo;
}

function database_connection_error_message(array $config, PDOException $exception): string
{
    $code = database_driver_error_code($exception);
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

    if (in_array($code, [2002, 2003], true)) {
        return 'Could not reach MySQL on host "' . $config['host'] . '" after retries. '
            . 'This is usually a brief shared-hosting MySQL stall, not a wrong DB_HOST. '
            . $details . '. PDO: ' . $exception->getMessage();
    }

    if (in_array($code, [1040, 1203], true)) {
        return 'MySQL connection limit reached. Close unused hPanel/phpMyAdmin sessions and retry. ' . $details;
    }

    return 'Database connection failed using ' . $source . '. ' . $details
        . '. In hPanel → Databases → Management, confirm the database name, user, and password match '
        . 'config/database.production.php (or .env), then reset the MySQL password if needed.';
}

function database_public_unavailable_message(): string
{
    return 'The site is temporarily unable to reach the database. Please try again in a moment.';
}

function database_exit_connection_failure(array $config, PDOException $exception): never
{
    $logMessage = database_connection_error_message($config, $exception);
    error_log('[db] ' . $logMessage);

    http_response_code(503);
    header('Retry-After: 3');

    if (APP_IS_LOCAL) {
        exit('Database connection failed. Make sure Laragon MySQL is running and the database "practicum_system" exists, then refresh this page.');
    }

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $alreadyRetried = isset($_GET['_dbretry']);
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $retryUrl = $requestUri;
    if (!$alreadyRetried && !str_contains($retryUrl, '_dbretry=')) {
        $retryUrl .= (str_contains($retryUrl, '?') ? '&' : '?') . '_dbretry=1';
    }

    header('Content-Type: text/html; charset=utf-8');
    $safeMessage = htmlspecialchars(database_public_unavailable_message(), ENT_QUOTES, 'UTF-8');
    $safeRetry = htmlspecialchars($retryUrl, ENT_QUOTES, 'UTF-8');
    $autoRefresh = ($method === 'GET' && !$alreadyRetried)
        ? '<meta http-equiv="refresh" content="2;url=' . $safeRetry . '">'
        : '';

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . $autoRefresh
        . '<title>Temporarily unavailable</title></head>'
        . '<body style="font-family:system-ui,sans-serif;max-width:36rem;margin:4rem auto;padding:0 1.25rem;color:#1a1a1a">'
        . '<h1 style="font-size:1.25rem">Temporarily unavailable</h1>'
        . '<p>' . $safeMessage . '</p>'
        . '<p><a href="' . $safeRetry . '">Try again</a></p>'
        . '</body></html>';
    exit;
}

// Auto-detect local vs production from the public host, not SERVER_NAME.
// Hostinger (and some proxies) can set SERVER_NAME to 127.0.0.1 on POST/AJAX,
// which previously made live requests use Laragon credentials and fail.
define('APP_IS_LOCAL', app_is_local_host());

if (APP_IS_LOCAL) {
    // Laragon defaults — do not use production DB_* from .env on .test / localhost.
    define('_DB_HOST', 'localhost');
    define('_DB_NAME', 'practicum_system');
    define('_DB_USER', 'root');
    define('_DB_PASS', '');
    define('_DB_CONFIG_SOURCE', 'local');
} else {
    $dbConfig = load_database_config();
    $dbHost = strtolower(trim((string)($dbConfig['host'] ?: 'localhost')));
    if (in_array($dbHost, ['127.0.0.1', '::1'], true)) {
        $dbHost = 'localhost';
    }
    define('_DB_HOST', $dbHost);
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

    $config = [
        'source' => defined('_DB_CONFIG_SOURCE') ? (string)_DB_CONFIG_SOURCE : '',
        'host' => _DB_HOST,
        'name' => _DB_NAME,
        'user' => _DB_USER,
        'pass' => _DB_PASS,
    ];
    $targets = database_connection_targets();
    $primary = $targets[0] ?? null;
    $fallbacks = array_slice($targets, 1);
    $lastError = null;
    $maxAttempts = 3;

    $connectTarget = static function (array $target) use (&$pdo, &$lastError, &$config): bool {
        try {
            $pdo = database_try_connect($target['dsn']);
            return true;
        } catch (PDOException $e) {
            $lastError = $e;
            $config['host'] = $target['host'];
            if (!database_is_transient_connect_error($e)) {
                database_exit_connection_failure($config, $e);
            }
            return false;
        }
    };

    if ($primary !== null) {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($connectTarget($primary)) {
                return $pdo;
            }
            if ($attempt < $maxAttempts) {
                usleep(200000 * $attempt);
            }
        }
    }

    foreach ($fallbacks as $target) {
        if ($connectTarget($target)) {
            return $pdo;
        }
    }

    database_exit_connection_failure($config, $lastError ?? new PDOException('Connection failed'));
}
