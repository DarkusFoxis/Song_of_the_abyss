<?php
date_default_timezone_set('Europe/Moscow');
session_start();
//Конфигурация и вспомогательные функции.
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (file_exists(__DIR__ . '/../../template/conn.php')) {
    require_once __DIR__ . '/../../template/conn.php';
} else {
    $_SESSION['error'] = 'Критическая ошибка: Файл конфигурации БД не найден.';
    header('Location: core.php');
    exit;
}
function get_db_connection_handler() {
    global $host, $log, $password_sql, $database;
    if (!isset($host)) {
        return null;
    }
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn || $conn->connect_error) {
        return null;
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}
function redirect_with_error($message) {
    $_SESSION['error'] = $message;
    header('Location: core.php');
    exit;
}
function redirect_with_success($message) {
    $_SESSION['success'] = $message;
    header('Location: core.php');
    exit;
}
//Проверка запроса и авторизации.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_error('Некорректный метод запроса.');
}
if (!isset($_SESSION['user'])) {
    redirect_with_error('Доступ запрещен. Пожалуйста, авторизуйтесь.');
}
$conn = get_db_connection_handler();
if (!$conn) {
    redirect_with_error('Ошибка подключения к базе данных при обработке загрузки.');
}
//Получение актуальных данных пользователя и проверка прав.
$user_login_session = $_SESSION['user'];
$escaped_login = mysqli_real_escape_string($conn, $user_login_session);
$user_query_sql = "SELECT u.id, u.permissions, sg.lvl, COALESCE(max_audio.latest_upload_time, 0) AS last_upload_ts FROM users u JOIN site_group sg ON u.permissions = sg.name LEFT JOIN (SELECT user_id, MAX(data_upload) AS latest_upload_time FROM audio GROUP BY user_id) max_audio ON u.id = max_audio.user_id WHERE u.login = '{$escaped_login}'";
$user_result = mysqli_query($conn, $user_query_sql);
if (!$user_result || mysqli_num_rows($user_result) === 0) {
    mysqli_close($conn);
    session_unset(); session_destroy();
    redirect_with_error('Ошибка получения данных пользователя. Авторизуйтесь снова.');
}
$user_data = mysqli_fetch_assoc($user_result);
$current_user_id = (int)$user_data['id'];
$current_user_lvl = (int)$user_data['lvl'];
$current_last_upload_ts = (int)$user_data['last_upload_ts'];
if ($current_user_lvl === 0 || $current_user_lvl === 1) {
    mysqli_close($conn);
    redirect_with_error('Извините, у вас не достаточно прав для загрузки треков.');
}
if ($current_user_lvl != 6) {
    $current_time = time();
    $cooldown_period = 5 * 60;
    if (($current_time - $current_last_upload_ts) < $cooldown_period) {
        mysqli_close($conn);
        redirect_with_error('Кулдаун на загрузку еще не прошел. Пожалуйста, подождите.');
    }
}
//Обработка данных формы.
$audio_name = trim($_POST['audio_name'] ?? '');
$author_name = trim($_POST['author_name'] ?? '');
$self_author_flag = isset($_POST['self_author']) && $_POST['self_author'] == '1';
if (empty($audio_name) || empty($author_name)) {
    mysqli_close($conn);
    redirect_with_error('Название трека и имя исполнителя не могут быть пустыми.');
}
$db_self_author = 0;
if ($self_author_flag) {
    if ($current_user_lvl < 3) {
        mysqli_close($conn);
        redirect_with_error('Внимание: Вы не являетесь верифицированным исполнителем! Треки под вашим авторством выходить не могут! Для уточнения, обратитесь к администратору: https://t.me/DarkusFoxis.');
    }
    $db_self_author = 1;
}
//Обработка аудиофайла.
if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
    mysqli_close($conn);
    redirect_with_error('Ошибка загрузки аудиофайла: ' . ($_FILES['audio_file']['error'] ?? 'неизвестная ошибка'));
}
$audio_file = $_FILES['audio_file'];
$audio_allowed_types = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/x-wav'];
$audio_max_size = 20 * 1024 * 1024;
if (!in_array($audio_file['type'], $audio_allowed_types)) {
    mysqli_close($conn);
    redirect_with_error('Недопустимый тип аудиофайла. Разрешены: MP3, WAV, OGG.');
}
if ($audio_file['size'] > $audio_max_size) {
    mysqli_close($conn);
    redirect_with_error('Размер аудиофайла превышает допустимый лимит (20 MB).');
}
$audio_upload_dir = __DIR__ . '/../media/audio/';
$audio_file_extension = pathinfo($audio_file['name'], PATHINFO_EXTENSION);
$audio_unique_name = uniqid('audio_', true) . '.' . strtolower($audio_file_extension);
$audio_destination_path = $audio_upload_dir . $audio_unique_name;

if (!is_dir($audio_upload_dir) || !is_writable($audio_upload_dir)) {
    mysqli_close($conn);
    error_log("Upload handler: Директория для аудио отсутствует или не доступна для записи: " . $audio_upload_dir);
    redirect_with_error('Серверная ошибка при подготовке к загрузке аудио. Обратитесь к администратору.');
}
if (!move_uploaded_file($audio_file['tmp_name'], $audio_destination_path)) {
    mysqli_close($conn);
    redirect_with_error('Не удалось сохранить загруженный аудиофайл.');
}
$db_audio_path = $audio_unique_name;
//Обработка файла обложки.
$db_cover_path = null;
if ($current_user_lvl >= 3 && isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
    $cover_file = $_FILES['cover_file'];
    $cover_allowed_types = ['image/jpeg', 'image/png'];
    $cover_max_size = 5 * 1024 * 1024;
    if (!in_array($cover_file['type'], $cover_allowed_types)) {
        $_SESSION['warning'] = 'Тип файла обложки не поддерживается (только JPG, PNG). Обложка не загружена.'; 
    } elseif ($cover_file['size'] > $cover_max_size) {
        $_SESSION['warning'] = 'Размер файла обложки превышает 5MB. Обложка не загружена.';
    } else {
        $cover_upload_dir = __DIR__ . '/../icon/';
        $cover_file_extension = pathinfo($cover_file['name'], PATHINFO_EXTENSION);
        $cover_unique_name = uniqid('cover_', true) . '.' . strtolower($cover_file_extension);
        $cover_destination_path = $cover_upload_dir . $cover_unique_name;
        if (!is_dir($cover_upload_dir) || !is_writable($cover_upload_dir)) {
            error_log("Upload handler: Директория для обложек отсутствует или не доступна для записи: " . $cover_upload_dir);
            $_SESSION['warning'] = 'Серверная ошибка при загрузке обложки. Обложка не загружена.';
        } elseif (move_uploaded_file($cover_file['tmp_name'], $cover_destination_path)) {
            $db_cover_path = $cover_unique_name;
        } else {
            $_SESSION['warning'] = 'Не удалось сохранить файл обложки.';
        }
    }
} elseif (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK && $current_user_lvl < 3) {
    $_SESSION['warning'] = 'У вас нет прав на загрузку обложки. Обложка не загружена.';
}

// Установка обложки по умолчанию, если она не была загружена
if (empty($db_cover_path)) {
    $db_cover_path = 'base_cover.png';
}

//Запись в БД.
$db_nsfw = 0;
$db_data_upload = date('Y-m-d H:i:s', time());
$stmt = mysqli_prepare($conn, "INSERT INTO audio (user_id, self_author, author_name, nsfw, name, path, cover_patch, data_upload) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log("MySQLi prepare error: " . mysqli_error($conn));
    mysqli_close($conn);
    redirect_with_error('Ошибка подготовки запроса к базе данных.');
}
mysqli_stmt_bind_param($stmt, "iisissss", 
    $current_user_id, 
    $db_self_author, 
    $author_name, 
    $db_nsfw, 
    $audio_name, 
    $db_audio_path, 
    $db_cover_path,
    $db_data_upload
);
if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    $success_msg = 'Трек \'' . htmlspecialchars($audio_name) . '\' успешно загружен!';
    if(isset($_SESSION['warning'])) {
        $success_msg .= "<br><em>Примечание по обложке: " . htmlspecialchars($_SESSION['warning']) . "</em>";
        unset($_SESSION['warning']);
    }
    redirect_with_success($success_msg);
} else {
    error_log("MySQLi execute error: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    redirect_with_error('Ошибка при сохранении данных трека в базу.');
}
session_write_close();