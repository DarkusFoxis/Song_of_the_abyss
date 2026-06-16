<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../template/auth.php';
require_once __DIR__ . '/../../../template/conn.php';

auth_start_session();
auth_sync_session_from_token();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'POST only';
    exit;
}

$authUser = auth_get_current_user();
if ($authUser === null) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Auth required';
    exit;
}

security_require_csrf(true);

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'DB error';
    exit;
}

$login = (string)$authUser['login'];
$userStmt = $conn->prepare(
    'SELECT u.id, sg.lvl
     FROM users u
     JOIN site_group sg ON u.permissions = sg.name
     WHERE u.login = ?
     LIMIT 1'
);
$userStmt->bind_param('s', $login);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if (!$user) {
    mysqli_close($conn);
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'User not found';
    exit;
}

$userId = (int)$user['id'];
$isPremium = (int)$user['lvl'] >= 3;

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    mysqli_close($conn);
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid JSON';
    exit;
}

$prompt = trim((string)($input['prompt'] ?? ''));
$steps = (int)($input['steps'] ?? 50);
$negativePrompt = trim((string)($input['n_prompt'] ?? '18+, nsfw'));

if ($prompt === '') {
    mysqli_close($conn);
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Empty prompt';
    exit;
}

$steps = max(1, min($steps, 60));

$toolsStmt = $conn->prepare('SELECT neyro, arts, bonus_arts, last_request FROM tools WHERE user_id = ? LIMIT 1');
$toolsStmt->bind_param('i', $userId);
$toolsStmt->execute();
$tools = $toolsStmt->get_result()->fetch_assoc();

if (!$tools) {
    mysqli_close($conn);
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'AI access denied';
    exit;
}

$today = date('Y-m-d');
if ((string)($tools['last_request'] ?? '') !== $today) {
    $baseLimit = $isPremium ? 6 : 3;
    $liteLimit = $isPremium ? 600 : 125;
    $toolLimit = $isPremium ? 15 : 5;
    $artsLimit = $isPremium ? 9 : 3;

    $resetStmt = $conn->prepare(
        'UPDATE tools
         SET base_message = ?, lite_message = ?, tools = ?, arts = ?, last_request = ?
         WHERE user_id = ?'
    );
    $resetStmt->bind_param('iiiisi', $baseLimit, $liteLimit, $toolLimit, $artsLimit, $today, $userId);
    $resetStmt->execute();

    $tools['arts'] = $artsLimit;
}

if (empty($tools['neyro']) || ((int)($tools['arts'] ?? 0) <= 0 && (int)($tools['bonus_arts'] ?? 0) <= 0)) {
    mysqli_close($conn);
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No arts left';
    exit;
}

$payload = [
    'prompt' => $prompt,
    'aspect_ratio' => '1:1',
    'steps' => $steps,
    'cfg_scale' => 5,
    'seed' => 0,
    'negative_prompt' => $negativePrompt,
];

$ch = curl_init('https://ai.api.nvidia.com/v1/genai/stabilityai/stable-diffusion-3-medium');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . app_nvidia_api_key(),
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 120,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    mysqli_close($conn);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Curl error: ' . $error;
    exit;
}

curl_close($ch);

if ($httpCode !== 200) {
    mysqli_close($conn);
    http_response_code($httpCode);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'NVAPI error ' . $httpCode;
    exit;
}

$json = json_decode($response, true);
if (!is_array($json) || empty($json['image'])) {
    mysqli_close($conn);
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Bad NVAPI response';
    exit;
}

$image = base64_decode((string)$json['image'], true);
if ($image === false) {
    mysqli_close($conn);
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Bad image data';
    exit;
}

$dir = __DIR__ . '/../arts';
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    mysqli_close($conn);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Storage error';
    exit;
}

$fileName = uniqid('art_', true) . '.png';
$filePath = $dir . DIRECTORY_SEPARATOR . $fileName;
if (file_put_contents($filePath, $image) === false) {
    mysqli_close($conn);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Storage error';
    exit;
}

$fieldToCharge = (int)($tools['arts'] ?? 0) > 0 ? 'arts' : 'bonus_arts';
$chargeQuery = sprintf('UPDATE tools SET `%s` = `%s` - 1 WHERE user_id = ? AND `%s` > 0', $fieldToCharge, $fieldToCharge, $fieldToCharge);
$chargeStmt = $conn->prepare($chargeQuery);
$chargeStmt->bind_param('i', $userId);
$chargeStmt->execute();

mysqli_close($conn);
session_write_close();

header('Content-Type: text/plain; charset=utf-8');
echo './arts/' . $fileName;
