<?php
/**
 * ONE-FILE email fix — upload only this file to public_html, open once in browser, then delete.
 * https://ama-ojtportal.com/fix-email.php?key=ama-ojt-mailer-2026
 */
header('Content-Type: application/json; charset=utf-8');

$key = (string) ($_GET['key'] ?? '');
if ($key !== 'ama-ojt-mailer-2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid key'], JSON_PRETTY_PRINT);
    exit;
}

$root = __DIR__;
$results = ['steps' => []];

function step(array &$results, string $name, bool $ok, string $detail = ''): void
{
    $results['steps'][] = ['step' => $name, 'ok' => $ok, 'detail' => $detail];
}

// 1. bootstrap/mailer.php
$bootstrapDir = $root . '/bootstrap';
$bootstrapFile = $bootstrapDir . '/mailer.php';
$bootstrapCode = <<<'PHP'
<?php
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
PHP;

if (!is_dir($bootstrapDir)) {
    @mkdir($bootstrapDir, 0755, true);
}
step($results, 'bootstrap_dir', is_dir($bootstrapDir));
step($results, 'bootstrap_mailer', file_put_contents($bootstrapFile, $bootstrapCode) !== false);

// 2. Download PHPMailer into lib/phpmailer/
$libDir = $root . '/lib/phpmailer';
if (!is_dir($libDir)) {
    @mkdir($libDir, 0755, true);
}
$version = 'v6.9.1';
$base = "https://raw.githubusercontent.com/PHPMailer/PHPMailer/{$version}/src/";
$files = ['Exception.php', 'PHPMailer.php', 'SMTP.php', 'DSNConfigurator.php', 'OAuth.php', 'OAuthTokenProvider.php', 'POP3.php'];
$dl = [];
foreach ($files as $file) {
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'AMA-OJT-Fix/1.0']]);
    $body = @file_get_contents($base . $file, false, $ctx);
    $dl[$file] = ($body !== false && $body !== '' && file_put_contents($libDir . '/' . $file, $body) !== false) ? 'ok' : 'fail';
}
step($results, 'phpmailer_download', !in_array('fail', $dl, true), json_encode($dl));

// 3. Patch init.php to load bootstrap/mailer.php
$initFile = $root . '/init.php';
$initOk = false;
if (is_file($initFile)) {
    $init = file_get_contents($initFile);
    if (str_contains($init, 'bootstrap/mailer.php')) {
        $initOk = true;
        step($results, 'init_php', true, 'already patched');
    } else {
        $needle = "require_once \$autoload;\n}\n";
        $insert = "require_once \$autoload;\n}\n\nrequire_once __DIR__ . '/bootstrap/mailer.php';\n";
        if (str_contains($init, $needle)) {
            $init = str_replace($needle, $insert, $init);
            $init = preg_replace(
                '/\nif \(!class_exists\(\\\\PHPMailer\\\\PHPMailer\\\\PHPMailer::class\)\) \{[^}]+\} else \{[^}]+\}\n/s',
                "\n",
                $init
            );
            $initOk = file_put_contents($initFile, $init) !== false;
            step($results, 'init_php', $initOk, 'patched');
        } else {
            step($results, 'init_php', false, 'could not find autoload block — upload init.php manually');
        }
    }
} else {
    step($results, 'init_php', false, 'init.php not found');
}

require_once $bootstrapFile;
$results['mailer_available'] = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
$results['ok'] = $results['mailer_available'];
$results['next'] = $results['ok']
    ? 'Resend coordinator credentials in admin. Delete fix-email.php and install-mailer.php.'
    : 'Check steps above; contact support if download failed.';

if (!$results['ok']) {
    http_response_code(503);
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
