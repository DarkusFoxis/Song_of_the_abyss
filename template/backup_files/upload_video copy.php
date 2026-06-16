<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/video_processing.php';

video_require_post_request();
security_require_csrf(true);

$viewer = video_require_user();
$policy = video_upload_policy($viewer);
if (!$policy['can_upload']) {
    video_json_error('У вашей роли нет доступа к загрузке видео.', 403);
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if (!isset($_FILES['video']) || !is_array($_FILES['video'])) {
    video_json_error('Видео не было передано.');
}

$videoFile = $_FILES['video'];
$sourceName = basename((string)($videoFile['name'] ?? 'video'));
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$uploadToken = video_normalize_upload_token((string)($_POST['upload_token'] ?? ''));
$isNsfw = video_post_bool('nsfw', false);

if ($isNsfw && !nsfw_user_has_access($viewer)) {
    video_json_error('NSFW-видео доступно только после подтверждения возраста.', 403);
}

if ($title === '') {
    $dotPosition = strrpos($sourceName, '.');
    $title = $dotPosition === false ? $sourceName : substr($sourceName, 0, $dotPosition);
}

if ($title === '') {
    $title = 'Новая загрузка';
}

if (mb_strlen($title) > 160) {
    video_json_error('Название слишком длинное.');
}

if (mb_strlen($description) > 6000) {
    video_json_error('Описание слишком длинное.');
}

$allowComments = video_post_bool('allow_comments', false);
$allowRating = ($allowComments && !$isNsfw) ? 1 : 0;

$allowedVideoMap = [
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/quicktime' => 'mov',
    'video/x-matroska' => 'mkv',
];

$allowedCoverMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

$conn = video_db();
$remaining = video_remaining_uploads($conn, $viewer);
$uploadCost = video_upload_cost($isNsfw);
if ($remaining < $uploadCost) {
    mysqli_close($conn);
    $conn = null;
    video_json_error('Месячный лимит загрузок уже исчерпан.');
}

video_write_upload_status($uploadToken, [
    'success' => true,
    'status' => 'processing',
    'progress' => 4,
    'message' => 'Подготовка загрузки...',
    'user_id' => (int)$viewer['id'],
]);

$sourceStored = null;
$finalVideoPath = null;
$finalVideoName = null;
$coverPath = null;
$storedCoverPath = null;
$videoId = 0;

try {
    $tempDir = video_private_dir('temp');
    $processedDir = video_private_dir('processed');
    $coversDir = video_private_dir('covers');

    video_write_upload_status($uploadToken, [
        'success' => true,
        'status' => 'processing',
        'progress' => 12,
        'message' => 'Сервер принимает файл...',
        'user_id' => (int)$viewer['id'],
    ]);

	    $sourceStored = security_store_uploaded_file(
	        $videoFile,
	        $tempDir,
	        $allowedVideoMap,
	        'video_source_',
	        video_effective_max_source_bytes($policy, $isNsfw)
	    );

    video_write_upload_status($uploadToken, [
        'success' => true,
        'status' => 'processing',
        'progress' => 28,
        'message' => 'Файл загружен, создаётся запись черновика...',
        'user_id' => (int)$viewer['id'],
    ]);

    $insertStmt = $conn->prepare(
        'INSERT INTO video_posts (
            user_id, title, description, video_file, video_mime, cover_mode, source_name, source_mime,
	            source_size_bytes, allow_comments, allow_rating, status, processing_progress, NSFW, created_at, updated_at
	         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $emptyVideoFile = '';
    $coverMode = (isset($_FILES['cover']) && !empty($_FILES['cover']['name'])) ? 'upload' : 'frame';
    $status = 'processing';
    $progress = 10;
    $sourceMime = (string)$sourceStored['mime'];
    $sourceSize = (int)$sourceStored['size'];
    $videoMime = 'video/webm';
	    $viewerId = (int)$viewer['id'];
	    $nsfwValue = $isNsfw ? 1 : 0;

	    $insertStmt->bind_param(
	        'isssssssiiisii',
        $viewerId,
        $title,
        $description,
        $emptyVideoFile,
        $videoMime,
        $coverMode,
        $sourceName,
        $sourceMime,
        $sourceSize,
	        $allowComments,
	        $allowRating,
	        $status,
	        $progress,
	        $nsfwValue
	    );
    $insertStmt->execute();
    $videoId = (int)$insertStmt->insert_id;
    $insertStmt->close();

    video_write_upload_status($uploadToken, [
        'success' => true,
        'status' => 'processing',
        'progress' => 34,
        'message' => 'Черновик создан, начинается обработка...',
        'user_id' => (int)$viewer['id'],
        'video_id' => $videoId,
        'watch_url' => video_watch_url($videoId),
    ]);

    $progressStmt = $conn->prepare('UPDATE video_posts SET processing_progress = 35 WHERE id = ?');
    $progressStmt->bind_param('i', $videoId);
    $progressStmt->execute();
    $progressStmt->close();

    // Закрываем соединение перед длительной операцией кодирования
    mysqli_close($conn);
    $conn = null;

    $processed = video_prepare_final_video(
        (string)$sourceStored['path'],
        $sourceMime,
        $sourceSize,
        $processedDir,
        static function (float $ratio) use ($uploadToken, $viewer, &$videoId): void {
            static $lastProgress = -1;

            $percent = 35 + (int)round(max(0.0, min(1.0, $ratio)) * 50);
            if ($percent <= $lastProgress) {
                return;
            }

            $lastProgress = $percent;
            video_write_upload_status($uploadToken, [
                'success' => true,
                'status' => 'processing',
                'progress' => $percent,
                'message' => 'Идёт сжатие и перекодирование видео...',
                'user_id' => (int)$viewer['id'],
                'video_id' => $videoId,
                'watch_url' => $videoId > 0 ? video_watch_url($videoId) : null,
            ]);
        }
    );

    // Открываем новое соединение после кодирования
    $conn = video_db();

    $finalVideoPath = (string)$processed['path'];
    $finalVideoName = (string)$processed['filename'];
    $videoMime = (string)$processed['final_mime'];
    $progressStmt = $conn->prepare('UPDATE video_posts SET processing_progress = 75 WHERE id = ?');
    $progressStmt->bind_param('i', $videoId);
    $progressStmt->execute();
    $progressStmt->close();

    video_write_upload_status($uploadToken, [
        'success' => true,
        'status' => 'processing',
        'progress' => 88,
        'message' => 'Видео готово, подготавливается обложка...',
        'user_id' => (int)$viewer['id'],
        'video_id' => $videoId,
        'watch_url' => video_watch_url($videoId),
    ]);

    if (isset($_FILES['cover']) && is_array($_FILES['cover']) && !empty($_FILES['cover']['name'])) {
        $coverStored = security_store_uploaded_file(
            $_FILES['cover'],
            $coversDir,
            $allowedCoverMap,
            'cover_',
            8 * 1024 * 1024
        );
        $coverFile = (string)$coverStored['filename'];
        $coverMime = (string)$coverStored['mime'];
        $storedCoverPath = (string)$coverStored['path'];
    } else {
        $coverFile = video_random_name('cover', 'jpg');
        $coverPath = rtrim($coversDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $coverFile;
        video_generate_frame_cover($finalVideoPath, $coverPath, (float)$processed['meta']['duration']);
        $coverMime = 'image/jpeg';
    }

    if (isset($conn) && $conn instanceof mysqli) {
        @mysqli_close($conn);
        $conn = null;
    }
    $conn = video_db();

    $updateStmt = $conn->prepare(
        'UPDATE video_posts
         SET video_file = ?,
             video_mime = ?,
             cover_file = ?,
             cover_mime = ?,
             final_size_bytes = ?,
             duration_seconds = ?,
             width = ?,
             height = ?,
             status = ?,
             processing_progress = 100,
             processing_error = NULL,
             processed_at = NOW(),
             updated_at = NOW()
         WHERE id = ?'
    );

    $finalSize = (int)($processed['meta']['size'] ?? filesize($finalVideoPath));
    $duration = (float)($processed['meta']['duration'] ?? 0);
    $width = (int)($processed['meta']['width'] ?? 0);
    $height = (int)($processed['meta']['height'] ?? 0);
    $readyStatus = 'ready';

    $updateStmt->bind_param(
        'ssssidiisi',
        $finalVideoName,
        $videoMime,
        $coverFile,
        $coverMime,
        $finalSize,
        $duration,
        $width,
        $height,
        $readyStatus,
        $videoId
    );
    $updateStmt->execute();
    $updateStmt->close();

	    video_register_monthly_upload($conn, $viewerId, null, $uploadCost);

    video_write_upload_status($uploadToken, [
        'success' => true,
        'status' => 'ready',
        'progress' => 100,
        'message' => 'Обработка завершена. Видео сохранено как черновик и ждёт публикации.',
        'user_id' => (int)$viewer['id'],
        'video_id' => $videoId,
        'watch_url' => video_watch_url($videoId),
    ]);

    if ($sourceStored !== null && is_file((string)$sourceStored['path'])) {
        @unlink((string)$sourceStored['path']);
    }

    mysqli_close($conn);
    $conn = null;

    video_json_response([
        'success' => true,
        'video_id' => $videoId,
        'watch_url' => video_watch_url($videoId),
        'message' => 'Видео обработано и сохранено как черновик. Подтвердите публикацию, когда будете готовы.',
    ]);
} catch (Throwable $e) {
    if ($sourceStored !== null && is_file((string)$sourceStored['path'])) {
        @unlink((string)$sourceStored['path']);
    }

    if ($finalVideoPath !== null && is_file($finalVideoPath)) {
        @unlink($finalVideoPath);
    }

    if ($coverPath !== null && is_file($coverPath)) {
        @unlink($coverPath);
    }

    if ($storedCoverPath !== null && is_file($storedCoverPath)) {
        @unlink($storedCoverPath);
    }

    if ($videoId > 0) {
        // Проверяем, живо ли соединение, и переподключаемся при необходимости
        $errorMessage = mb_substr($e->getMessage(), 0, 2000);
        $failedStatus = 'failed';
        try {
            $failureConn = video_db();
            $stmt = $failureConn->prepare(
                'UPDATE video_posts
                 SET status = ?, processing_error = ?, processing_progress = 0, updated_at = NOW()
                 WHERE id = ?'
            );
            if ($stmt) {
                $stmt->bind_param('ssi', $failedStatus, $errorMessage, $videoId);
                $stmt->execute();
                $stmt->close();
            }
            mysqli_close($failureConn);
        } catch (Throwable $dbThrowable) {
        }
    }

    video_write_upload_status($uploadToken, [
        'success' => false,
        'status' => 'failed',
        'progress' => 0,
        'message' => $e->getMessage(),
        'user_id' => (int)$viewer['id'],
        'video_id' => $videoId,
    ]);

    if (isset($conn) && $conn instanceof mysqli) {
        mysqli_close($conn);
        $conn = null;
    }

    video_json_error($e->getMessage());
}
session_write_close();
