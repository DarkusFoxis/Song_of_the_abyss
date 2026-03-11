<?php
require_once("./system/tools_check.php");
require_once("./system/config.php");

$stmt = $pdo->prepare("SELECT * FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
$mail_user = $stmt->fetch();

$messages = [];
if ($mail_user) {
    $my_mail_id = $mail_user['id'];
    $stmt = $pdo->prepare("SELECT m.*, mu.username AS sender_username, mu2.username AS recipient_username FROM mail m JOIN mail_user mu ON m.sender_id = mu.id JOIN mail_user mu2 ON m.recipient_id = mu2.id WHERE m.sender_id = ? OR m.recipient_id = ? ORDER BY m.sent_at DESC");
    $stmt->execute([$my_mail_id, $my_mail_id]);
    $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Почта | Abyss</title>
    <link rel="icon" href="../../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/jquery-3.7.1.min.js"></script>
    <style>
        .mail-list-container {
            max-width: 800px;
            margin: 40px auto;
            background: rgba(20, 15, 35, 0.92);
            border-radius: 20px;
            padding: 35px 30px;
            border: 1px solid rgba(229, 36, 255, 0.3);
            box-shadow: 
                0 0 25px rgba(187, 85, 211, 0.4),
                0 0 50px rgba(63, 0, 113, 0.3) inset;
            position: relative;
            overflow: hidden;
        }
        .mail-list-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3F0071, #BA55D3, #3F0071);
            animation: borderPulse 4s infinite;
        }
        @keyframes borderPulse {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .mail-list-title {
            font-size: 2.4rem;
            color: #FFD700;
            margin-bottom: 25px;
            text-align: center;
            font-family: 'Montserrat Alternates', sans-serif;
            text-shadow: 0 0 10px rgba(187, 85, 211, 0.7);
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 15px;
        }
        .mail-list-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 25%;
            right: 25%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #BA55D3, #FFD700, #BA55D3, transparent);
        }
        .mail-new-btn {
            display: block;
            margin: 0 auto 30px auto;
            padding: 14px 35px;
            font-size: 1.1rem;
            letter-spacing: 1px;
            box-shadow: 0 0 20px rgba(187, 85, 211, 0.5);
        }
        .mail-card {
            background: rgba(15, 10, 30, 0.7);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(187, 85, 211, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(4px);
        }
        .mail-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: rgba(187, 85, 211, 0.3);
        }
        .mail-card:hover {
            transform: translateY(-5px);
            border-color: rgba(229, 36, 255, 0.6);
            box-shadow: 0 5px 25px rgba(187, 85, 211, 0.3);
            background: rgba(25, 20, 45, 0.8);
        }
        .mail-card:hover::before {
            background: linear-gradient(90deg, #3F0071, #BA55D3, #3F0071);
            animation: borderPulse 2s infinite;
        }
        .mail-card-header {
            font-size: 1.15rem;
            color: #FFA500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .mail-card-header i {
            margin-right: 10px;
            color: #BA55D3;
        }
        .mail-card-subject {
            font-size: 1.3rem;
            color: #fff;
            margin-bottom: 6px;
            font-weight: 500;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }
        .mail-card-subject i {
            margin-right: 10px;
            color: #9370DB;
        }
        .mail-card-date {
            font-size: 0.95rem;
            color: #aaa;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .mail-card-date i {
            margin-right: 8px;
            color: #9370DB;
        }
        .mail-card-link {
            display: inline-flex;
            align-items: center;
            color: #BA55D3;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            padding: 8px 20px;
            border-radius: 30px;
            background: rgba(58, 0, 97, 0.3);
            border: 1px solid rgba(187, 85, 211, 0.4);
        }
        .mail-card-link i {
            margin-right: 8px;
            transition: transform 0.3s;
        }
        .mail-card-link:hover {
            color: #FFD700;
            background: rgba(90, 0, 150, 0.5);
            box-shadow: 0 0 15px rgba(187, 85, 211, 0.4);
            border-color: rgba(229, 36, 255, 0.7);
        }
        .mail-card-link:hover i {
            transform: translateX(3px);
        }
        .empty-message {
            text-align: center;
            color: #aaa;
            font-size: 1.2rem;
            padding: 30px;
            border: 2px dashed rgba(187, 85, 211, 0.3);
            border-radius: 15px;
            margin: 20px 0;
        }
        .alert-messages-container {
            max-width: 90%;
            margin: 0 auto 25px auto;
        }
        .alert-message {
            padding: 18px 25px;
            border-radius: 15px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            border: 1px solid transparent;
            backdrop-filter: blur(5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.3);
            animation: fadeIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transition: all 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-25px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-message i {
            font-size: 1.8rem;
            margin-right: 15px;
            flex-shrink: 0;
            text-shadow: 0 0 10px rgba(255,255,255,0.3);
        }
        .alert-message span {
            flex-grow: 1;
            font-size: 1.15rem;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        .alert-message .close-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            padding: 8px;
            margin-left: 15px;
            font-size: 1.4rem;
            transition: all 0.3s ease;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .alert-message .close-btn:hover {
            opacity: 1;
            background: rgba(255,255,255,0.15);
        }
        .alert-error {
            background: rgba(139, 0, 0, 0.25);
            border-color: rgba(255, 80, 80, 0.35);
            color: #ffcccc;
            border-left: 5px solid #ff4d4d;
        }
        .alert-error i {
            color: #ff4d4d;
        }
        .alert-error::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #8B0000, #ff4d4d, #8B0000);
            animation: borderPulse 3s infinite;
        }
        .alert-success {
            background: rgba(0, 100, 0, 0.25);
            border-color: rgba(100, 255, 100, 0.35);
            color: #ccffcc;
            border-left: 5px solid #4dff4d;
        }
        .alert-success i {
            color: #4dff4d;
        }
        .alert-success::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #006400, #4dff4d, #006400);
            animation: borderPulse 3s infinite;
        }
        @media (max-width: 768px) {
            .mail-list-container {
                padding: 25px 15px;
                margin: 20px 15px;
            }
            .mail-list-title {
                font-size: 1.8rem;
            }
            .mail-new-btn {
                padding: 12px 25px;
                font-size: 1rem;
            }
            .alert-message {
                padding: 15px 20px;
                flex-direction: column;
                text-align: center;
            }
            .alert-message i {
                margin-right: 0;
                margin-bottom: 12px;
            }
            .alert-message .close-btn {
                margin-left: 0;
                margin-top: 12px;
            }
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="../main">Back</a>
    <?php if ($user_lvl >= 5) {
        echo '<a href="./admin_panel">Админ панель</a>';
    }?>
    <a href="./compose">Новое письмо</a>
</div>
<div class="content-main">
    <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php if(isset($_SESSION['mail_error']) || isset($_SESSION['mail_great'])): ?>
                        <div class="alert-messages-container">
                            <?php if(isset($_SESSION['mail_error'])): ?>
                                <div class="alert-message alert-error">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span><?= htmlspecialchars($_SESSION['mail_error'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <button class="close-btn"><i class="fas fa-times"></i></button>
                                </div>
                                <?php unset($_SESSION['mail_error']); ?>
                            <?php endif; ?>
                            <?php if(isset($_SESSION['mail_great'])): ?>
                                <div class="alert-message alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?= htmlspecialchars($_SESSION['mail_great'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <button class="close-btn"><i class="fas fa-times"></i></button>
                                </div>
                                <?php unset($_SESSION['mail_great']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mail-list-container">
                        <div class="mail-list-title">Входящие и исходящие письма</div>
                        <?php if (!$mail_user): ?>
                            <div style="text-align:center;color:#aaa;font-size:1.1rem; margin-bottom:18px;">У вас нет почтового профиля.<br>Создайте его, чтобы пользоваться почтой.</div>
                            <a class="button mail-new-btn" href="create_mail_user">Создать почтовый профиль</a>
                        <?php else: ?>
                            <a class="button mail-new-btn" href="compose">➕ Новое письмо</a>
                            <?php if (empty($messages)): ?>
                                <div style="text-align:center;color:#aaa;font-size:1.1rem;">Нет писем</div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="mail-card">
                                        <div class="mail-card-header">
                                            <i class="fas fa-user"></i>
                                            <?= ($msg['sender_id'] == $mail_user['id']) ? 'Кому: <b>' . htmlspecialchars($msg['recipient_username']) . '</b>' : 'От: <b>' . htmlspecialchars($msg['sender_username']) . '</b>' ?>
                                        </div>
                                        <div class="mail-card-subject">
                                            <i class="fas fa-envelope"></i>
                                            <?= htmlspecialchars($msg['subject']) ?>
                                        </div>
                                        <div class="mail-card-date">
                                            <i class="far fa-clock"></i>
                                            <?= date('d.m.Y H:i', strtotime($msg['sent_at'])) ?>
                                        </div>
                                        <a class="mail-card-link" href="decrypt?id=<?= $msg['id'] ?>">
                                            <i class="fas fa-lock-open"></i>Читать письмо
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.close-btn').click(function() {
        $(this).closest('.alert-message').animate({
            opacity: 0,
            height: 0,
            paddingTop: 0,
            paddingBottom: 0,
            marginBottom: 0
        }, 400, function() {
            $(this).remove();
        });
    });
    setTimeout(function() {
        $('.alert-message').each(function() {
            $(this).animate({
                opacity: 0,
                height: 0,
                paddingTop: 0,
                paddingBottom: 0,
                marginBottom: 0
            }, 600, function() {
                $(this).remove();
            });
        });
    }, 7000);
});
</script>
</body>
</html>
<?php 
session_write_close();
?>