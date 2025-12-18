<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Ошибка: Необходимо авторизоваться.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Ошибка соединения: " . mysqli_connect_error();
    exit;
}

$login = $_SESSION['user'];
$user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
$result = $conn->query($user_query);
$user = $result->fetch_assoc();
$userId = $user['id'];

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$ageLimit = $_POST['age_limit'] ?? 0;
$storyContent = $_POST['story'] ?? '';

if (empty($title) || strlen($title) < 10) {
    echo json_encode(['success' => false, 'message' => 'Название рассказа должно содержать минимум 10 символов!!']);
    exit;
}

if (empty($description) || strlen($description) < 30) {
    echo json_encode(['success' => false, 'message' => 'Описание должно содержать минимум 30 символов!']);
    exit;
}

if (empty($storyContent) || strlen($storyContent) < 500) {
    echo json_encode(['success' => false, 'message' => 'Содержание рассказа должно содержать минимум 500 символов!']);
    exit;
}

$allowedAgeLimits = [0, 12, 16, 18];
if (!in_array((int)$ageLimit, $allowedAgeLimits)) {
    echo json_encode(['success' => false, 'message' => 'Некорректное возрастное ограничение.']);
    exit;
}

$iconPath = './icon/base.png';
if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = './icon/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($fileInfo, $_FILES['icon']['tmp_name']);
    
    if (!in_array($detectedType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Недопустимый тип файла. Разрешены: JPG, PNG, WebP']);
        exit;
    }

    $extension = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('story_icon_', true) . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['icon']['tmp_name'], $targetPath)) {
        $iconPath = $targetPath;
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке иконки.']);
        exit;
    }
}

$title = $conn->real_escape_string($title);
$description = $conn->real_escape_string($description);
$storyContent = $conn->real_escape_string($storyContent);
$iconPath = $iconPath ? $conn->real_escape_string($iconPath) : 'NULL';

$sql = "INSERT INTO story (id_user, title, description, age_limit, story, icon) VALUES ($userId, '$title', '$description', $ageLimit, '$storyContent', '$iconPath')";

if ($conn->query($sql)) {
    $story_id = $conn->insert_id;
    $url = 'https://so-ta.ru/story/story?id='. $story_id;
    if (strlen($description) > 200) {
        $story_desc = substr($description, 0, 200) . '...';
    } else {
        $story_desc = $description;
    }
    $query_sql = "INSERT INTO url (url, title, description, keywords, date_add, id_user) VALUES ('$url', '$title', '$story_desc', 'Рассказы', NOW(), $userId)";
    if ($conn->query($query_sql)) {
        echo json_encode(['success' => true, 'message' => 'Рассказ успешно опубликован!']);
    } else {
    error_log("Ошибка SQL: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении в базу данных.']);
    }
} else {
    error_log("Ошибка SQL: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении в базу данных.']);
}

$conn->close();
session_write_close();