<?php
require_once("./system/tools_check.php");
require_once("./system/config.php");

$id = intval($_GET['id'] ?? 0);
if (!$id) die("Некорректный ID");

if ($user_lvl < 5) {
    $_SESSION['mail_error'] = "Доступ запрещён: недостаточно прав.";
    header("Location: main");
    exit;
}

$admin_priv_key = file_get_contents(ADMIN_PRIVATE_KEY);
$priv = openssl_pkey_get_private($admin_priv_key);
if (!$priv) die("Ошибка загрузки ключа администратора");

$stmt = $pdo->prepare("SELECT m.*, mu.username AS from_user FROM mail m JOIN mail_user mu ON m.sender_id = mu.id WHERE m.id = ?");
$stmt->execute([$id]);
$msg = $stmt->fetch();

if (!$msg) die("Письмо не найдено");

$encrypted_key = base64_decode($msg['key_admin']);
openssl_private_decrypt($encrypted_key, $aes_key, $priv);

[$ciphertext, $iv_encoded] = explode("::", $msg['body']);
$iv = base64_decode($iv_encoded);
$body = openssl_decrypt($ciphertext, 'AES-256-CBC', $aes_key, 0, $iv);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-письмо | Abyss</title>
    <link rel="icon" href="../../img/icon.png">
    <link rel="stylesheet" href="../../style/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/jquery-3.7.1.min.js"></script>
    <style>
        .content-main {
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" opacity="0.08"><filter id="grain"><feTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="3" stitchTiles="stitch"/></filter><rect width="100%" height="100%" filter="url(%23grain)"/></svg>'), 
                        linear-gradient(to bottom, #0a0615, #1a0a2d);
        }
        .paper-container {
            max-width: 700px;
            margin: 40px auto;
            background: 
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" opacity="0.15"><filter id="grain"><feTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="3" stitchTiles="stitch"/></filter><rect width="100%" height="100%" filter="url(%23grain)"/></svg>'), 
                linear-gradient(to bottom, #1e0f2e, #0d0819);
            border-radius: 15px;
            padding: 50px 40px;
            font-family: 'Caveat', cursive;
            position: relative;
            box-shadow: 0 0 40px rgba(187, 85, 211, 0.4), 0 0 0 8px #3a1b5a, 0 0 0 12px #250e3a;
            border: 2px solid #5a2d8c;
            transform: rotate(0.3deg);
            animation: float 8s infinite ease-in-out;
            overflow: hidden;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0.3deg); }
            50% { transform: translateY(-8px) rotate(-0.3deg); }
        }
        .paper-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to bottom, rgba(90, 45, 140, 0.7), transparent);
            border-radius: 15px 15px 0 0;
        }
        .paper-container::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to top, rgba(90, 45, 140, 0.7), transparent);
            border-radius: 0 0 15px 15px;
        }
        .admin-seal {
            position: absolute;
            top: -25px;
            right: -25px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, #d4af37 40%, #b8860b 100%);
            border-radius: 50%;
            border: 3px solid #8b735b;
            box-shadow: 0 0 25px rgba(255, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transform: rotate(15deg);
            animation: pulse 4s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: rotate(15deg) scale(1); }
            50% { transform: rotate(15deg) scale(1.05); box-shadow: 0 0 35px rgba(255, 0, 0, 0.8); }
        }
        .admin-seal::before {
            content: "A";
            font-size: 50px;
            color: #b22222;
            font-weight: bold;
            font-family: 'Times New Roman', serif;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 10px 25px;
            background: linear-gradient(to right, #3a1b5a, #5a2d8c);
            border: 2px solid #9370DB;
            border-radius: 30px;
            color: #FFD700;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            z-index: 1;
            font-family: 'Montserrat Alternates', sans-serif;
        }
        .back-link::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .back-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(187, 85, 211, 0.5);
            color: white;
        }
        .back-link:hover::before {
            left: 100%;
        }
        .back-link i {
            margin-right: 10px;
            transition: transform 0.3s;
        }
        .back-link:hover i {
            transform: translateX(-5px);
        }
        .paper-header {
            font-size: 2.2rem;
            color: #FFA500;
            margin-bottom: 15px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
            text-shadow: 0 0 10px rgba(187, 85, 211, 0.7);
        }
        .paper-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 30%;
            right: 30%;
            height: 2px;
            background: linear-gradient(to right, transparent, #BA55D3, #FFD700, #BA55D3, transparent);
        }
        .paper-subject {
            font-size: 1.5rem;
            color: #BA55D3;
            margin-bottom: 25px;
            text-align: center;
            font-weight: bold;
            font-family: 'Montserrat Alternates', sans-serif;
            letter-spacing: 1px;
        }
        .paper-body {
            font-size: 1.4rem;
            color: #e0c8f0;
            white-space: pre-wrap;
            margin-bottom: 30px;
            line-height: 1.8;
            padding: 0 20px;
            position: relative;
            min-height: 300px;
            background-image: linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 100% 40px;
            background-position: 0 30px;
            text-shadow: 0.5px 0.5px 1px rgba(0,0,0,0.3);
            border-left: 3px solid #5a2d8c;
            padding-left: 25px;
        }
        .paper-body::first-letter {
            font-size: 4rem;
            float: left;
            line-height: 0.8;
            margin-right: 10px;
            color: #FFA500;
            font-weight: bold;
            text-shadow: 0 0 8px rgba(187, 85, 211, 0.8);
        }
        .paper-attachment {
            display: inline-flex;
            align-items: center;
            margin: 20px 20px 0 0;
            padding: 12px 25px;
            background: linear-gradient(to bottom, #3a1b5a, #2a1245);
            border: 2px solid #9370DB;
            border-radius: 30px;
            color: #FFD700;
            font-weight: bold;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            font-family: 'Montserrat Alternates', sans-serif;
        }
        .paper-attachment::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle, rgba(187, 85, 211, 0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .paper-attachment:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(187, 85, 211, 0.5);
            color: white;
            border-color: #FFA500;
        }
        .paper-attachment:hover::before {
            opacity: 1;
        }
        .paper-attachment i {
            margin-right: 12px;
            font-size: 1.6rem;
            transition: transform 0.3s;
        }
        .paper-attachment:hover i {
            transform: scale(1.3);
        }
        .admin-controls {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #5a2d8c;
            text-align: center;
        }
        .delete-btn {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(to right, #8b0000, #b22222);
            border: 2px solid #ff4500;
            border-radius: 40px;
            color: #FFD700;
            font-weight: bold;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(139, 0, 0, 0.4);
            font-family: 'Montserrat Alternates', sans-serif;
            position: relative;
            overflow: hidden;
        }
        .delete-btn::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -60%;
            width: 20px;
            height: 200%;
            background: rgba(255,255,255,0.3);
            transform: rotate(25deg);
            transition: all 0.8s;
        }
        .delete-btn:hover {
            background: linear-gradient(to right, #b22222, #ff0000);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(255, 0, 0, 0.6);
        }
        .delete-btn:hover::before {
            left: 120%;
        }
        .admin-note {
            margin-top: 25px;
            color: #BA55D3;
            font-size: 1rem;
            text-align: center;
            font-style: italic;
            border: 1px dashed #5a2d8c;
            padding: 15px;
            border-radius: 12px;
            background: rgba(25, 10, 45, 0.5);
        }
        @media (max-width: 768px) {
            .paper-container {
                padding: 35px 20px;
                margin: 20px 15px;
            }
            .paper-header {
                font-size: 1.8rem;
            }
            .paper-subject {
                font-size: 1.3rem;
            }
            .paper-body {
                font-size: 1.2rem;
                min-height: 200px;
                padding: 0 10px;
                padding-left: 15px;
            }
            .paper-body::first-letter {
                font-size: 3rem;
            }
            .admin-seal {
                width: 80px;
                height: 80px;
                top: -15px;
                right: -15px;
            }
            .admin-seal::before {
                font-size: 35px;
            }
            .delete-btn {
                padding: 12px 25px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="./admin_panel">Back</a>
        <a href="./compose">Новое письмо</a>
    </div>
    <div class="content-main">
        <div class="paper-container">
            <div class="admin-seal"></div>
            <a class="back-link" href="admin_panel"><i class="fas fa-arrow-left"></i> Назад к письмам</a>
            <div class="paper-header">Письмо от: <?= htmlspecialchars($msg['from_user']) ?></div>
            <div class="paper-subject">Тема: <?= htmlspecialchars($msg['subject']) ?></div>
            <div class="paper-body"><?= htmlspecialchars($body) ?></div>
            <div class="attachment-container">
            <?php
            if (!empty($msg['attachment_path'])) {
                $real_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $msg['attachment_path'];
                if (file_exists($real_path)) {
                    $enc_data = file_get_contents($real_path);
                    [$enc_file, $file_iv] = explode("::", $enc_data);
                    $attachment = openssl_decrypt($enc_file, 'AES-256-CBC', $aes_key, 0, base64_decode($file_iv));
                    $download_name = basename($msg['attachment_path']);
                    $tmp_file = sys_get_temp_dir() . "/admin_" . $download_name;
                    file_put_contents($tmp_file, $attachment);
                    echo "<a class='paper-attachment' href='/{$msg['attachment_path']}' download>📎 Скачать оригинал (зашифр.)</a> ";
                    echo "<a class='paper-attachment' href='download_attachment.php?id={$msg['id']}&admin=1' download>📥 Скачать расшифрованное</a>";
                }
            }
            ?>
            <div class="admin-controls">
                <form method="post">
                    <input type="hidden" name="delete" value="1">
                    <button type="submit" class="delete-btn" onclick="return confirm('Удалить письмо и вложение?')">
                        <i class="fas fa-trash-alt"></i> Удалить письмо
                    </button>
                </form>
                <div class="admin-note">
                    Административный доступ: это письмо было расшифровано с использованием корневого ключа Бездны
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $_POST['delete'] == '1') {
    if (!empty($msg['attachment_path'])) {
        $real_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $msg['attachment_path'];
        if (file_exists($real_path)) {
            unlink($real_path);
        }
    }
    $stmt = $pdo->prepare("DELETE FROM mail WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='admin_panel';</script>";
    exit;
}