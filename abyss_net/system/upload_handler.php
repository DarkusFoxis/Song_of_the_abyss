<?php
date_default_timezone_set('Europe/Moscow');

require_once __DIR__ . '/../../template/auth.php';
require_once __DIR__ . '/../../template/conn.php';
require_once __DIR__ . '/audio_compressor.php';

auth_start_session();
auth_sync_session_from_token();

function redirect_with_error(string $message): void
{
    $_SESSION['error'] = $message;
    header('Location: core.php');
    exit;
}

function redirect_with_success(string $message): void
{
    $_SESSION['success'] = $message;
    header('Location: core.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_error('Некорректный метод запроса.');
}

$currentUser = auth_get_current_user();
if ($currentUser === null) {
    redirect_with_error('Доступ запрещён. Пожалуйста, авторизуйтесь.');
}

security_require_csrf(true);

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    redirect_with_error('Ошибка подключения к базе данных при обработке загрузки.');
}
mysqli_set_charset($conn, 'utf8mb4');

$userStmt = $conn->prepare(
    "SELECT u.id, sg.lvl, COALESCE(max_audio.latest_upload_time, 0) AS last_upload_ts
     FROM users u
     JOIN site_group sg ON u.permissions = sg.name
     LEFT JOIN (
         SELECT user_id, MAX(data_upload) AS latest_upload_time
         FROM audio
         GROUP BY user_id
     ) max_audio ON u.id = max_audio.user_id
     WHERE u.login = ?
     LIMIT 1"
);
$userStmt->bind_param('s', $currentUser['login']);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$userData) {
    mysqli_close($conn);
    auth_logout_user();
    redirect_with_error('Ошибка получения данных пользователя. Авторизуйтесь снова.');
}

$currentUserId = (int)$userData['id'];
$currentUserLevel = (int)$userData['lvl'];
$currentLastUploadTs = (int)$userData['last_upload_ts'];

if ($currentUserLevel <= 1) {
    mysqli_close($conn);
    redirect_with_error('У вас недостаточно прав для загрузки треков.');
}

if ($currentUserLevel !== 6 && (time() - $currentLastUploadTs) < 5 * 60) {
    mysqli_close($conn);
    redirect_with_error('Кулдаун на загрузку ещё не прошёл. Пожалуйста, подождите.');
}

$audioName = trim((string)($_POST['audio_name'] ?? ''));
$authorName = trim((string)($_POST['author_name'] ?? ''));
$selfAuthorFlag = isset($_POST['self_author']) && $_POST['self_author'] == '1';

if ($audioName === '' || $authorName === '') {
    mysqli_close($conn);
    redirect_with_error('Название трека и имя исполнителя не могут быть пустыми.');
}

$dbSelfAuthor = 0;
if ($selfAuthorFlag) {
    if ($currentUserLevel < 3) {
        mysqli_close($conn);
        redirect_with_error('У вас нет прав публиковать треки под собственным авторством.');
    }
    $dbSelfAuthor = 1;
}

if (!isset($_FILES['audio_file'])) {
    mysqli_close($conn);
    redirect_with_error('Аудиофайл не передан.');
}

$audioAllowed = [
    'audio/mpeg' => 'mp3',
    'audio/wav' => 'wav',
    'audio/x-wav' => 'wav',
    'audio/ogg' => 'ogg',
    'audio/webm' => 'webm',
];

$audioUploadDir = __DIR__ . '/../media/audio';
try {
    $audioStored = security_store_uploaded_file($_FILES['audio_file'], $audioUploadDir, $audioAllowed, 'audio_', 20 * 1024 * 1024);
} catch (RuntimeException $e) {
    mysqli_close($conn);
    redirect_with_error('Ошибка загрузки аудиофайла: ' . $e->getMessage());
}

$compressor = new AudioCompressor();
if ($compressor->isFFmpegAvailable()) {
    $compressor->optimizeInPlace($audioStored['path'], ['quality' => 'medium']);
}

$dbAudioPath = $audioStored['filename'];
$dbCoverPath = 'base_cover.png';

if ($currentUserLevel >= 3 && isset($_FILES['cover_file']) && ($_FILES['cover_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $coverAllowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    $coverUploadDir = __DIR__ . '/../icon';

    try {
        $coverStored = security_store_uploaded_file($_FILES['cover_file'], $coverUploadDir, $coverAllowed, 'cover_', 5 * 1024 * 1024);
        $dbCoverPath = $coverStored['filename'];
    } catch (RuntimeException $e) {
        $_SESSION['warning'] = 'Обложка не загружена: ' . $e->getMessage();
    }
} elseif (isset($_FILES['cover_file']) && ($_FILES['cover_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $_SESSION['warning'] = 'У вас нет прав на загрузку обложки.';
}

$dbDataUpload = date('Y-m-d H:i:s');
$dbNsfw = 0;

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO audio (user_id, self_author, author_name, nsfw, name, path, cover_patch, data_upload)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    mysqli_close($conn);
    redirect_with_error('Ошибка подготовки запроса к базе данных.');
}

mysqli_stmt_bind_param(
    $stmt,
    "iisissss",
    $currentUserId,
    $dbSelfAuthor,
    $authorName,
    $dbNsfw,
    $audioName,
    $dbAudioPath,
    $dbCoverPath,
    $dbDataUpload
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    redirect_with_error('Ошибка при сохранении данных трека в базу.');
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

$successMessage = "Трек '" . security_html($audioName) . "' успешно загружен!";
if (isset($_SESSION['warning'])) {
    $successMessage .= '<br><em>Примечание по обложке: ' . security_html((string)$_SESSION['warning']) . '</em>';
    unset($_SESSION['warning']);
}

redirect_with_success($successMessage);
