<?php
$__cookieSecure = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => $__cookieSecure,
    'samesite' => 'Lax',
]);
session_start();

// Security response headers (defense-in-depth) on every web response.
// frame-ancestors is the modern clickjacking control; the policy stays
// resource-agnostic so it does not break existing inline styles/scripts.
// A stricter script-src/style-src can be layered later after UI testing.
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
    if ($__cookieSecure) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

require_once __DIR__ . '/bootstrap/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/helpers/address.php';

if (!defined('SESSION_IDLE_SECONDS')) {
    define('SESSION_IDLE_SECONDS', max(60, (int)(env('SESSION_IDLE_SECONDS', '1800') ?? '1800')));
}
if (!defined('SESSION_ABSOLUTE_SECONDS')) {
    define('SESSION_ABSOLUTE_SECONDS', max(300, (int)(env('SESSION_ABSOLUTE_SECONDS', '28800') ?? '28800')));
}
enforce_session_timeout();

date_default_timezone_set('Asia/Manila');

// Temporary testing bypasses — controlled by .env.
// TEMPORARY_REPORT_UNLOCK stays local-only (skips DTR/weekly gates).
// TEMPORARY_ORIENTATION_PAST_DATES may be enabled on live for short testing windows;
// set it back to false when done so normal scheduling rules apply.
$__tempReportUnlock = filter_var(env('TEMPORARY_REPORT_UNLOCK', 'false'), FILTER_VALIDATE_BOOLEAN);
$__tempOrientationPast = filter_var(env('TEMPORARY_ORIENTATION_PAST_DATES', 'false'), FILTER_VALIDATE_BOOLEAN);
define('TEMPORARY_REPORT_UNLOCK', APP_IS_LOCAL && $__tempReportUnlock);
define('TEMPORARY_ORIENTATION_PAST_DATES', $__tempOrientationPast);

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$mailerBootstrap = __DIR__ . '/bootstrap/mailer.php';
if (is_file($mailerBootstrap)) {
    require_once $mailerBootstrap;
}

spl_autoload_register(function (string $class): void {
    foreach (['models', 'controllers'] as $dir) {
        $path = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
