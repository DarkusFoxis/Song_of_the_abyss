<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Вы не в аккаунте!";
    exit();
}
require_once '../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Ошибка соединения: " . mysqli_connect_error();
    exit;
}

$login = $_SESSION['user'];
$query = "SELECT * FROM personal_data WHERE login = '$login'";
$result = $conn->query($query);
$data = $result -> fetch_assoc();
$query = "SELECT * FROM users WHERE login = '$login'";
$result = $conn->query($query);
$user = $result -> fetch_assoc();
$userId = $user['id'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$rarities = isset($_GET['rarities']) ? (array)$_GET['rarities'] : [];

$sql = "SELECT *, COUNT(s.id_stikers) AS count FROM stikers s WHERE id_user = '$userId'";

if (!empty($search)) {
    $escapedSearch = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (s.stikers LIKE '%$escapedSearch%' OR s.description LIKE '%$escapedSearch%')";
}

if (!empty($rarities)) {
    $rarityList = array_map(function($rarity) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $rarity) . "'";
    }, $rarities);
    
    $rarityStr = implode(',', $rarityList);
    $sql .= " AND s.rarity IN ($rarityStr)";
}

$sql .= " GROUP BY s.stikers, s.rarity ORDER BY CASE s.rarity WHEN 'com' THEN 1 WHEN 'rar' THEN 2 WHEN 'epic' THEN 3 WHEN 'leg' THEN 4 WHEN 'myst' THEN 5 ELSE 6 END, s.stikers";

$result = $conn->query($sql);
$stiker_data = '';
$total_count = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rarityClass = 'rarity-' . $row['rarity'];

        if ($row['rarity'] == "myst") {
            $stiker_dot = ".webm";
            $media = '<video class="sticker-media" src="./stikers/' . $row['stikers'] . '_' . $row['rarity'] . $stiker_dot . '" alt="' . $row['stikers'] .'" title="' . $row['description'] . '" autoplay loop muted playsinline></video>';
        } else {
            $stiker_dot = ".webp";
            $media = '<img class="sticker-media" src="./stikers/' . $row['stikers'] . '_' . $row['rarity'] . $stiker_dot . '" alt="' . $row['stikers'] .'" title="' . $row['description'] . '">';
        }

        $stiker_data .= '<div class="card">';
        $stiker_data .= '<div class="card-media">' . $media . '</div>';
        $stiker_data .= '<div class="card-content">';
        $stiker_data .= '<div class="sticker-name">' . $row['stikers'] . '</div>';
        $stiker_data .= '<div class="sticker-rarity ' . $rarityClass . '">' . $row['rarity'] . '</div>';
        $stiker_data .= '<div class="description" title="' . $row['description'] . '">' . $row['description'] . '</div>';
        $stiker_data .= '<div class="sticker-count">Количество: ' . $row['count'] . '</div>';
        $stiker_data .= '<div style="text-align:center;"><button onclick="sellStikers(' . $row['id_stikers'] . ')">Продать</button></div>';
        $stiker_data .= '</div>';
        $stiker_data .= '</div>';
        $total_count += $row['count'];
    }

    if ($_GET['action'] == "first") {
        $stiker_data .= '<script src="./js/stiker.js"></script>';
    }
    $stiker_data = '<div style="width:100%; margin-bottom:15px; padding:10px; text-align:center; background:rgba(0,0,0,0.3); border-radius:5px;">Всего стикеров: ' . $total_count . '</div>' . $stiker_data;
} else {
    $stiker_data = '<div class="col-12 text-center" style="padding:30px; background:rgba(0,0,0,0.2); border-radius:10px;">Кажется, у вас ещё нет стикеров. Участвуйте в активностях и собирайте стикеры!</div>';
}
$conn->close();
echo $stiker_data;
session_write_close();