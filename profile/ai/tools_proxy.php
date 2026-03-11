<?php
session_start();
require_once __DIR__ . '/../../template/auth.php';
auth_sync_session_from_token();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'tools_proxy_errors.log');

function sendError($msg, $code = 500, $details = null) {
    echo "data: " . json_encode(['error' => $msg, 'details' => $details]) . "\n\n";
    flush();
    exit;
}

function logRequest(...$args) {
    $ln = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL . str_repeat('-', 80) . PHP_EOL;
    file_put_contents('tools_proxy.log', $ln, FILE_APPEND | LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('POST only', 405);
}

if (!isset($_SESSION['user'])) {
    sendError('Auth required', 401);
}

require_once '../../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    sendError('DB failed', 500);
}

$login = $_SESSION['user'];
session_write_close();

$user = $conn->query("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'")->fetch_assoc();
if (!$user) {
    sendError('User not found', 403);
}

$user_id = $user['id'];
$is_premium = $user['lvl'] >= 3;

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('Invalid JSON', 400);
}

$model   = $input['model']   ?? 'mistralai/mistral-large-3-675b-instruct-2512';
$messages= $input['messages']?? [];
$tools   = $input['tools']   ?? null;
$toolChoice = $input['tool_choice'] ?? 'none';
$stream  = $input['stream'] ?? false;

$today = date('Y-m-d');
$toolsRow = $conn->query("SELECT * FROM tools WHERE user_id = $user_id")->fetch_assoc();

if (!$toolsRow || !$toolsRow['neyro']) {
    sendError('AI access denied', 403);
}

if ($toolsRow['last_request'] != $today) {
    $base = $is_premium ? 6 : 3;
    $lite = $is_premium ? 600 : 150;
    $t = $is_premium ? 15 : 5;
    $conn->query("UPDATE tools SET base_message=$base, lite_message=$lite, tools=$t, last_request='$today' WHERE user_id=$user_id");
    $toolsRow['base_message'] = $base;
    $toolsRow['lite_message'] = $lite;
    $toolsRow['tools'] = $t;
}

if ($tools) {
    if ($toolsRow['tools'] <= 0 && $toolsRow['bonus_tools'] <= 0) {
        sendError('No tools left today', 403);
    }
    $field = $toolsRow['tools'] > 0 ? 'tools' : 'bonus_tools';
    $conn->query("UPDATE tools SET $field = $field - 1 WHERE user_id = $user_id");
} else {
    if ($toolsRow['lite_message'] <= 0 && $toolsRow['bonus_lite_message'] <= 0) {
        sendError('No lite messages left', 403);
    }
    $field = $toolsRow['lite_message'] > 0 ? 'lite_message' : 'bonus_lite_message';
    $conn->query("UPDATE tools SET $field = $field - 1 WHERE user_id = $user_id");
}

$apiMessages = [["role" => "system", "content" => "System prompt: " . ($input['system_prompt'] ?? 'You are a helpful assistant.')]];
foreach ($messages as $m) {
    $role = $m['role'];
    if ($role === 'tool') {
        $role = 'user';
    }
    $apiMessages[] = [
        "role" => $role,
        "content" => $m['content']
    ];
    if (isset($m['name']) && $m['name']) {
        $apiMessages[count($apiMessages) - 1]['name'] = $m['name'];
    }
    if (isset($m['tool_call_id']) && $m['tool_call_id']) {
        $apiMessages[count($apiMessages) - 1]['tool_call_id'] = $m['tool_call_id'];
    }
}

$payload = [
    "model"       => $model,
    "messages"    => $apiMessages,
    "temperature" => 0.6,
    "top_p"       => 0.7,
    "max_tokens"  => 8192,
    "stream"      => true
];

if ($tools) {
    $payload["tools"] = $tools;
    $payload["tool_choice"] = $toolChoice;
}

$ch = curl_init("");

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer ",
        "Content-Type: application/json",
        "Accept: text/event-stream"
    ],
    CURLOPT_TIMEOUT => 120,
    CURLOPT_WRITEFUNCTION => function($curl, $data) {
        echo $data;
        flush();
        return strlen($data);
    }
]);

curl_exec($ch);

if (curl_errno($ch)) {
    sendError("Curl: " . curl_error($ch), 500);
}

$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http < 200 || $http >= 300) {
    sendError("Error", $http);
}

logRequest($login, $model, count($messages) . ' messages', 'streaming completed');