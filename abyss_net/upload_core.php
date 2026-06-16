<?php
require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/../template/conn.php';
require_once __DIR__ . '/post_helpers.php';

auth_start_session();
auth_sync_session_from_token();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Ошибка: Неверный метод запроса.";
    exit;
}

$authUser = auth_get_current_user();
if ($authUser === null) {
    echo "Ошибка: Необходимо авторизоваться.";
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
    "SELECT u.id, u.NSFW, u.AUTO_NSFW, u.CONFIRM_NSFW, sg.lvl
     FROM users u
     JOIN site_group sg ON u.permissions = sg.name
     WHERE u.login = ?
     LIMIT 1"
);
$userStmt->bind_param('s', $authUser['login']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    echo "Ошибка: Пользователь не найден.";
    mysqli_close($conn);
    exit;
}

$userId = (int)$user['id'];
$userLevel = (int)$user['lvl'];
$canUseNsfw = nsfw_user_has_access($user);

$invStmt = $conn->prepare("SELECT id_user FROM invent WHERE id_user = ? LIMIT 1");
$invStmt->bind_param('i', $userId);
$invStmt->execute();
$hasInventory = $invStmt->get_result()->fetch_assoc();
$invStmt->close();

if (!$hasInventory) {
    echo "Ошибка: Инвентарь не найден.";
    mysqli_close($conn);
    exit;
}

$maxFiles = $userLevel >= 3 ? 9 : 3;
$baseMb = $userLevel >= 3 ? 24 : 12;
$etherAdd = true;
$etherTime = mt_rand(3600, 7200);
$allowedMimeMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'audio/mpeg' => 'mp3',
    'video/mp4' => 'mp4',
];
$uploadedFiles = [];

$lastPostStmt = $conn->prepare("SELECT data FROM post WHERE id_user = ? ORDER BY data DESC LIMIT 1");
$lastPostStmt->bind_param('i', $userId);
$lastPostStmt->execute();
$lastPost = $lastPostStmt->get_result()->fetch_assoc();
$lastPostStmt->close();

$lastPostTime = $lastPost ? strtotime((string)$lastPost['data']) : 0;
$currentTime = time();

if ($userLevel < 4 && $lastPostTime > 0 && ($currentTime - $lastPostTime) < 900) {
    echo "Ошибка: Вы не можете публиковать посты чаще чем раз в 15 минут.";
    mysqli_close($conn);
    exit;
}

if ($lastPostTime > 0 && ($currentTime - $lastPostTime) < $etherTime) {
    $etherAdd = false;
}

$editId = (int)($_POST['edit_id'] ?? 0);
$postRow = null;

if ($editId > 0) {
    $postStmt = $conn->prepare("SELECT id_post, id_user, media, NSFW FROM post WHERE id_post = ? LIMIT 1");
    $postStmt->bind_param('i', $editId);
    $postStmt->execute();
    $postRow = $postStmt->get_result()->fetch_assoc();
    $postStmt->close();

    if (!$postRow) {
        echo "Ошибка: Пост не найден.";
        mysqli_close($conn);
        exit;
    }

    if ((int)$postRow['id_user'] !== $userId) {
        echo "Ошибка: Нет прав на редактирование.";
        mysqli_close($conn);
        exit;
    }
}

if ($editId > 0 && is_array($postRow) && abyss_post_is_nsfw($postRow) && !$canUseNsfw) {
    echo nsfw_access_denied_notice();
    mysqli_close($conn);
    exit;
}

if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
    $fileCount = count($_FILES['media']['name']);
    if ($fileCount > $maxFiles) {
        echo "Ошибка: Максимум $maxFiles файлов.";
        mysqli_close($conn);
        exit;
    }
}

$post = trim((string)($_POST['post'] ?? ''));
$title = trim((string)($_POST['title'] ?? ''));
$requestedNsfw = (string)($_POST['nsfw'] ?? '0') === '1';
if ($requestedNsfw && !$canUseNsfw) {
    echo "Ошибка: NSFW-контент доступен только после подтверждения возраста.";
    mysqli_close($conn);
    exit;
}

if ($post === '' && $title === '' && empty($_FILES['media']['name'][0])) {
    echo "Ошибка: Пост не может быть пустым.";
    mysqli_close($conn);
    exit;
}

$rawTags = trim((string)($_POST['tags'] ?? ''));
$tags = [];
if ($rawTags !== '') {
    $tagArray = array_unique(array_map('trim', explode(',', $rawTags)));
    foreach ($tagArray as $t) {
        if (mb_strlen($t) > 30) continue;
        if (!preg_match('/^[\p{L}\p{N}_\s-]+$/u', $t)) continue;
        $tags[] = $t;
    }
    $tags = array_slice($tags, 0, 5);
}

$isNsfwPost = $requestedNsfw;
$targetDir = abyss_post_media_dir($isNsfwPost);

if (!empty($_FILES['media']['name'][0])) {
    for ($i = 0; $i < count($_FILES['media']['name']); $i++) {
        $file = [
            'name' => $_FILES['media']['name'][$i],
            'type' => $_FILES['media']['type'][$i],
            'tmp_name' => $_FILES['media']['tmp_name'][$i],
            'error' => $_FILES['media']['error'][$i],
            'size' => $_FILES['media']['size'][$i],
        ];

        $mime = security_detect_mime((string)$file['tmp_name']);
        if ($mime === null || !isset($allowedMimeMap[$mime])) {
            echo "Ошибка: Неподдерживаемый тип файла.";
            mysqli_close($conn);
            exit;
        }

	        $maxSize = $mime === 'video/mp4'
	            ? $baseMb * 1.5 * 1024 * 1024
	            : $baseMb * 1024 * 1024;
	        if ($isNsfwPost) {
	            $maxSize = (int)floor($maxSize * 0.75);
	        }

	        try {
            $stored = security_store_uploaded_file($file, $targetDir, $allowedMimeMap, 'media_', $maxSize);
            $uploadedFiles[] = $stored['filename'];
        } catch (RuntimeException $e) {
            echo "Ошибка загрузки файла: " . $e->getMessage();
            mysqli_close($conn);
            exit;
        }
    }
}

$safeTitle = security_html($title);
$safePost = nl2br(security_html($post), false);

if (mb_strlen($safeTitle) > 150) {
    echo "Ошибка: Заголовок слишком длинный.";
    mysqli_close($conn);
    exit;
}

if (mb_strlen($safePost) > ($userLevel >= 3 ? 6000 : 3000)) {
    echo "Ошибка: Текст поста слишком длинный.";
    mysqli_close($conn);
    exit;
}

$titleSpaces = substr_count($safeTitle, ' ');
$titleLength = max(1, mb_strlen($safeTitle));
if (round(($titleSpaces / $titleLength) * 100, 2) > 40) {
    echo "Ошибка: Слишком много пробелов в заголовке.";
    mysqli_close($conn);
    exit;
}

$postSpaces = substr_count($safePost, ' ');
$postLength = max(1, mb_strlen($safePost));
if (round(($postSpaces / $postLength) * 100, 2) > 40) {
    echo "Ошибка: Слишком много пробелов в сообщении.";
    mysqli_close($conn);
    exit;
}

$enterCount = substr_count($post, "\n");
if (round(($enterCount / max(1, mb_strlen($post))) * 100, 2) > 20) {
    echo "Ошибка: Слишком много переходов на новую строку.";
    mysqli_close($conn);
    exit;
}

if ($editId > 0) {
    $existingMedia = trim((string)($postRow['media'] ?? ''));
    $mediaNames = array_filter($existingMedia === '' ? [] : explode(',', $existingMedia));
    $oldNsfw = (int)($postRow['NSFW'] ?? 0) === 1;
    abyss_move_post_media_files($mediaNames, $oldNsfw, $isNsfwPost);
    $mediaNames = array_merge($mediaNames, $uploadedFiles);
    $mediaValue = $mediaNames === [] ? null : implode(',', $mediaNames);

    $nsfwValue = $isNsfwPost ? 1 : 0;
    $stmt = $conn->prepare("UPDATE post SET title = ?, post = ?, media = ?, NSFW = ? WHERE id_post = ?");
    $stmt->bind_param('sssii', $safeTitle, $safePost, $mediaValue, $nsfwValue, $editId);
    $stmt->execute();
    if ($isNsfwPost) {
        $deleteRatingsStmt = $conn->prepare("DELETE FROM post_ratings WHERE id_post = ?");
        $deleteRatingsStmt->bind_param('i', $editId);
        $deleteRatingsStmt->execute();
        $deleteRatingsStmt->close();

        $resetRatingStmt = $conn->prepare("UPDATE post SET total_rating = 0 WHERE id_post = ?");
        $resetRatingStmt->bind_param('i', $editId);
        $resetRatingStmt->execute();
        $resetRatingStmt->close();
    }
    if ($tags !== []) {
        save_post_tags($conn, $editId, $tags);
    } else {
        $stmt = $conn->prepare("DELETE FROM post_tags WHERE id_post = ?");
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $stmt->close();
    }

    echo "Пост успешно обновлён.";
    $postUrl = 'https://so-ta.ru/abyss_net/post?id=' . $editId;
    $urlNsfwStmt = $conn->prepare("UPDATE url SET nsfw = ? WHERE url = ?");
    $urlNsfwStmt->bind_param('is', $nsfwValue, $postUrl);
    $urlNsfwStmt->execute();
    $urlNsfwStmt->close();
    $stmt->close();
    mysqli_close($conn);
    exit;
}

$mediaValue = $uploadedFiles === [] ? null : implode(',', $uploadedFiles);
$nsfwValue = $isNsfwPost ? 1 : 0;
$insertStmt = $conn->prepare(
    "INSERT INTO post (id_user, title, post, media, data, NSFW) VALUES (?, ?, ?, ?, NOW(), ?)"
);
$insertStmt->bind_param('isssi', $userId, $safeTitle, $safePost, $mediaValue, $nsfwValue);
$insertStmt->execute();
$postId = (int)$insertStmt->insert_id;
$insertStmt->close();

if ($tags !== []) {
    save_post_tags($conn, $postId, $tags);
}

$url = 'https://so-ta.ru/abyss_net/post?id=' . $postId;
$plainShortPost = mb_substr(trim(strip_tags($safePost)), 0, 200);
if (mb_strlen(trim(strip_tags($safePost))) > 200) {
    $plainShortPost .= '...';
}

$urlStmt = $conn->prepare(
    "INSERT INTO url (url, title, description, keywords, date_add, id_user)
     VALUES (?, ?, ?, 'Пост, Abyss Net, Блог', NOW(), ?)"
);
$urlStmt->bind_param('sssi', $url, $safeTitle, $plainShortPost, $userId);
$urlStmt->execute();
$urlStmt->close();

if ($isNsfwPost) {
    $urlNsfwStmt = $conn->prepare("UPDATE url SET nsfw = 1 WHERE url = ?");
    $urlNsfwStmt->bind_param('s', $url);
    $urlNsfwStmt->execute();
    $urlNsfwStmt->close();
}

if ($etherAdd) {
    $addStmt = $conn->prepare("UPDATE users SET abyss_ether = abyss_ether + 0.0000001 WHERE id = ?");
    $addStmt->bind_param('i', $userId);
    $addStmt->execute();
    $addStmt->close();

    $conn->query("UPDATE abyss_ether SET count = count - 0.0000001 WHERE id = 1");
}

echo "Успешно опубликованно!";
mysqli_close($conn);
session_write_close();
