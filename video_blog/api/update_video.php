<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/video_bootstrap.php';

video_require_post_request();
security_require_csrf(true);

$viewer = video_require_user();
$videoId = (int)($_POST['video_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));

if ($videoId <= 0 || $action === '') {
    video_json_error('Недостаточно данных для выполнения действия.');
}

$conn = video_db();
$video = video_fetch_video($conn, $videoId, $viewer);

if ($video === null || !video_can_manage($video, $viewer)) {
    mysqli_close($conn);
    video_json_error('Видео не найдено или недоступно.', 404);
}

if ($action === 'save_meta') {
	    $title = trim((string)($_POST['title'] ?? ''));
	    $description = trim((string)($_POST['description'] ?? ''));
	    $allowComments = video_post_bool('allow_comments', false);
	    $allowRating = (int)($video['NSFW'] ?? 0) === 1 ? 0 : ($allowComments ? 1 : 0);

    if ($title === '') {
        video_json_error('Название не может быть пустым.');
    }

    if (mb_strlen($title) > 160) {
        video_json_error('Название слишком длинное.');
    }

    if (mb_strlen($description) > 6000) {
        video_json_error('Описание слишком длинное.');
    }

    if (!video_title_can_be_edited($video) && $title !== (string)$video['title']) {
        mysqli_close($conn);
        video_json_error('Срок изменения названия уже истёк.');
    }

    $stmt = $conn->prepare(
        'UPDATE video_posts
         SET title = ?, description = ?, allow_comments = ?, allow_rating = ?, updated_at = NOW()
         WHERE id = ?'
    );
    $stmt->bind_param('ssiii', $title, $description, $allowComments, $allowRating, $videoId);
    $stmt->execute();
    $stmt->close();

    mysqli_close($conn);
    video_json_response([
        'success' => true,
        'message' => 'Изменения сохранены.',
    ]);
}

if ($action === 'set_status') {
    $targetStatus = trim((string)($_POST['target_status'] ?? ''));
    if (!in_array($targetStatus, ['published', 'draft'], true)) {
        mysqli_close($conn);
        video_json_error('Некорректный целевой статус.');
    }

    $currentStatus = (string)$video['status'];
    if ($targetStatus === 'published' && !in_array($currentStatus, ['ready', 'draft', 'published'], true)) {
        mysqli_close($conn);
        video_json_error('Это видео нельзя опубликовать из текущего состояния.');
    }

    if ($targetStatus === 'draft' && !in_array($currentStatus, ['published', 'ready', 'draft'], true)) {
        mysqli_close($conn);
        video_json_error('Это видео нельзя перевести в черновик.');
    }

    if ($targetStatus === 'published') {
        $stmt = $conn->prepare(
            'UPDATE video_posts
             SET status = "published",
                 published_at = COALESCE(published_at, NOW()),
                 updated_at = NOW()
             WHERE id = ?'
        );
    } else {
        $stmt = $conn->prepare(
            'UPDATE video_posts
             SET status = "draft", updated_at = NOW()
             WHERE id = ?'
        );
    }

    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $stmt->close();

    mysqli_close($conn);

    $newStatus = $targetStatus;
    $nextTarget = $targetStatus === 'published' ? 'draft' : 'published';
    $nextActionLabel = $targetStatus === 'published' ? 'Перевести в черновик' : 'Опубликовать';

    video_json_response([
        'success' => true,
        'message' => $targetStatus === 'published'
            ? 'Видео опубликовано.'
            : 'Видео переведено в черновик.',
        'status_label' => video_status_label($newStatus),
        'status_class' => video_status_class($newStatus),
        'next_target_status' => $nextTarget,
        'next_action_label' => $nextActionLabel,
    ]);
}

mysqli_close($conn);
video_json_error('Неизвестное действие.');