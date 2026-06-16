<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/video_bootstrap.php';

video_require_post_request();
security_require_csrf(true);

$viewer = video_require_user();
$videoId = (int)($_POST['video_id'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));

if ($videoId <= 0 || $comment === '') {
    video_json_error('Нужно указать видео и текст комментария.');
}

if (mb_strlen($comment) > 2048) {
    video_json_error('Комментарий слишком длинный.');
}

$now = time();
if (isset($_SESSION['vb_last_comment_time']) && ($now - (int)$_SESSION['vb_last_comment_time']) < 8) {
    video_json_error('Подождите немного перед следующим комментарием.');
}

$conn = video_db();
$video = video_fetch_video($conn, $videoId, $viewer);

if ($video === null || !video_can_view($video, $viewer)) {
    mysqli_close($conn);
    video_json_error('Видео не найдено.', 404);
}

if (!video_comments_enabled($video)) {
    mysqli_close($conn);
    video_json_error('Комментарии для этого видео отключены.');
}

$stmt = $conn->prepare(
    'INSERT INTO video_comments (video_id, user_id, text, created_at)
     VALUES (?, ?, ?, NOW())'
);
$viewerId = (int)$viewer['id'];
$stmt->bind_param('iis', $videoId, $viewerId, $comment);
$stmt->execute();
$commentId = (int)$stmt->insert_id;
$stmt->close();

$_SESSION['vb_last_comment_time'] = $now;

$commentStmt = $conn->prepare(
    'SELECT vc.*, u.username, u.avatar
     FROM video_comments vc
     JOIN users u ON u.id = vc.user_id
     WHERE vc.id = ?
     LIMIT 1'
);
$commentStmt->bind_param('i', $commentId);
$commentStmt->execute();
$savedComment = $commentStmt->get_result()->fetch_assoc();
$commentStmt->close();

mysqli_close($conn);

video_json_response([
    'success' => true,
    'comment_html' => $savedComment ? video_render_comment($savedComment) : '',
]);
