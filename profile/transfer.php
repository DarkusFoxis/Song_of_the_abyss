<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        $_SESSION['error'] = "Ошибка соединения с базой данных.";
        header("Location: setting");
        exit;
    }

    $sender_login = $_SESSION['user'];
    $recipient_id = (int)$_POST['recipient_id'];
    $resource_type = $_POST['resource_type'];
    $amount = (int)$_POST['amount'];

    $last_transfer = "SELECT time FROM pay_log WHERE first_user_id = (SELECT id FROM users WHERE login = '$sender_login') ORDER BY time DESC LIMIT 1";
    $result = $conn->query($last_transfer);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_time = strtotime($row['time']);
        if (time() - $last_time < 180) {
            $_SESSION['error'] = "Вы можете делать перевод только раз в 3 минуты.";
            header("Location: setting");
            exit;
        }
    }

    $tools_query = "SELECT * FROM tools WHERE user_id = (SELECT id FROM users WHERE login = '$sender_login')";
    $tools_result = $conn->query($tools_query);
    
    if ($tools_result->num_rows == 0) {
        $_SESSION['error'] = "У вас не активированны инструменты.";
        header("Location: setting");
        exit;
    }

    $tools = $tools_result->fetch_assoc();
    $limit_field = $resource_type . '_limit';

    if (!isset($tools[$limit_field])) {
        $_SESSION['error'] = "Неверный тип ресурса.";
        header("Location: setting");
        exit;
    }

    if ($amount > $tools[$limit_field]) {
        $_SESSION['error'] = "Превышен лимит перевода. Максимум: " . $tools[$limit_field];
        header("Location: setting");
        exit;
    }

    $invent_query = "SELECT $resource_type FROM invent WHERE id_user = (SELECT id FROM users WHERE login = '$sender_login')";
    $invent_result = $conn->query($invent_query);
    $invent = $invent_result->fetch_assoc();

    if ($invent[$resource_type] < $amount) {
        $_SESSION['error'] = "Недостаточно ресурсов.";
        header("Location: setting");
        exit;
    }
    $conn->begin_transaction();

    try {
        $update_sender = "UPDATE invent SET $resource_type = $resource_type - $amount WHERE id_user = (SELECT id FROM users WHERE login = '$sender_login')";
        $conn->query($update_sender);

        $update_recipient = "UPDATE invent SET $resource_type = $resource_type + $amount WHERE id_user = $recipient_id";
        $conn->query($update_recipient);

        $sender_id = $conn->query("SELECT id FROM users WHERE login = '$sender_login'")->fetch_assoc()['id'];
        $insert_log = "INSERT INTO pay_log (first_user_id, second_user_id, type, count, time) VALUES ($sender_id, $recipient_id, '$resource_type', $amount, NOW())";
        $conn->query($insert_log);

        $conn->commit();
        $_SESSION['great'] = "Перевод успешно выполнен!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Ошибка при выполнении перевода: " . $e->getMessage();
    }

    mysqli_close($conn);
    header("Location: setting");
    exit;
}
