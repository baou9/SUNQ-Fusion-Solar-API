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

function uuidv4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function get_request_id(): string {
    static $id = null;
    if ($id) return $id;
    $id = $_SERVER['HTTP_X_REQUEST_ID'] ?? uuidv4();
    return $id;
}

function json_success($data): void {
    send_headers();
    header('X-Request-Id: ' . get_request_id());
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

function json_fail(int $status, string $message): void {
    send_headers();
    header('X-Request-Id: ' . get_request_id());
    http_response_code($status);
    echo json_encode([
        'ok' => false,
        'error' => [
            'message' => $message,
            'requestId' => get_request_id(),
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
