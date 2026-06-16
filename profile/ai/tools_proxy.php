<?php
declare(strict_types=1);

require_once __DIR__ . '/../../template/auth.php';

auth_start_session();
auth_sync_session_from_token();

$allowedOrigin = (security_is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'so-ta.ru');
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    tools_proxy_send_json_error('POST only', 405);
}

$authUser = auth_get_current_user();
if ($authUser === null) {
    tools_proxy_send_json_error('Auth required', 401);
}

security_require_csrf(true);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    tools_proxy_send_json_error('Invalid JSON', 400);
}

$model = (string)($input['model'] ?? 'mistralai/mistral-large-3-675b-instruct-2512');
$messages = $input['messages'] ?? [];
$tools = $input['tools'] ?? null;
$toolChoice = (string)($input['tool_choice'] ?? 'none');
$systemPrompt = trim((string)($input['system_prompt'] ?? 'You are a helpful assistant.'));

if (!is_array($messages)) {
    tools_proxy_send_json_error('Messages must be an array', 400);
}

$allowedModels = [
    'mistralai/mistral-large-3-675b-instruct-2512',
    'mistralai/devstral-2-123b-instruct-2512',
    'openai/gpt-oss-120b',
    'z-ai/glm4.7',
    'nvidia/nemotron-3-super-120b-a12b',
    'moonshotai/kimi-k2-instruct-0905',
    'stepfun-ai/step-3.5-flash',
    'deepseek-ai/deepseek-v3.1',
    'google/gemma-4-31b-it',
];

if (!in_array($model, $allowedModels, true)) {
    tools_proxy_send_json_error('Unsupported model', 400);
}

require_once __DIR__ . '/../../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    tools_proxy_send_json_error('DB failed', 500);
}

$login = (string)$authUser['login'];
$user = tools_proxy_load_user($conn, $login);
if ($user === null) {
    mysqli_close($conn);
    tools_proxy_send_json_error('User not found', 403);
}

$userId = (int)$user['id'];
$userLevel = (int)$user['lvl'];
$isPremium = $userLevel >= 3;

if ($userLevel <= 0) {
    mysqli_close($conn);
    tools_proxy_send_json_error('Access denied', 403);
}

$toolsRow = tools_proxy_load_limits($conn, $userId);
if ($toolsRow === null || empty($toolsRow['neyro'])) {
    mysqli_close($conn);
    tools_proxy_send_json_error('AI access denied', 403);
}

$today = date('Y-m-d');
if ((string)($toolsRow['last_request'] ?? '') !== $today) {
    tools_proxy_reset_limits($conn, $userId, $isPremium);
    $toolsRow = tools_proxy_load_limits($conn, $userId);
    if ($toolsRow === null) {
        mysqli_close($conn);
        tools_proxy_send_json_error('AI limits unavailable', 500);
    }
}

$usesTools = is_array($tools) && $tools !== [];
$fieldToCharge = tools_proxy_pick_charge_field($toolsRow, $usesTools);
if ($fieldToCharge === null) {
    mysqli_close($conn);
    tools_proxy_send_json_error($usesTools ? 'No tools left today' : 'No lite messages left', 403);
}

tools_proxy_charge_limit($conn, $userId, $fieldToCharge);

$payloadMessages = [[
    'role' => 'system',
    'content' => 'System prompt: ' . $systemPrompt,
]];

foreach (array_slice($messages, -40) as $message) {
    if (!is_array($message)) {
        continue;
    }

    $role = (string)($message['role'] ?? 'user');
    if (!in_array($role, ['system', 'user', 'assistant', 'tool'], true)) {
        $role = 'user';
    }
    if ($role === 'tool') {
        $role = 'user';
    }

    $content = $message['content'] ?? '';
    if (is_array($content)) {
        $content = json_encode($content, JSON_UNESCAPED_UNICODE);
    }

    $payloadMessage = [
        'role' => $role,
        'content' => mb_substr((string)$content, 0, 12000),
    ];

    if (!empty($message['name']) && is_string($message['name'])) {
        $payloadMessage['name'] = $message['name'];
    }

    if (!empty($message['tool_call_id']) && is_string($message['tool_call_id'])) {
        $payloadMessage['tool_call_id'] = $message['tool_call_id'];
    }

    $payloadMessages[] = $payloadMessage;
}

$payload = [
    'model' => $model,
    'messages' => $payloadMessages,
    'temperature' => 0.6,
    'top_p' => 0.7,
    'max_tokens' => 8192,
    'stream' => true,
];

if ($usesTools) {
    $payload['tools'] = $tools;
    $payload['tool_choice'] = in_array($toolChoice, ['auto', 'none', 'required'], true) ? $toolChoice : 'auto';
}

session_write_close();

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

if (ob_get_level() > 0) {
    ob_end_flush();
}

$ch = curl_init('https://integrate.api.nvidia.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . app_nvidia_api_key(),
        'Content-Type: application/json',
        'Accept: text/event-stream',
    ],
    CURLOPT_TIMEOUT => 180,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_WRITEFUNCTION => static function ($curl, $data) {
        echo $data;
        flush();
        return strlen($data);
    },
]);

$result = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($result === false) {
    echo 'data: ' . json_encode(['error' => 'Curl: ' . curl_error($ch)], JSON_UNESCAPED_UNICODE) . "\n\n";
}

if ($httpCode >= 400) {
    echo 'data: ' . json_encode(['error' => 'NVIDIA error', 'status' => $httpCode], JSON_UNESCAPED_UNICODE) . "\n\n";
}

curl_close($ch);

function tools_proxy_send_json_error(string $message, int $code = 500, $details = null): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => $message,
        'details' => $details,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function tools_proxy_load_user(mysqli $conn, string $login): ?array
{
    $stmt = $conn->prepare(
        'SELECT u.id, sg.lvl
         FROM users u
         JOIN site_group sg ON u.permissions = sg.name
         WHERE u.login = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $login);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

function tools_proxy_load_limits(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare(
        'SELECT neyro, tools, bonus_tools, lite_message, bonus_lite_message, last_request
         FROM tools
         WHERE user_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

function tools_proxy_reset_limits(mysqli $conn, int $userId, bool $isPremium): void
{
    $base = $isPremium ? 6 : 3;
    $lite = $isPremium ? 600 : 150;
    $toolLimit = $isPremium ? 15 : 5;
    $today = date('Y-m-d');

    $stmt = $conn->prepare(
        'UPDATE tools
         SET base_message = ?, lite_message = ?, tools = ?, last_request = ?
         WHERE user_id = ?'
    );
    $stmt->bind_param('iiisi', $base, $lite, $toolLimit, $today, $userId);
    $stmt->execute();
}

function tools_proxy_pick_charge_field(array $toolsRow, bool $usesTools): ?string
{
    if ($usesTools) {
        if ((int)($toolsRow['tools'] ?? 0) > 0) {
            return 'tools';
        }

        if ((int)($toolsRow['bonus_tools'] ?? 0) > 0) {
            return 'bonus_tools';
        }

        return null;
    }

    if ((int)($toolsRow['lite_message'] ?? 0) > 0) {
        return 'lite_message';
    }

    if ((int)($toolsRow['bonus_lite_message'] ?? 0) > 0) {
        return 'bonus_lite_message';
    }

    return null;
}

function tools_proxy_charge_limit(mysqli $conn, int $userId, string $field): void
{
    $allowedFields = ['tools', 'bonus_tools', 'lite_message', 'bonus_lite_message'];
    if (!in_array($field, $allowedFields, true)) {
        tools_proxy_send_json_error('Invalid quota field', 500);
    }

    $query = sprintf('UPDATE tools SET `%s` = `%s` - 1 WHERE user_id = ? AND `%s` > 0', $field, $field, $field);
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        tools_proxy_send_json_error('Failed to charge quota', 500);
    }
}
