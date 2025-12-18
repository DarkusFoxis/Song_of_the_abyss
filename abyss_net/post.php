<?php
session_start();
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
$postId = $_GET['id'];
$sql = "SELECT p.*, u.username, u.avatar FROM post p JOIN users u ON p.id_user = u.id WHERE id_post = '$postId'";
$result = $conn->query($sql);

$comm = "SELECT c.*, u.username, u.avatar FROM comment c JOIN users u ON c.id_user = u.id WHERE c.id_post = '$postId' ORDER BY data";
$result_com = $conn->query($comm);

$comments = [];
if ($result->num_rows > 0) {
    while ($row = $result_com->fetch_assoc()) {
        $comments[] = [
            'username' => $row['username'],
            'avatar' => $row['avatar'],
            'text' => $row['text'],
            'data' => $row['data'],
            'id_user' => $row['id_user']
        ];
    }
}
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $description = $row['post'];
    if (strlen($description) > 220) {
        $shortDescription = substr($description, 0, 220) . '...';
    } else {
        $shortDescription = $description;
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $row['title']; ?></title>
    <link rel = "icon" href = "../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel = "stylesheet" href = "../style/style.css">
    <link rel = "stylesheet" href = "../style/player.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="<?php echo $shortDescription; ?>"/>
	<meta property="og:title" content="<?php echo $row['title']; ?>"/>
	<meta property="og:site_name" content="Song of the  abyss"/>
	<meta property="og:type" content="website"/>
	<meta property="og:url" content="https://so-ta.ru/abyss_net/post?id=<?php echo $row['id']; ?>"/>
	<meta property="og:description" content="<?php echo $shortDescription; ?>"/>
	<script src="../js/jquery-3.7.1.min.js"></script>
    <style>
        .container {
            margin: 0 auto;
        }
        .navbar {
            clear: both;
            overflow: hidden;
            background-color: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0px 1rem 0px;
        }
        .post-card {
            background: linear-gradient(90deg, rgba(186,20,126,0.5) 0%, rgba(60,9,121,1) 50%, rgba(255,102,0,0.5) 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        .post-card .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .post-card h3 {
            font-weight: bold;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination .page-item .page-link {
            background-color: #663399;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 0 10px;
            cursor: pointer;
        }
        .comments {
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding: 10px;
        }
        
        .comments-list {
            margin-bottom: 10px;
        }
        .comments-list .comment {
            padding: 10px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .comment-form textarea {
            width: 100%;
            height: 80px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .media {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .media-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: center;
            justify-content: center;
            margin: 25px 0;
        }
        .media-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s ease;
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .media-item:hover {
            transform: translateY(-5px);
        }
        .media-item img {
            width: 100%;
            height: auto;
            object-fit: cover;
            display: block;
            border-radius: 12px;
        }
        .media-item video {
            width: 100%;
            height: auto;
            max-width: 400px;
            border-radius: 12px;
            background: black;
        }
        .media-item .play-button {
            width: 50%;
            padding: 15px;
            background: linear-gradient(45deg, #6a11cb 0%, #2575fc 100%);
            border: none;
            color: white;
            border-radius: 12px;
            cursor: pointer;
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 16px;
            transition: opacity 0.3s;
        }
        .media-item .play-button:hover {
            opacity: 0.9;
        }
        @media (max-width: 768px) {
            .media-container {
                grid-template-columns: 1fr;
            }
            .media-item .play-button {
                padding: 5px;
                font-size: 16px;
            }
        }
        .file-type {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="#" onclick="window.history.back()">Back</a>
    <a href="./main">Home</a>
    <a href="#" id="musicBtn" onclick="openModal()">Плеер</a>
</div>
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <br>
                <div id="musicModal" class="modal">
                    <div id='content_music' class="modal-content">
                        <span class="close" id='close'>&times;</span>
                        <h2 id='title'>Настройки музыки</h2>
                        <div class="player-controls">
                            <label for="volumeSlider">Громкость:</label>
                            <div class="slider-container">
                                 <input type="range" id="volumeSlider" min="0" max="100" value="50">
                                <div class="slider-fill" id="volumeFill"></div>
                            </div>
                        </div>
                        <div class="time-display">
                            <span id="currentTime">00:00</span>/<span id="totalTime">00:00</span>
                        </div>
                        <div id="slideSong">
                            <button id="playMusic">▶️</button>
                            <div class="slider-container">
                                 <input type="range" id="seekSlider" min="0" max="100" value="0" step="0.1">
                                <div class="slider-fill" id="seekFill"></div>
                            </div>
                            <button id="muteMusic">⏹️</button>
                        </div>
                    </div>
                </div>
                <div class="post-card">
                    <a class="link" href="/profile/profile?id= <?php echo $row["id_user"] ?>">
                        <img class="avatar" src="../profile/avatars/<?php echo $row['avatar']; ?>" alt="Аватар" title="<?php echo $row['username'] ?> аватар"> <?php echo $row['username'] ?>
                    </a>
                    <hr>
                    <h3><?php echo $row['title']; ?></h3>
                    <p><?php echo nl2br($row['post']); ?></p>
                    <div class="media">
                    <?php 
                    $media_files = !empty($row['media']) ? explode(',', $row['media']) : [];
                    if (!empty($media_files)) : ?>
                        <div class="media-container">
                            <?php foreach ($media_files as $media_file) : 
                                $extension = strtolower(pathinfo($media_file, PATHINFO_EXTENSION));
                            ?>
                                <div class="media-item">
                                    <?php if (in_array($extension, ['jpg','jpeg','png','gif','webp','jfif'])) : ?>
                                        <span class="file-type">Изображение</span>
                                        <img src="./media/<?= htmlspecialchars($media_file) ?>" loading="lazy" alt="Медиа из поста">
                                    <?php elseif ($extension === 'mp3') : ?>
                                        <button class="play-button" onclick="playButtonStart('<?= htmlspecialchars($row['title'], ENT_QUOTES) . ' - ' . explode('_',$media_file)[1]?>', './media/<?= htmlspecialchars($media_file, ENT_QUOTES) ?>')">
                                            ▶️ Воспроизвести <?= explode('_',$media_file)[1] ?>
                                        </button>
                                    <?php elseif ($extension === 'mp4') : ?>
                                        <span class="file-type">Видео</span>
                                        <video controls class="media" loading="lazy">
                                            <source src="./media/<?= htmlspecialchars($media_file) ?>" type="video/mp4">
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                    <hr>
                    <p>Опубликовано: <?php echo date("H:i d.m.Y", strtotime($row['data'])); ?></p>
                    <div class="comments">
                    <h4>Комментарии:</h4>
                    <div id="comments" class="comments-list">
                        <?php if (!empty($comments)) : ?>
                            <?php foreach ($comments as $comment) : ?>
                                <div class="comment">
                                    <a href="../profile/profile?id=<?php echo $comment['id_user'];?>" class="link"><img src="../profile/avatars/<?php echo $comment['avatar']; ?>" class="avatar">
                                    <span class="username"><?php echo $comment['username']; ?></span></a>
                                    <p><?php echo nl2br($comment['text']); ?></p>
                                    <p style="text-align: right; font-size: 10px; margin-bottom: 0px;">Написан: <?php echo date("H:i d.m.Y", strtotime($comment['data'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>Комментариев пока нет. Станьте первым!</p>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($_SESSION['user'])) : ?>
                    <hr>
                    <h5 style="text-align: center;">Попробуйте оставить свой комментарий!</h3>
                        <div class="comment-form">
                            <textarea placeholder="Добавить комментарий..." required minlength="10" maxlength="2048"></textarea>
                            <button id="sendComment" class="button" type="button">Отправить</button>
                        </div>
                    <?php else : ?>
                        <div class="comment-form">
                            <p>Войдите в свой <a class="link" href="../profile/login">аккаунт</a>, чтобы оставлять свои комментарии!</p>
                        </div>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src='../js/player.js'></script>
<script>
    $('#sendComment').click(function() {
        const commentText = $('textarea').val();
        const postId = <?php echo $_GET['id']; ?>;
        $.ajax({
            url: 'comment_core',
            type: 'POST',
            data: { 
                comment: commentText,
                post_id: postId,
            },
            success: function(response) {
                $('#comments').html(response);
            },
            error: function(error) {
                console.error('Ошибка отправки комментария:', error);
                alert("При комментировании произошла ошибка.");
            }
        });
    });
</script>
<?php require_once '../template/video_plugin.html'; ?>
</body>
</html>
<?php
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Пост не найден</title>
    <link rel = "icon" href = "../img/icon.jpg">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Простите, но пост не найден."/>
	<meta property="og:title" content="Ошибка поста."/>
	<meta property="og:site_name" content="Song of the  abyss"/>
	<meta property="og:type" content="website"/>
	<meta property="og:url" content="https://so-ta.ru/abyss_net/main"/>
	<meta property="og:description" content="Простите, но пост не найден."/>
</head>
<body>
<div class="navbar">
    <a href="#" onclick="window.history.back()">Back</a>
    <a href="./main">Home</a>
</div>
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="head">
                    <p>Простите, но пост не найден. Возможно он был удалён, или вы неправильно написали ссылку.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
}
<?php
}
$conn->close();
session_write_close();
?>