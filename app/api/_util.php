<?php
// Utility helpers: config loader, responses, logging, IDs

function env($key, $default = null) {
    $val = getenv($key);
    return $val !== false ? $val : $default;
}

$CONFIG = [
    'FS_BASE' => rtrim(env('FS_BASE', 'https://intl.fusionsolar.huawei.com'), '/'),
    'FS_USER' => env('FS_USER'),
    'FS_CODE' => env('FS_CODE'),
    'MA_PROXY' => env('MA_PROXY'),
    'CACHE_TTL_SECONDS' => (int)env('CACHE_TTL_SECONDS', 90),
    'ALLOWED_ORIGIN' => env('ALLOWED_ORIGIN'),
    'APP_VERSION' => env('APP_VERSION', 'dev'),
];

function send_headers() {
    global $CONFIG;
    header('Content-Type: application/json');
    if (!empty($CONFIG['ALLOWED_ORIGIN'])) {
        header('Access-Control-Allow-Origin: ' . $CONFIG['ALLOWED_ORIGIN']);
        header('Vary: Origin');
    }
}

function uuidv4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function get_req_id() {
    static $id = null;
    if ($id) return $id;
    $id = $_SERVER['HTTP_X_REQUEST_ID'] ?? uuidv4();
    return $id;
}

function json_ok($data) {
    send_headers();
    echo json_encode($data);
    exit;
}

function json_error($status, $code, $message) {
    send_headers();
    http_response_code($status);
    echo json_encode([
        'error' => [
            'code' => $code,
            'status' => $status,
            'message' => $message,
            'req_id' => get_req_id(),
        ]
    ]);
    exit;
}

function log_json($fields) {
    $fields['ts'] = date('c');
    error_log(json_encode($fields));
}
?>
