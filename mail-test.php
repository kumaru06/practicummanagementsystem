<?php
/**
 * One-time SMTP test on the server — delete after use.
 * Visit: https://ama-ojtportal.com/mail-test.php?key=ama-ojt-2026-mail&to=YOUR_EMAIL
 */
declare(strict_types=1);

const MAIL_TEST_KEY = 'ama-ojt-2026-mail';

if (($_GET['key'] ?? '') !== MAIL_TEST_KEY) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/init.php';

$to = trim((string)($_GET['to'] ?? ''));
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    exit("Add ?to=email@example.com to the URL\n");
}

$sent = (new Email(db()))->send(
    $to,
    'AMA OJT Portal SMTP Test',
    'smtp_test',
    'account_credentials',
    [
        'name' => 'SMTP Test',
        'email' => $to,
        'password' => 'test-only-not-real',
        'roleLabel' => 'Test',
        'loginUrl' => defined('SYSTEM_URL') ? SYSTEM_URL : 'https://ama-ojtportal.com/auth.php',
    ]
);

$row = db()->query('SELECT status, error_message, sent_at FROM email_logs ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);

echo $sent ? "Send returned: OK\n" : "Send returned: FAILED\n";
echo 'Log status: ' . ($row['status'] ?? 'unknown') . "\n";
echo 'Log error: ' . ($row['error_message'] ?? 'none') . "\n";
echo 'Log time: ' . ($row['sent_at'] ?? '') . "\n";
echo "\nCheck inbox and spam. Delete mail-test.php after testing.\n";
