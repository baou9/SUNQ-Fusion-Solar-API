<?php
require_once __DIR__ . '/../api/_util.php';

$ch = curl_init('https://ifconfig.me');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_PROXY => $CONFIG['MA_PROXY'],
    CURLOPT_TIMEOUT => 10,
]);
$res = curl_exec($ch);
$ok = (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200);
curl_close($ch);

header('Content-Type: text/plain');
if ($ok) {
    echo "Proxy OK: $res";
} else {
    echo "Proxy FAILED";
}
