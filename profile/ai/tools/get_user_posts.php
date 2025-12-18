<?php
/*  tools/get_user_posts.php
    Возвращает 5 последних постов пользователя в JSON.
    GET-параметр u = ник (опционально)
*/
session_start();
require_once '../../../template/conn.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

$login = $_SESSION['user'];
$stmt  = $conn->prepare("SELECT u.id, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?");
$stmt->bind_param('s', $login);
$stmt->execute();
$res   = $stmt->get_result();
if (!$res->num_rows) {
    echo json_encode(['error' => 'Пользователь, от имени которого вы отправили запрос, не найден.']);
    exit;
}
$caller      = $res->fetch_assoc();
$caller_id   = (int)$caller['id'];
$caller_lvl  = (int)$caller['lvl'];

$targetName = isset($_GET['u']) && $_GET['u'] !== '' ? trim($_GET['u']) : $login;

if ($targetName !== $login && $caller_lvl < 3) {
    echo json_encode(['error' => 'У пользователя, от имени которого вы выполняете запрос, недостаточно прав, для анализа постов другого пользователя. Пожалуйста, соообщите ему об этом. Просматривать посты других пользователей может только премиум пользователи. Приобрести премиум возможно на рынке бездны за 250 руб. навсегда.']);
    exit;
}

if ($targetName === $login) {
    $target_id = $caller_id;
} else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param('s', $targetName);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res->num_rows) {
        echo json_encode(['error' => "Пользователь с именем $targetName не найден. Оповестите пользователя об этом. Пусть попробует проверить имя пользователя."]);
        exit;
    }
    $target_id = (int)$res->fetch_assoc()['id'];
}

$posts = [];
$stmt  = $conn->prepare("SELECT title, post, data FROM post WHERE id_user = ? ORDER BY data DESC LIMIT 5");
$stmt->bind_param('i', $target_id);
$stmt->execute();
$res   = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $plain = strip_tags($row['post']);
    $posts[] = [
        'title'  => $row['title'],
        'preview'=> $plain,
        'date'   => $row['data']
    ];
}

echo json_encode($posts ?: ['Нет постов']);
session_write_close();