<?php
require_once '../../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    error_log("Ошибка подключения к базе данных: " . mysqli_connect_error());
    echo "Fatal error.";
    exit;
}

if (!isset($_GET['to'])) {
    http_response_code(400);
    exit('Missing "to" parameter.');
}

$linkId = $_GET['to'];

$sql = "SELECT url FROM url WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $linkId);
if (!mysqli_stmt_execute($stmt)) {
    error_log("Ошибка выполнения запроса: " . mysqli_stmt_error($stmt));
    exit;
}
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    http_response_code(404);
    exit();
}

$sql = "UPDATE url SET clicking = clicking + 1 WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $linkId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: " . $row['url']);
exit;