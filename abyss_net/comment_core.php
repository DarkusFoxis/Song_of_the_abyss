<?php
require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/../template/conn.php';
require_once __DIR__ . '/post_helpers.php';

auth_start_session();
auth_sync_session_from_token();

$currentUser = auth_get_current_user();
if ($currentUser === null) {
    echo 'Вы не авторизованы.';
    exit;
}

security_require_csrf(true);

$currentTime = time();
if (isset($_SESSION['last_comment_time']) && ($currentTime - (int)$_SESSION['last_comment_time']) < 8) {
    echo 'Ошибка: Пожалуйста, подождите перед следующим комментарием.';
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$commentText = trim((string)($_POST['comment'] ?? ''));

if ($postId <= 0 || $commentText === '') {
    echo 'Ошибка: Отсутствуют необходимые данные.';
    exit;
}

if (mb_strlen($commentText) > 2048) {
    echo 'Ошибка: Комментарий слишком длинный.';
    exit;
}

$conn = new mysqli($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$userStmt = $conn->prepare(
    "SELECT u.id, u.username, u.avatar, sg.lvl
     FROM users u
     JOIN site_group sg ON u.permissions = sg.name
     WHERE u.login = ?
     LIMIT 1"
);
$userStmt->bind_param('s', $currentUser['login']);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$userData) {
    echo 'Пользователь не найден.';
    $conn->close();
    exit;
}

$userId = (int)$userData['id'];
$userLevel = (int)$userData['lvl'];

if ($userLevel <= 1) {
    echo $userLevel === 0
        ? 'Вы заблокированы на проекте.'
        : 'Пожалуйста, верифицируйте ваш аккаунт.';
    $conn->close();
    exit;
}

$postStmt = $conn->prepare("SELECT id_post, NSFW FROM post WHERE id_post = ? LIMIT 1");
$postStmt->bind_param('i', $postId);
$postStmt->execute();
$post = $postStmt->get_result()->fetch_assoc();
$postStmt->close();

if (!$post || !abyss_post_can_view($post, $currentUser)) {
    echo nsfw_access_denied_notice();
    $conn->close();
    exit;
}

$safeComment = nl2br(security_html($commentText), false);

$insertStmt = $conn->prepare("INSERT INTO comment (id_post, id_user, text, data) VALUES (?, ?, ?, NOW())");
$insertStmt->bind_param('iis', $postId, $userId, $safeComment);

if (!$insertStmt->execute()) {
    echo 'Ошибка добавления комментария: ' . $insertStmt->error;
    $insertStmt->close();
    $conn->close();
    exit;
}

$insertStmt->close();
$_SESSION['last_comment_time'] = $currentTime;

$achStmt = $conn->prepare("SELECT 1 FROM achievement WHERE id_user = ? AND title = 'Комментатор' LIMIT 1");
$achStmt->bind_param('i', $userId);
$achStmt->execute();
$hasAchievement = $achStmt->get_result()->fetch_assoc();
$achStmt->close();

if (!$hasAchievement) {
    $newAchStmt = $conn->prepare(
        "INSERT INTO achievement (id_user, title, description)
         VALUES (?, 'Комментатор', 'Прокомментировать любую запись в первый раз.')"
    );
    $newAchStmt->bind_param('i', $userId);
    $newAchStmt->execute();
    $newAchStmt->close();

    $newTitleStmt = $conn->prepare("INSERT INTO title (id_user, title) VALUES (?, 'Комментатор')");
    $newTitleStmt->bind_param('i', $userId);
    $newTitleStmt->execute();
    $newTitleStmt->close();
}

$commentsStmt = $conn->prepare(
    "SELECT c.*, u.username, u.avatar
     FROM comment c
     JOIN users u ON c.id_user = u.id
     WHERE c.id_post = ?
     ORDER BY c.data ASC"
);
$commentsStmt->bind_param('i', $postId);
$commentsStmt->execute();
$resultComments = $commentsStmt->get_result();

$commentData = '';
while ($row = $resultComments->fetch_assoc()) {
    $commentData .= '<div class="comment">';
    $commentData .= '<a href="../profile/profile?id=' . (int)$row['id_user'] . '" class="link"><img src="../profile/avatars/' . security_html(basename((string)$row['avatar'])) . '" class="avatar" alt="Аватар">';
    $commentData .= '<span class="username">' . security_html((string)$row['username']) . '</span></a>';
    $commentData .= '<p>' . $row['text'] . '</p>';
    $commentData .= '<p style="text-align: right; font-size: 10px; margin-bottom: 0px;">Написан: ' . security_html((string)$row['data']) . '</p>';
    $commentData .= '</div>';
}
$commentsStmt->close();

if ($commentData === '') {
    $commentData = '<p>Комментариев пока нет.</p>';
}

echo $commentData;
$conn->close();
session_write_close();
