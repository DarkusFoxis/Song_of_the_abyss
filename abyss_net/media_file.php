<?php
declare(strict_types=1);

require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/../template/conn.php';
require_once __DIR__ . '/post_helpers.php';

auth_start_session();
$viewer = auth_get_current_user();

$postId = (int)($_GET['post_id'] ?? 0);
$fileName = basename((string)($_GET['file'] ?? ''));

if ($postId <= 0 || $fileName === '') {
    http_response_code(404);
    exit('Файл не найден.');
}

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    http_response_code(500);
    exit('Ошибка соединения с базой данных.');
}
mysqli_set_charset($conn, 'utf8mb4');

$stmt = $conn->prepare('SELECT id_post, id_user, media, NSFW FROM post WHERE id_post = ? LIMIT 1');
$stmt->bind_param('i', $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();
mysqli_close($conn);

if (!$post || !abyss_post_can_view($post, $viewer)) {
    http_response_code(403);
    exit(nsfw_access_denied_notice());
}

$mediaFiles = array_map('trim', explode(',', (string)($post['media'] ?? '')));
if (!in_array($fileName, $mediaFiles, true)) {
    http_response_code(404);
    exit('Файл не найден.');
}

$path = abyss_post_media_path($fileName, abyss_post_is_nsfw($post));
$mime = security_detect_mime($path) ?? 'application/octet-stream';
abyss_send_file_with_range($path, $mime);
