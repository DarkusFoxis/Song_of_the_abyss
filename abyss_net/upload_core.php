<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Ошибка: Необходимо авторизоваться.";
    exit;
}

require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Ошибка соединения: " . mysqli_connect_error();
    exit;
}
// Получение данных пользователя.
$login = $_SESSION['user'];
$user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
$result = $conn->query($user_query);
$user = $result->fetch_assoc();
$userId = $user['id'];
// Проверка уровня и инвентаря.
$query = "SELECT * FROM invent WHERE id_user = '$userId'";
$result = mysqli_query($conn, $query);
$inv_data = $result->fetch_assoc();
// Новые лимиты.
$max_files = ($user['lvl'] >= 3) ? 6 : 3;
$base_mb = ($user['lvl'] >= 3) ? 30 : 15;
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'audio/mpeg', 'video/mp4'];
$uploaded_files = [];
// Проверка времени между постами.
if ($user['lvl'] < 4) {
    $last_post_query = "SELECT data FROM post WHERE id_user = '$userId' ORDER BY data DESC LIMIT 1";
    $last_post_result = mysqli_query($conn, $last_post_query);
    if ($last_post_result && mysqli_num_rows($last_post_result) > 0) {
        $last_post_data = mysqli_fetch_assoc($last_post_result);
        $last_post_time = strtotime($last_post_data['data']);
        $current_time = time();
        if (($current_time - $last_post_time) < 900) {
            echo "Ошибка: Вы не можете публиковать посты чаще чем раз в 15 минут.";
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = intval($_POST['edit_id'] ?? 0);
    if ($edit_id) {
        // Редактирование поста.
        $sql = "SELECT * FROM post WHERE id_post = $edit_id";
        $result = $conn->query($sql);
        if ($result->num_rows === 0) {
            echo "Ошибка: Пост не найден.";
            exit;
        }
        $post_row = $result->fetch_assoc();
        if ($post_row['id_user'] != $userId) {
            echo "Ошибка: Нет прав на редактирование.";
            exit;
        }
    }
    // Проверка количества файлов.
    if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
        $file_count = count($_FILES['media']['name']);
        if ($file_count > $max_files) {
            echo "Ошибка: Максимум $max_files файлов.";
            exit;
        }
    }
    $post = trim($_POST['post'] ?? '');
    $title = trim($_POST['title'] ?? '');
    if (empty($post) && empty($title) && (empty($_FILES['media']['name'][0]))) {
        echo "Ошибка: Пост не может быть пустым.";
        exit;
    }
    $target_dir = __DIR__ . "/media/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    if (!empty($_FILES['media']['name'][0])) {
        for ($i = 0; $i < count($_FILES['media']['name']); $i++) {
            $file_name = $_FILES['media']['name'][$i];
            $file_type = $_FILES['media']['type'][$i];
            $file_size = $_FILES['media']['size'][$i];
            $file_tmp = $_FILES['media']['tmp_name'][$i];
            $file_error = $_FILES['media']['error'][$i];
            if ($file_error !== UPLOAD_ERR_OK) {
                echo "Ошибка загрузки файла $file_name.";
                exit;
            }
            if (!in_array($file_type, $allowed_types)) {
                echo "Ошибка: Неподдерживаемый тип файла ($file_name).";
                exit;
            }
            $max_size = ($file_type === 'video/mp4') ? $base_mb * 2 * 1024 * 1024 : $base_mb * 1024 * 1024;
            if ($file_size > $max_size) {
                echo "Ошибка: Файл $file_name слишком большой (максимум: " . ($max_size / 1024 / 1024) . " МБ).";
                exit;
            }
            $unique_name = uniqid() . "_" . basename($file_name);
            $target_file = $target_dir . $unique_name;
            if (move_uploaded_file($file_tmp, $target_file)) {
                $uploaded_files[] = $unique_name;
            } else {
                echo "Ошибка загрузки файла $file_name.";
                exit;
            }
        }
    }
    $title = htmlspecialchars(mysqli_real_escape_string($conn, $title));
    $post = nl2br(htmlspecialchars(mysqli_real_escape_string($conn, $post)));
    if (mb_strlen($title) > 150) {
        echo "Ошибка: Заголовок слишком длинный (максимум 150 символов).";
        exit;
    }
    if (mb_strlen($post) > ($user['lvl'] >= 3 ? 3000 : 1500)) {
        echo "Ошибка: Текст поста слишком длинный (максимум " . ($user['lvl'] >= 3 ? 3000 : 1500) . " символов).";
        exit;
    }
    $title_spaces = substr_count($title, ' ');
    $title_space_percent = round(($title_spaces / mb_strlen($title)) * 100, 2);
    if ($title_space_percent > 40) {
        echo "Ошибка: Слишком много пробелов в заголовке.";
        exit;
    }
    $post_spaces = substr_count($post, ' ');
    $post_space_percent = round(($post_spaces / (mb_strlen($post)+1)) * 100, 2);
    if ($post_space_percent > 40) {
        echo "Ошибка: Слишком много пробелов в сообщении.";
        exit;
    }
    $enter_count = substr_count($post, '\r\n');
    $enter_percent = round(($enter_count / (mb_strlen($post)+1)) * 100, 2);
    if ($enter_percent > 20) {
        echo "Ошибка: Слишком много переходов на новую строку.";
        exit;
    }
    if ($edit_id) {
        // Редактирование.
        $media_names = $post_row['media'];
        if (!empty($uploaded_files)) {
            $media_names = $media_names ? $media_names . ',' . implode(',', $uploaded_files) : implode(',', $uploaded_files);
        }
        $sql = "UPDATE post SET title='$title', post='$post', media=" . ($media_names ? "'$media_names'" : "NULL") . " WHERE id_post=$edit_id";
        if (mysqli_query($conn, $sql)) {
            echo "Пост успешно обновлён!";
        } else {
            echo "Ошибка: " . mysqli_error($conn);
        }
    } else {
        // Новый пост.
        if ($user['lvl'] < 4) {
            $last_post_query = "SELECT data FROM post WHERE id_user = '$userId' ORDER BY data DESC LIMIT 1";
            $last_post_result = mysqli_query($conn, $last_post_query);
            if ($last_post_result && mysqli_num_rows($last_post_result) > 0) {
                $last_post_data = mysqli_fetch_assoc($last_post_result);
                $last_post_time = strtotime($last_post_data['data']);
                $current_time = time();
                if (($current_time - $last_post_time) < 900) {
                    echo "Ошибка: Вы не можете публиковать посты чаще чем раз в 15 минут.";
                    exit;
                }
            }
        }
        $media_names = !empty($uploaded_files) ? implode(',', $uploaded_files) : NULL;
        $sql = "INSERT INTO post (id_post, id_user, title, post, media, data) VALUES (NULL, '$userId', '$title', '$post', '$media_names', NOW())";
        if (mysqli_query($conn, $sql)) {
            $post_id = mysqli_insert_id($conn);
            $url = 'https://so-ta.ru/abyss_net/post?id='. $post_id;
            $shortPost = substr($post, 0, 200) . '...';
            $query_sql = "INSERT INTO url (url, title, description, keywords, date_add, id_user) VALUES ('$url', '$title', '$shortPost', 'Пост, Abyss Net, Блог', NOW(), $userId)";
            if (mysqli_query($conn, $query_sql)) {
                echo "Успешно опубликованно!";
            } else {
                echo "Ошибка: " . mysqli_error($conn);
            }
        } else {
            echo "Ошибка: " . mysqli_error($conn);
        }
    }
} else {
    echo "Ошибка: Неверный метод запроса.";
}
session_write_close();