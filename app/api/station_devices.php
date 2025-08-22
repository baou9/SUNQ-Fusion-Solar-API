<?php
require_once __DIR__ . '/_client.php';
require_once __DIR__ . '/_util.php';
require_once __DIR__ . '/_cache.php';

$start = microtime(true);
$cfg = app_config();
$code = $_GET['code'] ?? '';
if (!$code) {
    json_error(400, 'missing_code');
}
$key = 'devices_' . md5($code);

if ($cached = cache_get($key, $cfg['CACHE_TTL_SECONDS'])) {
    log_line('/api/station_devices', $start, 'HIT');
    json_ok($cached);
}

$res = fs_request('GET', '/thirdData/deviceList', null, ['stationCodes' => $code]);
if ($res['status'] == 200 && isset($res['body']['data'])) {
    $list = [];
    foreach ($res['body']['data'] as $d) {
        $list[] = [
            'name' => $d['devName'] ?? '',
            'model' => $d['devTypeStr'] ?? '',
            'serial' => $d['sn'] ?? '',
            'status' => $d['status'] ?? '',
            'lastSeen' => $d['updateTime'] ?? '',
            'metrics' => $d['kpi'] ?? [],
        ];
    }
    cache_set($key, $list);
    log_line('/api/station_devices', $start, 'MISS');
    json_ok($list);
}

log_line('/api/station_devices', $start, 'ERR');
json_error($res['status'] ?? 500, 'fs_error', $res['body'] ?? []);
