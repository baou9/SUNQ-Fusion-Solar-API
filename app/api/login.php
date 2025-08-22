<?php
require_once __DIR__ . '/_client.php';

try {
    fs_login(true);
    json_ok(['loggedIn' => true]);
} catch (Exception $e) {
    json_error(500, 'login_failed');
}
