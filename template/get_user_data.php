<?php
session_start();
require_once 'conn.php';

$error_list = ['connection_error','no_user','unverefy','banned', 'no_invent'];
$error = '';

$conn = mysqli_connect($host, $log, $password_sql, $database) or die($error = 'connection_error');

$login = $_SESSION['user'];
$user = mysqli_fetch_assoc($conn->query("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'"));
if (!$user) die($error = "no_user");

if ($user['lvl'] == 0) die($error = "unverefy");
if ($user['lvl'] == 1) die($error = "banned");

$userId = $user['id'];
$invent = mysqli_fetch_assoc($conn->query("SELECT * FROM invent WHERE id_user = '$userId'"));
if (!$invent) die ($error = "no_invent");

$coin = $invent['coins'];
$kase = $invent['kase'];
$sakura = $invent['sakura'];