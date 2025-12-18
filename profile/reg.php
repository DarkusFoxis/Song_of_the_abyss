<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../modules/PHPMailer/src/Exception.php';
require '../modules/PHPMailer/src/PHPMailer.php';
require '../modules/PHPMailer/src/SMTP.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        echo("Ошибка соединения.");
    } else {
        $email = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['email']));
        $login = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['login']));
        $password = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['password']));
        $password2 = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['password2']));
        $ip_address = $_SERVER['REMOTE_ADDR'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION["error"] = "Почта введена не верно!";
        } 
        elseif ($password != $password2) {
            $_SESSION["error"] = "Пароли не совпадают!";
        } 
        else {
            $allowed_domains = ['yandex.ru', 'mail.ru', 'gmail.com', 'so-ta.ru', 'bk.ru'];
            $email_parts = explode('@', $email);
            $domain = strtolower(end($email_parts));

            if (!checkdnsrr($domain, 'MX')) {
                $_SESSION["error"] = "Ваш почтовый домен не существует!";
                header("Location: main");
                die();
            }
            else if (!in_array($domain, $allowed_domains)) {
                $_SESSION["error"] = "Разрешены только почты @yandex.ru, @mail.ru, @gmail.com, @bk.ru!";
                header("Location: main");
                die();
            }
            else {

                $user_query = "SELECT * FROM users WHERE login = '$login'";
                $email_query = "SELECT * FROM users WHERE email = '$email'";
                $black_list = "SELECT * FROM black_ip WHERE ip = '$ip_address'";
                $result1 = $conn->query($user_query);
                $result2 = $conn->query($email_query);
                $result4 = $conn->query($black_list);

                if (($result1->num_rows > 0) || ($result2->num_rows > 0)) {
                    $_SESSION["error"] = "Такой аккаунт уже существует!";
                    header("Location: main");
                    die();
                } 
                else if ($result4->num_rows > 0) {
                    $_SESSION["error"] = "Вы были заблокированы на проекте.";
                    header("Location: main");
                    die();
                } 
                else if (preg_match('/[а-яА-ЯёЁ]/u', $login)) {
                    $_SESSION["error"] = "Логин должен быть написан латинскими буквами!";
                    header("Location: main");
                    die();
                }
                $result = $conn->query("SELECT * FROM users");
                if ($result) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $bytes = random_bytes(5);
                    $code = bin2hex($bytes);
                    $final = $conn->query("INSERT INTO users (id, login, username, email, ip, password, token, permissions, avatar, BIO, NSFW, reason, data_create, last_login, email_code, donate) VALUES (NULL, '$login', '$login', '$email', '$ip_address', '$hashed_password', NULL, 'GUEST', 'avatar.png', 'Не указан.', 0, NULL, NOW(), NOW(), '$code', 0)");
                    if ($final) {
                        $userId = mysqli_insert_id($conn);
                        $insertQuery = "INSERT INTO `invent`(`id_user`, `id_title`, `lvl`, `xp`, `xp_max`, `coins`, `bonus_data`, `gems`, `kase`, `sakura`) VALUES ('$userId', NULL, 0, 0, 1000, 25, NOW(), 3, 5, 5)";
                        $inventoryInsert = $conn->query($insertQuery);
                        if ($inventoryInsert) {
                            $personal_data = $conn->query("INSERT INTO `personal_data`(`id_data`, `login`, `birthdate`, `telegram`, `city`) VALUES (NULL,'$login',NULL,NULL,NULL)");
                            if ($personal_data) {
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
                                if ($mail->send()) {
                                    $_SESSION['great'] = "Регистрация успешна! На вашу почту отправленно письмо с кодом ";
                                    $_SESSION['user'] = $login;
                                    $_SESSION['username'] = $login;
                                } else {
                                    $_SESSION["error"] = "Ошибка при отправке письма: " . $mail->ErrorInfo;
                                }
                            } else {
                                $_SESSION["error"] = "Ошибка создания персональных данных: " . mysqli_error($conn);
                            }
                        } else {
                            $_SESSION["error"] = "Ошибка создания инвентаря: " . mysqli_error($conn);
                        }
                    } else {
                        $_SESSION["error"] = "Ошибка создания аккаунта: " . mysqli_error($conn);
                    }
                } else {
                    $_SESSION["error"] = "Ошибка запроса к базе данных: " . mysqli_error($conn);
                }
            }
        }
        mysqli_close($conn);
        header("Location: main");
    }
}
session_write_close();