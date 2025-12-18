<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../../template/conn.php';
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        echo("Ошибка соединения.");
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $login = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['login']));
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);
        $black_list = "SELECT * FROM black_ip WHERE ip = '$ip_address'";
        $black_data = $conn->query($black_list);
        if($result -> num_rows > 0){
            $user = $result -> fetch_assoc();
            $password_bd = $user["password"];
            $password = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['password']));
            if (password_verify($password, $password_bd)) {
                $ip_data = $user["ip"];
                $account_ip = "SELECT * FROM black_ip WHERE ip = '$ip_data'";
                $result2 = $conn->query($account_ip);
                if($result2->num_rows > 0 || $black_data->num_rows > 0) {
                    $_SESSION['error'] = "Доступ к аккаунту ограничен.";
                    exit;
                }
                $_SESSION['success_log'] = "Вход успешен! Загрузка формы через 3 секунды...";
                $conn->query("UPDATE users SET ip = '$ip_address', last_login = NOW() WHERE login = '$login'");
                $_SESSION['user'] = $user['login'];
                $_SESSION['username'] = $user['username'];
            } else {
                $_SESSION['error'] = "Неверный логин или пароль.";
            }
        } else {
            $_SESSION['error'] = "Неверный логин или пароль.";
        }
        mysqli_close($conn);
        header("Location: core");
    }
}
session_write_close();