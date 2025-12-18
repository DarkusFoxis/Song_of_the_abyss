<?php
session_start();
header('Access-Control-Allow-Origin: so-ta.ru');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'tools_proxy_errors.log');

$API_KEYS = [
    'nvidia' => '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'
];

function sendError($msg, $code = 500, $details = null) {
    http_response_code($code);
    echo json_encode(['error' => $msg, 'details' => $details]);
    exit;
}
function logRequest(...$args) {
    $ln = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL . str_repeat('-', 80) . PHP_EOL;
    file_put_contents('tools_proxy.log', $ln, FILE_APPEND | LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('POST only', 405);
if (!isset($_SESSION['user'])) sendError('Auth required', 401);

require_once '../../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) sendError('DB failed', 500);
$login = $_SESSION['user'];
session_write_close();
$user = $conn->query("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'")->fetch_assoc();
if (!$user) sendError('User not found', 403);
$user_id = $user['id'];
$is_premium = $user['lvl'] >= 3;

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON', 400);

$model   = $input['model']   ?? 'deepseek-ai/deepseek-v3.1';
$messages= $input['messages']?? [];
$tools   = $input['tools']   ?? null;
$toolChoice = $input['tool_choice'] ?? 'none';

$today = date('Y-m-d');
$toolsRow = $conn->query("SELECT * FROM tools WHERE user_id = $user_id")->fetch_assoc();
if (!$toolsRow || !$toolsRow['neyro']) sendError('AI access denied', 403);

if ($toolsRow['last_request'] != $today) {
    $base = $is_premium ? 6 : 3; $lite = $is_premium ? 600 : 125; $t = $is_premium ? 15 : 5;
    $conn->query("UPDATE tools SET base_message=$base, lite_message=$lite, tools=$t, last_request='$today' WHERE user_id=$user_id");
    $toolsRow['base_message'] = $base; $toolsRow['lite_message'] = $lite; $toolsRow['tools'] = $t;
}

if ($tools) {
    if ($toolsRow['tools'] <= 0 && $toolsRow['bonus_tools'] <= 0) sendError('No tools left today', 403);
    $field = $toolsRow['tools'] > 0 ? 'tools' : 'bonus_tools';
    $conn->query("UPDATE tools SET $field = $field - 1 WHERE user_id = $user_id");
} else {
    if ($toolsRow['lite_message'] <= 0 && $toolsRow['bonus_lite_message'] <= 0) sendError('No lite messages left', 403);
    $field = $toolsRow['lite_message'] > 0 ? 'lite_message' : 'bonus_lite_message';
    $conn->query("UPDATE tools SET $field = $field - 1 WHERE user_id = $user_id");
}

$apiMessages = [["role" => "system", "content" => "System prompt: " . ($input['system_prompt'] ?? 'Assistant.')]];
foreach ($messages as $m) {
    $apiMessages[] = ["role" => $m['role'] === 'tool' ? 'user' : $m['role'], "content" => $m['content']];
}
$payload = [
    "model"       => $model,
    "messages"    => $apiMessages,
    "temperature" => 0.6,
    "top_p"       => 0.7,
    "max_tokens"  => 8192,
    "stream"      => false
];
if ($tools) {
    $payload["tools"]      = $tools;
    $payload["tool_choice"]= $toolChoice;
}

$ch = curl_init("https://integrate.api.nvidia.com/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer ".$API_KEYS['nvidia'],
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 120
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) sendError("Curl: ".curl_error($ch), 500);
curl_close($ch);
if ($http < 200 || $http >= 300) sendError("NVIDIA error", $http, ['body'=>$resp]);

$json = json_decode($resp, true);
if (!$json || !isset($json['choices'][0]['message'])) sendError("Bad nvidia response", 500, $json);

$reply = $json['choices'][0]['message'];

if (isset($reply['reasoning_content']) && $reply['reasoning_content']) {
    $normalizedReply = [
        'role' => $reply['role'],
        'content' => $reply['content'],
        'reasoning_content' => $reply['reasoning_content']
    ];
    
    if (isset($reply['tool_calls'])) {
        $normalizedReply['tool_calls'] = $reply['tool_calls'];
    } elseif (isset($reply['function_call'])) {
        $normalizedReply['tool_calls'] = [
            [
                'id' => 'call_' . uniqid(),
                'type' => 'function',
                'function' => [
                    'name' => $reply['function_call']['name'],
                    'arguments' => $reply['function_call']['arguments']
                ]
            ]
        ];
    }
} else {
    $normalizedReply = $reply;
}

logRequest($login, $model, $messages, $normalizedReply, $json['usage'] ?? null);

echo json_encode([
    'message' => $normalizedReply,
    'usage'   => $json['usage'] ?? null
]);