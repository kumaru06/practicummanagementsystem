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
require_once __DIR__ . '/bootstrap/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/helpers.php';

date_default_timezone_set('Asia/Manila');

// Local/testing bypasses — read from .env on local only; always false on production.
$__tempReportUnlock = filter_var(env('TEMPORARY_REPORT_UNLOCK', 'false'), FILTER_VALIDATE_BOOLEAN);
$__tempOrientationPast = filter_var(env('TEMPORARY_ORIENTATION_PAST_DATES', 'false'), FILTER_VALIDATE_BOOLEAN);
define('TEMPORARY_REPORT_UNLOCK', APP_IS_LOCAL && $__tempReportUnlock);
define('TEMPORARY_ORIENTATION_PAST_DATES', APP_IS_LOCAL && $__tempOrientationPast);

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
