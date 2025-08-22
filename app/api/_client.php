<?php
/** FusionSolar HTTP helper */
require_once __DIR__ . '/_util.php';

function cookie_file(): string {
    return __DIR__ . '/../storage/cookies.txt';
}

// Extract XSRF token from cookie jar
function read_xsrf_token(): ?string {
    $file = cookie_file();
    if (!file_exists($file)) {
        return null;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if ($line[0] === '#') {
            continue;
        }
        $parts = preg_split('/\t/', $line);
        if (isset($parts[5]) && $parts[5] === 'XSRF-TOKEN') {
            return trim($parts[6] ?? '');
        }
    }
    return null;
}

// Ensure we are logged in
function fs_login(): void {
    $cfg = app_config();
    $url = rtrim($cfg['FS_BASE'], '/') . '/thirdData/login';
    $payload = json_encode(['userName' => $cfg['FS_USER'], 'systemCode' => $cfg['FS_CODE']]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => cookie_file(),
        CURLOPT_COOKIEFILE => cookie_file(),
        CURLOPT_PROXY => $cfg['MA_PROXY'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status < 200 || $status >= 300) {
        throw new Exception('Login failed');
    }
}

// Make a request to FusionSolar API
function fs_request(string $method, string $path, ?array $payload = null, array $query = [], bool $retry = true): array {
    $cfg = app_config();
    $base = rtrim($cfg['FS_BASE'], '/');
    $url = $base . $path;
    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    $xsrf = read_xsrf_token();
    if (!$xsrf) {
        fs_login();
        $xsrf = read_xsrf_token();
    }

    $headers = ['Content-Type: application/json'];
    if ($xsrf) {
        $headers[] = 'XSRF-TOKEN: ' . $xsrf;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => cookie_file(),
        CURLOPT_COOKIEFILE => cookie_file(),
        CURLOPT_PROXY => $cfg['MA_PROXY'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new Exception('cURL error');
    }

    $data = json_decode($body, true);

    // Retry logic
    if (($status >= 500 && $status < 600) && $retry) {
        usleep(random_int(100000, 500000));
        return fs_request($method, $path, $payload, $query, false);
    }
    if (($status == 401 || ($data['code'] ?? '') === 'USER_MUST_RELOGIN') && $retry) {
        fs_login();
        return fs_request($method, $path, $payload, $query, false);
    }

    return ['status' => $status, 'body' => $data];
}
