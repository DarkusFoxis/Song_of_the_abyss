<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        echo("Ошибка соединения.");
    } else {
        $nsfw = mysqli_real_escape_string($conn, $_POST['nsfw']);
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);
        $user = $result -> fetch_assoc();
        
        $update_query = "UPDATE users SET NSFW = '$nsfw' WHERE login = '$login'";
        if ($conn->query($update_query) === TRUE) {
            $_SESSION["great"] = "Доступ к NSFW успешно изменен!";
            header("Location: setting");
        } else {
            $_SESSION["error"] = "Ошибка при обновлении доступа к NSFW: " . $conn->error;
            header("Location: setting");
        }
    }
}
exit;