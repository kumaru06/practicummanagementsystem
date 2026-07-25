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

// Local/testing bypasses — set in .env; keep false on production unless intentionally testing.
define('TEMPORARY_REPORT_UNLOCK', filter_var(env('TEMPORARY_REPORT_UNLOCK', 'false'), FILTER_VALIDATE_BOOLEAN));
define('TEMPORARY_ORIENTATION_PAST_DATES', filter_var(env('TEMPORARY_ORIENTATION_PAST_DATES', 'false'), FILTER_VALIDATE_BOOLEAN));

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
