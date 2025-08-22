<?php
require_once __DIR__ . '/_client.php';
require_once __DIR__ . '/_util.php';
require_once __DIR__ . '/_cache.php';

$start = microtime(true);
$cfg = app_config();
$pageNo = isset($_GET['pageNo']) ? (int)$_GET['pageNo'] : 1;
$pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 50;
$key = 'stations_' . md5($pageNo . '_' . $pageSize);

if ($cached = cache_get($key, $cfg['CACHE_TTL_SECONDS'])) {
    log_line('/api/stations', $start, 'HIT');
    json_ok($cached);
}

$res = fs_request('GET', '/thirdData/stationList', null, ['pageNo' => $pageNo, 'pageSize' => $pageSize]);
if ($res['status'] == 200 && isset($res['body']['data'])) {
    $data = $res['body']['data'];
    cache_set($key, $data);
    log_line('/api/stations', $start, 'MISS');
    json_ok($data);
}

log_line('/api/stations', $start, 'ERR');
json_error($res['status'] ?? 500, 'fs_error', $res['body'] ?? []);
