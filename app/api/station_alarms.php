<?php
require_once __DIR__ . '/_client.php';
$ALARM_MAP = require __DIR__ . '/error_codes.php';

$code = $_GET['code'] ?? '';
$severity = $_GET['severity'] ?? 'all';
$allowed = ['critical','major','minor','info','all'];
if (!$code) {
    json_error(400, 'BAD_REQUEST', 'missing code');
}
if (!in_array(strtolower($severity), $allowed, true)) {
    json_error(400, 'BAD_REQUEST', 'invalid severity');
}

try {
    $body = ['stationCodes' => [$code], 'pageNo' => 1, 'pageSize' => 200];
    $resp = fs_request('POST', '/thirdData/stationAlarmList', [], $body);
    $list = $resp['data']['list'] ?? [];
    $alarms = [];
    foreach ($list as $al) {
        if (($al['status'] ?? '') !== '1') continue; // only active
        $id = $al['alarmId'] ?? '';
        $level = strtolower($al['alarmLevel'] ?? '');
        if ($severity !== 'all' && $level !== strtolower($severity)) continue;
        $alarms[] = [
            'id' => $id,
            'deviceId' => $al['devId'] ?? '',
            'level' => $level,
            'message' => $ALARM_MAP[$id] ?? 'Unknown alarm',
            'startTime' => $al['occurTime'] ?? '',
        ];
    }
    json_ok($alarms);
} catch (Exception $e) {
    $status = $e->getCode() >= 400 ? $e->getCode() : 502;
    json_error($status, 'UPSTREAM_ERROR', 'alarm list failed');
}
?>
