<?php
declare(strict_types=1);

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * @return array<int, array<string, mixed>>
 */
function psgc_fetch_items(string $url, string $cacheKey): array
{
    $cacheDir = __DIR__ . '/../storage/psgc-cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0775, true);
    }

    $cacheFile = $cacheDir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $cacheKey) . '.json';
    if (is_file($cacheFile) && filemtime($cacheFile) > time() - (86400 * 30)) {
        $cached = json_decode((string)file_get_contents($cacheFile), true);
        return is_array($cached) ? $cached : [];
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header' => "Accept: application/json\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        if (is_file($cacheFile)) {
            $cached = json_decode((string)file_get_contents($cacheFile), true);
            return is_array($cached) ? $cached : [];
        }

        throw new RuntimeException('Unable to load location data right now. Please try again.');
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid location data received.');
    }

    file_put_contents($cacheFile, $raw);

    return $decoded;
}

try {
    release_session_lock();

    $action = (string)($_GET['action'] ?? 'provinces');
    $code = trim((string)($_GET['code'] ?? ''));

    switch ($action) {
        case 'provinces':
            $items = psgc_fetch_items('https://psgc.cloud/api/provinces', 'provinces');
            array_unshift($items, [
                'name' => 'Metro Manila (NCR)',
                'code' => '1300000000',
            ]);
            echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
            break;

        case 'cities':
            if ($code === '') {
                throw new InvalidArgumentException('Province code is required.');
            }

            if ($code === '1300000000') {
                $items = psgc_fetch_items('https://psgc.cloud/api/regions/1300000000/cities-municipalities', 'ncr-cities');
            } else {
                $items = psgc_fetch_items(
                    'https://psgc.cloud/api/provinces/' . rawurlencode($code) . '/cities-municipalities',
                    'cities-' . $code
                );
            }

            echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
            break;

        case 'barangays':
            if ($code === '') {
                throw new InvalidArgumentException('City/Municipality code is required.');
            }

            $items = psgc_fetch_items(
                'https://psgc.cloud/api/cities-municipalities/' . rawurlencode($code) . '/barangays',
                'barangays-' . $code
            );

            echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new InvalidArgumentException('Unknown location action.');
    }
} catch (Throwable $e) {
    $code = $e instanceof InvalidArgumentException ? 422 : 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
