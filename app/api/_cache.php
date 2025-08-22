<?php
// Simple file-based cache
require_once __DIR__ . '/_util.php';

function cache_path($key) {
    return __DIR__ . '/../storage/cache/' . md5($key) . '.json';
}

function cache_get($key) {
    global $CONFIG;
    if ($CONFIG['APP_ENV'] === 'dev' && isset($_GET['nocache'])) {
        return null;
    }
    $file = cache_path($key);
    if (!file_exists($file)) {
        return null;
    }
    $ttl = $CONFIG['CACHE_TTL_SECONDS'];
    if (filemtime($file) + $ttl < time()) {
        return null;
    }
    $data = json_decode(file_get_contents($file), true);
    return $data;
}

function cache_set($key, $data) {
    $file = cache_path($key);
    file_put_contents($file, json_encode($data));
}
