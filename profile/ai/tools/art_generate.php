<?php
session_start();
require_once '../../../template/conn.php';

$API_KEY = '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';

if (!isset($_SESSION['user'])) echo 'Auth required';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    http_response_code(500);
    echo 'DB error';
    exit;
}

$login = $_SESSION['user'];
$stmt = $conn->prepare("SELECT u.id, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?");
$stmt->bind_param('s', $login);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) echo 'User not found';
$user_id   = $user['id'];
$isPremium = $user['lvl'] >= 3;

$prompt = $_GET['prompt'] ?? '';
$steps = $_GET['steps'] ?? 50;
$n_prompt = $_GET["n_prompt"];
if (!$prompt) echo 'Empty prompt';

$today = date('Y-m-d');
$tools = $conn->query("SELECT neyro, arts, bonus_arts, last_request FROM tools WHERE user_id = $user_id")->fetch_assoc();

if ($tools['last_request'] != $today) {
    $artsLimit = $isPremium ? 9 : 3;
    $conn->query("UPDATE tools SET base_message = ".($isPremium?6:3).", lite_message = ".($isPremium?600:125).", tools = ".($isPremium?15:5).", arts = $artsLimit, last_request = '$today' WHERE user_id = $user_id");
    $tools['arts'] = $artsLimit;
}

if (!$tools['neyro'] || ($tools['arts'] <= 0 && $tools['bonus_arts'] <= 0)){
    echo 'No arts left';
    exit;
}

$model = 'stable-diffusion-3-medium';
$url   = 'https://ai.api.nvidia.com/v1/genai/stabilityai/stable-diffusion-3-medium';
$payload = [
    'prompt'=> $prompt,
    'aspect_ratio'=> '1:1',
    'steps'=> $steps,
    'cfg_scale'=> 5,
    'seed'=> 0,
    'negative_prompt'=> $n_prompt
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer '.$API_KEY,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 60
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http !== 200) echo 'NVAPI error '.$http;

$json = json_decode($resp, true);
if (!isset($json['image'])) echo 'Bad NVAPI response';

$img = base64_decode($json['image']);
if (!$img) echo 'Bad image data';

$dir = '../arts';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$fileName = uniqid('art_', true).'.png';
$filePath = $dir.'/'.$fileName;
file_put_contents($filePath, $img);

$field = $tools['arts'] > 0 ? 'arts' : 'bonus_arts';
$conn->query("UPDATE tools SET $field = $field - 1 WHERE user_id = $user_id");

echo './arts/'.$fileName;
session_write_close();