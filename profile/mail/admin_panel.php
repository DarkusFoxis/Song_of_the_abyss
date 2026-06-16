<?php
require_once("./system/tools_check.php");
require_once("./system/config.php");

if ($user_lvl < 5) {
    $_SESSION['mail_error'] = "Доступ запрещён: недостаточно прав.";
    header("Location: main");
    exit;
}

$stmt = $pdo->query("SELECT m.id, m.subject, m.sent_at, mu.username AS sender, mu2.username AS recipient FROM mail m JOIN mail_user mu ON m.sender_id = mu.id JOIN mail_user mu2 ON m.recipient_id = mu2.id ORDER BY m.sent_at DESC LIMIT 100");
$mails = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель писем | Abyss</title>
    <link rel="icon" href="../../img/icon.png">
    <link rel="stylesheet" href="../../style/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/jquery-3.7.1.min.js"></script>
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 40px auto;
            background: rgba(15, 10, 30, 0.92);
            border-radius: 20px;
            padding: 40px 35px;
            border: 1px solid rgba(229, 36, 255, 0.3);
            box-shadow: 
                0 0 25px rgba(187, 85, 211, 0.4),
                0 0 50px rgba(63, 0, 113, 0.3) inset;
            position: relative;
            overflow: hidden;
            font-family: 'Montserrat Alternates', sans-serif;
        }
        .admin-container::before {
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
        .admin-header {
            position: relative;
            padding-bottom: 25px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(187, 85, 211, 0.3);
        }
        .admin-header h2 {
            font-size: 2.4rem;
            color: #FFD700;
            text-align: center;
            text-shadow: 0 0 15px rgba(187, 85, 211, 0.8);
            letter-spacing: 1px;
            margin: 0;
        }
        .admin-header::after {
            content: "Административный доступ";
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            text-align: center;
            color: #BA55D3;
            font-size: 1.1rem;
            letter-spacing: 2px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 25px;
            color: #BA55D3;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            padding: 8px 20px;
            border-radius: 30px;
            background: rgba(58, 0, 97, 0.3);
            border: 1px solid rgba(187, 85, 211, 0.4);
            transition: all 0.3s ease;
        }
        .back-link i {
            margin-right: 8px;
            transition: transform 0.3s;
        }
        .back-link:hover {
            color: #FFD700;
            background: rgba(90, 0, 150, 0.5);
            box-shadow: 0 0 15px rgba(187, 85, 211, 0.4);
            border-color: rgba(229, 36, 255, 0.7);
        }
        .back-link:hover i {
            transform: translateX(-3px);
        }
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
            margin: 25px 0;
        }
        .admin-table thead th {
            background: rgba(58, 0, 97, 0.7);
            color: #FFD700;
            font-weight: bold;
            padding: 16px 15px;
            text-align: left;
            font-size: 1.15rem;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid rgba(229, 36, 255, 0.5);
        }
        .admin-table thead th:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        .admin-table thead th:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .admin-table tbody tr {
            background: rgba(30, 20, 50, 0.7);
            transition: all 0.3s ease;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .admin-table tbody tr:hover {
            background: rgba(45, 30, 70, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(187, 85, 211, 0.3);
        }
        .admin-table td {
            padding: 14px 15px;
            color: #FFE4E1;
            border: none;
            position: relative;
        }
        .admin-table tr td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        .admin-table tr td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .admin-table tr:nth-child(even) {
            background: rgba(25, 15, 45, 0.7);
        }
        .admin-table tr:nth-child(even):hover {
            background: rgba(40, 25, 65, 0.9);
        }
        .admin-link {
            display: inline-flex;
            align-items: center;
            color: #BA55D3;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 30px;
            background: rgba(58, 0, 97, 0.3);
            border: 1px solid rgba(187, 85, 211, 0.4);
        }
        .admin-link i {
            margin-right: 8px;
            transition: transform 0.3s;
        }
        .admin-link:hover {
            color: #FFD700;
            background: rgba(90, 0, 150, 0.5);
            box-shadow: 0 0 15px rgba(187, 85, 211, 0.4);
            border-color: rgba(229, 36, 255, 0.7);
        }
        .admin-link:hover i {
            transform: scale(1.2);
        }
        .id-cell {
            font-family: monospace;
            font-size: 1.1rem;
            color: #FFA500;
            font-weight: bold;
        }
        .date-cell {
            font-family: monospace;
            font-size: 0.95rem;
            color: #aaa;
        }
        @media (max-width: 992px) {
            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }
        @media (max-width: 768px) {
            .admin-container {
                padding: 30px 20px;
                margin: 20px 15px;
            }
            .admin-header h2 {
                font-size: 1.8rem;
            }
            .admin-table thead th {
                font-size: 1rem;
                padding: 12px 10px;
            }
            .admin-table td {
                padding: 12px 10px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="./main">Back</a>
        <a href="./compose">Новое письмо</a>
    </div>
    <div class="content-main">
        <div class="admin-container">
            <div class="admin-header">
                <h2>Все письма</h2>
            </div>
            <a class="back-link" href="main.php">
                <i class="fas fa-arrow-left"></i> Назад к письмам
            </a>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Тема</th>
                        <th>Отправитель</th>
                        <th>Получатель</th>
                        <th>Дата</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($mails as $mail): ?>
                    <tr>
                        <td class="id-cell"><?= $mail['id'] ?></td>
                        <td><?= htmlspecialchars($mail['subject']) ?></td>
                        <td><?= htmlspecialchars($mail['sender']) ?></td>
                        <td><?= htmlspecialchars($mail['recipient']) ?></td>
                        <td class="date-cell"><?= htmlspecialchars($mail['sent_at']) ?></td>
                        <td>
                            <a class="admin-link" href="admin_read.php?id=<?= $mail['id'] ?>">
                                <i class="fas fa-eye"></i> Просмотр
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php 
session_write_close();
?>