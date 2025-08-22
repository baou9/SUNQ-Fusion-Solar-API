<?php
declare(strict_types=1);

/** @var FusionSolarClient $client */

$code = $_GET['code'] ?? '';
if ($code === '') {
    json_fail(400, 'missing code');
}

try {
    $resp = $client->getDevList($code);
    $list = $resp['data'] ?? [];
    $devices = [];
    foreach ($list as $d) {
        $devices[] = [
            'id' => $d['id'] ?? $d['devId'] ?? '',
            'devDn' => $d['devDn'] ?? '',
            'devName' => $d['devName'] ?? '',
            'devTypeId' => $d['devTypeId'] ?? null,
            'esnCode' => $d['esnCode'] ?? $d['esn'] ?? null,
            'softwareVersion' => $d['softVer'] ?? $d['softwareVersion'] ?? null,
            'invType' => $d['invType'] ?? null,
            'longitude' => $d['longitude'] ?? null,
            'latitude' => $d['latitude'] ?? null,
        ];
    }
    json_success($devices);
} catch (Throwable $e) {
    json_fail(502, 'Upstream error');
}
