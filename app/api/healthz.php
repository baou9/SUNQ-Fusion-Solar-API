<?php
require_once __DIR__ . '/_util.php';

$start = microtime(true);
$cfg = app_config();
$proxyOk = false;

$ch = curl_init('https://ifconfig.me');
curl_setopt_array($ch, [
    CURLOPT_NOBODY => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_PROXY => $cfg['MA_PROXY'],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
]);
if (curl_exec($ch) !== false) {
    $proxyOk = true;
}
curl_close($ch);

log_line('/api/healthz', $start, 'MISS');
json_ok([
    'ok' => true,
    'env' => $cfg['APP_ENV'],
    'proxyReachable' => $proxyOk,
    'time' => date('c'),
]);
