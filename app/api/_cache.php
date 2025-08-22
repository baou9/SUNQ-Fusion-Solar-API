<?php
require_once __DIR__ . '/_util.php';

/** Simple file-based cache */
function cache_path(string $key): string {
    return __DIR__ . '/../storage/cache/' . $key . '.json';
}

function cache_get(string $key, int $ttl): ?array {
    $cfg = app_config();
    if (($cfg['APP_ENV'] ?? '') === 'dev' && isset($_GET['nocache'])) {
        return null;
    }
    $file = cache_path($key);
    if (!file_exists($file)) {
        return null;
    }
    if (filemtime($file) + $ttl < time()) {
        return null;
    }
    $data = json_decode(file_get_contents($file), true);
    return $data;
}

function cache_set(string $key, array $data): void {
    $file = cache_path($key);
    file_put_contents($file, json_encode($data));
}
