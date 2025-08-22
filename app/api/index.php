<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/_util.php';
require_once __DIR__ . '/_logger.php';
require_once __DIR__ . '/_client.php';

$logger = new JsonLogger();
$client = new FusionSolarClient($CONFIG, $logger);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '/api/') === 0) {
    $uri = substr($uri, 4);
}
$segments = array_values(array_filter(explode('/', $uri)));

switch ($segments[0] ?? '') {
    case 'healthz':
        require __DIR__ . '/healthz.php';
        break;
    case 'stations':
        if (count($segments) === 1) {
            require __DIR__ . '/stations.php';
            break;
        }
        $_GET['code'] = $segments[1];
        $sub = $segments[2] ?? '';
        if ($sub === 'overview') {
            require __DIR__ . '/station_overview.php';
        } elseif ($sub === 'devices') {
            require __DIR__ . '/station_devices.php';
        } elseif ($sub === 'alarms') {
            require __DIR__ . '/station_alarms.php';
        } else {
            json_fail(404, 'Not found');
        }
        break;
    default:
        json_fail(404, 'Not found');
}
