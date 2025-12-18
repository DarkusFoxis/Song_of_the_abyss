<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == "POST"){
require_once '../template/conn.php';
require_once '../template/invent_api.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if(!$conn){
    echo("Ошибка соединения.");
} else {
    $login = $_SESSION['user'];
    $user_query = "SELECT * FROM users WHERE login = '$login'";
    $result = $conn->query($user_query);

    if($result -> num_rows > 0){
        $user = $result -> fetch_assoc();
        $user_id = $user["id"];

        $promo = mysqli_real_escape_string($conn, $_POST['promocode']);
        $promo_query = "SELECT * FROM code WHERE code = '$promo'";
        $promo_result = $conn->query($promo_query);

        if ($promo_result -> num_rows > 0) {
            $promocode = $promo_result -> fetch_assoc();
            $usage_qurery = "SELECT * FROM promo WHERE code = '$promo' AND id_user = '$user_id'";
            $usage_result = $conn->query($usage_qurery);
            if ($usage_result -> num_rows > 0) {
                $_SESSION['error'] = "Промокод уже использован!";
                header("Location: setting");
                exit;
            }

            if ($promocode['quantity'] <= 0) {
                $_SESSION['error'] = "Количество использования промокода закончилось!";
                header("Location: setting");
                exit;
            }
            mysqli_begin_transaction($conn);
            $rewards = [];

            try {
                $update_promo = "UPDATE code SET quantity = quantity - 1 WHERE id = '{$promocode['id']}'";
                $conn->query($update_promo);

                $insert_used = "INSERT INTO promo (id_usage, id_user, code, date) VALUES (NULL, '$user_id', '$promo', NOW())";
                $conn->query($insert_used);

                if ($promocode['petal'] !== null) {
                    $update_invent = "UPDATE invent SET sakura = sakura + {$promocode['petal']} WHERE id_user = '$user_id'";
                    $conn->query($update_invent);
                    $rewards[] = "{$promocode['petal']} лепестков";
                }

                if ($promocode['xp'] !== null) {
                    $update_invent = "UPDATE invent SET xp = xp + {$promocode['xp']} WHERE id_user = '$user_id'";
                    $conn->query($update_invent);
                    $rewards[] = "{$promocode['xp']} опыта";
                }

                if ($promocode['gems'] !== null) {
                    $update_invent = "UPDATE invent SET gems = gems + {$promocode['gems']} WHERE id_user = '$user_id'";
                    $conn->query($update_invent);
                    $rewards[] = "{$promocode['gems']} кристаллов";
                }

                if ($promocode['coin'] !== null) {
                    $update_invent = "UPDATE invent SET coins = coins + {$promocode['coin']} WHERE id_user = '$user_id'";
                    $conn->query($update_invent);
                    $rewards[] = "{$promocode['coin']} монет";
                }

                if ($promocode['kase'] !== null) {
                    $update_invent = "UPDATE invent SET kase = kase + {$promocode['kase']} WHERE id_user = '$user_id'";
                    $conn->query($update_invent);
                    $rewards[] = "{$promocode['kase']} кейсов";
                }

                if ($promocode['donate'] !== null) {
                    $update_invent = "UPDATE users SET donate = donate + {$promocode['donate']} WHERE id = '$user_id'";
                    $conn->query($update_invent);
                    $rewards[] = "{$promocode['donate']} рублей доната";
                }

                if ($promocode['stiker'] !== null) {
                    $rewards[] = add_stikers($conn, $user_id, $promocode['stiker']);
                }

                if ($promocode['title'] !== null) {
                    $title = mysqli_real_escape_string($conn, $promocode['title']);

                    $user_title_check = "SELECT * FROM title WHERE id_user = '$user_id' AND title = '$title'";
                    $user_title_result = $conn->query($user_title_check);

                    if ($user_title_result->num_rows == 0) {
                        $add_title = "INSERT INTO `title`(`id_title`, `id_user`, `title`) VALUES (NULL,'$user_id','$title')";
                        $conn->query($add_title);
                        $rewards[] = "титул: $title";
                    }
                }

                mysqli_commit($conn);
                if (!empty($rewards)) {
                    $_SESSION['great'] = "Промокод активирован! Получено: " . implode(", ", $rewards);
                } else {
                    $_SESSION['error'] = "Промокод не содержит доступных наград";
                }

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $_SESSION['error'] = "Ошибка при активации промокода: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Такого промокода не существует.";
        }
    } else {
        $_SESSION['error'] = "Ошибка при чтении базы данных.";
    }

    mysqli_close($conn);
    header("Location: setting");
    exit;
}
}