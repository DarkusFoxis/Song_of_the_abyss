<?php
session_start();
require_once 'conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$login = $_SESSION['user'];
$user_query = "SELECT id FROM users WHERE login = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user_id = $user['id'];
$tools_query = "SELECT tools, bonus_tools FROM tools WHERE user_id = ?";
$stmt = $conn->prepare($tools_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tools_result = $stmt->get_result();
$tools = $tools_result->fetch_assoc();

if (!$tools) {
    echo json_encode(['tools_available' => 0]);
    exit;
}

$tools_available = $tools['tools'] + $tools['bonus_tools'];
echo json_encode(['tools_available' => $tools_available]);

mysqli_close($conn);
session_write_close();