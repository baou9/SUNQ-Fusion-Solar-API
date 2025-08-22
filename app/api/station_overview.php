<?php
require_once __DIR__ . '/_client.php';

$code = $_GET['code'] ?? '';
if (!$code) {
    json_error(400, 'BAD_REQUEST', 'missing code');
}

try {
    $body = ['stationCodes' => [$code]];
    $resp = fs_request('POST', '/thirdData/stationRealKpi', [], $body);
    $kpi = $resp['data'][0] ?? [];
    $data = [
        'currentPower' => $kpi['realTimePower'] ?? null,
        'todayEnergy' => $kpi['dayPower'] ?? null,
        'totalEnergy' => $kpi['totalPower'] ?? null,
        'perpowerRatio' => $kpi['perpowerRatio'] ?? null,
    ];
    json_ok($data);
} catch (Exception $e) {
    $status = $e->getCode() >= 400 ? $e->getCode() : 502;
    json_error($status, 'UPSTREAM_ERROR', 'overview fetch failed');
}
?>
