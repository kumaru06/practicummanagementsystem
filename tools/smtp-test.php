<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/env.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$recipient = $argv[1] ?? 'natzumekirito@gmail.com';
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    $mail->Timeout = 30;
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = static function (string $str): void {
        echo $str;
    };
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($recipient);
    $mail->Subject = 'AMA OJT SMTP test';
    $mail->Body = 'If you received this, SMTP works.';
    $mail->send();
    echo PHP_EOL . "SENT OK to {$recipient}" . PHP_EOL;
} catch (Throwable $e) {
    echo PHP_EOL . 'FAIL: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
