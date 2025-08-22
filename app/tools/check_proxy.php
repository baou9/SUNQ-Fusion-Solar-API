<?php
$cfg = require __DIR__ . '/../config/.env.php';
$ch = curl_init('https://ifconfig.me');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_PROXY => $cfg['MA_PROXY'],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
]);
$res = curl_exec($ch);
if ($res === false) {
    echo "Proxy unreachable\n";
} else {
    echo "Proxy OK: $res\n";
}
