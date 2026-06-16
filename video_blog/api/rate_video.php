<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/video_bootstrap.php';

video_require_post_request();
security_require_csrf(true);

$viewer = video_require_user();
$videoId = (int)($_POST['video_id'] ?? 0);
$vote = (int)($_POST['vote'] ?? 0);

if ($videoId <= 0 || !in_array($vote, [1, -1], true)) {
    video_json_error('Переданы некорректные данные.');
}

$conn = video_db();
$video = video_fetch_video($conn, $videoId, $viewer);

if ($video === null || !video_can_view($video, $viewer)) {
    mysqli_close($conn);
    video_json_error('Видео не найдено.', 404);
}

if (!video_rating_enabled($video)) {
    mysqli_close($conn);
    video_json_error('Оценки для этого видео отключены.');
}

if ((int)$video['user_id'] === (int)$viewer['id']) {
    mysqli_close($conn);
    video_json_error('Нельзя оценивать собственное видео.');
}

$registeredAt = new DateTime((string)($viewer['data_create'] ?? 'now'));
$months = $registeredAt->diff(new DateTime())->y * 12 + $registeredAt->diff(new DateTime())->m;
$voteWeight = 1.0 + ($months * 0.1);
$ratingValue = round($voteWeight * $vote, 2);

$existingStmt = $conn->prepare(
    'SELECT id, vote_value, rating
     FROM video_ratings
     WHERE video_id = ? AND user_id = ?
     LIMIT 1'
);
$viewerId = (int)$viewer['id'];
$existingStmt->bind_param('ii', $videoId, $viewerId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();
$existingStmt->close();

mysqli_begin_transaction($conn);

try {
    if ($existing) {
        $oldVote = (int)$existing['vote_value'];
        $oldRating = (float)$existing['rating'];

        if ($oldVote === $vote) {
            $deleteStmt = $conn->prepare('DELETE FROM video_ratings WHERE video_id = ? AND user_id = ?');
            $deleteStmt->bind_param('ii', $videoId, $viewerId);
            $deleteStmt->execute();
            $deleteStmt->close();

            $updateStmt = $conn->prepare('UPDATE video_posts SET rating_total = rating_total - ? WHERE id = ?');
            $updateStmt->bind_param('di', $oldRating, $videoId);
            $updateStmt->execute();
            $updateStmt->close();

            $userVote = 0;
        } else {
            $updateVoteStmt = $conn->prepare(
                'UPDATE video_ratings
                 SET vote_value = ?, rating = ?, updated_at = NOW()
                 WHERE video_id = ? AND user_id = ?'
            );
            $updateVoteStmt->bind_param('idii', $vote, $ratingValue, $videoId, $viewerId);
            $updateVoteStmt->execute();
            $updateVoteStmt->close();

            $diff = $ratingValue - $oldRating;
            $updateStmt = $conn->prepare('UPDATE video_posts SET rating_total = rating_total + ? WHERE id = ?');
            $updateStmt->bind_param('di', $diff, $videoId);
            $updateStmt->execute();
            $updateStmt->close();

            $userVote = $vote;
        }
    } else {
        $insertStmt = $conn->prepare(
            'INSERT INTO video_ratings (video_id, user_id, rating, vote_value, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $insertStmt->bind_param('iidi', $videoId, $viewerId, $ratingValue, $vote);
        $insertStmt->execute();
        $insertStmt->close();

        $updateStmt = $conn->prepare('UPDATE video_posts SET rating_total = rating_total + ? WHERE id = ?');
        $updateStmt->bind_param('di', $ratingValue, $videoId);
        $updateStmt->execute();
        $updateStmt->close();

        $userVote = $vote;
    }

    $ratingStmt = $conn->prepare('SELECT rating_total FROM video_posts WHERE id = ? LIMIT 1');
    $ratingStmt->bind_param('i', $videoId);
    $ratingStmt->execute();
    $ratingRow = $ratingStmt->get_result()->fetch_assoc();
    $ratingStmt->close();

    mysqli_commit($conn);
    mysqli_close($conn);

    video_json_response([
        'success' => true,
        'rating' => number_format((float)($ratingRow['rating_total'] ?? 0), 2, '.', ' '),
        'user_vote' => $userVote,
    ]);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    mysqli_close($conn);
    video_json_error('Не удалось сохранить оценку.', 500);
}
