<?php
session_start();
require_once("./system/tools_check.php");
require_once("./system/config.php");

$id = intval($_GET['id'] ?? 0);
if (!$id) die("Некорректный ID письма.");

$stmt = $pdo->prepare("SELECT * FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
$mail_user = $stmt->fetch();
if (!$mail_user) die("Почтовый профиль не найден.");
$my_mail_id = $mail_user['id'];

$login = $_SESSION['user'];
$user_query = $pdo->prepare("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?");
$user_query->execute([$login]);
$user = $user_query->fetch();

$stmt = $pdo->prepare("SELECT * FROM mail WHERE id = ? AND (sender_id = ? OR recipient_id = ?)");
$stmt->execute([$id, $my_mail_id, $my_mail_id]);
$msg = $stmt->fetch();

if (!$msg) die("Письмо не найдено или у вас нет прав на его просмотр.");

$key_path = __DIR__ . "/../../keys/users/user_{$user_id}.pem";
if (!file_exists($key_path)) die("Приватный ключ не найден.");

$private_key_data = file_get_contents($key_path);
$priv = openssl_pkey_get_private($private_key_data);
if (!$priv) die("Не удалось загрузить приватный ключ.");

$is_sender = ($msg['sender_id'] == $my_mail_id);
$is_recipient = ($msg['recipient_id'] == $my_mail_id);

if ($is_sender && !empty($msg['key_sender'])) {
    $encrypted_key = base64_decode($msg['key_sender']);
} elseif ($is_recipient && !empty($msg['key_user'])) {
    $encrypted_key = base64_decode($msg['key_user']);
} else {
    die("Нет подходящего ключа для расшифровки письма.");
}

if (!openssl_private_decrypt($encrypted_key, $aes_key, $priv)) die("Ошибка расшифровки AES-ключа.");

if (empty($msg['attachment_path'])) die("Вложение не найдено.");
$real_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $msg['attachment_path'];
if (!file_exists($real_path)) die("Файл вложения не найден.");

$enc_data = file_get_contents($real_path);
[$enc_file, $file_iv] = explode("::", $enc_data);
$attachment = openssl_decrypt($enc_file, 'AES-256-CBC', $aes_key, 0, base64_decode($file_iv));
$download_name = basename($msg['attachment_path']);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
echo $attachment;
exit;
