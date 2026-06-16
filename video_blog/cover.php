<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/video_bootstrap.php';

$viewer = video_current_user();
$videoId = (int)($_GET['id'] ?? 0);

if ($videoId <= 0) {
    http_response_code(404);
    exit('Обложка не найдена.');
}

$conn = video_db();
$video = video_fetch_video($conn, $videoId, $viewer);
mysqli_close($conn);

if ($video === null || !video_can_view($video, $viewer) || empty($video['cover_file'])) {
    http_response_code(404);
    exit('Обложка не найдена.');
}

$path = rtrim(video_private_dir('covers'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename((string)$video['cover_file']);
video_send_image($path, (string)($video['cover_mime'] ?? 'image/jpeg'));
