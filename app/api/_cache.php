<?php
// File-based cache utilities
require_once __DIR__ . '/_util.php';

function cache_dir() {
    $dir = __DIR__ . '/../storage/cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    return $dir;
}

function cache_key($method, $path, $query, $body) {
    $src = strtoupper($method) . '|' . $path . '|' . http_build_query($query) . '|' . md5($body ?? '');
    return md5($src);
}

function cache_get($key) {
    global $CONFIG;
    $file = cache_dir() . '/' . $key . '.json';
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

function cache_put($key, $body, $ttl = null) {
    global $CONFIG;
    $file = cache_dir() . '/' . $key . '.json';
    file_put_contents($file, json_encode($body));
    if ($ttl !== null) {
        touch($file, time());
    }
}
?>
