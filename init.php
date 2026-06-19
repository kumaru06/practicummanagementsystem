<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/helpers.php';

date_default_timezone_set('Asia/Manila');

define('TEMPORARY_REPORT_UNLOCK', false);

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/bootstrap/mailer.php';

spl_autoload_register(function (string $class): void {
    foreach (['models', 'controllers'] as $dir) {
        $path = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
