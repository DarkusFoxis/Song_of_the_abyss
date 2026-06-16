<?php
require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/../template/conn.php';
require_once __DIR__ . '/post_helpers.php';

auth_start_session();
auth_sync_session_from_token();

$currentUser = auth_get_current_user();
if ($currentUser === null) {
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

$idPost = (int)($_POST['id_post'] ?? 0);
if ($idPost <= 0) {
    echo "Ошибка: Некорректный ID поста.";
    mysqli_close($conn);
    exit;
}

$postStmt = $conn->prepare("SELECT id_user, media, NSFW FROM post WHERE id_post = ? LIMIT 1");
$postStmt->bind_param('i', $idPost);
$postStmt->execute();
$post = $postStmt->get_result()->fetch_assoc();
$postStmt->close();

if (!$post) {
    echo "Ошибка: Пост не найден.";
    mysqli_close($conn);
    exit;
}

$userStmt = $conn->prepare("SELECT id FROM users WHERE login = ? LIMIT 1");
$userStmt->bind_param('s', $currentUser['login']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user || (int)$post['id_user'] !== (int)$user['id']) {
    echo "Ошибка: Нет прав на удаление этого поста.";
    mysqli_close($conn);
    exit;
}

if (!empty($post['media'])) {
    foreach (explode(',', (string)$post['media']) as $file) {
	        $filePath = abyss_post_media_path((string)$file, abyss_post_is_nsfw($post));
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}

$deletePostStmt = $conn->prepare("DELETE FROM post WHERE id_post = ?");
$deletePostStmt->bind_param('i', $idPost);
$deletePostStmt->execute();
$deletePostStmt->close();

$deleteCommentsStmt = $conn->prepare("DELETE FROM comment WHERE id_post = ?");
$deleteCommentsStmt->bind_param('i', $idPost);
$deleteCommentsStmt->execute();
$deleteCommentsStmt->close();

$urlLike = '%id=' . $idPost;
$deleteUrlStmt = $conn->prepare("DELETE FROM url WHERE url LIKE ?");
$deleteUrlStmt->bind_param('s', $urlLike);
$deleteUrlStmt->execute();
$deleteUrlStmt->close();

echo "Пост успешно удалён.";
mysqli_close($conn);
session_write_close();
