<?php
require_once __DIR__ . '/_client.php';
require_once __DIR__ . '/_cache.php';
$ALARM_MAP = require __DIR__ . '/error_codes.php';

$code = $_GET['code'] ?? '';
$severity = $_GET['severity'] ?? 'all';
if (!$code) {
    json_error(400, 'missing_code');
}
$key = 'alarms_' . $code . '_' . $severity;
$start = microtime(true);

if ($cached = cache_get($key)) {
    log_line('station_alarms', microtime(true) - $start, 'hit');
    json_ok($cached);
}

try {
    $payload = ['stationCodes' => [$code], 'pageNo' => 1, 'pageSize' => 200];
    $resp = fs_request('POST', '/thirdData/stationAlarmList', $payload);
    $list = $resp['data']['list'] ?? [];
    $alarms = [];
    foreach ($list as $al) {
        $codeId = $al['alarmId'] ?? '';
        $alarms[] = [
            'code' => $codeId,
            'severity' => strtolower($al['alarmLevel'] ?? ''),
            'message' => $ALARM_MAP[$codeId] ?? 'Unknown alarm',
            'deviceName' => $al['devName'] ?? '',
            'firstSeen' => $al['occurTime'] ?? '',
            'lastSeen' => $al['recoverTime'] ?? '',
            'status' => $al['status'] ?? '',
        ];
    }
    if ($severity !== 'all') {
        $alarms = array_values(array_filter($alarms, function ($a) use ($severity) {
            return $a['severity'] === strtolower($severity);
        }));
    }
    cache_set($key, $alarms);
    log_line('station_alarms', microtime(true) - $start, 'miss');
    json_ok($alarms);
} catch (Exception $e) {
    log_line('station_alarms', microtime(true) - $start, 'error');
    json_error(500, 'fetch_failed');
}
