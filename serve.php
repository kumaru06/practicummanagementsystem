<?php

/**
 * Authenticated file proxy for everything under /uploads/.
 * The root .htaccess rewrites `uploads/<path>` to `serve.php?f=<path>` so existing
 * links keep working, but every access now requires login + an authorization check.
 */
require_once __DIR__ . '/init.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Authentication required.');
}

$rel = str_replace('\\', '/', (string)($_GET['f'] ?? ''));
$rel = ltrim($rel, '/');

$uploadsRoot = realpath(__DIR__ . '/uploads');
$absolute = $rel !== '' ? realpath(__DIR__ . '/uploads/' . $rel) : false;

// Path-traversal guard: the resolved file must live inside /uploads/.
if ($uploadsRoot === false
    || $absolute === false
    || !str_starts_with($absolute, $uploadsRoot . DIRECTORY_SEPARATOR)
    || !is_file($absolute)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('File not found.');
}

$relFromRoot = 'uploads/' . str_replace('\\', '/', substr($absolute, strlen($uploadsRoot) + 1));

try {
    $allowed = FileAccess::canView($relFromRoot, $user);
} catch (Throwable $e) {
    error_log('File access check failed for ' . $relFromRoot . ': ' . $e->getMessage());
    $allowed = false;
}

if (!$allowed) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('You do not have access to this file.');
}

$mime = mime_content_type($absolute) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($absolute));
header('Content-Disposition: inline; filename="' . rawurlencode(basename($absolute)) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($absolute);
exit;
