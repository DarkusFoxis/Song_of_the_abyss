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

$file = basename((string)($_POST['file'] ?? ''));
$postId = (int)($_POST['post_id'] ?? 0);
if ($file === '' || $postId <= 0) {
    echo "Ошибка: Нет данных.";
    mysqli_close($conn);
    exit;
}

$postStmt = $conn->prepare("SELECT id_user, media, NSFW FROM post WHERE id_post = ? LIMIT 1");
$postStmt->bind_param('i', $postId);
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
    echo "Ошибка: Нет прав на удаление вложения.";
    mysqli_close($conn);
    exit;
}

$filePath = abyss_post_media_path($file, abyss_post_is_nsfw($post));
if (is_file($filePath)) {
    unlink($filePath);
}

$mediaFiles = array_filter(explode(',', (string)$post['media']), static function ($mediaFile) use ($file) {
    return trim((string)$mediaFile) !== $file;
});
$newMedia = $mediaFiles === [] ? null : implode(',', $mediaFiles);

$updateStmt = $conn->prepare("UPDATE post SET media = ? WHERE id_post = ?");
$updateStmt->bind_param('si', $newMedia, $postId);
$updateStmt->execute();
$updateStmt->close();

echo "Вложение удалено.";
mysqli_close($conn);
session_write_close();
