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

$end = (int)(microtime(true) * 1000);
$begin = $end - 30 * 24 * 3600 * 1000;

try {
    $resp = $client->getAlarmList($code, $begin, $end, $levelsStr);
    $list = $resp['data'] ?? [];
    $alarms = [];
    foreach ($list as $al) {
        $lev = $al['lev'] ?? $al['level'] ?? null;
        $levelMap = [1 => 'critical', 2 => 'major', 3 => 'minor', 4 => 'warning'];
        $alarms[] = [
            'stationCode' => $al['stationCode'] ?? '',
            'stationName' => $al['stationName'] ?? '',
            'devName' => $al['devName'] ?? '',
            'devTypeId' => $al['devTypeId'] ?? null,
            'esnCode' => $al['esnCode'] ?? null,
            'alarmId' => $al['alarmId'] ?? null,
            'alarmName' => $al['alarmName'] ?? '',
            'lev' => $lev,
            'levelText' => $lev !== null ? ($levelMap[(int)$lev] ?? null) : null,
            'status' => $al['status'] ?? null,
            'raiseTime' => $al['raiseTime'] ?? $al['occurTime'] ?? null,
            'repairSuggestion' => $al['repairSuggestion'] ?? '',
        ];
    }
    json_success($alarms);
} catch (Throwable $e) {
    json_fail(502, 'Upstream error');
}
