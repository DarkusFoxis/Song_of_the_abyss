<?php
session_start();
require_once __DIR__ . '/../../../template/auth.php';
auth_sync_session_from_token();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

$url = $_GET['url'] ?? '';

if (empty(trim($url))) {
    http_response_code(400);
    echo json_encode(['error' => 'Пустой URL']);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный URL']);
    exit;
}

$parsedUrl = parse_url($url);
if (!isset($parsedUrl['scheme']) || !in_array(strtolower($parsedUrl['scheme']), ['http', 'https'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Разрешены только http и https протоколы']);
    exit;
}

$host = $parsedUrl['host'] ?? '';
if (empty($host)) {
    http_response_code(400);
    echo json_encode(['error' => 'Не удалось определить хост']);
    exit;
}

$resolvedIp = gethostbyname($host);
if (filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
    $privateRanges = [
        ['0.0.0.0',       '0.255.255.255'],
        ['10.0.0.0',      '10.255.255.255'],
        ['100.64.0.0',    '100.127.255.255'],
        ['127.0.0.0',     '127.255.255.255'],
        ['169.254.0.0',   '169.254.255.255'],
        ['172.16.0.0',    '172.31.255.255'],
        ['192.0.0.0',     '192.0.0.255'],
        ['192.168.0.0',   '192.168.255.255'],
        ['198.18.0.0',    '198.19.255.255'],
        ['198.51.100.0',  '198.51.100.255'],
        ['203.0.113.0',   '203.0.113.255'],
        ['240.0.0.0',     '255.255.255.255'],
    ];
    $ipLong = ip2long($resolvedIp);
    foreach ($privateRanges as [$start, $end]) {
        if ($ipLong >= ip2long($start) && $ipLong <= ip2long($end)) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ к локальным адресам запрещён']);
            exit;
        }
    }
}

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_ENCODING       => '',
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; DarkAI/1.0; +https://so-ta.ru)',
    CURLOPT_HTTPHEADER     => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection: close',
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response    = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? '';
$error       = curl_error($ch);
$errno       = curl_errno($ch);

curl_close($ch);

if ($errno || $response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Невозможно получить страницу: ' . ($error ?: 'Неизвестная ошибка curl')]);
    exit;
}

if ($httpCode >= 400) {
    http_response_code(502);
    echo json_encode(['error' => "Сервер вернул HTTP $httpCode"]);
    exit;
}

$baseContentType = strtolower(explode(';', $contentType)[0]);
$isHtml      = str_contains($baseContentType, 'text/html');
$isPlainText = str_contains($baseContentType, 'text/plain');

if (!$isHtml && !$isPlainText) {
    http_response_code(400);
    echo json_encode(['error' => 'Неподдерживаемый тип контента: ' . $baseContentType]);
    exit;
}

if ($isHtml) {
    $response = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $response);
    $response = preg_replace('/<style\b[^>]*>.*?<\/style>/is',  '', $response);
    $response = strip_tags($response);
    $response = html_entity_decode($response, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $response = preg_replace('/[ \t]+/', ' ', $response);
    $response = preg_replace('/(\r?\n){3,}/', "\n\n", $response);
    $response = trim($response);
}

if (empty($response)) {
    http_response_code(422);
    echo json_encode(['error' => 'Страница получена, но текстового содержимого не найдено']);
    exit;
}

if (mb_strlen($response, 'UTF-8') > 50000) {
    $response = mb_substr($response, 0, 50000, 'UTF-8') . "\n\n[... содержимое обрезано ...]";
}

echo json_encode([
    'url'     => $url,
    'content' => $response,
    'length'  => mb_strlen($response, 'UTF-8'),
], JSON_UNESCAPED_UNICODE);