<?php
    require_once '../template/conn.php';
    $conn = new mysqli($host, $log, $password_sql, $database);
    if ($conn->connect_error) {
      die("Ошибка подключения: " . $conn->connect_error);
    }
    $login = $_SESSION['user'];
    $userId = (int)$_GET['id'];

    $stmt = $conn->prepare("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $currentUserResult = $stmt->get_result();
    $currentUser = $currentUserResult->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $user = $userResult->fetch_assoc();
    $stmt->close();

    if (!$user) {
        die("Пользователь не найден.");
    }

    $color = ($user['lvl'] > 2) ? '#B22222' : '#9966cc';

    $donate_amount = (int)$user['donate'];
    if ($donate_amount < 100) {
        $border_color = '#33004b';
    } else if ($donate_amount < 1000) {
        $border_color = '#005599';
    } else if ($donate_amount < 2000) {
        $border_color = '#0066cc';
    } else if ($donate_amount < 5000) {
        $border_color = '#ff8c00';
    } else {
        $border_color = '#FFD700';
    }

    $stmt = $conn->prepare("SELECT * FROM post WHERE id_user = ? ORDER BY data DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $postsResult = $stmt->get_result();
    $posts = $postsResult->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("SELECT title, description FROM achievement WHERE id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $achievements_result = $stmt->get_result();
    $stmt->close();

    $stmt = $conn->prepare("SELECT t.title FROM title t JOIN invent i ON t.id_title = i.id_title WHERE i.id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $title_result = $stmt->get_result();
    $title_row = $title_result->fetch_assoc();
    $stmt->close();

    $title = $title_row ? $title_row['title'] : "Пользователь";
    $head = $title_row ? $title_row['title'] : 'пользователем';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Профиль <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel = "icon" href = "../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel = "stylesheet" href = "../style/style.css">
    <link rel="stylesheet" href="../style/style_profile.css">
    <link rel = "stylesheet" href = "../style/player.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Познакомьтесь с <?php echo htmlspecialchars($head . ' ' . $user['username']); ?>"/>
    <meta property="og:title" content="<?php echo htmlspecialchars($title . ' ' . $user['username']); ?>"/>
    <meta property="og:site_name" content="Song of the abyss"/>
    <meta property="og:type" content="website"/>
    <meta property="og:url" content="https://so-ta.ru/profile/profile?id=<?php echo $user['id']; ?>"/>
    <meta property="og:description" content="Познакомьтесь с <?php echo htmlspecialchars($head . ' ' . $user['username']); ?>!"/>
    <style>
        .profile-container{border:2px solid <?php echo $border_color; ?>;max-width:800px;margin:20px auto;background:linear-gradient(135deg, rgba(34, 34, 34, 0.9) 0%, rgba(15, 15, 30, 0.95) 100%);padding:25px;border-radius:15px;box-shadow:0 5px 20px rgba(0, 0, 0, 0.7);color:#eee;font-family:'Montserrat Alternates',sans-serif;}.profile-header{text-align:center;margin-bottom:25px;}
        .profile-header img{width:150px;height:150px;border-radius:50%;border:5px solid <?php echo $color; ?>;object-fit:cover;box-shadow:0 0 15px rgba(0, 0, 0, 0.5);}
        .profile-header h1,h6{color:<?php echo $color; ?>;margin-top:12px;font-weight:600;}
        .permissions{color:<?php echo $color; ?>;font-style:italic;margin:8px 0;}
        .profile-section h2,h3{color:<?php echo $color; ?>;margin-bottom:15px;}
        .post{background:linear-gradient(135deg, rgba(50, 50, 50, 0.8) 0%, rgba(20, 20, 40, 0.9) 100%);padding:20px;margin-bottom:20px;border-radius:12px;color:#eee;box-shadow:0 3px 10px rgba(0, 0, 0, 0.4);border-left:4px solid <?php echo $color; ?>;}
        .profile-section {margin-top: 30px;padding-top: 20px;border-top: 1px solid rgba(255, 255, 255, 0.1);width:100%;}
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
                        <img src="./avatars/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Аватар <?php echo htmlspecialchars($user['username']); ?>"  loading="lazy" onerror="this.src='../img/default_avatar.png';">
                        <h6><?php echo htmlspecialchars($title); ?></h6>
                        <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                        <?php
                        $mail_addr = '';
                        $mail_user_query = "SELECT username FROM mail_user WHERE user_id = ?";
                        $stmt_mail = $conn->prepare($mail_user_query);
                        $stmt_mail->bind_param("i", $userId);
                        $stmt_mail->execute();
                        $mail_user_result = $stmt_mail->get_result();
                        if ($mail_user_result && $mail_user_result->num_rows > 0) {
                            $mail_user_row = $mail_user_result->fetch_assoc();
                            $mail_addr = $mail_user_row['username'] . '@abyss';
                            echo '<p style="color:#BA55D3;font-size:1.05rem;margin-bottom:4px;">Почта: <b>' . htmlspecialchars($mail_addr) . '</b></p>';
                        }
                        $stmt_mail->close();
                        ?>
                        <p class="permissions"><?php echo htmlspecialchars($user['permissions']); ?></p>
                        <p class="donate">Поддержка проекта: <?php echo $user['donate']; ?> руб.</p>
                    </div>
                    <div class="profile-info">
                        <p class="bio"><?php echo $user['BIO'] ? nl2br($user['BIO']) : '<span class="no-content">Пользователь не добавил описание.</span>'; ?></p>
                        <?php if ($user['NSFW'] == 1): ?>
                            <p class="nsfw">Имеет доступ к 18+ контенту.</p>
                        <?php endif; ?>
                    </div>
                    <div class='profile-section achievement-info'>
                        <h3>Достижения пользователя:</h3>
                        <?php if (mysqli_num_rows($achievements_result) > 0): ?>
                            <?php while ($achievement = mysqli_fetch_assoc($achievements_result)):?>
                            <p><?php echo htmlspecialchars($achievement["title"]) . ': ' . htmlspecialchars($achievement["description"]); ?></p>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-content">У пользователя пока нет достижений. Но это пока.</p>
                        <?php endif; ?>
                    </div>
                    <div class="profile-section profile-posts">
                        <h2>Посты (<?php echo count($posts); ?>)</h2>
                        <?php if (count($posts) > 0): ?>
                            <?php foreach ($posts as $post): ?>
                            <div class="post">
                                <h3><a class="link" href="../abyss_net/post?id=<?php echo $post["id_post"];?>"><?php echo $post['title']; ?></a></h3>
                                <p><?php echo $post['post'] ? nl2br($post['post']) : '<span class="no-content">Пользователь решил не комментировать свой пост...</span>'; ?></p>
                                <div class="media">
                                    <?php 
                                    $media_files = !empty($post['media']) ? explode(',', $post['media']) : [];
                                    if (!empty($media_files)) : ?>
                                        <div class="media-container">
                                            <?php foreach ($media_files as $media_file) : 
                                                $extension = strtolower(pathinfo($media_file, PATHINFO_EXTENSION));
                                                $safe_media_file = htmlspecialchars($media_file);
                                            ?>
                                                <div class="media-item">
                                                    <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp','jfif'])) : ?>
                                                        <span class="file-type">Изображение</span>
                                                        <img src="../abyss_net/media/<?php echo $safe_media_file; ?>" alt="Медиа из поста" loading="lazy">
                                                    <?php elseif ($extension === 'mp3') : ?>
                                                        <button class="play-button" onclick="playButtonStart('<?php echo htmlspecialchars($post['title'], ENT_QUOTES) . ' - ' . htmlspecialchars(explode('_',$media_file)[1] ?? 'Аудио', ENT_QUOTES); ?>', '../abyss_net/media/<?php echo $safe_media_file; ?>')">
                                                            ▶️ Воспроизвести <?php echo htmlspecialchars(explode('_',$media_file)[1] ?? 'Аудио'); ?>
                                                        </button>
                                                    <?php elseif ($extension === 'mp4') : ?>
                                                        <span class="file-type">Видео</span>
                                                        <video controls class="media" preload="none">
                                                            <source src="../abyss_net/media/<?php echo $safe_media_file; ?>" type="video/mp4">
                                                            Ваш браузер не поддерживает видео.
                                                        </video>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php 
                                    $stmt = $conn->prepare("SELECT COUNT(*) AS comment_count FROM comment WHERE id_post = ?");
                                    $stmt->bind_param("i", $post['id_post']);
                                    $stmt->execute();
                                    $comment_count_result = $stmt->get_result();
                                    $comment_count_row = $comment_count_result->fetch_assoc();
                                    $comment_count = $comment_count_row['comment_count'];
                                    $stmt->close();
                                ?>
                                <a href="../abyss_net/post?id=<?php echo $post['id_post']; ?>#comments">
                                    <div class="comment-count">
                                        <span><img src="../abyss_net/icon/comment.png" width="20" height="20" style="object-fit: cover;"> <?php echo $comment_count; ?> <?php echo ($comment_count == 1) ? 'комментарий' : (($comment_count % 10 >= 2 && $comment_count % 10 <= 4 && ($comment_count % 100 < 10 || $comment_count % 100 >= 20)) ? 'комментария' : 'комментариев'); ?></span>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <p class="no-content">Пользователь пока не делал постов :(</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src='../js/player.js'></script>
<?php require_once '../template/video_plugin.html'; ?>
</body>
</html>
<?php 
session_write_close();
?>