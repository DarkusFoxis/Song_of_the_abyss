<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../modules/PHPMailer/src/Exception.php';
require '../modules/PHPMailer/src/PHPMailer.php';
require '../modules/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../template/auth.php';

auth_start_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        echo 'Ошибка соединения.';
        exit;
    }

    $email = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
    $login = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['login'] ?? ''));
    $username = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['username'] ?? ''));
    $password = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['password'] ?? ''));
    $password2 = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['password2'] ?? ''));
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Почта введена не верно!';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    if ($password !== $password2) {
        $_SESSION['error'] = 'Пароли не совпадают!';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
        $_SESSION['error'] = 'Ник должен быть длиной от 3 до 20 символов.';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    $allowed_domains = ['yandex.ru', 'mail.ru', 'gmail.com', 'so-ta.ru', 'bk.ru'];
    $email_parts = explode('@', $email);
    $domain = strtolower(end($email_parts));

    if (!checkdnsrr($domain, 'MX')) {
        $_SESSION['error'] = 'Ваш почтовый домен не существует!';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    if (!in_array($domain, $allowed_domains, true)) {
        $_SESSION['error'] = 'Разрешены только почты @yandex.ru, @mail.ru, @gmail.com, @bk.ru!';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    if (preg_match('/[а-яА-ЯёЁ]/u', $login)) {
        $_SESSION['error'] = 'Логин должен быть написан латинскими буквами!';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    $user_query = "SELECT id FROM users WHERE login = '$login'";
    $email_query = "SELECT id FROM users WHERE email = '$email'";
    $username_query = "SELECT id FROM users WHERE username = '$username'";
    $black_list = "SELECT id FROM black_ip WHERE ip = '$ip_address'";

    $result1 = $conn->query($user_query);
    $result2 = $conn->query($email_query);
    $result3 = $conn->query($username_query);
    $result4 = $conn->query($black_list);

    if (($result1 && $result1->num_rows > 0) || ($result2 && $result2->num_rows > 0)) {
        $_SESSION['error'] = 'Такой аккаунт уже существует!';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    if ($result3 && $result3->num_rows > 0) {
        $_SESSION['error'] = 'Такой ник уже используется.';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    if ($result4 && $result4->num_rows > 0) {
        $_SESSION['error'] = 'Вы были заблокированы на проекте.';
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $bytes = random_bytes(5);
    $code = bin2hex($bytes);
    $token = auth_generate_token($login);

    $final = $conn->query("INSERT INTO users (id, login, username, email, ip, password, token, permissions, avatar, BIO, NSFW, reason, data_create, last_login, email_code, donate) VALUES (NULL, '$login', '$username', '$email', '$ip_address', '$hashed_password', '$token', 'GUEST', 'avatar.png', 'Не указан.', 0, NULL, NOW(), NOW(), '$code', 0)");

    if (!$final) {
        $_SESSION['error'] = 'Ошибка создания аккаунта: ' . mysqli_error($conn);
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    $userId = mysqli_insert_id($conn);
    $inventoryInsert = $conn->query("INSERT INTO invent (`id_user`, `id_title`, `lvl`, `xp`, `xp_max`, `coins`, `bonus_data`, `kase`, `sakura`) VALUES ('$userId', NULL, 0, 0, 1000, 25, NOW(), 5, 0)");

    if (!$inventoryInsert) {
        $_SESSION['error'] = 'Ошибка создания инвентаря: ' . mysqli_error($conn);
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    $personal_data = $conn->query("INSERT INTO personal_data (`id_data`, `login`, `birthdate`, `telegram`, `city`) VALUES (NULL,'$login',NULL,NULL,NULL)");

    if (!$personal_data) {
        $_SESSION['error'] = 'Ошибка создания персональных данных: ' . mysqli_error($conn);
        mysqli_close($conn);
        header('Location: main');
        exit;
    }

    auth_set_token_cookie($token);
    auth_set_session_user([
        'login' => $login,
        'username' => $username,
    ]);

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
        $_SESSION['great'] = 'Регистрация успешна! На вашу почту отправленно письмо с кодом.';
    } else {
        $_SESSION['error'] = 'Аккаунт создан, но письмо отправить не удалось: ' . $mail->ErrorInfo;
    }

    mysqli_close($conn);
    header('Location: ' . auth_get_redirect_target('main'));
    exit;
}

exit;
