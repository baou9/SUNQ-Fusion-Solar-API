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
$key = 'overview_' . md5($code);

if ($cached = cache_get($key, $cfg['CACHE_TTL_SECONDS'])) {
    log_line('/api/station_overview', $start, 'HIT');
    json_ok($cached);
}

$res = fs_request('GET', '/thirdData/stationKpi', null, ['stationCodes' => $code, 'isRealTime' => 1]);
if ($res['status'] == 200 && isset($res['body']['data'][0])) {
    $k = $res['body']['data'][0];
    $data = [
        'currentPower' => $k['power'] ?? null,
        'todayEnergy' => $k['day_energy'] ?? null,
        'totalEnergy' => $k['total_energy'] ?? null,
        'pr' => $k['performanceRatio'] ?? null,
    ];
    cache_set($key, $data);
    log_line('/api/station_overview', $start, 'MISS');
    json_ok($data);
}

log_line('/api/station_overview', $start, 'ERR');
json_error($res['status'] ?? 500, 'fs_error', $res['body'] ?? []);
