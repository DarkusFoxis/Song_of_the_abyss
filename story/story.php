<?php
require_once '../template/conn.php';
if (!isset($_GET['id'])) {
    header("Location: main.php");
    exit;
}
$story_id = intval($_GET['id']);
$conn = new mysqli($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка соединения: " . $conn->connect_error);
}
$sql = "SELECT s.*, u.username FROM story s JOIN users u ON s.id_user = u.id WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $story_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Рассказ не найден");
}
$story = $result->fetch_assoc();
$conn->close();

require_once '../modules/parsedown/Parsedown.php';
$parsedown = new Parsedown();
$raw_content = $story['story'];
$content = nl2br($parsedown->text($raw_content), false);

$story_desc = $story['description'];
if (strlen($story_desc) > 200) {
    $description = substr($story_desc, 0, 200) . '...';
} else {
    $description = $story_desc;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($story['title']) ?> - Рассказы бездны</title>
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/story.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300;400;500&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo $description; ?>"/>
</head>
<body>
    <div class="read-container">
        <div class="story-header">
            <h1 class="story-title"><?= htmlspecialchars($story['title']) ?></h1>
            <div class="story-meta">
                <div class="author-info">
                    <span>Автор:</span>
                    <span class="author-name"><?= htmlspecialchars($story['username']) ?></span>
                </div>
                <div class="age-rating age-<?= $story['age_limit'] ?>">
                    <?php 
                    switch($story['age_limit']) {
                        case 18: echo '18+'; break;
                        case 16: echo '16+'; break;
                        case 12: echo '12+'; break;
                        default: echo 'Без ограничений';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="story-content">
            <?= $content; ?>
        </div>
        <div style="text-align: center;">
            <a href="main" class="back-button">← Вернуться к рассказам</a>
        </div>
        <div class="footer-note">
            Рассказ прочитан! Спасибо!
        </div>
    </div>
</body>
</html>