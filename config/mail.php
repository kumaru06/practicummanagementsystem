<?php
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_USERNAME',   'markandreyperez@gmail.com');
define('SMTP_PASSWORD',   'oyvh qpzp shmc nuon');
define('SMTP_SECURE',     'tls');
define('MAIL_FROM_EMAIL', 'markandreyperez@gmail.com');
define('MAIL_FROM_NAME',  'AMA Computer College OJT Department');

// Optional override on the server: create config/mail.local.php (not in git).
if (is_file(__DIR__ . '/mail.local.php')) {
    require __DIR__ . '/mail.local.php';
}

$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', ''], true);
$host = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
if ($isLocal) {
    define('SYSTEM_URL', 'http://localhost/amaccmanagementsystem/');
} elseif ($host === 'ama-ojtportal.com' || str_ends_with($host, '.ama-ojtportal.com')) {
    define('SYSTEM_URL', 'https://ama-ojtportal.com/');
} else {
    define('SYSTEM_URL', 'https://practicummanagementsystem.xo.je/');
}
