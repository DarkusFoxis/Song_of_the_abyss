<?php
session_start();
if(!isset($_SESSION['user'])) {
    exit;
}
include('../template/conn.php');
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

$login = $_SESSION['user'];
$user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
$result = $conn->query($user_query);
if($result->num_rows > 0){
    $user = $result->fetch_assoc();
    $lvl = $user['lvl'];
    if($lvl < 6) {
        exit;
    }
} else {
    exit;
}

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 10;

$query = "SELECT * FROM promo ORDER BY date DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<div class="history-item">';
        echo '<div class="info-row"><span class="info-label">Пользователь:</span><span class="info-value">ID ' . $row['id_user'] . '</span></div>';
        echo '<div class="info-row"><span class="info-label">Промокод:</span><span class="info-value">' . $row['code'] . '</span></div>';
        echo '<div class="info-row"><span class="info-label">Дата:</span><span class="info-value">' . date('d.m.Y в H:i', strtotime($row['date'])) . '</span></div>';
        echo '</div>';
    }
}
session_write_close();