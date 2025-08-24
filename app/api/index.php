<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$staticFile = __DIR__ . '/../' . ltrim($uri, '/');
if ($uri !== '/' && file_exists($staticFile)) {
    return false;
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/_util.php';
handle_preflight_and_headers();
$GLOBALS['REQUEST_ID'] = $GLOBALS['REQUEST_ID'] ?? bin2hex(random_bytes(8));
require_once __DIR__ . '/_logger.php';
$logger = new JsonLogger();
$missingEnv = [];
foreach (['FS_BASE', 'FS_USER', 'FS_CODE', 'MA_PROXY'] as $k) {
    if (empty($CONFIG[$k])) $missingEnv[] = $k;
}
if ($missingEnv) {
    $logger->error('missing_env', ['requestId' => get_request_id(), 'fields' => $missingEnv]);
}
require_once __DIR__ . '/_client.php';
if (!$missingEnv) {
    $client = new FusionSolarClient($CONFIG, $logger);
}

// deny direct access to storage directory
if (preg_match('#^/storage(/|$)#', $uri)) {
    http_response_code(404);
    exit;
}
if ($missingEnv && strpos($uri, '/api/') === 0) {
    send_headers();
    header('X-Request-Id: ' . get_request_id());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => [
            'message' => 'missing_env',
            'fields' => $missingEnv,
            'requestId' => get_request_id(),
        ],
    ]);
    exit;
}
if (strpos($uri, '/api/') === 0) {
    enforce_rate_limit($CONFIG['RATE_LIMIT_PER_MINUTE']);
    $uri = substr($uri, 4);
}
$segments = array_values(array_filter(explode('/', $uri)));

switch ($segments[0] ?? '') {
    case 'healthz':
        require __DIR__ . '/healthz.php';
        break;
    case 'stations':
        if (count($segments) === 1) {
            require __DIR__ . '/stations.php';
            break;
        }
        $_GET['code'] = $segments[1];
        $sub = $segments[2] ?? '';
        if ($sub === 'overview') {
            require __DIR__ . '/station_overview.php';
        } elseif ($sub === 'devices') {
            require __DIR__ . '/station_devices.php';
        } elseif ($sub === 'alarms') {
            require __DIR__ . '/station_alarms.php';
        } elseif ($sub === 'monthly') {
            require __DIR__ . '/station_monthly.php';
        } else {
            json_fail(404, 'Not found');
        }
        break;
    default:
        json_fail(404, 'Not found');
}
