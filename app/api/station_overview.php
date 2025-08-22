<?php
require_once __DIR__ . '/_client.php';
require_once __DIR__ . '/_cache.php';

$code = $_GET['code'] ?? '';
if (!$code) {
    json_error(400, 'missing_code');
}
$key = 'overview_' . $code;
$start = microtime(true);

if ($cached = cache_get($key)) {
    log_line('station_overview', microtime(true) - $start, 'hit');
    json_ok($cached);
}

try {
    $payload = ['stationCodes' => [$code]];
    $resp = fs_request('POST', '/thirdData/stationRealKpi', $payload);
    $kpi = $resp['data'][0] ?? [];
    $data = [
        'currentPower' => $kpi['realTimePower'] ?? null,
        'todayEnergy' => $kpi['dayPower'] ?? null,
        'totalEnergy' => $kpi['totalPower'] ?? null,
        'pr' => $kpi['perpowerRatio'] ?? null,
    ];
    cache_set($key, $data);
    log_line('station_overview', microtime(true) - $start, 'miss');
    json_ok($data);
} catch (Exception $e) {
    log_line('station_overview', microtime(true) - $start, 'error');
    json_error(500, 'fetch_failed');
}
