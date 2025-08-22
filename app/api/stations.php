<?php
require_once __DIR__ . '/_client.php';

$pageNo = isset($_GET['pageNo']) ? (int)$_GET['pageNo'] : 1;
$pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 50;
if ($pageNo < 1) $pageNo = 1;
if ($pageSize < 1) $pageSize = 1;
$pageNo = min($pageNo, 100);
$pageSize = min($pageSize, 200);

try {
    $body = ['pageNo' => $pageNo, 'pageSize' => $pageSize];
    $resp = fs_request('POST', '/thirdData/stationList', [], $body);
    $list = $resp['data']['list'] ?? $resp['data'] ?? [];
    $stations = [];
    foreach ($list as $s) {
        $stations[] = [
            'code' => $s['stationCode'] ?? $s['id'] ?? '',
            'name' => $s['stationName'] ?? '',
            'capacity' => $s['capacity'] ?? null,
            'city' => $s['city'] ?? '',
        ];
    }
    json_ok($stations);
} catch (Exception $e) {
    $status = $e->getCode() >= 400 ? $e->getCode() : 502;
    json_error($status, 'UPSTREAM_ERROR', 'station list failed');
}
?>
