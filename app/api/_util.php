<?php
/** Utility helpers for API endpoints */

// Load configuration once
function app_config(): array {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/.env.php';
    }
    return $cfg;
}

// Send common CORS and JSON success response
function json_ok($data): void {
    cors();
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

// Send error response
function json_error(int $code, string $error, array $details = []): void {
    cors();
    http_response_code($code);
    header('Content-Type: application/json');
    $body = ['ok' => false, 'error' => $error];
    if ($details) {
        $body['details'] = $details;
    }
    echo json_encode($body);
    exit;
}

// Basic CORS header
function cors(): void {
    $cfg = app_config();
    header('Access-Control-Allow-Origin: ' . ($cfg['ALLOWED_ORIGIN'] ?? '*'));
    header('Vary: Origin');
}

// Basic structured log line
function log_line(string $endpoint, float $start, string $cache = 'MISS'): void {
    $ms = round((microtime(true) - $start) * 1000, 2);
    error_log(date('c') . " $endpoint [$cache] {$ms}ms");
}
