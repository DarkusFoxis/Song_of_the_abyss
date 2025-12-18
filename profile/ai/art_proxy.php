<?php
session_start();
header('Access-Control-Allow-Origin: so-ta.ru');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'api_proxy_errors.log');

$API_KEY = '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';

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
    sendError('Invalid JSON  ' . json_last_error_msg(), 400);
}

if (!isset($input['type']) || $input['type'] !== 'image_generation') {
    sendError('Invalid request type. Only image_generation is supported.', 400);
}

if (!isset($input['prompt'])) {
    sendError('Missing prompt field', 400);
}

$model = $input['model'] ?? 'flux.1-schnell';
$supported_models = ['flux.1-schnell', 'stable-diffusion-3-medium'];
if (!in_array($model, $supported_models)) {
    sendError('Unsupported model: ' . $model, 400);
}

$tools_query = "SELECT neyro, arts, bonus_arts, last_request FROM tools WHERE user_id = ?";
$stmt = $conn->prepare($tools_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tools_result = $stmt->get_result();
$tools = $tools_result->fetch_assoc();

if (!$tools) {
    sendError('Access to AI is not allowed', 403);
}

$today = date('Y-m-d');
if ($tools['last_request'] != $today) {
    $base_limit = $is_premium ? 6 : 3;
    $lite_limit = $is_premium ? 800 : 300;
    $tools_limit = $is_premium ? 3 : 1;
    $arts_limit = $is_premium ? 9: 3;
    $update_query = "UPDATE tools SET base_message = ?, lite_message = ?, tools = ?, arts = ?, bonus_arts = 0, last_request = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iiiisi", $base_limit, $lite_limit, $tools_limit, $arts_limit, $today, $user_id);
    $stmt->execute();
    $tools['arts'] = $arts_limit;
    $tools['bonus_arts'] = 0;
    $tools['last_request'] = $today;
}

if (!$tools['neyro']) {
    sendError('Access to AI is not allowed', 403);
}

if ($tools['arts'] <= 0 && $tools['bonus_arts'] <= 0) {
    sendError('No arts generations available', 403);
}

if ($model === 'flux.1-schnell') {
    $nvidia_url = 'https://ai.api.nvidia.com/v1/genai/black-forest-labs/flux.1-schnell';
    $payload = [
        "prompt" => $input['prompt'],
        "width" => $input['width'] ?? 1024,
        "height" => $input['height'] ?? 1024,
        "seed" => $input['seed'] ?? 0,
        "steps" => $input['steps'] ?? 4
    ];
} else if ($model === 'stable-diffusion-3-medium') {
    $nvidia_url = 'https://ai.api.nvidia.com/v1/genai/stabilityai/stable-diffusion-3-medium';
    $payload = [
        "prompt" => $input['prompt'],
        "cfg_scale" => $input['cfg_scale'] ?? 5,
        "aspect_ratio" => $input['aspect_ratio'] ?? "1:1",
        "seed" => $input['seed'] ?? 0,
        "steps" => $input['steps'] ?? 28,
        "negative_prompt" => $input['negative_prompt'] ?? ""
    ];
}

$headers = [
    "Authorization: Bearer " . $API_KEY,
    "Content-Type: application/json",
    "Accept: application/json",
    "User-Agent: NVIDIA-AI-Proxy/1.0"
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $nvidia_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_FAILONERROR => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    $curl_error = curl_error($ch);
    curl_close($ch);
    mysqli_close($conn);
    sendError('API request failed', 500, [
        'curl_error' => $curl_error,
        'error_code' => curl_errno($ch),
        'model' => $model,
        'payload' => $payload
    ]);
}

curl_close($ch);
mysqli_close($conn);

if ($http_code < 200 || $http_code >= 300) {
    sendError('API provider returned error', $http_code, [
        'http_code' => $http_code,
        'response_body' => $response,
        'model' => $model,
        'payload' => $payload
    ]);
}

$response_data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('Failed to parse API response', 500, [
        'json_error' => json_last_error_msg(),
        'raw_response' => $response,
        'model' => $model
    ]);
}

if ($model === 'flux.1-schnell') {
    if (!isset($response_data['artifacts']) || !is_array($response_data['artifacts']) || empty($response_data['artifacts'])) {
        sendError('Unexpected response format from API: missing artifacts array', 500, [
            'response_structure' => $response_data,
            'model' => $model
        ]);
    }

    $artifact = $response_data['artifacts'][0];
    if (!isset($artifact['base64'])) {
        sendError('Image data not found in API response artifact', 500, [
            'response_structure' => $response_data,
            'model' => $model
        ]);
    }

    $image_base64 = $artifact['base64'];
    
} else if ($model === 'stable-diffusion-3-medium') {
    if (!isset($response_data['image'])) {
        sendError('Unexpected response format from API: missing image field', 500, [
            'response_structure' => $response_data,
            'model' => $model
        ]);
    }

    if (isset($response_data['finish_reason']) && $response_data['finish_reason'] === 'CONTENT_FILTERED') {
        sendError('Image generation was blocked by content filter. Please try a different prompt.', 400, [
            'finish_reason' => $response_data['finish_reason'],
            'model' => $model
        ]);
    }

    $image_base64 = $response_data['image'];
}

if (!$image_base64) {
    sendError('Image data is empty', 500);
}

$image_data = base64_decode($image_base64);
if ($image_data === false) {
    sendError('Failed to decode base64 image data', 500);
}

$arts_dir = __DIR__ . '/arts';
if (!is_dir($arts_dir)) {
    if (!mkdir($arts_dir, 0755, true)) {
        sendError('Failed to create arts directory', 500);
    }
}

$filename = uniqid('image_', true) . '.png';
$filepath = $arts_dir . '/' . $filename;

if (file_put_contents($filepath, $image_data) === false) {
    sendError('Failed to save image to server', 500);
}

echo json_encode([
    'image_url' => './arts/' . $filename,
    'filename' => $filename,
    'prompt' => $input['prompt'],
    'model' => $model
]);

if ($tools['arts'] > 0) {
    $sql = "UPDATE tools SET arts = arts - 1 WHERE user_id = ?";
} else {
    $sql = "UPDATE tools SET bonus_arts = bonus_arts - 1 WHERE user_id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
if ($stmt->affected_rows === 0) {
    sendError('Failed to deduct arts generation', 500);
}
$stmt->close();
session_write_close();