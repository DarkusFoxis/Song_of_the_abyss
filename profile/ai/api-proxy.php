<?php
require_once __DIR__ . '/../../template/auth.php';
auth_start_session();
auth_sync_session_from_token();

header('Access-Control-Allow-Origin: so-ta.ru');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/rp_proxy_errors.log');

$API_KEYS = [
    'nvidia' => app_nvidia_api_key() 
];

function sendError($message, $code = 500, $details = null) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $message,
        'details' => $details,
        'timestamp' => time()
    ]);
    exit;
}

function logApiRequest($user, $model, $status, $details = '') {
    $logFile = __DIR__ . '/rp_requests.log';
    $entry = json_encode([
        'time' => date('Y-m-d H:i:s'),
        'user' => $user,
        'model' => $model,
        'status' => $status,
        'details' => $details
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

if (!isset($_SESSION['user'])) {
    sendError('Authentication required', 401);
}

security_require_csrf(true);

require_once '../../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    sendError('Database connection failed', 500);
}

$login = $_SESSION['user'];
session_write_close();

$user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $login);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    sendError('User not found', 403);
}

$user_id = $user['id'];
$is_premium = $user['lvl'] >= 3;

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('Invalid JSON: ' . json_last_error_msg(), 400);
}

$provider = $input['provider'] ?? 'nvidia';
$model = $input['model'] ?? 'google/gemma-3-27b-it';
$stream = !empty($input['stream']);
$max_tokens = min($input['max_tokens'] ?? 4096, 8192);
$temperature = $input['temperature'] ?? 0.55;
$top_p = $input['top_p'] ?? 0.7;

$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT neyro, lite_message, bonus_lite_message, last_request FROM tools WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tools = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tools || !$tools['neyro']) {
    sendError('Access to AI is disabled', 403);
}

if ($tools['last_request'] !== $today) {
    $lite_limit = $is_premium ? 600 : 150;
    $stmt = $conn->prepare("UPDATE tools SET lite_message = ?, bonus_lite_message = 0, last_request = ? WHERE user_id = ?");
    $stmt->bind_param("isi", $lite_limit, $today, $user_id);
    $stmt->execute();
    $stmt->close();
    $tools['lite_message'] = $lite_limit;
}

if ($tools['lite_message'] <= 0 && $tools['bonus_lite_message'] <= 0) {
    sendError('Daily message limit exceeded', 429);
}

$deductBonus = $tools['lite_message'] <= 0;
$sql = $deductBonus 
    ? "UPDATE tools SET bonus_lite_message = bonus_lite_message - 1 WHERE user_id = ?"
    : "UPDATE tools SET lite_message = lite_message - 1 WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

$system_prompt = $input['system_prompt'] ?? '';
$messages = $input['messages'] ?? [];

$api_messages = [];
if (!empty($system_prompt)) {
    $api_messages[] = [
        "role" => "system",
        "content" => $system_prompt
    ];
}
foreach ($messages as $msg) {
    $api_messages[] = [
        "role" => $msg['role'] === 'user' ? 'user' : 'assistant',
        "content" => $msg['content']
    ];
}

$payload = [
    "model" => $model,
    "messages" => $api_messages,
    "max_tokens" => $max_tokens,
    "temperature" => $temperature,
    "top_p" => $top_p,
    "stream" => $stream,
//    "chat_template_kwargs" => [
//        "enable_thinking" => false
//    ]
];

if ($provider !== 'nvidia' || empty($API_KEYS['nvidia'])) {
    sendError('Provider configuration error', 500);
}

$ch = curl_init('https://integrate.api.nvidia.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $API_KEYS['nvidia']
    ],
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 120,
    
    CURLOPT_RETURNTRANSFER => !$stream,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

if ($stream) {
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
    ini_set('zlib.output_compression', 0);
    ini_set('implicit_flush', 1);
    while (ob_get_level()) ob_end_clean();
    
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    
    $full_response = '';
    
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$full_response) {
        echo $data;
        flush();
        
        if (strpos($data, 'data: ') === 0) {
            $json = substr($data, 6);
            if ($json !== '[DONE]') {
                $decoded = json_decode($json, true);
                if (isset($decoded['choices'][0]['delta']['content'])) {
                    $full_response .= $decoded['choices'][0]['delta']['content'];
                }
            }
        }
        return strlen($data);
    });
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    mysqli_close($conn);
    
    if ($error) {
        logApiRequest($login, $model, 'CURL_ERROR', $error);
        echo "data: " . json_encode(['error' => $error]) . "\n\n";
        flush();
    } else {
        logApiRequest($login, $model, 'STREAM_OK', 'Length: ' . strlen($full_response));
    }
    exit;
} else {
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    mysqli_close($conn);
    
    if ($error) {
        logApiRequest($login, $model, 'CURL_ERROR', $error);
        sendError('Request failed: ' . $error, 500);
    }
    
    if ($http_code < 200 || $http_code >= 300) {
        logApiRequest($login, $model, 'HTTP_ERROR', "Code: $http_code");
        sendError('API Error', $http_code, json_decode($response, true));
    }
    
    logApiRequest($login, $model, 'OK', 'Non-stream');
    header('Content-Type: application/json');
    echo $response;
    exit;
}