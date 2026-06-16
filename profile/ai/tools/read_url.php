<?php
require_once __DIR__ . '/../../../template/auth.php';

auth_start_session();
auth_sync_session_from_token();

header('Content-Type: application/json; charset=utf-8');

if (auth_get_current_user() === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

$url = (string)($_GET['url'] ?? '');
if (trim($url) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Пустой URL']);
    exit;
}

try {
    security_assert_public_url($url);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_MAXREDIRS => 0,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_ENCODING => '',
    CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; DarkAI/1.0; +https://so-ta.ru)',
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,text/plain;q=0.8,*/*;q=0.7',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection: close',
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? '';
$error = curl_error($ch);
$errno = curl_errno($ch);

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
$isHtml = str_contains($baseContentType, 'text/html');
$isPlainText = str_contains($baseContentType, 'text/plain');

if (!$isHtml && !$isPlainText) {
    http_response_code(400);
    echo json_encode(['error' => 'Неподдерживаемый тип контента: ' . $baseContentType]);
    exit;
}

if ($isHtml) {
    $response = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $response);
    $response = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $response);
    $response = strip_tags($response);
    $response = html_entity_decode($response, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $response = preg_replace('/[ \t]+/', ' ', $response);
    $response = preg_replace('/(\r?\n){3,}/', "\n\n", $response);
    $response = trim($response);
}

if ($response === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Страница получена, но текстового содержимого не найдено']);
    exit;
}

if (mb_strlen($response, 'UTF-8') > 50000) {
    $response = mb_substr($response, 0, 50000, 'UTF-8') . "\n\n[... содержимое обрезано ...]";
}

echo json_encode([
    'url' => $url,
    'content' => $response,
    'length' => mb_strlen($response, 'UTF-8'),
], JSON_UNESCAPED_UNICODE);
