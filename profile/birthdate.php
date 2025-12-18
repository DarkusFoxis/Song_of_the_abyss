<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        echo("Ошибка соединения.");
        exit;
    } else {
        $new_birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM personal_data WHERE login = '$login'";
        $result = $conn->query($user_query);
        $update_query = "UPDATE personal_data SET birthdate = '$new_birthdate' WHERE login = '$login'";
        if ($conn->query($update_query) === TRUE) {
            $_SESSION["great"] = "Дата рождения успешно изменена!";
        } else {
            $_SESSION["error"] = "Ошибка при обновлении никнейма: " . $conn->error;
        }
        mysqli_close($conn);
    } 
    header("Location: setting");
    exit;
}