<?php
declare(strict_types=1);

require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/post_helpers.php';

auth_start_session();
auth_sync_session_from_token();

header('Content-Type: application/json; charset=utf-8');

$authUser = auth_get_current_user();
$postId = (int)($_GET['post_id'] ?? 0);

if ($authUser === null || $postId <= 0) {
    echo json_encode(['voted' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo json_encode(['voted' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$login = (string)$authUser['login'];

$userStmt = $conn->prepare('SELECT id FROM users WHERE login = ? LIMIT 1');
$userStmt->bind_param('s', $login);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if (!$user) {
    mysqli_close($conn);
    echo json_encode(['voted' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$user['id'];
$postStmt = $conn->prepare('SELECT id_post, NSFW FROM post WHERE id_post = ? LIMIT 1');
$postStmt->bind_param('i', $postId);
$postStmt->execute();
$post = $postStmt->get_result()->fetch_assoc();

if (!$post || !abyss_post_can_view($post, $authUser) || !abyss_post_can_rate($post)) {
    mysqli_close($conn);
    echo json_encode(['voted' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$voteStmt = $conn->prepare('SELECT vote_value FROM post_ratings WHERE id_post = ? AND id_user = ? LIMIT 1');
$voteStmt->bind_param('ii', $postId, $userId);
$voteStmt->execute();
$vote = $voteStmt->get_result()->fetch_assoc();

mysqli_close($conn);
session_write_close();

if ($vote) {
    echo json_encode([
        'voted' => true,
        'vote_value' => (int)$vote['vote_value'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['voted' => false], JSON_UNESCAPED_UNICODE);
