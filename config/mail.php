<?php
if (!function_exists('env')) {
    require_once __DIR__ . '/../bootstrap/env.php';
}

$host = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
$isLocal = in_array($host, ['localhost', '127.0.0.1', ''], true)
    || str_ends_with($host, '.test')
    || str_ends_with($host, '.loc')
    || str_ends_with($host, '.localhost');

// Legacy PHP config files (optional fallback if .env is missing).
if ((env('SMTP_PASSWORD') ?? '') === '') {
    if ($isLocal && is_file(__DIR__ . '/mail.local.dev.php')) {
        require __DIR__ . '/mail.local.dev.php';
    } elseif (is_file(__DIR__ . '/mail.local.php')) {
        require __DIR__ . '/mail.local.php';
    }
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', env('SMTP_HOST', 'smtp.hostinger.com') ?? 'smtp.hostinger.com');
    define('SMTP_PORT', (int)(env('SMTP_PORT', '465') ?? '465'));
    define('SMTP_SECURE', env('SMTP_SECURE', 'ssl') ?? 'ssl');
    define('SMTP_USERNAME', env('SMTP_USERNAME', 'amaccdavao@ama-ojtportal.com') ?? 'amaccdavao@ama-ojtportal.com');
    define('SMTP_PASSWORD', env('SMTP_PASSWORD', '') ?? '');
}
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', env('MAIL_FROM_EMAIL', 'amaccdavao@ama-ojtportal.com') ?? 'amaccdavao@ama-ojtportal.com');
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'AMA Computer College OJT Department') ?? 'AMA Computer College OJT Department');
}

if (!defined('REGISTRATION_SMTP_USERNAME')) {
    define('REGISTRATION_SMTP_USERNAME', env('REGISTRATION_SMTP_USERNAME', MAIL_FROM_EMAIL) ?? MAIL_FROM_EMAIL);
}
if (!defined('REGISTRATION_SMTP_PASSWORD')) {
    define('REGISTRATION_SMTP_PASSWORD', env('REGISTRATION_SMTP_PASSWORD', SMTP_PASSWORD) ?? SMTP_PASSWORD);
}
if (!defined('REGISTRATION_MAIL_FROM_EMAIL')) {
    define('REGISTRATION_MAIL_FROM_EMAIL', env('REGISTRATION_MAIL_FROM_EMAIL', REGISTRATION_SMTP_USERNAME) ?? REGISTRATION_SMTP_USERNAME);
}
if (!defined('REGISTRATION_MAIL_FROM_NAME')) {
    define('REGISTRATION_MAIL_FROM_NAME', env('REGISTRATION_MAIL_FROM_NAME', 'AMA OJT Student Registration') ?? 'AMA OJT Student Registration');
}

if ($isLocal) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $port = (int)($_SERVER['SERVER_PORT'] ?? 80);
    $portPart = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80) ? '' : ':' . $port;
    $isLaragonVhost = str_ends_with($host, '.test') || str_ends_with($host, '.loc') || str_ends_with($host, '.localhost');
    if ($isLaragonVhost) {
        define('SYSTEM_URL', $scheme . '://' . $host . $portPart . '/');
    } else {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '/' : rtrim($scriptDir, '/') . '/';
        define('SYSTEM_URL', $scheme . '://' . ($host !== '' ? $host : 'localhost') . $portPart . $base);
    }
} elseif ($host === 'ama-ojtportal.com' || str_ends_with($host, '.ama-ojtportal.com')) {
    define('SYSTEM_URL', 'https://ama-ojtportal.com/');
} else {
    define('SYSTEM_URL', 'https://practicummanagementsystem.xo.je/');
}
