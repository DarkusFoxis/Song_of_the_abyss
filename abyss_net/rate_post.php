<?php
declare(strict_types=1);

require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/post_helpers.php';

auth_start_session();
auth_sync_session_from_token();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

$authUser = auth_get_current_user();
if ($authUser === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Вы должны быть авторизованы'], JSON_UNESCAPED_UNICODE);
    exit;
}

security_require_csrf(true);

require_once __DIR__ . '/../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка соединения с базой данных'], JSON_UNESCAPED_UNICODE);
    exit;
}

$login = (string)$authUser['login'];
$userStmt = $conn->prepare(
    'SELECT u.id, u.data_create, sg.lvl
     FROM users u
     JOIN site_group sg ON u.permissions = sg.name
     WHERE u.login = ?
     LIMIT 1'
);
$userStmt->bind_param('s', $login);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if (!$user) {
    mysqli_close($conn);
    echo json_encode(['success' => false, 'error' => 'Пользователь не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userLevel = (int)$user['lvl'];
if ($userLevel === 0) {
    mysqli_close($conn);
    echo json_encode(['success' => false, 'error' => 'Вы заблокированы на проекте'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($userLevel === 1) {
    mysqli_close($conn);
    echo json_encode(['success' => false, 'error' => 'Вы не подтверждены на сайте'], JSON_UNESCAPED_UNICODE);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$vote = (int)($_POST['vote'] ?? 0);
$userId = (int)$user['id'];

if ($postId <= 0 || !in_array($vote, [1, -1], true)) {
    mysqli_close($conn);
    echo json_encode(['success' => false, 'error' => 'Некорректные данные'], JSON_UNESCAPED_UNICODE);
    exit;
}

$postStmt = $conn->prepare('SELECT id_post, id_user, total_rating, NSFW FROM post WHERE id_post = ? LIMIT 1');
$postStmt->bind_param('i', $postId);
$postStmt->execute();
$post = $postStmt->get_result()->fetch_assoc();

if (!$post) {
    mysqli_close($conn);
    echo json_encode(['success' => false, 'error' => 'Пост не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!abyss_post_can_view($post, $authUser)) {
    mysqli_close($conn);
    echo json_encode(['success' => false, 'error' => nsfw_access_denied_notice()], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!abyss_post_can_rate($post)) {
    mysqli_close($conn);
    echo json_encode(['success' => false, 'error' => 'NSFW-посты нельзя оценивать.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int)$post['id_user'] === $userId) {
    mysqli_close($conn);
    echo json_encode(['success' => false, 'error' => 'Вы не можете ставить оценку сами себе'], JSON_UNESCAPED_UNICODE);
    exit;
}

$registrationDate = new DateTime((string)$user['data_create']);
$currentDate = new DateTime();
$interval = $registrationDate->diff($currentDate);
$months = ($interval->y * 12) + $interval->m;
$voteWeight = 1.0 + ($months * 0.1);
$ratingValue = $voteWeight * $vote;

$existingVoteStmt = $conn->prepare(
    'SELECT id, vote_value, rating
     FROM post_ratings
     WHERE id_post = ? AND id_user = ?
     LIMIT 1'
);
$existingVoteStmt->bind_param('ii', $postId, $userId);
$existingVoteStmt->execute();
$existingVote = $existingVoteStmt->get_result()->fetch_assoc();

mysqli_begin_transaction($conn);

try {
    if ($existingVote) {
        $oldRating = (float)$existingVote['rating'];
        $oldVote = (int)$existingVote['vote_value'];

        if ($oldVote === $vote) {
            $deleteStmt = $conn->prepare('DELETE FROM post_ratings WHERE id_post = ? AND id_user = ?');
            $deleteStmt->bind_param('ii', $postId, $userId);
            $deleteStmt->execute();

            $updatePostStmt = $conn->prepare('UPDATE post SET total_rating = total_rating - ? WHERE id_post = ?');
            $updatePostStmt->bind_param('di', $oldRating, $postId);
            $updatePostStmt->execute();

            $userVote = 0;
        } else {
            $updateVoteStmt = $conn->prepare(
                'UPDATE post_ratings
                 SET vote_value = ?, rating = ?, updated_at = NOW()
                 WHERE id_post = ? AND id_user = ?'
            );
            $updateVoteStmt->bind_param('idii', $vote, $ratingValue, $postId, $userId);
            $updateVoteStmt->execute();

            $ratingDiff = $ratingValue - $oldRating;
            $updatePostStmt = $conn->prepare('UPDATE post SET total_rating = total_rating + ? WHERE id_post = ?');
            $updatePostStmt->bind_param('di', $ratingDiff, $postId);
            $updatePostStmt->execute();

            $userVote = $vote;
        }
    } else {
        $insertStmt = $conn->prepare(
            'INSERT INTO post_ratings (id_post, id_user, rating, vote_value, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $insertStmt->bind_param('iidi', $postId, $userId, $ratingValue, $vote);
        $insertStmt->execute();

        $updatePostStmt = $conn->prepare('UPDATE post SET total_rating = total_rating + ? WHERE id_post = ?');
        $updatePostStmt->bind_param('di', $ratingValue, $postId);
        $updatePostStmt->execute();

        $userVote = $vote;
    }

    $ratingStmt = $conn->prepare('SELECT total_rating FROM post WHERE id_post = ? LIMIT 1');
    $ratingStmt->bind_param('i', $postId);
    $ratingStmt->execute();
    $ratingRow = $ratingStmt->get_result()->fetch_assoc();

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'rating' => round((float)($ratingRow['total_rating'] ?? 0), 2),
        'vote_weight' => round($voteWeight, 2),
        'user_vote' => $userVote,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при обработке голоса',
    ], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conn);
session_write_close();
