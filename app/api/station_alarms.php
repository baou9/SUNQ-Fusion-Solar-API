<?php
declare(strict_types=1);

/** @var FusionSolarClient $client */

$code = $_GET['code'] ?? '';
if ($code === '') {
    json_fail(400, 'missing code');
}

$levelsParam = $_GET['levels'] ?? '';
$levelsStr = null;
if ($levelsParam !== '') {
    $parts = array_filter(array_map('trim', explode(',', $levelsParam)));
    $valid = [];
    foreach ($parts as $p) {
        if (ctype_digit($p) && (int)$p >= 1 && (int)$p <= 4) {
            $valid[] = $p;
        }
    }
    if ($valid) {
        $levelsStr = implode(',', $valid);
    } else {
        json_fail(400, 'invalid levels');
    }
}

$severity = $_GET['severity'] ?? '';
if ($severity !== '' && !$levelsStr) {
    $map = ['critical' => 1, 'major' => 2, 'minor' => 3, 'warning' => 4, 'info' => 4];
    $severity = strtolower($severity);
    if (isset($map[$severity])) $levelsStr = (string)$map[$severity];
}

$end = (int)(microtime(true) * 1000);
$begin = $end - 30 * 24 * 3600 * 1000;

try {
    $resp = $client->getAlarmList($code, $begin, $end, $levelsStr);
    $list = $resp['data'] ?? [];
    $alarms = [];
    foreach ($list as $al) {
        $num = (int)($al['lev'] ?? $al['level'] ?? 0);
        $txt = [1 => 'critical', 2 => 'major', 3 => 'minor', 4 => 'warning'][$num] ?? null;
        $alarms[] = [
            'stationCode' => $al['stationCode'] ?? '',
            'stationName' => $al['stationName'] ?? '',
            'devName' => $al['devName'] ?? '',
            'devTypeId' => $al['devTypeId'] ?? null,
            'esnCode' => $al['esnCode'] ?? null,
            'alarmId' => $al['alarmId'] ?? null,
            'alarmName' => $al['alarmName'] ?? '',
            'lev' => $num,
            'level' => $txt,
            'levelText' => $txt,
            'status' => $al['status'] ?? null,
            'raiseTime' => $al['raiseTime'] ?? $al['occurTime'] ?? null,
            'repairSuggestion' => $al['repairSuggestion'] ?? '',
        ];
    }
    json_success($alarms);
} catch (Throwable $e) {
    json_fail(502, 'Upstream error');
}
