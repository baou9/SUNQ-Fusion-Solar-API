<?php
declare(strict_types=1);

/** @var FusionSolarClient $client */

$code = $_GET['code'] ?? '';
$year = (int)($_GET['year'] ?? date('Y'));
if ($code === '') {
    json_fail(400, 'missing code');
}

try {
    $resp = $client->getKpiStationYear($code, $year);
    $data = [];
    foreach (($resp['data'] ?? []) as $item) {
        $data[] = [
            'month' => (int)($item['month'] ?? 0),
            'energy' => $item['energy'] ?? $item['month_power'] ?? null,
        ];
    }
    json_success($data);
} catch (Throwable $e) {
    json_fail(502, 'Upstream error');
}
