<?php
/**
 * Load PHPMailer without Composer vendor/ (works when Hostinger zip extract fails).
 */
function loadPhpmailer(): void
{
    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class, false)) {
        return;
    }

    $lib = dirname(__DIR__) . '/lib/phpmailer';
    foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $file) {
        $path = $lib . '/' . $file;
        if (is_file($path)) {
            require_once $path;
        }
    }
}

loadPhpmailer();

if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    define('MAILER_AVAILABLE', false);
} else {
    define('MAILER_AVAILABLE', true);
}
