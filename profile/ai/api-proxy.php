<?php
session_start();
header('Access-Control-Allow-Origin: so-ta.ru');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'api_proxy_errors.log');

$API_KEYS = [
    'nvidia' => 'Ключ тут'
];

function sendError($message, $code = 500, $details = null) {
    http_response_code($code);
    echo json_encode([
        'error' => $message,
        'details' => $details,
        'timestamp' => time()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Only POST requests are allowed', 405);
}

if (!isset($_SESSION['user'])) {
    sendError('Authentication required', 401);
}

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
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    sendError('User not found', 403);
}
$user_id = $user['id'];
$is_premium = $user['lvl'] >= 3;

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('Invalid JSON data: ' . json_last_error_msg(), 400);
}

if (!isset($input['provider'])) {
    sendError('Missing provider field', 400);
}

if (!isset($input['system_prompt']) || !isset($input['messages'])) {
    sendError('Missing required fields: system_prompt or messages', 400);
}

$provider = $input['provider'];

$today = date('Y-m-d');
$tools_query = "SELECT neyro, base_message, bonus_base_message, tools, lite_message, bonus_lite_message, last_request FROM tools WHERE user_id = ?";
$stmt = $conn->prepare($tools_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tools_result = $stmt->get_result();
$tools = $tools_result->fetch_assoc();

if (!$tools) {
    sendError('Access to AI is not allowed', 403);
}

if ($tools['last_request'] != $today) {
    $base_limit = $is_premium ? 6 : 3;
    $lite_limit = $is_premium ? 600 : 125;
    $tools_limit = $is_premium ? 15 : 5;
    $arts_limit = $is_premium ? 9 : 3;
    
    $update_query = "UPDATE tools SET base_message = ?, lite_message = ?, tools = ?, arts = ?, last_request = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iiiisi", $base_limit, $lite_limit, $tools_limit, $arts_limit, $today, $user_id);
    $stmt->execute();
    
    $tools['base_message'] = $base_limit;
    $tools['lite_message'] = $lite_limit;
    $tools['last_request'] = $today;
}

if (!$tools['neyro']) {
    sendError('Access to AI is not allowed', 403);
}

if ($provider === 'nvidia') {
    if ($tools['lite_message'] <= 0 && $tools['bonus_lite_message'] <= 0) {
        sendError('No lite messages available', 403);
    }
    
    if ($tools['lite_message'] > 0) {
        $sql = "UPDATE tools SET lite_message = lite_message - 1 WHERE user_id = ?";
    } else {
        $sql = "UPDATE tools SET bonus_lite_message = bonus_lite_message - 1 WHERE user_id = ?";
    }
} else {
    sendError('Unsupported provider', 400);
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    sendError('Failed to deduct messages', 500);
}

$stmt->close();

$model = $input['model'] ?? 'google/gemma-3-27b-it';
$max_tokens = $input['max_tokens'] ?? 8224;
$temperature = $input['temperature'] ?? 0.5;
$top_p = $input['top_p'] ?? 0.70;
$frequency_penalty = $input['frequency_penalty'] ?? 0.00;
$presence_penalty = $input['presence_penalty'] ?? 0.00;
$stream = $input['stream'] ?? false;

$api_messages = [["role" => "system", "content" => "This is System prompt. Follow the following instructions that describe you:" . $input['system_prompt'] . " You don't disclose your story and other details until the user asks for it. Follow the set character of the character. Answer in Russian, unless the user has asked otherwise."]];
foreach ($input['messages'] as $msg) {
    $role = $msg['role'] === 'user' ? 'user' : 'assistant';
    $api_messages[] = [
        "role" => $role,
        "content" => $msg['content']
    ];
}

$payload = [
    "model" => $model,
    "messages" => $api_messages,
    "max_tokens" => $max_tokens,
    "temperature" => $temperature,
    "top_p" => $top_p,
    "frequency_penalty" => $frequency_penalty,
    "presence_penalty" => $presence_penalty,
    "stream" => $stream
];

if ($provider === 'nvidia') {
    $url = 'https://integrate.api.nvidia.com/v1/chat/completions';
    $api_key = $API_KEYS['nvidia'];
    $headers = [
        "Authorization: Bearer " . $api_key,
        "Content-Type: application/json",
        "User-Agent: NVIDIA-AI-Proxy/1.0"
    ];
} else {
    sendError('Unsupported provider', 400);
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => !$stream,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_FAILONERROR => false
]);

if ($stream) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    
    $full_response = '';
    
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$full_response) {
        echo $data;
        flush();
        if (strpos($data, 'data: ') === 0) {
            $json_data = substr($data, 6);
            if ($json_data !== '[DONE]') {
                try {
                    $parsed = json_decode($json_data, true);
                    if (isset($parsed['choices'][0]['delta']['content'])) {
                        $full_response .= $parsed['choices'][0]['delta']['content'];
                    }
                } catch (Exception $e) {
                }
            }
        }
        return strlen($data);
    });

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    mysqli_close($conn);
    exit;
}

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    $curl_error = curl_error($ch);
    curl_close($ch);
    mysqli_close($conn);
    
    sendError('API request failed', 500, [
        'curl_error' => $curl_error,
        'error_code' => curl_errno($ch)
    ]);
}

curl_close($ch);
mysqli_close($conn);

if ($http_code < 200 || $http_code >= 300) {
    sendError('API provider returned error', $http_code, [
        'http_code' => $http_code,
        'response_body' => $response
    ]);
}

$response_data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('Failed to parse API response', 500, [
        'json_error' => json_last_error_msg(),
        'raw_response' => $response
    ]);
}

if (!isset($response_data['choices'][0]['message']['content'])) {
    sendError('Unexpected response format from API', 500, [
        'response_structure' => $response_data
    ]);
}

echo json_encode([
    'response' => $response_data['choices'][0]['message']['content'],
    'usage' => $response_data['usage'] ?? null,
    'model' => $response_data['model'] ?? 'unknown'
]);