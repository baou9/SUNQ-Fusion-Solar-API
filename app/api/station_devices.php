<?php
require_once __DIR__ . '/_client.php';
require_once __DIR__ . '/_cache.php';

$code = $_GET['code'] ?? '';
if (!$code) {
    json_error(400, 'missing_code');
}
$key = 'devices_' . $code;
$start = microtime(true);

if ($cached = cache_get($key)) {
    log_line('station_devices', microtime(true) - $start, 'hit');
    json_ok($cached);
}

try {
    $payload = ['stationCode' => $code, 'pageNo' => 1, 'pageSize' => 200];
    $resp = fs_request('POST', '/thirdData/stationDevList', $payload);
    $list = $resp['data']['list'] ?? [];
    $devices = [];
    foreach ($list as $dev) {
        $devices[] = [
            'name' => $dev['devName'] ?? '',
            'model' => $dev['devTypeName'] ?? '',
            'serial' => $dev['esn'] ?? '',
            'status' => $dev['devState'] ?? '',
            'lastSeen' => $dev['updateTime'] ?? '',
            'metrics' => ['power' => $dev['power'] ?? null],
        ];
    }
    cache_set($key, $devices);
    log_line('station_devices', microtime(true) - $start, 'miss');
    json_ok($devices);
} catch (Exception $e) {
    log_line('station_devices', microtime(true) - $start, 'error');
    json_error(500, 'fetch_failed');
}
