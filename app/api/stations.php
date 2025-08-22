<?php
declare(strict_types=1);

/** @var FusionSolarClient $client */

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

try {
    $resp = $client->stations($page);
    $data = $resp['data'] ?? [];
    $list = [];
    foreach ($data['list'] ?? [] as $s) {
        $list[] = [
            'plantCode' => $s['stationCode'] ?? $s['plantCode'] ?? '',
            'plantName' => $s['stationName'] ?? $s['plantName'] ?? '',
            'plantAddress' => $s['stationAddr'] ?? $s['stationAddress'] ?? '',
            // aliases expected by the legacy SPA
            'code' => $s['stationCode'] ?? $s['plantCode'] ?? '',
            'name' => $s['stationName'] ?? $s['plantName'] ?? '',
            'city' => $s['stationAddr'] ?? $s['stationAddress'] ?? '',
            'latitude' => $s['latitude'] ?? null,
            'longitude' => $s['longitude'] ?? null,
            'capacity' => $s['capacity'] ?? null,
            'gridConnectionDate' => $s['gridConnectionDate'] ?? $s['gridConnectedDate'] ?? null,
        ];
    }
    $result = [
        'pageNo' => $data['pageNo'] ?? $page,
        'pageSize' => 100,
        'pageCount' => $data['pageCount'] ?? 0,
        'total' => $data['total'] ?? count($list),
        'list' => $list,
    ];
    json_success($result);
} catch (Throwable $e) {
    json_fail(502, 'Upstream error');
}
