<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');

if ($path === '/thirdData/login') {
    // simulate login with xsrf header and cookie
    header('xsrf-token: mock-token');
    header('Set-Cookie: XSRF-TOKEN=mock-token; Path=/; HttpOnly');
    echo json_encode(['data' => true, 'failCode' => 0]);
    return;
}

if ($path === '/thirdData/stations') {
    echo json_encode([
        'data' => [
            'pageNo' => 1,
            'pageCount' => 1,
            'total' => 1,
            'list' => [[
                'stationCode' => '001',
                'stationName' => 'Mock Station',
                'stationAddr' => 'Rabat',
                'latitude' => 34.0,
                'longitude' => -6.0,
                'capacity' => 10,
                'gridConnectionDate' => '2023-01-01',
            ]],
        ],
        'failCode' => 0,
    ]);
    return;
}

if ($path === '/thirdData/getStationRealKpi') {
    echo json_encode([
        'data' => [[
            'inverter_power' => 5,
            'day_power' => 10,
            'total_power' => 100,
            'installed_capacity' => 20,
            'performance_ratio' => 0.9,
        ]],
        'failCode' => 0,
    ]);
    return;
}

if ($path === '/thirdData/getDevList') {
    echo json_encode([
        'data' => [[
            'id' => 1,
            'devDn' => 'dn1',
            'devName' => 'Mock Device',
            'devTypeId' => 1,
            'esnCode' => 'ESN1',
            'softVer' => '1.0',
            'invType' => 'INV',
            'longitude' => 0,
            'latitude' => 0,
        ]],
        'failCode' => 0,
    ]);
    return;
}

if ($path === '/thirdData/getAlarmList') {
    echo json_encode([
        'data' => [[
            'stationCode' => '001',
            'stationName' => 'Mock Station',
            'devName' => 'Mock Device',
            'devTypeId' => 1,
            'esnCode' => 'ESN1',
            'alarmId' => 'A1',
            'alarmName' => 'High Temp',
            'lev' => 1,
            'status' => 1,
            'raiseTime' => 1700000000,
            'repairSuggestion' => 'Check',
        ]],
        'failCode' => 0,
    ]);
    return;
}

if ($path === '/thirdData/getKpiStationDay') {
    echo json_encode([
        'data' => [[
            'day_power' => 10,
            'total_power' => 100,
        ]],
        'failCode' => 0,
    ]);
    return;
}

http_response_code(404);
echo json_encode(['error' => 'not found']);
