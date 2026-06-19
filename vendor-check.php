<?php
/**
 * Confirms PHPMailer is available (vendor/ or lib/phpmailer/ fallback).
 * Delete after email works.
 */
header('Content-Type: application/json; charset=utf-8');

$autoload = __DIR__ . '/vendor/autoload.php';
$vendorPhpmailer = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
$libPhpmailer = __DIR__ . '/lib/phpmailer/PHPMailer.php';

$result = [
    'server_time' => date('Y-m-d H:i:s T'),
    'timezone' => date_default_timezone_get(),
    'autoload_exists' => is_file($autoload),
    'vendor_phpmailer_exists' => is_file($vendorPhpmailer),
    'lib_phpmailer_exists' => is_file($libPhpmailer),
    'mailer_available' => false,
    'php_version' => PHP_VERSION,
];

if ($result['autoload_exists']) {
    require_once $autoload;
}
require_once __DIR__ . '/bootstrap/mailer.php';

$result['mailer_available'] = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
$result['ok'] = $result['mailer_available'];

if (!$result['ok']) {
    http_response_code(503);
    $result['fix'] = 'Upload install-mailer.php, init.php, and bootstrap/mailer.php to public_html, '
        . 'then open install-mailer.php?key=ama-ojt-mailer-2026 in your browser (auto-downloads PHPMailer).';
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
