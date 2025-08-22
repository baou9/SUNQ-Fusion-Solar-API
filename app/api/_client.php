<?php
// FusionSolar HTTP client with proxy, cookies, login, caching and logging
require_once __DIR__ . '/_util.php';
require_once __DIR__ . '/_cache.php';

function fs_cookie_file() {
    $dir = __DIR__ . '/../storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    $file = $dir . '/cookies.txt';
    if (!file_exists($file)) {
        touch($file);
        chmod($file, 0600);
    }
    return $file;
}

function fs_get_xsrf() {
    $file = fs_cookie_file();
    if (!file_exists($file)) {
        return null;
    }
    foreach (file($file) as $line) {
        if (strpos($line, "XSRF-TOKEN") !== false) {
            $parts = explode("\t", trim($line));
            return end($parts);
        }
    }
    return null;
}

function fs_login($force = false) {
    global $CONFIG;
    if (!$force && fs_get_xsrf()) {
        return true;
    }
    $url = $CONFIG['FS_BASE'] . '/thirdData/login';
    $payload = json_encode([
        'userName' => $CONFIG['FS_USER'],
        'systemCode' => $CONFIG['FS_CODE'],
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_PROXY => $CONFIG['MA_PROXY'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_COOKIEJAR => fs_cookie_file(),
        CURLOPT_COOKIEFILE => fs_cookie_file(),
    ]);
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false || $status !== 200) {
        throw new Exception('login_failed', $status ?: 500);
    }
    return true;
}

function fs_request($method, $path, $query = [], $body = null) {
    global $CONFIG;
    $reqId = get_req_id();
    $url = $CONFIG['FS_BASE'] . $path;
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    $bodyStr = $body ? json_encode($body) : null;
    $cacheKey = cache_key($method, $path, $query, $bodyStr);
    $start = microtime(true);
    $cacheHit = false;

    if ($cached = cache_get($cacheKey)) {
        $cacheHit = true;
        log_json(['req_id'=>$reqId,'method'=>$method,'url_path'=>$path,'status'=>200,'latency_ms'=>0,'cache_hit'=>true,'retries'=>0]);
        return $cached;
    }

    fs_login();
    $headers = [];
    if ($bodyStr !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($token = fs_get_xsrf()) {
        $headers[] = 'XSRF-TOKEN: ' . $token;
    }

    $retries = 0;
    while (true) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_PROXY => $CONFIG['MA_PROXY'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIEJAR => fs_cookie_file(),
            CURLOPT_COOKIEFILE => fs_cookie_file(),
        ]);
        if ($bodyStr !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr);
        }
        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res === false) {
            $status = 502;
            $error = $err ?: 'curl_error';
        } else {
            $json = json_decode($res, true);
            if ($status == 401 || (isset($json['failCode']) && $json['failCode'] === 'USER_MUST_RELOGIN')) {
                fs_login(true);
                $retries++;
                if ($retries > 1) break; // avoid infinite
                continue;
            }
            if ($status >= 500 && $status < 600) {
                $retries++;
                if ($retries > 1) break;
                usleep(rand(100,300) * 1000);
                continue;
            }
            if ($status == 200 && is_array($json)) {
                cache_put($cacheKey, $json, $CONFIG['CACHE_TTL_SECONDS']);
            }
        }
        break;
    }

    $latency = (microtime(true) - $start) * 1000;
    $log = ['req_id'=>$reqId,'method'=>$method,'url_path'=>$path,'status'=>$status,'latency_ms'=>(int)$latency,'cache_hit'=>$cacheHit,'retries'=>$retries];
    if (isset($error)) $log['error'] = $error;
    log_json($log);

    if (!isset($json) || $status != 200 || !is_array($json)) {
        throw new Exception('upstream_error', $status ?: 502);
    }
    return $json;
}
?>
