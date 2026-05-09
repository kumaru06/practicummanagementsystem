<?php
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_USERNAME',   'markandreyperez@gmail.com');
define('SMTP_PASSWORD',   'oyvh qpzp shmc nuon');
define('SMTP_SECURE',     'tls');
define('MAIL_FROM_EMAIL', 'markandreyperez@gmail.com');
define('MAIL_FROM_NAME',  'AMA Computer College OJT Department');

$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '']);
define('SYSTEM_URL', $isLocal ? 'http://localhost/amaccmanagementsystem/' : 'https://practicummanagementsystem.xo.je/');
