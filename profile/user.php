<?php
session_start();
require_once '../template/conn.php';
$conn = new mysqli($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
$userId = $_GET['id'];
$sql = "SELECT * FROM post WHERE id_user = '$postId'";
$result = $conn->query($sql);
$result_com = $conn->query($comm);

$comments = [];
if ($result->num_rows > 0) {
    while ($row = $result_com->fetch_assoc()) {
        $comments[] = [
            'username' => $row['username'],
            'avatar' => $row['avatar'],
            'text' => $row['text']
        ];
    }
}
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
}