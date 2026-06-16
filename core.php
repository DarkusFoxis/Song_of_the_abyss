<?php
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/modules/PHPMailer/src/Exception.php';
require __DIR__ . '/modules/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/modules/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/template/auth.php';
require_once __DIR__ . '/template/conn.php';

date_default_timezone_set('Europe/Moscow');

$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
if (!$isAjaxRequest || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index');
    exit;
}

auth_start_session();
auth_sync_session_from_token();
security_require_csrf(true);

function sendMail(string $email, string $code): bool
{
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

    return $mail->send();
}

$authUser = auth_get_current_user();
if ($authUser === null) {
    echo 'Вы должны быть авторизованы.';
    exit;
}

$action = $_POST['action'] ?? null;
if (!is_string($action) || $action === '') {
    echo 'Неизвестная команда.';
    exit;
}

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo 'Ошибка соединения: ' . mysqli_connect_error();
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

$login = $authUser['login'];
$userStmt = $conn->prepare(
    "SELECT u.id, u.login, u.email, u.email_code, u.permissions, u.username, sg.lvl
     FROM users u
     JOIN site_group sg ON u.permissions = sg.name
     WHERE u.login = ?
     LIMIT 1"
);
$userStmt->bind_param('s', $login);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    echo 'Пользователь не найден.';
    mysqli_close($conn);
    exit;
}

$userId = (int)$user['id'];
$userLevel = (int)$user['lvl'];

switch ($action) {
    case 'ban':
        if ($userLevel !== 6) {
            echo 'У вас недостаточно прав.';
            break;
        }

        $targetId = (int)($_POST['userId'] ?? 0);
        if ($targetId <= 0 || $targetId === $userId) {
            echo 'Некорректный пользователь для блокировки.';
            break;
        }

        $reasonText = trim((string)($_POST['reason'] ?? ''));
        if ($reasonText === '') {
            echo 'Укажите причину блокировки.';
            break;
        }

        $fullReason = mb_substr($reasonText, 0, 500) . '. Модератор: ' . $user['username'];
        $stmt = $conn->prepare("UPDATE users SET permissions = 'BANNED', reason = ? WHERE id = ?");
        $stmt->bind_param('si', $fullReason, $targetId);
        $stmt->execute();
        echo $stmt->affected_rows > 0 ? 'Участник успешно заблокирован.' : 'Пользователь не найден.';
        $stmt->close();
        break;

    case 'unban':
        if ($userLevel !== 6) {
            echo 'У вас недостаточно прав.';
            break;
        }

        $targetId = (int)($_POST['userId'] ?? 0);
        if ($targetId <= 0) {
            echo 'Некорректный пользователь.';
            break;
        }

        $stmt = $conn->prepare("UPDATE users SET permissions = 'USER', reason = NULL WHERE id = ?");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        echo $stmt->affected_rows > 0 ? 'Участник успешно разблокирован.' : 'Пользователь не найден.';
        $stmt->close();
        break;

    case 'switch_group':
        if ($userLevel !== 6) {
            echo 'У вас недостаточно прав.';
            break;
        }

        $targetId = (int)($_POST['userId'] ?? 0);
        $newGroup = trim((string)($_POST['group'] ?? ''));
        if ($targetId <= 0 || $newGroup === '') {
            echo 'Некорректные параметры.';
            break;
        }

        $groupStmt = $conn->prepare("SELECT name FROM site_group WHERE name = ? LIMIT 1");
        $groupStmt->bind_param('s', $newGroup);
        $groupStmt->execute();
        $groupExists = $groupStmt->get_result()->fetch_assoc();
        $groupStmt->close();

        if (!$groupExists) {
            echo 'Неизвестная группа.';
            break;
        }

        $stmt = $conn->prepare("UPDATE users SET permissions = ? WHERE id = ?");
        $stmt->bind_param('si', $newGroup, $targetId);
        $stmt->execute();
        echo $stmt->affected_rows > 0 ? 'Группа успешно изменена.' : 'Пользователь не найден.';
        $stmt->close();
        break;

    case 'verification':
        if ($userLevel !== 1) {
            echo 'Данная функция вам недоступна.';
            break;
        }

        $code = trim((string)($_POST['code'] ?? ''));
        if ($code === '' || !hash_equals((string)$user['email_code'], $code)) {
            echo 'Код введён неверно.';
            break;
        }

        $stmt = $conn->prepare("UPDATE users SET permissions = 'USER' WHERE login = ?");
        $stmt->bind_param('s', $login);
        $stmt->execute();
        echo $stmt->affected_rows > 0 ? 'Аккаунт успешно верифицирован.' : 'Не удалось обновить аккаунт.';
        $stmt->close();
        break;

    case 'add_city':
        if ($userLevel <= 1) {
            echo 'Вы не верифицировали свой аккаунт.';
            break;
        }

        $city = trim((string)($_POST['city'] ?? ''));
        if ($city === '') {
            echo 'Город не указан.';
            break;
        }

        $city = mb_substr($city, 0, 120);
        $stmt = $conn->prepare("UPDATE personal_data SET city = ? WHERE login = ?");
        $stmt->bind_param('ss', $city, $login);
        $stmt->execute();
        echo 'Город успешно установлен.';
        $stmt->close();
        break;

    case 'resend_code':
        if ($userLevel <= 0) {
            echo 'Данная функция вам недоступна.';
            break;
        }

        $code = bin2hex(random_bytes(5));
        $stmt = $conn->prepare("UPDATE users SET email_code = ? WHERE login = ?");
        $stmt->bind_param('ss', $code, $login);
        $stmt->execute();
        $stmt->close();

        echo sendMail((string)$user['email'], $code)
            ? 'Код успешно отправлен. Проверьте вашу почту.'
            : 'При отправке письма произошла ошибка.';
        break;

    default:
        echo 'Неизвестная команда.';
        break;
}

mysqli_close($conn);
session_write_close();
