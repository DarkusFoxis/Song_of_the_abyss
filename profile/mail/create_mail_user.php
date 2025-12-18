<?php
require_once("./system/tools_check.php");
require_once("./system/config.php");


$stmt = $pdo->prepare("SELECT * FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
if ($stmt->fetch()) {
    $_SESSION['mail_error'] = "У вас уже есть почтовый аккаунт! Перезагрузите страницу, или обратитесь к администратору!";
    header("Location: main");
    exit;
}

$keypair = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA
]);
openssl_pkey_export($keypair, $privateKey);
$publicKeyDetails = openssl_pkey_get_details($keypair);
$publicKey = $publicKeyDetails['key'];

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = $user ? $user['username'] : ('user'.$user_id);

$stmt = $pdo->prepare("INSERT INTO mail_user (user_id, username, public_key) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $username, $publicKey]);

$key_path = __DIR__ . "/../../keys/users/user_{$user_id}.pem";
file_put_contents($key_path, $privateKey);
chmod($key_path, 0600);

$_SESSION['mail_great'] = "Почтовый клиент создан! Если вы всё ещё видите кнопку создать новый почтовы профиль, перезагрузите страницу. Ваша почта:" . $username . "@abyss.";
header("Location: main");
exit;
