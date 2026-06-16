<?php
require_once("./system/tools_check.php");
require_once("./system/config.php");

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['mail_error'] = "Некорректный ID письма.";
    header("Location: main");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
$mail_user = $stmt->fetch();
if (!$mail_user) {
    $_SESSION['mail_error'] = "Почтовый профиль не найден.";
    header("Location: main");
    exit;
}
$my_mail_id = $mail_user['id'];

$stmt = $pdo->prepare("SELECT m.*, mu.username AS from_user FROM mail m JOIN mail_user mu ON m.sender_id = mu.id WHERE m.id = ? AND (m.sender_id = ? OR m.recipient_id = ?)");
$stmt->execute([$id, $my_mail_id, $my_mail_id]);
$msg = $stmt->fetch();

if (!$msg) {
    $_SESSION['mail_error'] = "Письмо не найдено или у вас нет прав на его просмотр.";
    header("Location: main");
    exit;
}

$key_path = app_user_private_key_path((int)$user_id);

if (!file_exists($key_path)) {
    $_SESSION['mail_error'] = "Приватный ключ не найден. Пожалуйста, сгенерируйте ключи. Оповестите администратора об этой ошибке!";
    header("Location: main");
    exit;
}

$private_key_data = file_get_contents($key_path);
$priv = openssl_pkey_get_private($private_key_data);

if (!$priv) {
    $_SESSION['mail_error'] = "Не удалось загрузить приватный ключ.";
    header("Location: main");
    exit;
}


$is_sender = ($msg['sender_id'] == $my_mail_id);
$is_recipient = ($msg['recipient_id'] == $my_mail_id);

if ($is_sender && !empty($msg['key_sender'])) {
    $encrypted_key = base64_decode($msg['key_sender']);
} elseif ($is_recipient && !empty($msg['key_user'])) {
    $encrypted_key = base64_decode($msg['key_user']);
} else {
    $_SESSION['mail_error'] = "Нет подходящего ключа для расшифровки письма.";
    header("Location: main");
    exit;
}

if (!openssl_private_decrypt($encrypted_key, $aes_key, $priv)) {
    $_SESSION['mail_error'] = "Ошибка расшифровки AES-ключа.";
    header("Location: main");
    exit;
}

[$ciphertext, $iv_encoded] = explode("::", $msg['body']);
$iv = base64_decode($iv_encoded);
$body = openssl_decrypt($ciphertext, 'AES-256-CBC', $aes_key, 0, $iv);

if ($msg['recipient_id'] == $my_mail_id && $msg['is_read'] == 0) {
    $stmt = $pdo->prepare("UPDATE mail SET is_read = 1 WHERE id = ?");
    $stmt->execute([$msg['id']]);
}

$reply_to = htmlspecialchars($msg['from_user']);
$reply_subject = htmlspecialchars($msg['subject']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo security_html((string)$msg['subject']); ?></title>
    <link rel="icon" href="../../img/icon.png">
    <link rel="stylesheet" href="../../style/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/jquery-3.7.1.min.js"></script>
    <style>
        .paper-container {
            max-width: 700px;
            margin: 40px auto;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" opacity="0.1"><filter id="grain"><feTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="3" stitchTiles="stitch"/></filter><rect width="100%" height="100%" filter="url(%23grain)"/></svg>'), 
                        linear-gradient(to bottom, #fffbe9, #e8dfc4);
            border-radius: 12px;
            padding: 50px 40px;
            font-family: 'Montserrat Alternates', serif;
            position: relative;
            box-shadow: 0 0 30px rgba(187, 85, 211, 0.3), 0 0 0 8px #d4c7a0, 0 0 0 12px #a38c6d;
            border: 2px solid #8b735b;
            transform: rotate(0.5deg);
            animation: float 8s infinite ease-in-out;
            overflow: hidden;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0.5deg); }
            50% { transform: translateY(-10px) rotate(-0.5deg); }
        }
        .paper-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to bottom, rgba(163, 140, 109, 0.5), transparent);
            border-radius: 12px 12px 0 0;
        }
        .paper-container::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to top, rgba(163, 140, 109, 0.5), transparent);
            border-radius: 0 0 12px 12px;
        }
        .paper-header {
            font-size: 1.8rem;
            color: #5d4037;
            margin-bottom: 25px;
            font-weight: bold;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
            font-family: 'Caveat', cursive;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .paper-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(to right, transparent, #8b735b, transparent);
        }
        .paper-body {
            font-size: 1.25rem;
            color: #4e342e;
            white-space: pre-wrap;
            margin-bottom: 30px;
            line-height: 1.8;
            padding: 0 10px;
            position: relative;
            font-family: 'Caveat', cursive;
            min-height: 300px;
            background-image: linear-gradient(to bottom, rgba(0,0,0,0.05) 1px, transparent 1px);
            background-size: 100% 36px;
            background-position: 0 25px;
            text-shadow: 0.5px 0.5px 1px rgba(0,0,0,0.1);
        }
        .paper-body::first-letter {
            font-size: 3.5rem;
            float: left;
            line-height: 0.8;
            margin-right: 8px;
            color: #5d4037;
            font-weight: bold;
        }
        .paper-seal {
            position: absolute;
            top: -25px;
            right: -25px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, #d4af37 40%, #b8860b 100%);
            border-radius: 50%;
            border: 3px solid #8b735b;
            box-shadow: 0 0 20px rgba(187, 85, 211, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transform: rotate(15deg);
            animation: pulse 4s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: rotate(15deg) scale(1); }
            50% { transform: rotate(15deg) scale(1.05); box-shadow: 0 0 30px rgba(187, 85, 211, 0.7); }
        }
        .paper-seal::before {
            content: "✉";
            font-size: 40px;
            color: #5d4037;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .paper-attachment {
            display: inline-flex;
            align-items: center;
            margin: 15px 15px 0 0;
            padding: 12px 20px;
            background: linear-gradient(to bottom, #e8dfc4, #d4c7a0);
            border: 2px solid #8b735b;
            border-radius: 30px;
            color: #5d4037;
            font-weight: bold;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            font-family: 'Montserrat Alternates', sans-serif;
        }
        .paper-attachment::before {
            content: "";
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: radial-gradient(circle, rgba(187, 85, 211, 0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .paper-attachment:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: #3F0071;
            border-color: #BA55D3;
        }
        .paper-attachment:hover::before {
            opacity: 1;
        }
        .paper-attachment i {
            margin-right: 10px;
            font-size: 1.4rem;
            transition: transform 0.3s;
        }
        .paper-attachment:hover i {
            transform: scale(1.2);
        }
        .paper-footer {
            margin-top: 30px;
            text-align: right;
            color: #8b735b;
            font-style: italic;
            padding-top: 15px;
            border-top: 1px dashed #8b735b;
        }
        .magic-ink {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            color: #5d4037;
            font-size: 0.9rem;
        }
        .magic-ink::before {
            content: "";
            display: inline-block;
            width: 20px;
            height: 20px;
            background: #5d4037;
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 10px rgba(187, 85, 211, 0.5);
        }
        .reply-btn-container {
            text-align: center;
            margin-top: 30px;
        }
        .reply-btn {
            display: inline-flex;
            align-items: center;
            margin: 15px 15px 0 0;
            padding: 12px 20px;
            background: linear-gradient(to bottom, #e8dfc4, #d4c7a0);
            border: 2px solid #8b735b;
            border-radius: 30px;
            color: #5d4037;
            font-weight: bold;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            font-family: 'Montserrat Alternates', sans-serif;
        }
        .reply-btn i {
           margin-right: 10px;
            font-size: 1.4rem;
            transition: transform 0.3s;
        }
        .reply-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: #3F0071;
            border-color: #BA55D3;
        }
        .reply-btn:hover i {
            transform: rotate(-20deg);
            transform: scale(1.2);
        }
        @media (max-width: 768px) {
            .paper-container {
                padding: 35px 20px;
                margin: 20px 15px;
            }
            .paper-header {
                font-size: 1.5rem;
            }
            .paper-body {
                font-size: 1.1rem;
                min-height: 200px;
            }
            .paper-seal {
                width: 70px;
                height: 70px;
                top: -15px;
                right: -15px;
            }
            .paper-seal::before {
                font-size: 28px;
            }
            .reply-btn {
                padding: 12px 25px;
                font-size: 1.1rem;
            }
        }
        @keyframes appear {
            from { 
                opacity: 0;
                transform: translateY(50px) rotate(5deg);
            }
            to { 
                opacity: 1;
                transform: translateY(0) rotate(0.5deg);
            }
        }
        .paper-container {
            animation: appear 1.2s ease-out forwards;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="./main">Back</a>
        <a href="./compose">Новое письмо</a>
        <a href="./compose?reply_to=<?= urlencode($msg['from_user']) ?>&subject=<?= urlencode($msg['subject']) ?>">Ответить</a>
    </div>
    <div class="content-main">
        <div class="paper-container">
            <div class="paper-seal"></div>
            <div class="paper-header">Сообщение от: <?= htmlspecialchars($msg['from_user']) ?></div>
            <div class="paper-body"><?= htmlspecialchars($body) ?></div>
            <div class="attachment-container">
                <?php
                if (!empty($msg['attachment_path'])) {
                    $real_path = app_mail_attachment_path((string)$msg['attachment_path']);
                    if (file_exists($real_path)) {
                        $enc_data = file_get_contents($real_path);
                        [$enc_file, $file_iv] = explode("::", $enc_data);
                        $attachment = openssl_decrypt($enc_file, 'AES-256-CBC', $aes_key, 0, base64_decode($file_iv));
                        $download_name = basename($msg['attachment_path']);
                        $tmp_file = sys_get_temp_dir() . "/$download_name";
                        file_put_contents($tmp_file, $attachment);
                        echo "<a class='paper-attachment' href='/{$msg['attachment_path']}' download>📎 Скачать оригинал (зашифр.)</a> ";
                        echo "<a class='paper-attachment' href='download_attachment?id={$msg['id']}' download>📥 Скачать расшифрованное</a>";
                    }
                }
                ?>
            </div>
            <div class="paper-footer">Отправлено: <?= date('d.m.Y H:i', strtotime($msg['sent_at'])) ?></div>
            <div class="reply-btn-container">
                <a href="./compose?reply_to=<?= urlencode($reply_to) ?>&subject=<?= urlencode($reply_subject) ?>" 
                   class="reply-btn">
                    <i class="fas fa-reply"></i> Ответить на письмо
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
session_write_close();
?>
