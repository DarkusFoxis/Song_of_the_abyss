<?php
require_once("config.php");
session_start();

$login = $_SESSION['user'] ?? null;

if (!$login) {
    header("Location: ../login");
    exit;
}

$stmt = $pdo->prepare("SELECT u.id, g.lvl, t.mail FROM users u JOIN site_group g ON u.permissions = g.name LEFT JOIN tools t ON u.id = t.user_id WHERE u.login = ?");
$stmt->execute([$login]);
$row = $stmt->fetch();

if (!$row || $row['lvl'] < 2 || $row['mail'] != 1) {
    $_SESSION['error'] = "У вас недостаточно прав, или у вас не приобретён доступ к инструментам сайта! Приобрести доступ к инструментарию в можете на <a href='./shop' class='link'>рынке бездны</a>.";
    header("Location: ../main");
    exit;
}

$user_id = $row['id'];
$user_lvl = $row['lvl'];