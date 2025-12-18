<?php
require_once("./system/tools_check.php");
require_once("./system/config.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['compose_error'] = "Невозможно отправить пустое, или не очень, письмо.";
    header("Location: compose");
    exit;
}

$to = trim($_POST['to'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body = trim($_POST['body'] ?? '');

if (!preg_match('/^([a-zA-Z0-9_ .]+)@abyss$/', $to, $matches)) {
    $_SESSION['compose_error'] = "Неверный формат адреса получателя.";
    header('Location: compose');
    exit;
}

$to_username = $matches[1];

$stmt = $pdo->prepare("SELECT id, user_id, public_key FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
$sender_mail = $stmt->fetch();
if (!$sender_mail || empty(trim($sender_mail['public_key'])) || strpos($sender_mail['public_key'], 'BEGIN PUBLIC KEY') === false) {
    $_SESSION['compose_error'] = "У вас нет почтового профиля или публичный ключ некорректен.";
    header('Location: compose');
    exit;
}

$stmt = $pdo->prepare("SELECT u.id, g.lvl FROM users u JOIN site_group g ON u.permissions = g.name WHERE u.id = ?");
$stmt->execute([$user_id]);
$user_row = $stmt->fetch();
$user_lvl = $user_row ? (int)$user_row['lvl'] : 2;

if ($user_lvl < 4) {
    $stmt = $pdo->prepare("SELECT sent_at FROM mail WHERE sender_id = ? ORDER BY sent_at DESC LIMIT 1");
    $stmt->execute([$sender_mail['id']]);
    $last_sent = $stmt->fetchColumn();
    if ($last_sent) {
        $last_time = strtotime($last_sent);
        $now = time();
        $cooldown = ($user_lvl == 3) ? 30 : 60; //Премиум 30 сек, обычные 1 минута.
        if ($now - $last_time < $cooldown) {
            $wait = $cooldown - ($now - $last_time);
            $_SESSION['compose_error'] = "Вы слишком часто отправляете письма. Подождите ещё " . $wait . " сек.";
            header('Location: compose');
            exit;
        }
    }
}

$stmt = $pdo->prepare("SELECT id, user_id, public_key FROM mail_user WHERE username = ?");
$stmt->execute([$to_username]);
$recipient = $stmt->fetch();

if (!$recipient || empty($recipient['public_key'])) {
    $_SESSION['compose_error'] = "Получатель не найден или публичный ключ некорректен.";
    header('Location: compose');
    exit;
}

if($sender_mail['user_id'] == $recipient['user_id']) {
    $_SESSION['compose_error'] = "Отправитель не может быть получателем!";
    header('Location: compose');
    exit;
}

$aes_key = openssl_random_pseudo_bytes(32);
$iv = openssl_random_pseudo_bytes(16);

$encrypted_body = openssl_encrypt($body, 'AES-256-CBC', $aes_key, 0, $iv);
$encrypted_body .= '::' . base64_encode($iv);

openssl_public_encrypt($aes_key, $key_for_user, $recipient['public_key']);
openssl_public_encrypt($aes_key, $key_for_sender, $sender_mail['public_key']);
openssl_public_encrypt($aes_key, $key_for_admin, file_get_contents(ADMIN_PUBLIC_KEY));

$stmt = $pdo->prepare("INSERT INTO mail (sender_id, recipient_id, subject, body, key_user, key_sender, key_admin) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $sender_mail['id'],
    $recipient['id'],
    $subject,
    $encrypted_body,
    base64_encode($key_for_user),
    base64_encode($key_for_sender),
    base64_encode($key_for_admin)
]);
$mail_id = $pdo->lastInsertId();

if (!empty($_FILES['attachment']['tmp_name'])) {
    $file_data = file_get_contents($_FILES['attachment']['tmp_name']);
    $file_name = basename($_FILES['attachment']['name']);
    $file_iv = openssl_random_pseudo_bytes(16);
    $encrypted_file = openssl_encrypt($file_data, 'AES-256-CBC', $aes_key, 0, $file_iv);

    $rel_path = 'encrypted_attachments/' . $mail_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9_.-]/", "", $file_name);
    $abs_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $rel_path;
    file_put_contents($abs_path, $encrypted_file . "::" . base64_encode($file_iv));

    $stmt = $pdo->prepare("UPDATE mail SET attachment_path = ? WHERE id = ?");
    $stmt->execute([
        $rel_path,
        $mail_id
    ]);
}

$_SESSION['mail_great'] = "Сообщение успешно отправленно!";
header("Location: main");
exit;