<?php
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/../modules/PHPMailer/src/Exception.php';
require __DIR__ . '/../modules/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../modules/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/../template/conn.php';

auth_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

security_require_csrf(true);

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo 'Ошибка соединения.';
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

$email = trim((string)($_POST['email'] ?? ''));
$login = trim((string)($_POST['login'] ?? ''));
$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$password2 = (string)($_POST['password2'] ?? '');
$ipAddress = (string)($_SERVER['REMOTE_ADDR'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Почта введена неверно.';
    mysqli_close($conn);
    header('Location: main');
    exit;
}

if ($password !== $password2) {
    $_SESSION['error'] = 'Пароли не совпадают.';
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

$allowedDomains = ['yandex.ru', 'mail.ru', 'gmail.com', 'so-ta.ru', 'bk.ru'];
$emailParts = explode('@', $email);
$domain = strtolower((string)end($emailParts));

if (!checkdnsrr($domain, 'MX')) {
    $_SESSION['error'] = 'Ваш почтовый домен не существует.';
    mysqli_close($conn);
    header('Location: main');
    exit;
}

if (!in_array($domain, $allowedDomains, true)) {
    $_SESSION['error'] = 'Разрешены только почты @yandex.ru, @mail.ru, @gmail.com, @bk.ru и @so-ta.ru.';
    mysqli_close($conn);
    header('Location: main');
    exit;
}

if (preg_match('/[а-яА-ЯёЁ]/u', $login)) {
    $_SESSION['error'] = 'Логин должен быть написан латинскими буквами.';
    mysqli_close($conn);
    header('Location: main');
    exit;
}

$checkStmt = $conn->prepare(
    "SELECT
        EXISTS(SELECT 1 FROM users WHERE login = ?) AS login_exists,
        EXISTS(SELECT 1 FROM users WHERE email = ?) AS email_exists,
        EXISTS(SELECT 1 FROM users WHERE username = ?) AS username_exists,
        EXISTS(SELECT 1 FROM black_ip WHERE ip = ?) AS ip_blocked"
);
$checkStmt->bind_param('ssss', $login, $email, $username, $ipAddress);
$checkStmt->execute();
$checks = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!empty($checks['login_exists']) || !empty($checks['email_exists'])) {
    $_SESSION['error'] = 'Такой аккаунт уже существует.';
    mysqli_close($conn);
    header('Location: main');
    exit;
}

if (!empty($checks['username_exists'])) {
    $_SESSION['error'] = 'Такой ник уже используется.';
    mysqli_close($conn);
    header('Location: main');
    exit;
}

if (!empty($checks['ip_blocked'])) {
    $_SESSION['error'] = 'Вы были заблокированы на проекте.';
    mysqli_close($conn);
    header('Location: main');
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$code = bin2hex(random_bytes(5));
$token = auth_generate_token($login);

mysqli_begin_transaction($conn);

try {
    $insertUser = $conn->prepare(
        "INSERT INTO users
            (login, username, email, ip, password, token, permissions, avatar, BIO, NSFW, reason, data_create, last_login, email_code, donate)
         VALUES
            (?, ?, ?, ?, ?, ?, 'GUEST', 'avatar.png', 'Не указан.', 0, NULL, NOW(), NOW(), ?, 0)"
    );
    $insertUser->bind_param('sssssss', $login, $username, $email, $ipAddress, $hashedPassword, $token, $code);
    $insertUser->execute();
    $userId = (int)$insertUser->insert_id;
    $insertUser->close();

    $inventStmt = $conn->prepare(
        "INSERT INTO invent (id_user, id_title, lvl, xp, xp_max, coins, bonus_data, kase, sakura)
         VALUES (?, NULL, 0, 0, 1000, 25, NOW(), 5, 0)"
    );
    $inventStmt->bind_param('i', $userId);
    $inventStmt->execute();
    $inventStmt->close();

    $personalStmt = $conn->prepare(
        "INSERT INTO personal_data (login, birthdate, telegram, city) VALUES (?, NULL, NULL, NULL)"
    );
    $personalStmt->bind_param('s', $login);
    $personalStmt->execute();
    $personalStmt->close();

    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = 'Ошибка создания аккаунта: ' . $e->getMessage();
    mysqli_close($conn);
    header('Location: main');
    exit;
}

auth_set_token_cookie($token);
auth_set_session_user([
    'login' => $login,
    'username' => $username,
]);

$smtp = app_smtp_settings();
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $smtp['host'];
$mail->SMTPAuth = true;
$mail->Username = $smtp['username'];
$mail->Password = $smtp['password'];
$mail->SMTPSecure = $smtp['secure'];
$mail->Port = $smtp['port'];
$mail->setFrom($smtp['from_email'], $smtp['from_name']);
$mail->addAddress($email);
$mail->Subject = 'Верификация на сайте Song of the Abyss';
$mail->CharSet = 'UTF-8';
$mail->msgHTML(
    '<html><body>'
    . '<p>Добро пожаловать в бездну.</p>'
    . '<p>Для верификации аккаунта используйте код: <b>' . security_html($code) . '</b></p>'
    . '</body></html>'
);

if ($mail->send()) {
    $_SESSION['great'] = 'Регистрация успешна. На вашу почту отправлено письмо с кодом.';
} else {
    $_SESSION['error'] = 'Аккаунт создан, но письмо отправить не удалось: ' . $mail->ErrorInfo;
}

mysqli_close($conn);
header('Location: ' . auth_get_redirect_target('main'));
exit;
