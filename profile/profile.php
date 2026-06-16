<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/../abyss_net/post_helpers.php';
auth_sync_session_from_token();
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
    if ($donate_amount < 75) {
        $border_color = '#33004b';
    } else if ($donate_amount < 150) {
        $border_color = '#005599';
    } else if ($donate_amount < 1250) {
        $border_color = '#0066cc';
    } else if ($donate_amount < 3000) {
        $border_color = '#ff8c00';
    } else {
        $border_color = '#FFD700';
    }

	    $canViewNsfw = nsfw_user_has_access($currentUser);
	    if ($canViewNsfw) {
	        $stmt = $conn->prepare("SELECT * FROM post WHERE id_user = ? ORDER BY data DESC");
	        $stmt->bind_param("i", $userId);
	    } else {
	        $stmt = $conn->prepare("SELECT * FROM post WHERE id_user = ? AND NSFW = 0 ORDER BY data DESC");
	        $stmt->bind_param("i", $userId);
	    }
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
    <link rel="stylesheet" href="../style/bootstrap.min.css">
    <link rel = "stylesheet" href = "../style/style.css">
    <link rel="stylesheet" href="../style/style_profile.css">
    <link rel = "stylesheet" href = "../style/player.css">
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
        .profile-container{border:2px solid <?php echo $border_color; ?>;max-width:800px;margin:20px auto;background:linear-gradient(135deg, rgba(34, 34, 34, 0.9) 0%, rgba(15, 15, 30, 0.95) 100%);padding:25px;border-radius:15px;box-shadow:0 5px 20px rgba(0, 0, 0, 0.7);color:#eee;font-family:'Montserrat Alternates',sans-serif;}
        .photo-modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            animation: fadeIn 0.3s ease;
        }
        .photo-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-modal-content {
            position: relative;
            width: 95vw;
            height: 95vh;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
        }
        .photo-modal-img-wrapper {
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-modal img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 10px;
            cursor: grab;
            user-select: none;
            transition: transform 0.1s ease;
        }
        .photo-modal img:active {
            cursor: grabbing;
        }
        .photo-modal-controls {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 102;
        }
        .photo-modal-buttons {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            padding: 15px;
            pointer-events: auto;
        }
        .photo-modal-close {
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.7);
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .photo-modal-close:hover {
            background: rgba(255, 0, 0, 0.8);
            transform: scale(1.1);
        }
        .photo-modal-nav {
            color: white;
            font-size: 30px;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.7);
            border: none;
            padding: 20px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
            height: fit-content;
            align-self: center;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }
        .photo-modal-nav:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .photo-modal-nav.prev {
            left: 15px;
            pointer-events: auto;
        }
        .photo-modal-nav.next {
            right: 15px;
            pointer-events: auto;
        }
        .photo-modal-info {
            position: absolute;
            bottom: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            pointer-events: auto;
            z-index: 102;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .profile-header{text-align:center;margin-bottom:25px;}
        .profile-header img{width:150px;height:150px;border-radius:50%;border:5px solid <?php echo $color; ?>;object-fit:cover;box-shadow:0 0 15px rgba(0, 0, 0, 0.5);}
        .profile-header h1,h6{color:<?php echo $color; ?>;margin-top:12px;font-weight:600;}
        .permissions{color:<?php echo $color; ?>;font-style:italic;margin:8px 0;}
        .profile-section h2,h3{color:<?php echo $color; ?>;margin-bottom:15px;}
        .post{background:linear-gradient(135deg, rgba(50, 50, 50, 0.8) 0%, rgba(20, 20, 40, 0.9) 100%);padding:20px;margin-bottom:20px;border-radius:12px;color:#eee;box-shadow:0 3px 10px rgba(0, 0, 0, 0.4);border-left:4px solid <?php echo $color; ?>;}
        .profile-section {margin-top: 30px;padding-top: 20px;border-top: 1px solid rgba(255, 255, 255, 0.1);width:100%;}
        .ether{border-radius:20px;padding:8px 15px;background:radial-gradient(circle,rgba(106, 61, 255, 0.5) 0%, rgba(40, 0, 56, 0.5) 100%);font-weight:bold;display:inline-block;margin-top:10px;}
        .post-tags {
            margin: 10px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .post-tags .tag-link {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(186, 20, 126, 0.3);
            border-radius: 16px;
            font-size: 13px;
            color: #fff;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .post-tags .tag-link:hover {
            background: rgba(186, 20, 126, 0.6);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
        .media-item img {
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .media-item img:hover {
            transform: scale(1.02);
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
                <div id="photoModal" class="photo-modal">
                    <div class="photo-modal-content">
                        <div class="photo-modal-img-wrapper">
                            <img id="modalPhoto" src="" alt="Увеличенное фото">
                        </div>
                        <div class="photo-modal-controls">
                            <div class="photo-modal-buttons">
                                <button class="photo-modal-close" onclick="closePhotoModal()">&times;</button>
                                <div></div>
                            </div>
                            <button class="photo-modal-nav prev" onclick="prevPhoto()">&#10094;</button>
                            <button class="photo-modal-nav next" onclick="nextPhoto()">&#10095;</button>
                            <div class="photo-modal-info" id="photoInfo">Zoom: 100% | Scroll: Приблизить | Drag: Переместить</div>
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
                        <p class="donate">Поддержка проекта: <?php echo $user['donate']; ?> руб.</p><br>
                        <p class="ether">Эфир бездны: <?php echo number_format($user['abyss_ether'], 7)?> ед.</p>
                    </div>
                    <div class="profile-info">
                        <p class="bio"><?php echo $user['BIO'] ? nl2br($user['BIO']) : '<span class="no-content">Пользователь не добавил описание.</span>'; ?></p>
	                        <?php if ((int)$user['NSFW'] === 1 && (int)$user['CONFIRM_NSFW'] === 1 && $canViewNsfw): ?>
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
                                <?php
                                $postTags = get_post_tags($conn, (int)$post['id_post']);
                                if ($postTags !== []) : ?>
                                    <div class="post-tags">
                                        <?php foreach ($postTags as $tag) : ?>
                                            <a href="../abyss_net/main?filter=recent&tag=<?php echo urlencode($tag); ?>" class="tag-link">#<?php echo htmlspecialchars($tag); ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <p><?php echo $post['post'] ? nl2br($post['post']) : '<span class="no-content">Пользователь решил не комментировать свой пост...</span>'; ?></p>
                                <div class="media">
                                    <?php 
                                    $media_files = !empty($post['media']) ? explode(',', $post['media']) : [];
                                    if (!empty($media_files)) : ?>
                                        <div class="media-container">
                                            <?php foreach ($media_files as $media_file_index => $media_file) : 
	                                                $extension = strtolower(pathinfo($media_file, PATHINFO_EXTENSION));
	                                                $safe_media_file = htmlspecialchars($media_file);
	                                                $media_url = "../abyss_net/media/" . $safe_media_file;
	                                                if (abyss_post_is_nsfw($post)) {
	                                                    $media_url = "../../private/abyss_net/media/" . $safe_media_file;
	                                                }
	                                            ?>
                                                <div class="media-item">
                                                    <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp','jfif'])) : ?>
                                                        <span class="file-type">Изображение</span>
                                                        <img src="<?php echo $media_url; ?>" alt="Медиа из поста" loading="lazy" onclick="openPhotoModal(this, <?php echo $post['id_post']; ?>, <?php echo $media_file_index; ?>)" data-post-id="<?php echo $post['id_post']; ?>" data-media-index="<?php echo $media_file_index; ?>">
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
<script>
let currentPhotoModal = null;
let currentPostId = null;
let currentMediaIndex = 0;
let allPostsPhotos = {};
let photoZoom = 1;
let photoOffsetX = 0;
let photoOffsetY = 0;
let isDraggings = false;
let dragStartX = 0;
let dragStartY = 0;

function initPhotoModal() {
    const mediaItems = document.querySelectorAll('[data-post-id]');
    mediaItems.forEach(item => {
        const postId = item.getAttribute('data-post-id');
        const mediaIndex = parseInt(item.getAttribute('data-media-index'));
        
        if (!allPostsPhotos[postId]) {
            allPostsPhotos[postId] = [];
        }
        allPostsPhotos[postId][mediaIndex] = item.src;
    });
}

function resetPhotoTransform() {
    photoZoom = 1;
    photoOffsetX = 0;
    photoOffsetY = 0;
    updatePhotoTransform();
}

function updatePhotoTransform() {
    const modalImg = document.getElementById('modalPhoto');
    if (modalImg) {
        modalImg.style.transform = `scale(${photoZoom}) translate(${photoOffsetX}px, ${photoOffsetY}px)`;
    }
    const photoInfo = document.getElementById('photoInfo');
    if (photoInfo) {
        const zoomPercent = Math.round(photoZoom * 100);
        photoInfo.textContent = `Zoom: ${zoomPercent}% | Scroll: Приблизить | Drag: Переместить`;
    }
}

function openPhotoModal(img, postId, mediaIndex) {
    const modal = document.getElementById('photoModal');
    const modalImg = document.getElementById('modalPhoto');
    
    resetPhotoTransform();
    currentPhotoModal = img.src;
    currentPostId = postId;
    currentMediaIndex = mediaIndex;
    
    modalImg.src = img.src;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    const wrapper = document.querySelector('.photo-modal-img-wrapper');
    if (wrapper) {
        wrapper.addEventListener('wheel', handlePhotoZoom, false);
        wrapper.addEventListener('mousedown', handlePhotoDragStart, false);
        wrapper.addEventListener('mousemove', handlePhotoDragMove, false);
        wrapper.addEventListener('mouseup', handlePhotoDragEnd, false);
        wrapper.addEventListener('mouseleave', handlePhotoDragEnd, false);
    }
    
    const contentArea = document.querySelector('.photo-modal-content');
    if (contentArea) {
        contentArea.addEventListener('click', function(e) {
            if (e.target === contentArea) {
                closePhotoModal();
            }
        });
    }
    
    const handleEscape = function(e) {
        if (e.key === 'Escape') {
            closePhotoModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

function closePhotoModal() {
    const modal = document.getElementById('photoModal');
    const wrapper = document.querySelector('.photo-modal-img-wrapper');
    
    if (wrapper) {
        wrapper.removeEventListener('wheel', handlePhotoZoom);
        wrapper.removeEventListener('mousedown', handlePhotoDragStart);
        wrapper.removeEventListener('mousemove', handlePhotoDragMove);
        wrapper.removeEventListener('mouseup', handlePhotoDragEnd);
        wrapper.removeEventListener('mouseleave', handlePhotoDragEnd);
    }
    
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    currentPhotoModal = null;
    currentPostId = null;
    resetPhotoTransform();
}

function handlePhotoZoom(e) {
    e.preventDefault();
    const zoomSpeed = 0.1;
    const oldZoom = photoZoom;
    photoZoom += (e.deltaY < 0 ? zoomSpeed : -zoomSpeed);
    photoZoom = Math.max(0.5, Math.min(5, photoZoom));
    updatePhotoTransform();
}

function handlePhotoDragStart(e) {
    if (photoZoom > 1) {
        isDraggings = true;
        dragStartX = e.clientX - photoOffsetX;
        dragStartY = e.clientY - photoOffsetY;
    }
}

function handlePhotoDragMove(e) {
    if (!isDraggings) return;
    photoOffsetX = e.clientX - dragStartX;
    photoOffsetY = e.clientY - dragStartY;
    updatePhotoTransform();
}

function handlePhotoDragEnd() {
    isDraggings = false;
}

function nextPhoto() {
    if (!currentPostId || !allPostsPhotos[currentPostId]) return;
    
    const photos = allPostsPhotos[currentPostId];
    const nextIndex = (currentMediaIndex + 1) % photos.length;
    currentMediaIndex = nextIndex;
    
    const modalImg = document.getElementById('modalPhoto');
    modalImg.src = photos[nextIndex];
    resetPhotoTransform();
}

function prevPhoto() {
    if (!currentPostId || !allPostsPhotos[currentPostId]) return;
    
    const photos = allPostsPhotos[currentPostId];
    const prevIndex = (currentMediaIndex - 1 + photos.length) % photos.length;
    currentMediaIndex = prevIndex;
    
    const modalImg = document.getElementById('modalPhoto');
    modalImg.src = photos[prevIndex];
    resetPhotoTransform();
}

document.addEventListener('DOMContentLoaded', initPhotoModal);
</script>
<?php require_once '../template/video_plugin.html'; ?>
</body>
</html>
<?php 
session_write_close();
?>
