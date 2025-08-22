<?php
// FusionSolar HTTP helper: cURL + proxy + cookie jar + XSRF
require_once __DIR__ . '/_util.php';

function fs_cookie_file() {
    return __DIR__ . '/../storage/cookies.txt';
}

function fs_get_xsrf() {
    $file = fs_cookie_file();
    if (!file_exists($file)) {
        return null;
    }
    $lines = file($file);
    foreach ($lines as $line) {
        if (strpos($line, 'XSRF-TOKEN') !== false) {
            $parts = explode("\t", trim($line));
            return end($parts);
        }
    }
    return null;
}

function fs_login($force = false) {
    global $CONFIG;
    if (!$force && fs_get_xsrf()) {
        return;
    }
    $payload = [
        'userName' => $CONFIG['FS_USER'],
        'systemCode' => $CONFIG['FS_CODE'],
    ];
    $url = rtrim($CONFIG['FS_BASE'], '/') . '/thirdData/login';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_PROXY => $CONFIG['MA_PROXY'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_COOKIEJAR => fs_cookie_file(),
        CURLOPT_COOKIEFILE => fs_cookie_file(),
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Login failed');
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        throw new Exception('Login failed');
    }
}

function fs_request($method, $path, $payload = null, $query = []) {
    global $CONFIG;
    fs_login();
    $url = rtrim($CONFIG['FS_BASE'], '/') . $path;
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    $headers = ['Content-Type: application/json'];
    if ($token = fs_get_xsrf()) {
        $headers[] = 'XSRF-TOKEN: ' . trim($token);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_PROXY => $CONFIG['MA_PROXY'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIEJAR => fs_cookie_file(),
        CURLOPT_COOKIEFILE => fs_cookie_file(),
    ]);
    if ($payload) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) {
        throw new Exception('Request error');
    }
    if ($code == 401 || strpos($res, 'USER_MUST_RELOGIN') !== false) {
        fs_login(true);
        return fs_request($method, $path, $payload, $query);
    }
    if ($code >= 500 && $code < 600) {
        // retry once on server error
        usleep(100000);
        return fs_request($method, $path, $payload, $query);
    }
    return json_decode($res, true);
}
