<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();

require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database) or die('Ошибка соединения: ' . mysqli_connect_error());

header('Content-Type: application/json; charset=utf-8');

$post_id = intval($_GET['post_id'] ?? 0);

if (!isset($_SESSION['user']) || $post_id <= 0) {
    echo json_encode(['voted' => false]);
    mysqli_close($conn);
    exit;
}

$login = $_SESSION['user'];

$user_query = "SELECT id FROM users WHERE login = '$login'";
$user_result = $conn->query($user_query);
$user = $user_result ? $user_result->fetch_assoc() : null;

if (!$user) {
    echo json_encode(['voted' => false]);
    mysqli_close($conn);
    exit;
}

$vote_query = "SELECT vote_value FROM post_ratings WHERE id_post = $post_id AND id_user = {$user['id']}";
$vote_result = $conn->query($vote_query);
$vote = $vote_result ? $vote_result->fetch_assoc() : null;

if ($vote) {
    echo json_encode([
        'voted' => true,
        'vote_value' => intval($vote['vote_value'])
    ]);
} else {
    echo json_encode(['voted' => false]);
}

mysqli_close($conn);
session_write_close();

