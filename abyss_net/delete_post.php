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
$id_post = intval($_POST['id_post'] ?? 0);
if ($id_post <= 0) {
    echo "Ошибка: Некорректный ID поста.";
    exit;
}
$sql = "SELECT * FROM post WHERE id_post = $id_post";
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
    echo "Ошибка: Нет прав на удаление этого поста.";
    exit;
}
if (!empty($post['media'])) {
    $media_files = explode(',', $post['media']);
    foreach ($media_files as $file) {
        $file_path = __DIR__ . '/media/' . $file;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
}
$conn->query("DELETE FROM post WHERE id_post = $id_post");
$conn->query("DELETE FROM comment WHERE id_post = $id_post");
$conn->query("DELETE FROM url WHERE url LIKE '%id=$id_post'");

echo "Пост успешно удалён.";
session_write_close();
