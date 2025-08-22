<?php
declare(strict_types=1);

function env(string $key, $default = null) {
    $val = getenv($key);
    return $val !== false ? $val : $default;
}

$CONFIG = [
    'FS_BASE' => rtrim((string)env('FS_BASE', 'https://intl.fusionsolar.huawei.com'), '/'),
    'FS_USER' => env('FS_USER'),
    'FS_CODE' => env('FS_CODE'),
    'MA_PROXY' => env('MA_PROXY'),
    'CACHE_TTL_SECONDS' => (int)env('CACHE_TTL_SECONDS', 90),
    'CACHE_BACKEND' => env('CACHE_BACKEND', 'memory'),
    'FRONTEND_ORIGIN' => env('FRONTEND_ORIGIN'),
    'APP_VERSION' => env('APP_VERSION', 'dev'),
    'RATE_LIMIT_PER_MINUTE' => (int)env('RATE_LIMIT_PER_MINUTE', 0),
];

function send_headers(): void {
    $origin = getenv('FRONTEND_ORIGIN') ?: '';
    header('Content-Type: application/json');
    if ($origin !== '') {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    }
}

function handle_preflight_and_headers(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        send_headers();
        http_response_code(204);
        exit;
    }
}

function get_request_id(): string {
    return $GLOBALS['REQUEST_ID'] ?? ($GLOBALS['REQUEST_ID'] = bin2hex(random_bytes(8)));
}

function json_success($data): void {
    send_headers();
    if (!headers_sent()) {
        header('X-Request-Id: ' . ($GLOBALS['REQUEST_ID'] ?? ($GLOBALS['REQUEST_ID'] = bin2hex(random_bytes(8)))));
    }
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

function json_fail(int $status, string $message): void {
    send_headers();
    if (!headers_sent()) {
        header('X-Request-Id: ' . ($GLOBALS['REQUEST_ID'] ?? ($GLOBALS['REQUEST_ID'] = bin2hex(random_bytes(8)))));
    }
    http_response_code($status);
    $reqId = $GLOBALS['REQUEST_ID'] ?? '';
    echo json_encode([
        'ok' => false,
        'error' => [
            'message' => $message,
            'requestId' => $reqId,
        ],
    ]);
    exit;
}

function enforce_rate_limit(int $limit): void {
    if ($limit <= 0) return;
    static $buckets = [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = microtime(true);
    $rate = $limit / 60;
    $bucket = $buckets[$ip] ?? ['tokens' => $limit, 'updated' => $now];
    $tokens = $bucket['tokens'] + ($now - $bucket['updated']) * $rate;
    $tokens = min($limit, $tokens);
    if ($tokens < 1) {
        $buckets[$ip] = ['tokens' => $tokens, 'updated' => $now];
        json_fail(429, 'rate_limited');
    }
    $buckets[$ip] = ['tokens' => $tokens - 1, 'updated' => $now];
}
