<?php
declare(strict_types=1);

require_once __DIR__ . '/_util.php';

use GuzzleHttp\Client;

$proxyOk = false;
try {
    $client = new Client([
        'proxy' => $CONFIG['MA_PROXY'],
        'timeout' => 10,
        'connect_timeout' => 5,
    ]);
    $res = $client->request('HEAD', 'https://ifconfig.me');
    $proxyOk = $res->getStatusCode() > 0;
} catch (Throwable $e) {
    $proxyOk = false;
}

json_success([
    'version' => $CONFIG['APP_VERSION'],
    'proxyReachable' => $proxyOk,
]);
