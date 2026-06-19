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

/**
 * PHPMailer bootstrap for Hostinger (auto-downloads if lib/ missing).
 * Upload only this file to public_html/config/mail.php to fix email.
 */
(function (): void {
    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class, false)) {
        if (!defined('MAILER_AVAILABLE')) {
            define('MAILER_AVAILABLE', true);
        }
        return;
    }

    // #region agent log
    $debugLog = static function (string $hypothesisId, string $message, array $data = []): void {
        $entry = json_encode([
            'sessionId' => '824cc8',
            'runId' => 'mail-bootstrap',
            'hypothesisId' => $hypothesisId,
            'location' => 'config/mail.php',
            'message' => $message,
            'data' => $data,
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_SLASHES);
        @file_put_contents(dirname(__DIR__) . '/uploads/debug-824cc8.log', $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    };
    // #endregion

    $lib = dirname(__DIR__) . '/lib/phpmailer';
    $files = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];
    $needsDownload = false;
    foreach ($files as $file) {
        if (!is_file($lib . '/' . $file)) {
            $needsDownload = true;
            break;
        }
    }

    if ($needsDownload) {
        $debugLog('F', 'PHPMailer missing, attempting GitHub download', ['lib' => $lib]);
        if (!is_dir($lib)) {
            @mkdir($lib, 0755, true);
        }
        $base = 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.9.1/src/';
        $ctx = stream_context_create([
            'http' => ['timeout' => 25, 'user_agent' => 'AMA-OJT-Portal/1.0'],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $downloadResults = [];
        foreach ($files as $file) {
            if (is_file($lib . '/' . $file)) {
                $downloadResults[$file] = 'exists';
                continue;
            }
            $body = @file_get_contents($base . $file, false, $ctx);
            if ($body === false || $body === '') {
                $downloadResults[$file] = 'download_failed';
                continue;
            }
            $downloadResults[$file] = file_put_contents($lib . '/' . $file, $body) !== false ? 'ok' : 'write_failed';
        }
        $debugLog('G', 'Download results', $downloadResults);
    }

    foreach ($files as $file) {
        $path = $lib . '/' . $file;
        if (is_file($path)) {
            require_once $path;
        }
    }

    $available = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
    if (!defined('MAILER_AVAILABLE')) {
        define('MAILER_AVAILABLE', $available);
    }
    $debugLog('H', 'Bootstrap complete', ['mailer_available' => $available]);
})();
