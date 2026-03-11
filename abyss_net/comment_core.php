<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();

if (!isset($_SESSION['user'])) {
    echo 'Вы не авторизованы.';
    exit;
}

$currentTime = time();
if (isset($_SESSION['last_comment_time']) && ($currentTime - $_SESSION['last_comment_time']) < 8) {
    $remainingTime = 8 - ($currentTime - $_SESSION['last_comment_time']);
    echo "Ошибка: Пожалуйста, подождите " . $remainingTime . " секунд(ы) перед следующим комментарием.";
    exit;
}

if (!isset($_POST['post_id']) || !isset($_POST['comment'])) {
    echo 'Ошибка: Отсутствуют необходимые данные.';
    exit;
}

$postId = intval($_POST['post_id']);
if ($postId <= 0) {
    echo 'Ошибка: Некорректный ID поста.';
    exit;
}

require_once '../template/conn.php';

$conn = new mysqli($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$login = $_SESSION['user'];

$sqlUser = "SELECT u.id, u.username, u.avatar, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?";
$stmtUser = $conn->prepare($sqlUser);
if (!$stmtUser) {
    die("Ошибка подготовки запроса (пользователь): " . $conn->error);
}
$stmtUser->bind_param("s", $login);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultUser->num_rows > 0) {
    $userData = $resultUser->fetch_assoc();
    $userID = $userData['id'];
    $userLevel = $userData['lvl'];
} else {
    echo 'Пользователь не найден.';
    $stmtUser->close();
    $conn->close();
    exit;
}
$stmtUser->close();

if ($userLevel == 0) {
    echo "Вы заблокированы на проекте, поэтому ваши возможности ограничены.";
    $conn->close();
    exit;
} else if ($userLevel == 1) {
    echo "Пожалуйста, верифицируйте ваш аккаунт.";
    $conn->close();
    exit;
}

$etherAdd = true;
$etherTime = mt_rand(600, 1800);
$etherChans = mt_rand(0, 20);

$last_comment_query = "SELECT data FROM comment WHERE id_user = ? ORDER BY data DESC LIMIT 1";
$stmt_last_comment = $conn->prepare($last_comment_query);
if ($stmt_last_comment) {
    $stmt_last_comment->bind_param("i", $userID);
    $stmt_last_comment->execute();
    $result_last_comment = $stmt_last_comment->get_result();

    if ($result_last_comment->num_rows > 0) {
        $last_comment_data = $result_last_comment->fetch_assoc();
        $last_comment_time = strtotime($last_comment_data['data']);
        $current_time = time();

        if (($current_time - $last_comment_time) < $etherTime) {
            $etherAdd = false;
        }
    }
    $stmt_last_comment->close();
}

$commentText = trim($_POST['comment']);
$commentTextLength = mb_strlen($commentText);

if ($commentTextLength > 2048) {
    echo "Ошибка: Комментарий слишком длинный. Максимальное количество символов: 2048.";
    $conn->close();
    exit;
}

if ($commentTextLength == 0) {
    echo "Ошибка: Комментарий не может быть пустым.";
    $conn->close();
    exit;
}

$commentText = htmlspecialchars($commentText, ENT_QUOTES, 'UTF-8');
$commentText = nl2br($commentText);


$text_spaces = substr_count($commentText, ' ');
$text_space_percent = ($commentTextLength > 0) ? round(($text_spaces / $commentTextLength) * 100, 2) : 0;
if ($text_space_percent > 20) {
    echo "Ошибка: Слишком много пробелов в комментарии. Пробелов не должно быть больше 20% от общего количества символов.";
    $conn->close();
    exit;
}

$originalNewLines = substr_count($_POST['comment'], "\n");
$enter_percent = ($commentTextLength > 0) ? round(($originalNewLines / $commentTextLength) * 100, 2) : 0;
if ($enter_percent > 10) {
    echo "Ошибка: Слишком много переходов на новую строку в сообщении.";
    $conn->close();
    exit;
}

$sqlInsert = "INSERT INTO comment (id_comment, id_post, id_user, text, data) VALUES (NULL, ?, ?, ?, NOW())";
$stmtInsert = $conn->prepare($sqlInsert);
if (!$stmtInsert) {
    echo 'Ошибка подготовки запроса (вставка): ' . $conn->error;
    $conn->close();
    exit;
}
$stmtInsert->bind_param("iis", $postId, $userID, $commentText);

if ($stmtInsert->execute()) {
    $_SESSION['last_comment_time'] = $currentTime;

    $sqlCheckAchievement = "SELECT 1 FROM achievement WHERE id_user = ? AND title = 'Комментатор' LIMIT 1";
    $stmtCheckAch = $conn->prepare($sqlCheckAchievement);
    if ($stmtCheckAch) {
        $stmtCheckAch->bind_param("i", $userID);
        $stmtCheckAch->execute();
        $resultCheckAch = $stmtCheckAch->get_result();
        if ($resultCheckAch->num_rows === 0) {
            $sqlInsertAchievement = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, ?, 'Комментатор', 'Прокомментировать любую запись в первый раз.')";
            $stmtInsertAch = $conn->prepare($sqlInsertAchievement);
            if ($stmtInsertAch) {
                $stmtInsertAch->bind_param("i", $userID);
                $stmtInsertAch->execute();
                $stmtInsertAch->close();
            }

            $sqlInsertTitle = "INSERT INTO `title`(`id_title`, `id_user`, `title`) VALUES (NULL, ?, 'Комментатор')";
            $stmtInsertTitle = $conn->prepare($sqlInsertTitle);
             if ($stmtInsertTitle) {
                $stmtInsertTitle->bind_param("i", $userID);
                $stmtInsertTitle->execute();
                $stmtInsertTitle->close();
            }
        }
        $stmtCheckAch->close();
    }

    $sqlComments = "SELECT c.*, u.username, u.avatar FROM comment c JOIN users u ON c.id_user = u.id WHERE c.id_post = ? ORDER BY c.data ASC";

    $stmtComments = $conn->prepare($sqlComments);
    if (!$stmtComments) {
        echo 'Ошибка подготовки запроса (комментарии): ' . $conn->error;
        $stmtInsert->close();
        $conn->close();
        exit;
    }
    $stmtComments->bind_param("i", $postId);
    $stmtComments->execute();
    $resultComments = $stmtComments->get_result();

    $comment_data = '';
    if ($resultComments->num_rows > 0) {
        while ($row = $resultComments->fetch_assoc()) {
            $comment_data .= '<div class="comment">';
            $comment_data .= '<a href="../profile/profile?id=' . intval($row["id_user"]) . '" class="link"><img src="../profile/avatars/'. htmlspecialchars($row["avatar"], ENT_QUOTES, 'UTF-8') .'" class="avatar" alt="Аватар">';
            $comment_data .= '<span class="username">' . htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') . '</span></a>';
            $comment_data .= '<p>' . $row['text'] .'</p>';
            $comment_data .= '<p style="text-align: right; font-size: 10px; margin-bottom: 0px;">Написан: ' . htmlspecialchars($row['data'], ENT_QUOTES, 'UTF-8') . '</p>';
            $comment_data .= '</div>';
        }
    } else {
        $comment_data = '<p>Комментариев пока нет.</p>';
    }
    $stmtComments->close();
    echo $comment_data;

    if ($etherAdd && $etherChans > 19) {
        $tonen_add_sql = "UPDATE users SET abyss_ether= abyss_ether + 0.0000001 WHERE id = ?";
        $stmtTonenAdd = $conn->prepare($tonen_add_sql);
        $stmtTonenAdd->bind_param("i", $userID);
        $stmtTonenAdd->execute();
        $stmtTonenAdd->close();

        $tonen_sql = "UPDATE abyss_ether SET count= count - 0.0000001 WHERE id = 1";
        $stmtTonen = $conn->prepare($tonen_sql);
        $stmtTonen->execute();
        $stmtTonen->close();
    }

} else {
    echo 'Ошибка добавления комментария: ' . $stmtInsert->error;
}
$stmtInsert->close();
$conn->close();
session_write_close();


