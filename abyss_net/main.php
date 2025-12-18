<?php
session_start();
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

$is_ajax = isset($_GET['ajax']);

$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$posts_per_page = 10;
$offset = ($current_page - 1) * $posts_per_page;

$sql = "SELECT p.*, u.username, u.avatar FROM post p JOIN users u ON p.id_user = u.id ORDER BY p.data DESC LIMIT $posts_per_page OFFSET $offset";
$result = $conn->query($sql);

$total_posts = $conn->query("SELECT COUNT(*) FROM post")->fetch_assoc()['COUNT(*)'];
$total_pages = ceil($total_posts / $posts_per_page);
setlocale(LC_TIME, 'ru_RU.UTF-8');

if ($is_ajax) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            ?>
            <div class="post-card">
                <a class="link" href="/profile/profile?id= <?php echo $row["id_user"] ?>">
                    <img class="avatar" src="../profile/avatars/<?php echo $row['avatar']; ?>" alt="Аватар" title="<?php echo $row['username'] ?> аватар" loading="lazy"> <?php echo $row['username'] ?>
                </a>
                <hr>
                <h3><?php echo $row['title']; ?></h3>
                <p><?php echo nl2br($row['post']); ?></p>
                <?php if (isset($_SESSION['user']) && $_SESSION['user'] == $row['username']) : ?>
                    <?php
                    $post_time = strtotime($row['data']);
                    $current_time = time();
                    $hours_diff = ($current_time - $post_time) / 3600;
                    $allow_edit = ($hours_diff <= 3);
                    ?>
                    <div class="post-actions" style="position:absolute; top:10px; right:10px;">
                        <button class="dots-btn" onclick="toggleMenu(this)">⋮</button>
                        <div class="actions-menu" style="display:none; position:absolute; right:0; background:#222; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.2); z-index:10;">
                            <button onclick="editPost(<?= $row['id_post'] ?>)" <?= !$allow_edit ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                                <?= $allow_edit ? 'Редактировать' : 'Редакт. (заблокировано)' ?>
                            </button>
                            <button onclick="deletePost(<?php echo $row['id_post']; ?>)">Удалить</button>
                        </div>
                    </div>
                <?php endif; ?>
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
                                        <img src="./media/<?= htmlspecialchars($media_file) ?>" alt="Медиа из поста" loading="lazy">
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
                <a class="link" href="post?id=<?php echo $row['id_post']; ?>">Полный пост</a>
                <?php 
                    $comment_count_query = "SELECT COUNT(*) AS comment_count FROM comment WHERE id_post = " . $row['id_post'];
                    $comment_count_result = $conn->query($comment_count_query);
                    $comment_count = $comment_count_result->fetch_assoc()['comment_count'];
                ?>
                <a href="post?id=<?php echo $row['id_post']; ?>#comments"><div class="comment-count">
                    <span><img src="./icon/comment.png" width="35" height="35" style="object-fit: cover;"> <?php echo $comment_count; ?></span>
                </div></a>
            </div>
            
            <?php
        }
    } else {
        echo '<p>Больше постов нет.</p>';
    }
    exit;
}
session_write_close();
?>
<!DOCTYPE html>
<html lang="ru" prefix="og:http://ogp.me/ns#">
<head>
    <title>AbyssNet Блоги</title>
    <link rel = "icon" href = "../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "../style/style.css">
    <link rel = "stylesheet" href = "../style/player.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Abyss Net - Интернет бездны, и по совместительству первая социальная сеть в бездне, где любой житель может общаться с другими душами на любом расстоянии, как и в нашем реальном мире!"/>
    <meta property="og:title" content="Abyss Net"/>
    <meta property="og:site_name" content="Song of the  abyss"/>
    <meta property="og:type" content="website"/>
    <meta property="og:url" content="https://so-ta.ru/abyss_net/main"/>
    <meta property="og:description" content="Abyss Net - Интернет бездны, и по совместительству первая социальная сеть в бездне, где любой житель может общаться с другими душами на любом расстоянии, как и в нашем реальном мире!"/>
    <style>
        #close {
            opacity: 1;
        }
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
            border-radius: 30px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            position: relative;
        }
        .post-card .avatar {
            width: 75px;
            height: 75px;
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
        }
        .post-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 20;
        }
        .dots-btn {
            font-size: 24px;
            background: rgba(102, 51, 153, 0.7);
            border: none;
            color: #fff;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            line-height: 1;
        }
        .dots-btn:hover {
            background: rgba(186, 20, 126, 0.9);
            transform: scale(1.1);
        }
        .actions-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 40px;
            min-width: 160px;
            background: #2a0a52;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(102, 51, 153, 0.3);
            z-index: 30;
            overflow: hidden;
            border: 1px solid #663399;
        }
        .actions-menu button {
            display: block;
            width: 100%;
            background: none;
            border: none;
            color: #e0d6f0;
            padding: 12px 20px;
            text-align: left;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .actions-menu button:hover {
            background: linear-gradient(90deg, #7a3fa3 0%, #ba147e 100%);
            color: #fff;
        }
        .actions-menu button:before {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            background-size: contain;
            background-repeat: no-repeat;
        }
        .actions-menu button:first-child:before {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>');
        }
        .actions-menu button:last-child:before {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>');
        }
        .comment-count {
            position: absolute;
            bottom: 10px;
            right: 10px;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .media {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .media-container {
            width: 50%;
            height: 50%;
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
            .media-item img {
                width: 100%;
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
        @media (max-width: 520px){
            .post-card {
                padding: 10px;
                margin-right: -10px;
                margin-left: -10px;
            }
        }
        .loading-indicator {
            text-align: center;
            padding: 20px;
            color: #fff;
            display: none;
        }
        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid #ba147e;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .end-of-posts {
            text-align: center;
            padding: 20px;
            color: #fff;
            display: none;
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="../index">Back</a>
    <a href="#" id="musicBtn" onclick="openModal()">Плеер</a>
    <a href="./search">Abyss Search</a>
    <a href="./audio_libraly">AbyssNet Song</a>
    <a href="redact">Создать пост</a>
</div>
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="header">
                    <h2>Abyss Net - Блоги</h2>
                </div>
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
            <div class="container" id="posts-container">
                <?php if ($result->num_rows > 0) : ?>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <div class="post-card">
                            <a class="link" href="/profile/profile?id= <?php echo $row["id_user"] ?>">
                                <img class="avatar" src="../profile/avatars/<?php echo $row['avatar']; ?>" alt="Аватар" title="<?php echo $row['username'] ?> аватар" loading="lazy"> <?php echo $row['username'] ?>
                            </a>
                            <hr>
                            <h3><?php echo $row['title']; ?></h3>
                            <p><?php echo nl2br($row['post']); ?></p>
                            <?php if (isset($_SESSION['user']) && $_SESSION['user'] == $row['username']) : ?>
                                <?php
                                $post_time = strtotime($row['data']);
                                $current_time = time();
                                $hours_diff = ($current_time - $post_time) / 3600;
                                $allow_edit = ($hours_diff <= 3);
                                ?>
                                <div class="post-actions" style="position:absolute; top:10px; right:10px;">
                                    <button class="dots-btn" onclick="toggleMenu(this)">⋮</button>
                                    <div class="actions-menu" style="display:none; position:absolute; right:0; background:#222; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.2); z-index:10;">
                                        <button onclick="editPost(<?= $row['id_post'] ?>)" <?= !$allow_edit ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                                            <?= $allow_edit ? 'Редактировать' : 'Редакт. (заблокировано)' ?>
                                        </button>
                                        <button onclick="deletePost(<?php echo $row['id_post']; ?>)">Удалить</button>
                                    </div>
                                </div>
                            <?php endif; ?>
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
                                                    <img src="./media/<?= htmlspecialchars($media_file) ?>" alt="Медиа из поста" loading="lazy">
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
                            <a class="link" href="post?id=<?php echo $row['id_post']; ?>">Полный пост</a>
                            <?php 
                                $comment_count_query = "SELECT COUNT(*) AS comment_count FROM comment WHERE id_post = " . $row['id_post'];
                                $comment_count_result = $conn->query($comment_count_query);
                                $comment_count = $comment_count_result->fetch_assoc()['comment_count'];
                            ?>
                            <a href="post?id=<?php echo $row['id_post']; ?>#comments"><div class="comment-count">
                                <span><img src="./icon/comment.png" width="35" height="35" style="object-fit: cover;"> <?php echo $comment_count; ?></span>
                            </div></a>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p>Постов нет. Может вы хотите стать первым?</p>
                <?php endif; ?>
            </div>
            <div id="loading-indicator" class="loading-indicator">
                <div class="loading-spinner"></div>
                <p>Загрузка...</p>
            </div>
            <div id="end-of-posts" class="end-of-posts">
                <p>Вы посмотрели все посты</p>
            </div>
        </div>
    </div>
</div>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src='../js/player.js'></script>
<?php require_once '../template/video_plugin.html'; ?>
<script>
let currentPage = 1;
let isLoading = false;
let hasMorePosts = true;

function isBottomReached() {
    return window.innerHeight + window.scrollY >= document.body.offsetHeight - 100;
}

function loadMorePosts() {
    if (isLoading || !hasMorePosts) return;
    
    isLoading = true;
    currentPage++;

    $('#loading-indicator').show();

    $.ajax({
        url: '?page=' + currentPage + '&ajax=1',
        type: 'GET',
        success: function(response) {
            if (response.trim() === '<p>Больше постов нет.</p>') {
                hasMorePosts = false;
                $('#end-of-posts').show();
            } else {
                const $container = $('#posts-container');
                $container.append(response);
                if (typeof DFPlayerInit === 'function') {
                    DFPlayerInit($container[0]);
                }
            }
        },
        error: function() {
            console.error('Ошибка при загрузке постов');
            currentPage--;
        },
        complete: function() {
            isLoading = false;
            $('#loading-indicator').hide();
        }
    });
}

$(window).scroll(function() {
    if (isBottomReached()) {
        loadMorePosts();
    }
});

$(document).ready(function() {
    if (isBottomReached() && hasMorePosts) {
        loadMorePosts();
    }
});

function toggleMenu(btn) {
    var menu = btn.nextElementSibling;
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    document.addEventListener('click', function hideMenu(e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
            document.removeEventListener('click', hideMenu);
        }
    });
}

function editPost(id) {
    window.location.href = 'redact?id=' + id;
}

function deletePost(id) {
    if (confirm('Вы уверены, что хотите удалить этот пост?')) {
        $.ajax({
            url: 'delete_post',
            type: 'POST',
            data: { id_post: id },
            success: function(response) {
                alert(response);
                location.reload();
            },
            error: function(xhr, status, error) {
                alert('Ошибка удаления: ' + error);
            }
        });
    }
}
</script>
</body>
</html>