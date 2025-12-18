<?php
require_once("./system/tools_check.php");

$stmt = $pdo->prepare("SELECT * FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
$mail_user = $stmt->fetch();

if (!$mail_user) {
    $_SESSION['mail_error'] = "У вас нет почтового профиля! Создайте его!";
    header("Location: main");
    exit;
}

$reply_to = $_GET['reply_to'] ?? '';
$reply_subject = $_GET['subject'] ?? '';

$prefilled_to = '';
$prefilled_subject = '';

if ($reply_to) {
    $reply_to = str_replace('@abyss', '', $reply_to);
    $prefilled_to = htmlspecialchars($reply_to) . '@abyss';
}

if ($reply_subject) {
    if (stripos($reply_subject, 'Ответ на') === false) {
        $prefilled_subject = 'Ответ на "' . htmlspecialchars($reply_subject) . '"';
    } else {
        $prefilled_subject = $reply_subject;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отправка письма</title>
    <link rel="icon" href="../../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/jquery-3.7.1.min.js"></script>
    <style>
        .mail-container {
            max-width: 800px;
            margin: 40px auto;
            background: rgba(20, 15, 35, 0.92);
            border-radius: 20px;
            padding: 40px 35px;
            border: 1px solid rgba(229, 36, 255, 0.3);
            box-shadow: 
                0 0 25px rgba(187, 85, 211, 0.4),
                0 0 50px rgba(63, 0, 113, 0.3) inset;
            position: relative;
            overflow: hidden;
        }
        .mail-container::before {
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
        .header h1 {
            font-size: 2.8rem;
            color: #FFD700;
            text-align: center;
            font-family: 'Montserrat Alternates', sans-serif;
            text-shadow: 0 0 15px rgba(187, 85, 211, 0.8);
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 20px;
            margin-bottom: 40px;
        }
        .header h1::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 20%;
            right: 20%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #BA55D3, #FFD700, #BA55D3, transparent);
            border-radius: 3px;
        }
        .mail-container label {
            display: block;
            margin-top: 25px;
            margin-bottom: 10px;
            color: #BA55D3;
            font-size: 1.2rem;
            font-weight: bold;
            position: relative;
            padding-left: 35px;
        }
        .mail-container label::before {
            content: "➤";
            position: absolute;
            left: 0;
            top: 0;
            color: #FFA500;
            font-size: 1.3rem;
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .mail-container input[type="text"],
        .mail-container input[type="file"] {
            width: 100%;
            padding: 14px 22px;
            background: rgba(15, 10, 30, 0.7);
            border: 1px solid rgba(187, 85, 211, 0.25);
            border-radius: 12px;
            color: #FFE4E1;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            outline: none;
            font-family: 'Montserrat Alternates', sans-serif;
        }
        .mail-container input[type="text"]:focus,
        .mail-container input[type="file"]:focus {
            border-color: rgba(229, 36, 255, 0.6);
            box-shadow: 0 0 20px rgba(187, 85, 211, 0.4);
            background: rgba(25, 20, 45, 0.8);
        }
        .mail-container textarea {
            width: 100%;
            padding: 20px;
            background: rgba(15, 10, 30, 0.7);
            border: 1px solid rgba(187, 85, 211, 0.25);
            border-radius: 15px;
            color: #FFE4E1;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            outline: none;
            resize: vertical;
            min-height: 250px;
            font-family: 'Montserrat Alternates', sans-serif;
            background-image: radial-gradient(circle, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .mail-container textarea:focus {
            border-color: rgba(229, 36, 255, 0.6);
            box-shadow: 0 0 25px rgba(187, 85, 211, 0.4);
            background: rgba(25, 20, 45, 0.8);
        }
        .mail-container button[type="submit"] {
            display: block;
            width: 100%;
            padding: 16px;
            margin-top: 35px;
            background: linear-gradient(135deg, rgba(58, 0, 97, 0.8) 0%, rgba(90, 0, 150, 0.8) 100%);
            border: 2px solid rgba(229, 36, 255, 1);
            border-radius: 40px;
            color: #FFD700;
            font-size: 1.3rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.5);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: 'Montserrat Alternates', sans-serif;
            position: relative;
            overflow: hidden;
        }
        .mail-container button[type="submit"]::before {
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
        .mail-container button[type="submit"]:hover {
            background: linear-gradient(135deg, rgba(90, 0, 150, 0.9) 0%, rgba(120, 0, 200, 0.9) 100%);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.7);
        }
        .mail-container button[type="submit"]:hover::before {
            left: 120%;
        }
        .mail-container button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.5);
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
            .mail-container {
                padding: 30px 20px;
                margin: 20px 15px;
            }
            .header h1 {
                font-size: 2.2rem;
                margin-bottom: 30px;
            }
            .mail-container label {
                font-size: 1.1rem;
                padding-left: 30px;
            }
            .mail-container input[type="text"],
            .mail-container input[type="file"] {
                padding: 12px 18px;
                font-size: 1rem;
            }
            .mail-container textarea {
                padding: 15px;
                font-size: 1rem;
                min-height: 200px;
            }
            .mail-container button[type="submit"] {
                padding: 14px;
                font-size: 1.1rem;
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
    <a href="./main">Back</a>
</div>
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php if(isset($_SESSION['compose_error']) || isset($_SESSION['compose_great'])): ?>
                    <div class="alert-messages-container">
                        <?php if(isset($_SESSION['compose_error'])): ?>
                            <div class="alert-message alert-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><?= htmlspecialchars($_SESSION['compose_error'], ENT_QUOTES, 'UTF-8') ?></span>
                                <button class="close-btn"><i class="fas fa-times"></i></button>
                            </div>
                            <?php unset($_SESSION['compose_error']); ?>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['compose_great'])): ?>
                            <div class="alert-message alert-success">
                                <i class="fas fa-check-circle"></i>
                                <span><?= htmlspecialchars($_SESSION['compose_great'], ENT_QUOTES, 'UTF-8') ?></span>
                                <button class="close-btn"><i class="fas fa-times"></i></button>
                            </div>
                            <?php unset($_SESSION['compose_great']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="header">
                    <h1>Отправка письма</h1>
                </div>
                <form class="mail-container" method="POST" action="send" enctype="multipart/form-data">
                    <label>Кому (например, username@abyss):</label><br>
                    <input type="text" name="to" placeholder="username@abyss" value="<?= $prefilled_to ?>" required><br><br>
            
                    <label>Тема:</label><br>
                    <input type="text" name="subject" minlength="3" maxlength="100" value='<?= $prefilled_subject ?>' required><br><br>

                    <label>Сообщение:</label><br>
                    <textarea name="body" rows="10" minlength="5" required></textarea><br><br>

                    <label>Вложение (необязательно):</label><br>
                    <input type="file" name="attachment"><br><br>

                    <button type="submit" class="button">Отправить</button>
                </form>
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