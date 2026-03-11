<?php
    require_once '../template/conn.php';
    $conn = new mysqli($host, $log, $password_sql, $database);
    if ($conn->connect_error) {
      die("Ошибка подключения: " . $conn->connect_error);
    }
    $login = $_SESSION['user'];
    $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
    $result = $conn->query($user_query);

    $userId = $_GET['id'];
    $sql = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.id = $userId";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();

    $color = '#9966cc';
    if ($user['lvl'] > 2) {
        $color = '#B22222';
    }

    if ($user['donate'] < 100) {
        $border_color = '#33004b';
    } else if ($user['donate'] < 1000) {
        $border_color = '#005599';
    } else if ($user['donate'] < 2000) {
        $border_color = '#0066cc';
    } else if ($user['donate'] < 5000) {
        $border_color = '#ff8c00';
    } else {
        $border_color = '#FFD700';
    }

    $sqlPosts = "SELECT * FROM post WHERE id_user = $userId ORDER BY data DESC";
    $resultPosts = $conn->query($sqlPosts);
    $posts = $resultPosts->fetch_all(MYSQLI_ASSOC);

    $achievements_sql = "SELECT title, description FROM achievement WHERE id_user = $userId";
    $achievements_result = mysqli_query($conn, $achievements_sql);

    $title_sql = "SELECT title FROM title WHERE id_title = (SELECT id_title FROM invent WHERE id_user = $userId)";
    $title_result = mysqli_query($conn, $title_sql);
    $title;
    $head;
    if (mysqli_num_rows($title_result) > 0) {
        $title_row = mysqli_fetch_assoc($title_result);
        $title = $title_row['title'];
        $head = $title_row['title'];
    } else {
        $title = "Пользователь";
        $head = 'пользователем';
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Профиль <?php echo $user['username']; ?></title>
    <link rel = "icon" href = "../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "../style/style.css">
	<link rel = "stylesheet" href = "../style/player.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Познакомьтесь с <?php echo $head . ' ' . $user['username']; ?>!"/>
	<meta property="og:title" content="<?php echo $title . ' ' . $user['username']; ?>"/>
	<meta property="og:site_name" content="Song of the  abyss"/>
	<meta property="og:type" content="website"/>
	<meta property="og:url" content="https://so-ta.ru/profile/profile?id=<?php $user['id']?>"/>
	<meta property="og:description" content="Познакомьтесь с <?php echo $head . ' ' . $user['username']; ?>!"/>
  <style>
    .profile-container {
        border: 1px solid <?php echo $border_color; ?>;
        max-width: 800px;
        margin: 20px auto;
        background-color: rgba(34, 34, 34, 0.8);
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        color: #eee;
    }
    .profile-header img {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid <?php echo $color; ?>;
    }
    .profile-header h1, h6 {
        color: <?php echo $color; ?>;
        margin-top: 10px;
    }
    .permissions {
        color: <?php echo $color; ?>;
    }
    .bio {
        margin-top: 20px;
        line-height: 1.5;
    }
    .nsfw {
        color: #ff6666;
    }
    .profile-posts {
        margin-top: 30px;
    }
    .post {
        background-color: rgba(50, 50, 50, 0.7);
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 5px;
        color: #eee;
    }
    .comment-count {
        bottom: 10px;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
    }
    .xp-bar {
        position: relative;
        height: 20px;
        background: #420;
        max-width: 475px;
        width: 100%;
        border-radius: 35px;
        margin-bottom: 10px;
    }
    .xp-fill {
        position: absolute;
        border-radius: 35px;
        top: 0;
        left: 0;
        height: 100%;
        background: linear-gradient(90deg, rgba(255,118,36,1) 0%, rgba(64,16,190,1) 100%);
    }
    .xp-text {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        text-align: center;
        font-size: 13px;
        color: #ffe2aa;
        text-shadow:
            1px 1px 1px #000,
            -1px 1px 1px #000,
            1px -1px 1px #000,
            -1px -1px 1px #000;
    }
    @media (max-width: 520px){
        .profile-container {
            padding: 8px;
        }
        .post {
            padding: 5px;
        }
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
        max-height: 400px;
        border-radius: 12px;
        background: black;
    }
    .media-item .play-button {
        width: 100%;
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
    .donate {
	    border-radius: 20px;
	    padding: 5px;
	    background: radial-gradient(circle,rgba(238, 174, 202, 0.1) 0%, rgba(148, 187, 233, 0.4) 100%);
	}
    </style>
</head>
<body>
<div class="navbar">
	<a href="#" onclick="window.history.back()">Back</a>
	<a href="#" id="musicBtn" onclick="openModal()">Плеер</a>
	<a href="./main">Ваш профиль</a>
</div>
<div class="content-main">
	<div class="container">
		<div class="row">
			<div class="col-12">
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
                <div class="profile-container">
                    <div class="profile-header">
                        <img src="./avatars/<?php echo $user['avatar']; ?>" alt="Аватар">
                        <h6><?php echo $title; ?></h6>
                        <h1><?php echo $user['username']; ?></h1>
                        <p class="permissions"><?php echo $user['permissions']; ?></p>
                        <p class="donate">Поддержка проекта: <?php echo $user['donate']; ?> руб.</p>
                    </div>
                    <div class="profile-info">
                        <p class="bio"><?php echo nl2br($user['BIO']); ?></p>
                        <?php if ($user['NSFW'] == 1): ?>
                            <p class="nsfw">Имеет доступ к 18+ контенту.</p>
                        <?php endif; ?>
                    </div>
                    <div class='achievement-info'>
                        <h3>Достижения пользователя:</h3>
                        <?php if (mysqli_num_rows($achievements_result) > 0): ?>
                            <?php while ($achievement = mysqli_fetch_assoc($achievements_result)):?>
                            <p style="background: linear-gradient(90deg, rgba(93,25,138,1) 0%, rgba(135,11,11,1) 50%, rgba(190,118,16,1) 100%);"><?php echo $achievement["title"] . ': ' . $achievement["description"] ?></p>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>У пользователя пока нет достижений. Но это пока.</p>
                        <?php endif; ?>
                    </div>
                    <div class="profile-posts">
                        <h2>Посты</h2>
                        <?php if (count($posts) > 0): ?>
                            <?php foreach ($posts as $post): ?>
                            <div class="post">
                                <h3> <a class="link" href="../abyss_net/post?id=<?php echo $post["id_post"];?>"><?php echo $post['title']; ?></a></h3>
                                <p><?php echo nl2br($post['post']); ?></p>
                                <div class="media">
                                    <?php 
                                    $media_files = !empty($post['media']) ? explode(',', $post['media']) : [];
                                    if (!empty($media_files)) : ?>
                                        <div class="media-container">
                                            <?php foreach ($media_files as $media_file) : 
                                                $extension = strtolower(pathinfo($media_file, PATHINFO_EXTENSION));
                                            ?>
                                                <div class="media-item">
                                                    <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) : ?>
                                                        <span class="file-type">Изображение</span>
                                                        <img src="../abyss_net/media/<?= htmlspecialchars($media_file) ?>" 
                                                             alt="Медиа из поста">
                                                    <?php elseif ($extension === 'mp3') : ?>
                                                        <button class="play-button" 
                                                                onclick="playButtonStart('<?= htmlspecialchars($post['title'], ENT_QUOTES) . ' - ' . explode('_',$media_file)[1]?>', 
                                                                        '../abyss_net/media/<?= htmlspecialchars($media_file, ENT_QUOTES) ?>')">
                                                            ▶️ Воспроизвести <?= explode('_',$media_file)[1] ?>
                                                        </button>
                                                    <?php elseif ($extension === 'mp4') : ?>
                                                        <span class="file-type">Видео</span>
                                                        <video controls class="media">
                                                            <source src="../abyss_net/media/<?= htmlspecialchars($media_file) ?>" type="video/mp4">
                                                        </video>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php 
                                    $comment_count_query = "SELECT COUNT(*) AS comment_count FROM comment WHERE id_post = " . $post['id_post'];
                                    $comment_count_result = $conn->query($comment_count_query);
                                    $comment_count = $comment_count_result->fetch_assoc()['comment_count'];
                                ?>
                                <a href="../abyss_net/post?id=<?php echo $post['id_post']; ?>#comments"><div class="comment-count">
                                    <span><img src="../abyss_net/icon/comment.png" width="35" height="35" style="object-fit: cover;"> <?php echo $comment_count; ?></span>
                                </div></a>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <p>Пользователь пока не делал постов :(</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src='../js/player.js'></script>
</body>
</html>