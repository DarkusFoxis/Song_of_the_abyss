<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        echo("Ошибка соединения.");
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);

        if($result -> num_rows > 0){
            $user = $result -> fetch_assoc();
            $password_bd = $user["password"];

            $old_password = mysqli_real_escape_string($conn, $_POST['old_password']);
            $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);

            if (password_verify($old_password, $password_bd)) {
                if (password_verify($new_password, $password_bd)) {
                    $_SESSION['error'] = "Вы не можете изменить пароль на точно такой же. Придумайте новый.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $conn->query("UPDATE users SET password = '$hashed_password' WHERE login = '$login'");
                    $_SESSION['great'] = "Пароль успешно изменён!";
                }
            } else {
                $_SESSION['error'] = "Неверный старый пароль.";
            }
        } else {
            $_SESSION['error'] = "Ошибка при чтении базы данных.";
        }

        mysqli_close($conn);
        header("Location: setting");
        exit;
    }
}