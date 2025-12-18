<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../template/conn.php';
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        echo("Ошибка соединения.");
    } else {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);
        $user = $result -> fetch_assoc();
        $userId = $user['id'];

        $username_query = "SELECT * FROM users WHERE username = '$username'";
        $result2 = $conn->query($username_query);

        if ($result2->fetch_assoc() == 0){
            $update_query = "UPDATE users SET username = '$username' WHERE login = '$login'";
            if ($conn->query($update_query) === TRUE) {
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Воин Родос'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) !== 0) {
                    $title_update_query = "UPDATE achievement SET description = '$username, не забывайте ваше призвание. Наше дело ещё не подошло к концу.' WHERE id_user = '$userId' AND title = 'Воин Родос'";
                    $conn->query($title_update_query);
                }
                $_SESSION["great"] = "Никнейм успешно изменен!";
                header("Location: setting");
            } else {
                $_SESSION["error"] = "Ошибка при обновлении никнейма: " . $conn->error;
                header("Location: setting");
            }
        } else {
            $_SESSION["error"] = "Такой никнейм используется другим пользователем. Пожалуйста, попробуй другой никнейм.";
            header("Location: setting");
        }
    }
}
exit;