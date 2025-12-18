<?php
session_start();
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Ошибка соединения: " . mysqli_connect_error();
    exit;
}
if (!isset($_SESSION['user'])) {
    echo "Ошибка: Необходимо авторизоваться.";
    exit;
}
$file = $_POST['file'] ?? '';
$post_id = intval($_POST['post_id'] ?? 0);
if (!$file || !$post_id) {
    echo "Ошибка: Нет данных.";
    exit;
}
$sql = "SELECT * FROM post WHERE id_post = $post_id";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    echo "Ошибка: Пост не найден.";
    exit;
}
$post = $result->fetch_assoc();
$user_query = "SELECT * FROM users WHERE login = '" . mysqli_real_escape_string($conn, $_SESSION['user']) . "'";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();
if ($post['id_user'] != $user['id']) {
    echo "Ошибка: Нет прав на удаление вложения.";
    exit;
}
$file_path = __DIR__ . '/media/' . $file;
if (file_exists($file_path)) {
    unlink($file_path);
}
$media_files = explode(',', $post['media']);
$media_files = array_filter($media_files, function($f) use ($file) { return $f !== $file; });
$new_media = count($media_files) ? implode(',', $media_files) : NULL;
$conn->query("UPDATE post SET media = " . ($new_media ? "'" . mysqli_real_escape_string($conn, $new_media) . "'" : "NULL") . " WHERE id_post = $post_id");
echo "Вложение удалено.";
session_write_close();