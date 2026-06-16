<?php
require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/../template/conn.php';

auth_start_session();
auth_sync_session_from_token();

$currentUser = auth_get_current_user();
if ($currentUser === null) {
    echo "Ошибка: Необходимо авторизоваться.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешён']);
    exit;
}

security_require_csrf(true);

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Ошибка соединения: " . mysqli_connect_error();
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

$userStmt = $conn->prepare(
    "SELECT u.id, sg.lvl
     FROM users u
     JOIN site_group sg ON u.permissions = sg.name
     WHERE u.login = ?
     LIMIT 1"
);
$userStmt->bind_param('s', $currentUser['login']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    echo "Ошибка: Пользователь не найден.";
    mysqli_close($conn);
    exit;
}

$userId = (int)$user['id'];
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$ageLimit = (int)($_POST['age_limit'] ?? 0);
$storyContent = trim((string)($_POST['story'] ?? ''));

if ($title === '' || mb_strlen($title) < 10) {
    echo json_encode(['success' => false, 'message' => 'Название рассказа должно содержать минимум 10 символов.']);
    mysqli_close($conn);
    exit;
}

if ($description === '' || mb_strlen($description) < 30) {
    echo json_encode(['success' => false, 'message' => 'Описание должно содержать минимум 30 символов.']);
    mysqli_close($conn);
    exit;
}

if ($storyContent === '' || mb_strlen($storyContent) < 500) {
    echo json_encode(['success' => false, 'message' => 'Содержание рассказа должно содержать минимум 500 символов.']);
    mysqli_close($conn);
    exit;
}

$allowedAgeLimits = [0, 12, 16, 18];
if (!in_array($ageLimit, $allowedAgeLimits, true)) {
    echo json_encode(['success' => false, 'message' => 'Некорректное возрастное ограничение.']);
    mysqli_close($conn);
    exit;
}

$iconPath = './icon/base.png';
if (isset($_FILES['icon']) && ($_FILES['icon']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $iconAllowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    try {
        $stored = security_store_uploaded_file($_FILES['icon'], __DIR__ . '/icon', $iconAllowed, 'story_icon_', 5 * 1024 * 1024);
        $iconPath = './icon/' . $stored['filename'];
    } catch (RuntimeException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке иконки: ' . $e->getMessage()]);
        mysqli_close($conn);
        exit;
    }
}

$stmt = $conn->prepare(
    "INSERT INTO story (id_user, title, description, age_limit, story, icon, data)
     VALUES (?, ?, ?, ?, ?, ?, NOW())"
);
$stmt->bind_param('ississ', $userId, $title, $description, $ageLimit, $storyContent, $iconPath);

if (!$stmt->execute()) {
    error_log("Story insert SQL error: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении в базу данных.']);
    $stmt->close();
    mysqli_close($conn);
    exit;
}

$storyId = (int)$stmt->insert_id;
$stmt->close();

$url = 'https://so-ta.ru/story/story?id=' . $storyId;
$storyDesc = mb_strlen($description) > 200 ? mb_substr($description, 0, 200) . '...' : $description;

$urlStmt = $conn->prepare(
    "INSERT INTO url (url, title, description, keywords, date_add, id_user)
     VALUES (?, ?, ?, 'Рассказы', NOW(), ?)"
);
$urlStmt->bind_param('sssi', $url, $title, $storyDesc, $userId);
$urlStmt->execute();
$urlStmt->close();

$tonenAddStmt = $conn->prepare("UPDATE users SET abyss_ether = abyss_ether + 0.0000001 WHERE id = ?");
$tonenAddStmt->bind_param('i', $userId);
$tonenAddStmt->execute();
$tonenAddStmt->close();

$conn->query("UPDATE abyss_ether SET count = count - 0.0000001 WHERE id = 1");
mysqli_close($conn);

echo json_encode(['success' => true, 'message' => 'Рассказ успешно опубликован!'], JSON_UNESCAPED_UNICODE);
