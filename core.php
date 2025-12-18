<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require './modules/PHPMailer/src/Exception.php';
require './modules/PHPMailer/src/PHPMailer.php';
require './modules/PHPMailer/src/SMTP.php';
date_default_timezone_set('Europe/Moscow');
$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

function sendMail($email, $code) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.beget.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'aurora@so-ta.ru';
    $mail->Password = 'Dark015+';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->setFrom('aurora@so-ta.ru', 'Аврора');
    $mail->addAddress($email);
    $mail->Subject = 'Верификация на сайте Song of the Abyss';
    $message = '
    <html>
        <body>
            <p>Добро пожаловать в бездну!</p>
            <p>Меня зовут Аврора, и я буду вашим проводником в Бездне!</p>
            <p>Ваша почта была указана при регистрации аккаунта. Если это были не вы, проигнорируйте это письмо.</p>
            <p>Для верификации аккаунта, используйте код: <b>' . $code . '</b></p>
            <p>По всем вопросам, можете писать мне! Буду рада ответить на ваши вопросы!</p>
            <p><i>С уважением, Аврора!</i></p>
        </body>
    </html>
    ';
    $mail->msgHTML($message);
    $mail->CharSet = 'UTF-8';
    return $mail ->send();
}

if ($isAjaxRequest) {
    session_start();
    if (!isset($_SESSION['user'])) {
        echo "Вы должны быть авторизованы.";
        exit;
    }
    $action = isset($_POST['action']) ? $_POST['action'] : null;
    if ($action) {
        require_once './template/conn.php';
        $conn = mysqli_connect($host, $log, $password_sql, $database);
        if (!$conn) {
            echo "Ошибка соединения: " . mysqli_connect_error();
            exit;
        }
        $login = $_SESSION['user'];
        $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
        $result = $conn->query($user_query);
        $user = $result -> fetch_assoc();
        $userId = $user['id'];
        if ($user['lvl'] == 0) {
            echo "Вы заблокированы на проекте, поэтому возможности ограничены.";
            exit;
        }
        $username = $_SESSION['username'];
        switch ($action) {
            case 'ban':
                if ($user['lvl'] == 6) {
                    $targetId = $_POST['userId'];
                    if ($targetId !== $userId) {
                        $reason = $_POST['reason'] . '. Модератор: ' . $_POST['moder'];
                        $sql = "UPDATE users SET permissions = 'BANNED', reason = '$reason' WHERE id = '$targetId'";
                        if (!mysqli_query($conn, $sql)) {
                            echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                        } else {
                            echo "Участник успешно заблокирован.";
                        }
                    } else {
                        echo "Вы не можете себя заблокировать.";
                    }
                } else {
                    echo "У вас недостаточно прав.";
                }
                break;
            case 'unban':
                if ($user['lvl'] == 6) {
                    $targetId = $_POST['userId'];
                    $sql = "UPDATE users SET permissions = 'USER', reason = NULL WHERE id = '$targetId'";
                    if (!mysqli_query($conn, $sql)) {
                        echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                    } else {
                        echo "Участник успешно разблокирован.";
                    }
                } else {
                    echo "У вас недостаточно прав.";
                }
                break;
            case 'switch_group':
                if ($user['lvl'] == 6) {
                    $targetId = $_POST['userId'];
                    $new_group = $_POST['group'];
                    $sql = "UPDATE users SET permissions = '$new_group' WHERE id = '$targetId'";
                    if (!mysqli_query($conn, $sql)) {
                        echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                    } else {
                        echo "Группа успешно изменена.";
                    }
                } else {
                    echo "У вас недостаточно прав.";
                }
                break;
            case 'verification':
                if ($user['lvl'] == 1) {
                    $code = $_POST['code'];
                    if ($user["email_code"] == $code) {
                        $sql = "UPDATE users SET permissions = 'USER' WHERE login = '$login'";
                        if (!mysqli_query($conn, $sql)) {
                            echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                        } else {
                            echo "Аккаунт успешно верифицирован! Измените страницу, чтобы изменения вступили в силу.";
                        }
                    } else {
                        echo "Код введён не верно.";
                    }
                } else {
                    echo "Вы верифицированны, или данная функция вам недоступна.";
                }
                break;
            case "add_city":
                if ($user['lvl'] > 1) {
                    $city =  mysqli_real_escape_string($conn, $_POST['city']);
                    $sql = "UPDATE personal_data SET city = '$city' WHERE login = '$login'";
                    if (!mysqli_query($conn, $sql)) {
                        echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                    } else {
                        echo "Город успешно установлен!";
                    }
                } else {
                    echo "Вы не верифицировали свой аккаунт.";
                }
                break;
            case "resend_code":
                if ($user['lvl'] > 0) {
                    $bytes = random_bytes(5);
                    $code = bin2hex($bytes);
                    $email = $user['email'];
                    
                    $sql = "UPDATE users SET email_code = '$code' WHERE login = '$login'";
                    if (!mysqli_query($conn, $sql)) {
                        echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                    } else {
                        if (sendMail($email, $code)) {
                            echo 'Код успешно отправлен! Проверте ваш почтовый ящик и/или папку "Спам"';
                        } else {
                            echo 'При отправке письмя произошла ошибка. Сообщите создателю.';
                        }
                    }
                } else {
                    echo "Данная функция вам недоступна.";
                }
                break;
            default:
                echo "Неизвестная команда. Выполнение невозможно.";
                break;
        }
        mysqli_close($conn);
        exit;
    }
} else{
    header('Location: index');
    exit;
}
session_write_close();