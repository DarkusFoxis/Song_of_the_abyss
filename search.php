<?php
session_start();
require_once __DIR__ . '/./template/auth.php';
auth_sync_session_from_token();
$authUser = auth_require_user('/profile/login');

if(!isset($_SESSION['user'])) {
    header("Location: ./profile/login");
} else {
    require_once './template/conn.php';
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        echo "Ошибка соединения: " . mysqli_connect_error();
        exit;
    }
    $login = $_SESSION['user'];
    $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
    $result = $conn->query($user_query);
    $user = $result -> fetch_assoc();
    if ($user['lvl'] < 3) {
        $_SESSION["perm_error"] = "У вас недостаточно прав, для доступа к этой странице.";
        header("Location: 403");
        exit;
    } 
    $baseDir = __DIR__;
    $allowedExtensions = ['mp3'];
    $musicDir = realpath($baseDir) . '/';
    function findMusic($dir) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $GLOBALS['allowedExtensions'])) {
                $relativePath = substr($file->getRealPath(), strlen($GLOBALS['musicDir']));
                $files[] = [
                    'path' => rawurlencode($relativePath), // Безопасное кодирование для URL.
                    'name' => htmlspecialchars(basename($file->getFilename())) // Защита от XSS.
                ];
            }
        }
        return $files;
    }
    $musicFiles = findMusic($musicDir);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Музыка на сайте</title>
    <link rel = "icon" href = "./img/icon.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "./style/style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {padding: 0px;}
        .track { margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        audio { width: 300px; margin-top: 5px; }
        a { color: #069; text-decoration: none; }
    </style>
</head>
<body>
    <div class="navbar">
		<a href="index">Back</a>
	</div>
	<div class="content-main">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="header">
                        <h3>Музыкальная коллекция (<?= count($musicFiles) ?> треков)</h3>
                    </div>
                    <p>Тут представленны все аудио файлы, которые есть на сайте (скрытые, и открытые). Послушайте!</p>
                    <?php if (!empty($musicFiles)): ?>
                        <?php foreach ($musicFiles as $file): ?>
                            <div class="track">
                                <strong><?= $file['name'] ?></strong>
                                <div>
                                    <audio controls>
                                        <source src="<?= $file['path'] ?>" type="audio/mpeg">
                                        Ваш браузер не поддерживает аудио-тег.
                                    </audio>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Ничего не найдено.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



