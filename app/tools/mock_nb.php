<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');
if ($path === '/thirdData/login') {
    header('Set-Cookie: XSRF-TOKEN=mock; Path=/');
    echo json_encode(['data' => true]);
    return;
}
if ($path === '/thirdData/stationList') {
    echo json_encode(['data' => ['list' => [
        ['stationCode'=>'001','stationName'=>'Mock Station','capacity'=>10,'city'=>'Rabat']
    ]]]);
    return;
}
http_response_code(404);
?>
