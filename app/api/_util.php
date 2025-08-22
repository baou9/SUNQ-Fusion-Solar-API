<?php
// Common utilities: config loader, JSON helpers, logging, CORS

$CONFIG = require __DIR__ . '/../config/.env.php';

// Send CORS and JSON headers
function send_headers() {
    global $CONFIG;
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . $CONFIG['ALLOWED_ORIGIN']);
    header('Vary: Origin');
}

// Successful JSON response
function json_ok($data) {
    send_headers();
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

// Error JSON response
function json_error($httpCode, $error, $details = []) {
    send_headers();
    http_response_code($httpCode);
    echo json_encode(['ok' => false, 'error' => $error, 'details' => $details]);
    exit;
}

// Basic structured logging
function log_line($endpoint, $latency, $cache) {
    $ts = date('c');
    $msg = sprintf('[%s] endpoint=%s latency=%.3fs cache=%s', $ts, $endpoint, $latency, $cache);
    error_log($msg);
}
