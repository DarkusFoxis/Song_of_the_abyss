<?php
require_once("./system/config.php");
session_start();
require_once("./system/tools_check.php");

$stmt = $pdo->prepare("SELECT id FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
$mail_user = $stmt->fetch();

if (!$mail_user) {
    echo "Mail-профиль не найден.";
    exit;
}

$key_path = __DIR__ . "/../../keys/users/user_{$user_id}.pem";

    echo "🔐 У вас уже есть сгенерированный ключ.<br>
          <a href=\"download_key.php\">Скачать приватный ключ</a><br><br>
          Если вы сгенерируете новый ключ — вы потеряете доступ ко всем старым письмам.<br>
          <form method='POST'>
            <input type='hidden' name='confirm' value='1'>
            <button type='submit'>🔁 Сгенерировать новый ключ</button>
          </form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['confirm'] == '1') {
    $keypair = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA
    ]);
    openssl_pkey_export($keypair, $privateKey);
    $publicKeyDetails = openssl_pkey_get_details($keypair);
    $publicKey = $publicKeyDetails['key'];

    $stmt = $pdo->prepare("UPDATE mail_user SET public_key = ? WHERE user_id = ?");
    $stmt->execute([$publicKey, $user_id]);

    file_put_contents($key_path, $privateKey);
    chmod($key_path, 0600);

    header("Location: main");
    exit;
}