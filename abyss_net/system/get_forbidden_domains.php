<?php
header('Content-Type: application/json');
$domains = include './forbidden_domains.php';

if (is_array($domains)) {
    echo json_encode(['domains' => array_values($domains)]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Не удалось загрузить список доменов']);
}