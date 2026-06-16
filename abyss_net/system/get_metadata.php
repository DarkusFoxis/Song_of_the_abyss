<?php
require_once __DIR__ . '/../../template/auth.php';

auth_start_session();
auth_sync_session_from_token();

header('Content-Type: application/json; charset=utf-8');

if (auth_get_current_user() === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$url = (string)($_GET['url'] ?? '');
if ($url === '') {
    echo json_encode(['error' => 'URL is required']);
    exit;
}

$forbiddenDomains = ['vk.com', 'vkontakte.ru', 'userapi.com', 'tiktok.com', 'tiktokcdn.com', 'musical.ly'];

try {
    $parts = security_assert_public_url($url, $forbiddenDomains);
    $host = (string)($parts['host'] ?? '');
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SongOfTheAbyss/1.0)',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($html === false || $httpCode !== 200 || $curlError) {
    echo json_encode(['error' => 'Failed to fetch URL']);
    exit;
}

$encoding = mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'Windows-1251'], true);
if ($encoding && $encoding !== 'UTF-8') {
    $html = mb_convert_encoding($html, 'UTF-8', $encoding);
}

$title = '';
preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatches);
if (isset($titleMatches[1])) {
    $title = trim(html_entity_decode($titleMatches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

$description = '';
$metaPatterns = [
    '/<meta\s+name="description"\s+content="([^"]*)"\s*\/?>/i',
    '/<meta\s+name="description"\s+content=\'([^\']*)\'\s*\/?>/i',
    '/<meta\s+property="og:description"\s+content="([^"]*)"\s*\/?>/i',
    '/<meta\s+property="og:description"\s+content=\'([^\']*)\'\s*\/?>/i',
    '/<meta\s+name="twitter:description"\s+content="([^"]*)"\s*\/?>/i',
    '/<meta\s+name="twitter:description"\s+content=\'([^\']*)\'\s*\/?>/i',
];

foreach ($metaPatterns as $pattern) {
    if (preg_match($pattern, $html, $descMatches)) {
        $description = trim(html_entity_decode($descMatches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($description !== '') {
            break;
        }
    }
}

if ($description === '' && (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be'))) {
    if (preg_match('/"description":"(.*?)"/', $html, $ytMatches)) {
        $description = (string)json_decode('"' . $ytMatches[1] . '"');
    }
}

echo json_encode([
    'title' => $title,
    'description' => $description,
], JSON_UNESCAPED_UNICODE);
