<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/video_bootstrap.php';

$viewer = video_current_user();
$videoId = (int)($_GET['id'] ?? 0);

if ($videoId <= 0) {
    http_response_code(404);
    exit('Видео не найдено.');
}

$conn = video_db();
$video = video_fetch_video($conn, $videoId, $viewer);
mysqli_close($conn);

if ($video === null || !video_can_view($video, $viewer)) {
    http_response_code(404);
    exit('Видео недоступно.');
}

$path = rtrim(video_private_dir('processed'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename((string)$video['video_file']);
video_send_file_with_range($path, (string)($video['video_mime'] ?? 'video/webm'));
