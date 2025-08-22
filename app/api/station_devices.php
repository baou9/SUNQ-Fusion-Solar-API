<?php
require_once __DIR__ . '/_client.php';

$code = $_GET['code'] ?? '';
if (!$code) {
    json_error(400, 'BAD_REQUEST', 'missing code');
}

try {
    $body = ['stationCode' => $code, 'pageNo' => 1, 'pageSize' => 200];
    $resp = fs_request('POST', '/thirdData/stationDevList', [], $body);
    $list = $resp['data']['list'] ?? [];
    $devices = [];
    foreach ($list as $d) {
        $devices[] = [
            'id' => $d['devId'] ?? ($d['id'] ?? ''),
            'type' => $d['devTypeName'] ?? '',
            'model' => $d['model'] ?? ($d['devTypeName'] ?? ''),
            'status' => $d['devState'] ?? '',
            'ratedPower' => $d['nominalPower'] ?? null,
            'sn' => $d['esn'] ?? null,
        ];
    }
    json_ok($devices);
} catch (Exception $e) {
    $status = $e->getCode() >= 400 ? $e->getCode() : 502;
    json_error($status, 'UPSTREAM_ERROR', 'device list failed');
}
?>
