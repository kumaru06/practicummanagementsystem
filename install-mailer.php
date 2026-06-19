<?php
/**
 * One-time: downloads PHPMailer into lib/phpmailer/ on the server (no zip extract).
 * Visit once, then delete this file.
 *
 * https://ama-ojtportal.com/install-mailer.php?key=ama-ojt-mailer-2026
 */
header('Content-Type: application/json; charset=utf-8');

$key = (string) ($_GET['key'] ?? '');
if ($key !== 'ama-ojt-mailer-2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid or missing key.'], JSON_PRETTY_PRINT);
    exit;
}

$libDir = __DIR__ . '/lib/phpmailer';
if (!is_dir($libDir) && !@mkdir($libDir, 0755, true) && !is_dir($libDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not create lib/phpmailer directory.'], JSON_PRETTY_PRINT);
    exit;
}

$version = 'v6.9.1';
$base = "https://raw.githubusercontent.com/PHPMailer/PHPMailer/{$version}/src/";
$files = ['Exception.php', 'PHPMailer.php', 'SMTP.php', 'DSNConfigurator.php', 'OAuth.php', 'OAuthTokenProvider.php', 'POP3.php'];

$fileResults = [];
foreach ($files as $file) {
    $context = stream_context_create([
        'http' => ['timeout' => 30, 'user_agent' => 'AMA-OJT-Portal/1.0'],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $content = @file_get_contents($base . $file, false, $context);
    if ($content === false || $content === '') {
        $fileResults[$file] = 'download_failed';
        continue;
    }
    $fileResults[$file] = file_put_contents($libDir . '/' . $file, $content) !== false ? 'ok' : 'write_failed';
}

require_once __DIR__ . '/bootstrap/mailer.php';

$result = [
    'ok' => class_exists(\PHPMailer\PHPMailer\PHPMailer::class),
    'files' => $fileResults,
    'lib_dir' => 'lib/phpmailer',
    'next' => 'Open vendor-check.php, then Resend coordinator credentials. Delete install-mailer.php when done.',
];

if (!$result['ok']) {
    http_response_code(503);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
