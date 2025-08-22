<?php
require_once __DIR__ . '/_client.php';
require_once __DIR__ . '/_util.php';

$start = microtime(true);
try {
    fs_login();
    log_line('/api/login', $start, 'MISS');
    json_ok(['loggedIn' => true]);
} catch (Exception $e) {
    log_line('/api/login', $start, 'ERR');
    json_error(500, 'login_failed');
}
