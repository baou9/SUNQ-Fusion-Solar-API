<?php
require_once __DIR__ . '/_util.php';

$proxyOk = false;
$ch = curl_init('https://ifconfig.me');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY => true,
    CURLOPT_PROXY => $CONFIG['MA_PROXY'],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
]);
curl_exec($ch);
if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) > 0) {
    $proxyOk = true;
}
curl_close($ch);

send_headers();
echo json_encode([
    'ok' => true,
    'env' => $CONFIG['APP_ENV'],
    'proxyReachable' => $proxyOk,
    'time' => date('c'),
]);
