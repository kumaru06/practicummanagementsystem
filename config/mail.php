<?php
$host = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
$isLocal = in_array($host, ['localhost', '127.0.0.1', ''], true)
    || str_ends_with($host, '.test')
    || str_ends_with($host, '.loc')
    || str_ends_with($host, '.localhost');

if ($isLocal && is_file(__DIR__ . '/mail.local.dev.php')) {
    require __DIR__ . '/mail.local.dev.php';
} elseif (is_file(__DIR__ . '/mail.local.php')) {
    require __DIR__ . '/mail.local.php';
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST',       'smtp.hostinger.com');
    define('SMTP_PORT',       465);
    define('SMTP_SECURE',     'ssl');
    define('SMTP_USERNAME',   'betatesting@ama-ojtportal.com');
    define('SMTP_PASSWORD',   '');
}
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', 'betatesting@ama-ojtportal.com');
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME',  'AMA Education System - College Department');
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
