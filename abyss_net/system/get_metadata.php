<?php
header('Content-Type: application/json');
$url = $_GET['url'] ?? '';

if (empty($url)) {
    echo json_encode(['error' => 'URL is required']);
    exit;
}

$forbiddenDomains = ['vk.com', 'vkontakte.ru', 'userapi.com', 'tiktok.com', 'tiktokcdn.com', 'musical.ly'];

$host = parse_url($url, PHP_URL_HOST);
$forbidden = false;
foreach ($forbiddenDomains as $domain) {
    if (strpos($host, $domain) !== false) {
        $forbidden = true;
        break;
    }
}

if ($forbidden) {
    echo json_encode(['error' => 'Forbidden domain']);
    exit;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    CURLOPT_SSL_VERIFYPEER => false
]);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($html)) {
    echo json_encode(['error' => 'Failed to fetch URL']);
    exit;
}

$encoding = mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'Windows-1251']);
if ($encoding && $encoding !== 'UTF-8') {
    $html = mb_convert_encoding($html, 'UTF-8', $encoding);
}

$title = '';
preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatches);
if (isset($titleMatches[1])) {
    $title = trim(html_entity_decode($titleMatches[1]));
}

$description = '';
$metaPatterns = [
    '/<meta\s+name="description"\s+content="([^"]*)"\s*\/?>/i',
    '/<meta\s+name="description"\s+content=\'([^\']*)\'\s*\/?>/i',

    '/<meta\s+property="og:description"\s+content="([^"]*)"\s*\/?>/i',
    '/<meta\s+property="og:description"\s+content=\'([^\']*)\'\s*\/?>/i',

    '/<meta\s+name="twitter:description"\s+content="([^"]*)"\s*\/?>/i',
    '/<meta\s+name="twitter:description"\s+content=\'([^\']*)\'\s*\/?>/i'
];

foreach ($metaPatterns as $pattern) {
    if (preg_match($pattern, $html, $descMatches)) {
        $description = trim(html_entity_decode($descMatches[1]));
        if (!empty($description)) break;
    }
}

if (empty($description) && (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false)) {
    if (preg_match('/"description":"(.*?)"/', $html, $ytMatches)) {
        $description = json_decode('"' . $ytMatches[1] . '"');
    }
}

echo json_encode([
    'title' => $title,
    'description' => $description
]);