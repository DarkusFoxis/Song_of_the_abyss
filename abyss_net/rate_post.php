<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Вы должны быть авторизованы']);
    exit;
}

require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database) or die('Ошибка соединения: ' . mysqli_connect_error());

header('Content-Type: application/json; charset=utf-8');

$login = $_SESSION['user'];
$user = mysqli_fetch_assoc($conn->query("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'"));

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
    exit;
}

if ($user['lvl'] == 0) {
    echo json_encode(['success' => false, 'error' => 'Вы заблокированы на проекте']);
    exit;
}

if ($user['lvl'] == 1) {
    echo json_encode(['success' => false, 'error' => 'Вы не подтверждены на сайте']);
    exit;
}

$post_id = intval($_POST['post_id'] ?? 0);
$vote = intval($_POST['vote'] ?? 0);

if ($post_id <= 0 || ($vote != 1 && $vote != -1)) {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
    exit;
}

$user_id = $user['id'];

$postData = mysqli_fetch_assoc($conn->query("SELECT * FROM post WHERE id_user = $user_id AND id_post = $post_id"));

if ($postData) {
    echo json_encode(['success' => false, 'error' => 'Вы не можете ставить оценку сами себе!']);
    exit;
}

$registration_date = new DateTime($user['data_create']);
$current_date = new DateTime();
$interval = $registration_date->diff($current_date);
$months = ($interval->y * 12) + $interval->m;
$vote_weight = 1.0 + ($months * 0.1);
$rating_value = $vote_weight * $vote;

$check_query = "SELECT id, vote_value, rating FROM post_ratings WHERE id_post = $post_id AND id_user = $user_id";
$existing_vote_result = $conn->query($check_query);
$existing_vote = $existing_vote_result ? $existing_vote_result->fetch_assoc() : null;

mysqli_begin_transaction($conn);

try {
    if ($existing_vote) {
        if ($existing_vote['vote_value'] == $vote) {
            $old_rating = $existing_vote['rating'];

            $delete_query = "DELETE FROM post_ratings WHERE id_post = $post_id AND id_user = $user_id";
            if (!mysqli_query($conn, $delete_query)) {
                throw new Exception('Ошибка удаления голоса');
            }

            $update_total = "UPDATE post SET total_rating = total_rating - $old_rating WHERE id_post = $post_id";
            if (!mysqli_query($conn, $update_total)) {
                throw new Exception('Ошибка обновления рейтинга поста');
            }

            $user_vote = 0;
        } else {
            $old_rating = $existing_vote['rating'];

            $update_query = "UPDATE post_ratings SET vote_value = $vote, rating = $rating_value, updated_at = NOW() WHERE id_post = $post_id AND id_user = $user_id";
            if (!mysqli_query($conn, $update_query)) {
                throw new Exception('Ошибка обновления голоса');
            }

            $rating_diff = $rating_value - $old_rating;
            $update_total = "UPDATE post SET total_rating = total_rating + $rating_diff WHERE id_post = $post_id";
            if (!mysqli_query($conn, $update_total)) {
                throw new Exception('Ошибка обновления рейтинга поста');
            }

            $user_vote = $vote;
        }
    } else {
        $insert_query = "INSERT INTO post_ratings (id_post, id_user, rating, vote_value, created_at) VALUES ($post_id, $user_id, $rating_value, $vote, NOW())";
        if (!mysqli_query($conn, $insert_query)) {
            throw new Exception('Ошибка добавления голоса');
        }

        $update_total = "UPDATE post SET total_rating = total_rating + $rating_value WHERE id_post = $post_id";
        if (!mysqli_query($conn, $update_total)) {
            throw new Exception('Ошибка обновления рейтинга поста');
        }

        $user_vote = $vote;
    }

    $get_rating = "SELECT total_rating FROM post WHERE id_post = $post_id";
    $result = $conn->query($get_rating);
    $post_data = $result->fetch_assoc();

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'rating' => round($post_data['total_rating'], 2),
        'vote_weight' => round($vote_weight, 2),
        'user_vote' => $user_vote
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Ошибка при обработке голоса: ' . $e->getMessage()]);
}

mysqli_close($conn);
session_write_close();

