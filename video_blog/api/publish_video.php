<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/video_bootstrap.php';

video_require_post_request();
security_require_csrf(true);

$viewer = video_require_user();
$videoId = (int)($_POST['video_id'] ?? 0);

if ($videoId <= 0) {
    video_json_error('Не указан идентификатор видео.');
}

$conn = video_db();
$video = video_fetch_video($conn, $videoId, $viewer);

if ($video === null || !video_can_manage($video, $viewer)) {
    mysqli_close($conn);
    video_json_error('Видео не найдено или недоступно.', 404);
}

$status = (string)$video['status'];
if (!in_array($status, ['ready', 'draft', 'published'], true)) {
    mysqli_close($conn);
    video_json_error('Это видео нельзя опубликовать из текущего состояния.');
}

if ($status === 'published') {
    mysqli_close($conn);
    video_json_response([
        'success' => true,
        'watch_url' => video_watch_url($videoId),
        'message' => 'Видео уже опубликовано.',
    ]);
}

$stmt = $conn->prepare(
    'UPDATE video_posts
     SET status = "published",
         published_at = COALESCE(published_at, NOW()),
         updated_at = NOW()
     WHERE id = ?'
);
$stmt->bind_param('i', $videoId);
$stmt->execute();
$stmt->close();

mysqli_close($conn);

video_json_response([
    'success' => true,
    'watch_url' => video_watch_url($videoId),
    'message' => 'Видео опубликовано и теперь доступно в каталоге.',
]);
