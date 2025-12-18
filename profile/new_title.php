<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn){
        echo("Ошибка соединения.");
    } else {
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);
        
        if ($result -> num_rows > 0) {
            $title = $_POST['title'];
            $user = $result -> fetch_assoc();
            $id = $user['id'];
            $invent_sql = "SELECT * FROM invent WHERE id_user = " . $user["id"];
            $invent_result = mysqli_query($conn, $invent_sql);
            $invent = mysqli_fetch_assoc($invent_result);
            
            if ($title == "NULL") {
                $update_title = "UPDATE `invent` SET `id_title` = NULL WHERE `invent`.`id_user` = '$id'";
            } else {
                $update_title = "UPDATE invent SET id_title = '$title' WHERE id_user = '$id'";
            }
            if ($conn->query($update_title) === TRUE) {
                $_SESSION["great"] = "Титул успешно изменён!";
            } else {
                $_SESSION["error"] = "Ошибка при обновлении титула: " . $conn->error;
            }
        } else {
            $_SESSION["error"] = "У вас остсутствует инвентарь, пожалуйста, подключите его на главной странице.";
        }
        mysqli_close($conn);
        header("Location: setting");
        exit;
    }
}