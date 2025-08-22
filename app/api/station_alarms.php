<?php
require_once __DIR__ . '/_client.php';
require_once __DIR__ . '/_util.php';
require_once __DIR__ . '/_cache.php';

$codes = require __DIR__ . '/error_codes.php';

$start = microtime(true);
$cfg = app_config();
$code = $_GET['code'] ?? '';
$severity = $_GET['severity'] ?? 'all';
if (!$code) {
    json_error(400, 'missing_code');
}
$key = 'alarms_' . md5($code . '_' . $severity);

if ($cached = cache_get($key, $cfg['CACHE_TTL_SECONDS'])) {
    log_line('/api/station_alarms', $start, 'HIT');
    json_ok($cached);
}

$params = ['stationCodes' => $code];
if ($severity !== 'all') {
    $params['severity'] = $severity;
}
$res = fs_request('GET', '/thirdData/queryAlarmList', null, $params);
if ($res['status'] == 200 && isset($res['body']['data'])) {
    $list = [];
    foreach ($res['body']['data'] as $a) {
        $codeVal = $a['alarmId'] ?? '';
        $list[] = [
            'code' => $codeVal,
            'severity' => strtolower($a['alarmLevel'] ?? ''),
            'message' => $codes[$codeVal] ?? ($a['alarmName'] ?? ''),
            'deviceName' => $a['devName'] ?? '',
            'firstSeen' => $a['beginTime'] ?? '',
            'lastSeen' => $a['endTime'] ?? '',
            'status' => $a['status'] ?? '',
        ];
    }
    cache_set($key, $list);
    log_line('/api/station_alarms', $start, 'MISS');
    json_ok($list);
}

log_line('/api/station_alarms', $start, 'ERR');
json_error($res['status'] ?? 500, 'fs_error', $res['body'] ?? []);
