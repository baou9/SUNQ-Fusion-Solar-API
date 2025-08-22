<?php
declare(strict_types=1);

/** @var FusionSolarClient $client */

$code = $_GET['code'] ?? '';
if ($code === '') {
    json_fail(400, 'missing code');
}

try {
    $resp = $client->getStationRealKpi($code);
    $kpi = ($resp['data'] ?? [])[0] ?? [];
    $currentPower = $kpi['inverter_power'] ?? $kpi['power'] ?? $kpi['ongrid_power'] ?? null;
    $installedCapacity = $kpi['installed_capacity'] ?? null;
    $performanceRatio = $kpi['performance_ratio'] ?? $kpi['perpower_ratio'] ?? null;
    $todayEnergy = $kpi['day_power'] ?? $kpi['dayEnergy'] ?? $kpi['PVYield'] ?? $kpi['today_energy'] ?? null;
    $totalEnergy = $kpi['total_power'] ?? $kpi['totalEnergy'] ?? $kpi['total_yield'] ?? null;
    if ($todayEnergy === null || $totalEnergy === null) {
        $dayResp = $client->getKpiStationDay($code, (int)(microtime(true) * 1000));
        $dayData = ($dayResp['data'] ?? [])[0] ?? [];
        if ($todayEnergy === null) {
            $todayEnergy = $dayData['day_power'] ?? $dayData['dayEnergy'] ?? $dayData['prodIn'] ?? null;
        }
        if ($totalEnergy === null) {
            $totalEnergy = $dayData['total_power'] ?? $dayData['totalEnergy'] ?? $dayData['total_yield'] ?? null;
        }
    }
    $result = [
        'currentPower' => $currentPower,
        'todayEnergy' => $todayEnergy,
        'totalEnergy' => $totalEnergy,
        'installedCapacity' => $installedCapacity,
        'performanceRatio' => $performanceRatio,
        // alias for legacy SPA
        'perpowerRatio' => $performanceRatio,
    ];
    json_success($result);
} catch (Throwable $e) {
    json_fail(502, 'Upstream error');
}
