<?php
require_once("./system/config.php");
require_once __DIR__ . '/../../template/auth.php';
require_once("./system/tools_check.php");

auth_start_session();
auth_sync_session_from_token();

$stmt = $pdo->prepare("SELECT id FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
$mail_user = $stmt->fetch();

if (!$mail_user) {
    echo "Mail-профиль не найден.";
    exit;
}

$key_path = app_user_private_key_path((int)$user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === '1') {
    security_require_csrf(true);
    app_private_ensure_dir('keys/users');

    $keypair = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
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

if (file_exists($key_path)) {
    echo "У вас уже есть сгенерированный ключ.<br>
          Если вы сгенерируете новый ключ, вы потеряете доступ ко всем старым письмам.<br>
          <form method='POST'>"
          . security_csrf_input() .
          "<input type='hidden' name='confirm' value='1'>
            <button type='submit'>Сгенерировать новый ключ</button>
          </form>";
    exit;
}

echo "<form method='POST'>"
    . security_csrf_input() .
    "<input type='hidden' name='confirm' value='1'>
      <button type='submit'>Сгенерировать ключ</button>
    </form>";
