<?php
/**
 * Load key=value pairs from .env (no Composer dependency).
 * Real .env is gitignored; copy .env.example to .env on each machine.
 */
function resolve_env_file(): ?string
{
    $candidates = [];

    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $candidates[] = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . '.env';
    }

    $candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

    foreach ($candidates as $path) {
        if (is_file($path) && is_readable($path)) {
            return $path;
        }
    }

    return null;
}

function load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        return;
    }

    $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
    $lines = preg_split('/\R/', $contents) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    return $default;
}

$envFile = resolve_env_file();
if ($envFile !== null) {
    load_env($envFile);
}
