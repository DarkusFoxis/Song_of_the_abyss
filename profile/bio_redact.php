<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        echo("Error connection.");
        exit;
    } else {
        $bio = mysqli_real_escape_string($conn, strip_tags($_POST['bio']));
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);
        $user = $result->fetch_assoc();

        // Проверка на HTML теги и количество пробелов.
        if (preg_match('/<(script|style|iframe|frame|frameset|meta|link|object|a|b|s|body|head|div|input|rextarea|form)/i', $bio) || (strlen(preg_replace('/\s/', '', $bio)) / strlen($bio) < 0.5)) {
            $_SESSION["error"] = "Ваш статус содержит HTML теги или большое количество пробелов. Пожалуйста, удалите их и попробуйте снова.";
            header("Location: setting");
            exit;
        }
        $post_spaces = substr_count($bio, ' ');
        $space_percent = round(($post_spaces / mb_strlen($bio)) * 100, 2);
        if ($space_percent > 20) {
            $_SESSION["error"] = "Ошибка: Слишком много пробелов в сообщении.";
            header("Location: setting");
            exit;
        }

        $enter_count = substr_count($bio, '\r\n');
        $enter_percent = round(($enter_count / mb_strlen($bio)) * 100, 2);
        if ($enter_percent > 15) {
            $_SESSION["error"] = "Ошибка: Слишком много переходов на новую строку в сообщении.";
            header("Location: setting");
            exit;
        }
        $bio = htmlspecialchars($bio);
        $update_query = "UPDATE users SET BIO = '$bio' WHERE login = '$login'";
        if ($conn->query($update_query) === TRUE) {
            $_SESSION["great"] = "Статус успешно изменен!";
            header("Location: setting");
            exit;
        } else {
            $_SESSION["error"] = "Ошибка при обновлении статуса: " . $conn->error;
            header("Location: setting");
            exit;
        }
    }
}