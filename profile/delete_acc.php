<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        echo("Ошибка соединения.");
        exit;
    }

    $user = $_SESSION['user'];
    $data = $conn->query("SELECT * FROM users WHERE login = '$user'");

    if ($data->num_rows > 0) {
        $row = $data->fetch_assoc();
        $password_bd = $row["password"];

        $password = mysqli_real_escape_string($conn, $_POST['password']);

        if (password_verify($password, $password_bd)) {
            $final = $conn->query("DELETE FROM users WHERE login = '$user'");
            if ($final) {
                unset($_SESSION['user']);
                unset($_SESSION['username']);
                $_SESSION['great'] = "Ваш аккаунт удалён. Надеемся на нашу скорую встречу вновь!";
            } else {
                echo "Error SQL: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = "Неверный пароль.";
        }
    } else {
        $_SESSION['error'] = "Пользователь не найден.";
    }
    header("Location: main");
    exit;
}