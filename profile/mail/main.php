<?php
require_once("./system/tools_check.php");
require_once("./system/config.php");

$stmt = $pdo->prepare("SELECT * FROM mail_user WHERE user_id = ?");
$stmt->execute([$user_id]);
$mail_user = $stmt->fetch();

$colCheck = $pdo->query("SHOW COLUMNS FROM mail LIKE 'is_read'");
if ($colCheck->rowCount() == 0) {
    $pdo->exec("ALTER TABLE mail ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_GET['action'] === 'get_messages' && $mail_user) {
        $my_mail_id = $mail_user['id'];

        $stmtSent = $pdo->prepare("
            SELECT m.*, mu.username AS recipient_username, m.is_read AS recipient_read
            FROM mail m
            JOIN mail_user mu ON m.recipient_id = mu.id
            WHERE m.sender_id = ?
            ORDER BY m.sent_at DESC
        ");
        $stmtSent->execute([$my_mail_id]);
        $sent = $stmtSent->fetchAll(PDO::FETCH_ASSOC);

        $stmtRecv = $pdo->prepare("
            SELECT m.*, mu.username AS sender_username, m.is_read
            FROM mail m
            JOIN mail_user mu ON m.sender_id = mu.id
            WHERE m.recipient_id = ?
            ORDER BY m.sent_at DESC
        ");
        $stmtRecv->execute([$my_mail_id]);
        $received = $stmtRecv->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['sent' => $sent, 'received' => $received], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_GET['action'] === 'mark_read' && $mail_user) {
        $msg_id = intval($_POST['id'] ?? 0);
        $my_mail_id = $mail_user['id'];
        if ($msg_id > 0) {
            $stmt = $pdo->prepare("UPDATE mail SET is_read = 1 WHERE id = ? AND recipient_id = ?");
            $stmt->execute([$msg_id, $my_mail_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid id']);
        }
        exit;
    }

    echo json_encode(['sent' => [], 'received' => []]);
    exit;
}

$messages = [];
if ($mail_user) {
    $my_mail_id = $mail_user['id'];
    $stmt = $pdo->prepare("
        SELECT m.*, mu.username AS sender_username, mu2.username AS recipient_username, m.is_read
        FROM mail m
        JOIN mail_user mu ON m.sender_id = mu.id
        JOIN mail_user mu2 ON m.recipient_id = mu2.id
        WHERE m.sender_id = ? OR m.recipient_id = ?
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute([$my_mail_id, $my_mail_id]);
    $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Почта Abyss</title>
    <link rel="icon" href="../../img/icon.png">
    <link rel="stylesheet" href="../../style/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/jquery-3.7.1.min.js"></script>
    <style>
        .mail-list-container {
            max-width: 860px;
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
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3F0071, #BA55D3, #3F0071);
            background-size: 200% 100%;
            animation: borderPulse 4s infinite;
        }
        @keyframes borderPulse {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
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
            bottom: 0; left: 25%; right: 25%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #BA55D3, #FFD700, #BA55D3, transparent);
        }
        .mail-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 22px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(187, 85, 211, 0.35);
            box-shadow: 0 0 12px rgba(187, 85, 211, 0.18);
        }
        .mail-tab {
            flex: 1;
            text-align: center;
            padding: 14px 10px;
            font-size: 1.08rem;
            font-weight: 600;
            letter-spacing: .5px;
            cursor: pointer;
            background: rgba(15, 10, 30, 0.75);
            color: #aaa;
            border: none;
            transition: all .3s ease;
            position: relative;
            user-select: none;
        }
        .mail-tab i {
            margin-right: 8px;
        }
        .mail-tab:first-child {
            border-right: 1px solid rgba(187, 85, 211, 0.25);
        }
        .mail-tab:hover {
            background: rgba(58, 0, 97, 0.45);
            color: #ddd;
        }
        .mail-tab.active {
            background: linear-gradient(135deg, rgba(58, 0, 97, 0.7), rgba(187, 85, 211, 0.35));
            color: #FFD700;
            text-shadow: 0 0 8px rgba(255, 215, 0, 0.5);
        }
        .mail-tab .tab-badge {
            display: inline-block;
            background: #BA55D3;
            color: #fff;
            font-size: .72rem;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
            font-weight: 700;
            vertical-align: middle;
        }
        .mail-tab.active .tab-badge {
            background: #FFD700;
            color: #1a0a2e;
        }
        .mail-filter-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-bottom: 18px;
            gap: 10px;
        }
        .mail-filter-bar label {
            color: #ccc;
            font-size: .95rem;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mail-filter-bar input[type="checkbox"] {
            accent-color: #BA55D3;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .mail-tab-panel {
            display: none;
        }
        .mail-tab-panel.active {
            display: block;
            animation: fadeInPanel .35s ease;
        }
        @keyframes fadeInPanel {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
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
            top: 0; left: 0; right: 0;
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
            background-size: 200% 100%;
            animation: borderPulse 2s infinite;
        }
        .mail-card.unread {
            border-left: 4px solid #FFD700;
            background: rgba(25, 20, 50, 0.85);
        }
        .mail-card.unread::before {
            background: linear-gradient(90deg, #FFD700, #BA55D3, #FFD700);
            background-size: 200% 100%;
            animation: borderPulse 2s infinite;
        }
        .mail-card-header {
            font-size: 1.15rem;
            color: #FFA500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }
        .mail-card-header i {
            margin-right: 10px;
            color: #BA55D3;
        }
        .read-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: .82rem;
            padding: 3px 12px;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: .3px;
            margin-left: auto;
        }
        .read-status-badge.read {
            background: rgba(0, 180, 0, 0.2);
            color: #4dff4d;
            border: 1px solid rgba(100, 255, 100, 0.3);
        }
        .read-status-badge.unread {
            background: rgba(255, 160, 0, 0.2);
            color: #FFA500;
            border: 1px solid rgba(255, 165, 0, 0.35);
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
        .mail-loading {
            text-align: center;
            padding: 40px 20px;
            color: #BA55D3;
            font-size: 1.1rem;
        }
        .mail-loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
            display: block;
            margin-bottom: 12px;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
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
            to   { opacity: 1; transform: translateY(0); }
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
        .alert-error i { color: #ff4d4d; }
        .alert-error::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #8B0000, #ff4d4d, #8B0000);
            background-size: 200% 100%;
            animation: borderPulse 3s infinite;
        }
        .alert-success {
            background: rgba(0, 100, 0, 0.25);
            border-color: rgba(100, 255, 100, 0.35);
            color: #ccffcc;
            border-left: 5px solid #4dff4d;
        }
        .alert-success i { color: #4dff4d; }
        .alert-success::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #006400, #4dff4d, #006400);
            background-size: 200% 100%;
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
            .mail-tab {
                font-size: .95rem;
                padding: 12px 6px;
            }
            .mail-card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .read-status-badge {
                margin-left: 0;
                margin-top: 4px;
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
    <?php if ($user_lvl >= 5): ?>
        <a href="./admin_panel">Админ панель</a>
    <?php endif; ?>
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
                    <div class="mail-list-title">Почта Abyss</div>

                    <?php if (!$mail_user): ?>
                        <div style="text-align:center;color:#aaa;font-size:1.1rem; margin-bottom:18px;">
                            У вас нет почтового профиля.<br>Создайте его, чтобы пользоваться почтой.
                        </div>
                        <a class="button mail-new-btn" href="create_mail_user">Создать почтовый профиль</a>
                    <?php else: ?>
                        <a class="button mail-new-btn" href="compose"><i class="fas fa-plus"></i> Новое письмо</a>

                        <div class="mail-tabs">
                            <div class="mail-tab active" data-tab="received">
                                <i class="fas fa-inbox"></i>Полученные
                                <span class="tab-badge" id="received-count">0</span>
                            </div>
                            <div class="mail-tab" data-tab="sent">
                                <i class="fas fa-paper-plane"></i>Отправленные
                                <span class="tab-badge" id="sent-count">0</span>
                            </div>
                        </div>

                        <div class="mail-tab-panel active" id="panel-received">
                            <div class="mail-filter-bar">
                                <label>
                                    <input type="checkbox" id="filter-unread-received">
                                    <span>Только непрочитанные</span>
                                </label>
                            </div>
                            <div id="received-list">
                                <div class="mail-loading">
                                    <i class="fas fa-circle-notch"></i>
                                    Загрузка писем...
                                </div>
                            </div>
                        </div>

                        <div class="mail-tab-panel" id="panel-sent">
                            <div class="mail-filter-bar">
                                <label>
                                    <input type="checkbox" id="filter-unread-sent">
                                    <span>Только непрочитанные получателем</span>
                                </label>
                            </div>
                            <div id="sent-list">
                                <div class="mail-loading">
                                    <i class="fas fa-circle-notch"></i>
                                    Загрузка писем...
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.close-btn').click(function() {
        $(this).closest('.alert-message').animate({
            opacity: 0, height: 0, paddingTop: 0, paddingBottom: 0, marginBottom: 0
        }, 400, function() { $(this).remove(); });
    });
    setTimeout(function() {
        $('.alert-message').each(function() {
            $(this).animate({
                opacity: 0, height: 0, paddingTop: 0, paddingBottom: 0, marginBottom: 0
            }, 600, function() { $(this).remove(); });
        });
    }, 7000);

    let sentMessages = [];
    let receivedMessages = [];

    $('.mail-tab').on('click', function() {
        const tab = $(this).data('tab');
        $('.mail-tab').removeClass('active');
        $(this).addClass('active');
        $('.mail-tab-panel').removeClass('active');
        $('#panel-' + tab).addClass('active');
    });

    function loadMessages() {
        $.getJSON(window.location.pathname + '?action=get_messages', function(data) {
            sentMessages = data.sent || [];
            receivedMessages = data.received || [];
            renderReceived();
            renderSent();
        }).fail(function() {
            $('#received-list').html('<div class="empty-message"><i class="fas fa-exclamation-circle"></i> Ошибка загрузки</div>');
            $('#sent-list').html('<div class="empty-message"><i class="fas fa-exclamation-circle"></i> Ошибка загрузки</div>');
        });
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const mins = String(d.getMinutes()).padStart(2, '0');
        return day + '.' + month + '.' + year + ' ' + hours + ':' + mins;
    }

    function renderReceived() {
        const onlyUnread = $('#filter-unread-received').is(':checked');
        let list = receivedMessages;
        if (onlyUnread) list = list.filter(m => m.is_read == 0);

        const unreadCount = receivedMessages.filter(m => m.is_read == 0).length;
        $('#received-count').text(receivedMessages.length);

        if (list.length === 0) {
            const msg = onlyUnread
                ? '<i class="fas fa-check-circle"></i> Нет непрочитанных писем'
                : '<i class="fas fa-inbox"></i> Нет полученных писем';
            $('#received-list').html('<div class="empty-message">' + msg + '</div>');
            return;
        }

        let html = '';
        list.forEach(function(m) {
            const unreadClass = m.is_read == 0 ? ' unread' : '';
            html += '<div class="mail-card' + unreadClass + '" data-id="' + m.id + '">';
            html += '  <div class="mail-card-header">';
            html += '    <i class="fas fa-user"></i>';
            html += '    От: <b>' + esc(m.sender_username) + '</b>';
            if (m.is_read == 0) {
                html += '    <span class="read-status-badge unread"><i class="fas fa-eye-slash"></i> Непрочитано</span>';
            } else {
                html += '    <span class="read-status-badge read"><i class="fas fa-eye"></i> Прочитано</span>';
            }
            html += '  </div>';
            html += '  <div class="mail-card-subject"><i class="fas fa-envelope"></i>' + esc(m.subject) + '</div>';
            html += '  <div class="mail-card-date"><i class="far fa-clock"></i>' + formatDate(m.sent_at) + '</div>';
            html += '  <a class="mail-card-link" href="decrypt?id=' + m.id + '" data-msg-id="' + m.id + '">';
            html += '    <i class="fas fa-lock-open"></i>Читать письмо';
            html += '  </a>';
            html += '</div>';
        });
        $('#received-list').html(html);
    }

    function renderSent() {
        const onlyUnread = $('#filter-unread-sent').is(':checked');
        let list = sentMessages;
        if (onlyUnread) list = list.filter(m => m.is_read == 0 || m.recipient_read == 0);

        const unreadByRecipient = sentMessages.filter(m => (m.is_read == 0 || m.recipient_read == 0)).length;
        $('#sent-count').text(sentMessages.length);

        if (list.length === 0) {
            const msg = onlyUnread
                ? '<i class="fas fa-check-circle"></i> Все письма прочитаны получателями'
                : '<i class="fas fa-paper-plane"></i> Нет отправленных писем';
            $('#sent-list').html('<div class="empty-message">' + msg + '</div>');
            return;
        }

        let html = '';
        list.forEach(function(m) {
            const isReadByRecipient = (m.is_read == 1 || m.recipient_read == 1);
            html += '<div class="mail-card' + (!isReadByRecipient ? ' unread' : '') + '" data-id="' + m.id + '">';
            html += '  <div class="mail-card-header">';
            html += '    <i class="fas fa-user"></i>';
            html += '    Кому: <b>' + esc(m.recipient_username) + '</b>';
            if (isReadByRecipient) {
                html += '    <span class="read-status-badge read"><i class="fas fa-eye"></i> Прочитано получателем</span>';
            } else {
                html += '    <span class="read-status-badge unread"><i class="fas fa-eye-slash"></i> Непрочитано получателем</span>';
            }
            html += '  </div>';
            html += '  <div class="mail-card-subject"><i class="fas fa-envelope"></i>' + esc(m.subject) + '</div>';
            html += '  <div class="mail-card-date"><i class="far fa-clock"></i>' + formatDate(m.sent_at) + '</div>';
            html += '  <a class="mail-card-link" href="decrypt?id=' + m.id + '">';
            html += '    <i class="fas fa-lock-open"></i>Читать письмо';
            html += '  </a>';
            html += '</div>';
        });
        $('#sent-list').html(html);
    }

    $('#filter-unread-received').on('change', renderReceived);
    $('#filter-unread-sent').on('change', renderSent);

    $(document).on('click', '.mail-card-link', function(e) {
        const msgId = $(this).data('msg-id');
        if (msgId) {
            $.post(window.location.pathname + '?action=mark_read', { id: msgId });
        }
    });

    setInterval(loadMessages, 60000);

    loadMessages();
});
</script>
</body>
</html>
<?php
session_write_close();
?>