<?php
require_once __DIR__ . '/_client.php';
require_once __DIR__ . '/_cache.php';

$pageNo = isset($_GET['pageNo']) ? (int)$_GET['pageNo'] : 1;
$pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 50;
$key = "stations_{$pageNo}_{$pageSize}";
$start = microtime(true);

if ($cached = cache_get($key)) {
    log_line('stations', microtime(true) - $start, 'hit');
    json_ok($cached);
}

try {
    $payload = ['pageNo' => $pageNo, 'pageSize' => $pageSize];
    $resp = fs_request('POST', '/thirdData/stationList', $payload);
    cache_set($key, $resp);
    log_line('stations', microtime(true) - $start, 'miss');
    json_ok($resp);
} catch (Exception $e) {
    log_line('stations', microtime(true) - $start, 'error');
    json_error(500, 'fetch_failed');
}
