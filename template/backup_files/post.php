<?php
declare(strict_types=1);

require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/post_helpers.php';

auth_start_session();
$viewer = auth_get_current_user();

require_once __DIR__ . '/../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if ($conn === false) {
    http_response_code(500);
    exit('Ошибка подключения к базе данных.');
}

$postId = (int)($_GET['id'] ?? 0);
$postStmt = $conn->prepare(
    'SELECT p.*, u.username, u.avatar
     FROM post p
     JOIN users u ON p.id_user = u.id
     WHERE p.id_post = ?
     LIMIT 1'
);
$postStmt->bind_param('i', $postId);
$postStmt->execute();
$post = $postStmt->get_result()->fetch_assoc();

if ($post === null) {
    mysqli_close($conn);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Пост не найден</title>
        <link rel="icon" href="../img/icon.jpg">
        <link rel="stylesheet" href="../style/style.css">
    	<link rel="stylesheet" href="../style/bootstrap.min.css">
    	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
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
                        <p>Пост не найден. Возможно, он был удалён или ссылка указана неверно.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    session_write_close();
    exit;
}

if (!abyss_post_can_view($post, $viewer)) {
    mysqli_close($conn);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Доступ запрещён</title>
        <link rel="icon" href="../img/icon.png">
        <link rel="stylesheet" href="../style/style.css">
        <link rel="stylesheet" href="../style/bootstrap.min.css">
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
                        <p><?php echo nsfw_access_denied_notice(); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    session_write_close();
    exit;
}

$commentStmt = $conn->prepare(
    'SELECT c.*, u.username, u.avatar
     FROM comment c
     JOIN users u ON c.id_user = u.id
     WHERE c.id_post = ?
     ORDER BY c.data'
);
$commentStmt->bind_param('i', $postId);
$commentStmt->execute();
$commentResult = $commentStmt->get_result();

$comments = [];
while ($comment = $commentResult->fetch_assoc()) {
    $commentText = preg_replace('/<br\s*\/?>/i', "\n", (string)$comment['text']);
    $decodedText = html_entity_decode($commentText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $safeText = security_html(strip_tags($decodedText));
    $comments[] = [
        'id_user' => (int)$comment['id_user'],
        'username' => security_html((string)$comment['username']),
        'avatar' => security_html(basename((string)$comment['avatar'])),
        'text' => nl2br($safeText),
        'date' => (string)$comment['data'],
    ];
}

$postTitle = security_html((string)$post['title']);
$postAuthor = security_html((string)$post['username']);
$postAvatar = security_html(basename((string)$post['avatar']));
$postBody = nl2br(strip_tags((string)$post['post'], '<br>'));
$description = mb_substr(strip_tags((string)$post['post']), 0, 220);
if (mb_strlen(strip_tags((string)$post['post'])) > 220) {
    $description .= '...';
}
$safeDescription = security_html($description);
$isNsfwPost = abyss_post_is_nsfw($post);
$canRatePost = abyss_post_can_rate($post);

$mediaFiles = [];
foreach (explode(',', (string)($post['media'] ?? '')) as $mediaFile) {
    $mediaFile = basename(trim($mediaFile));
    if ($mediaFile !== '') {
        $mediaFiles[] = $mediaFile;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $postTitle; ?></title>
    <meta name="description" content="<?php echo $safeDescription; ?>">
    <meta property="og:title" content="<?php echo $postTitle; ?>">
    <meta property="og:site_name" content="Song of the abyss">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://so-ta.ru/abyss_net/post?id=<?php echo (int)$post['id_post']; ?>">
    <meta property="og:description" content="<?php echo $safeDescription; ?>">
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/player.css">
    <link rel="stylesheet" href="../style/rating_.css">
    <?php echo security_csrf_meta_tags(); ?>
    <script src="../js/jquery-3.7.1.min.js"></script>
    <style>
        .container { margin: 0 auto; }
        .navbar {
            clear: both;
            overflow: hidden;
            background-color: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0 1rem;
        }
        .post-card {
            background: linear-gradient(90deg, rgba(186,20,126,0.5) 0%, rgba(60,9,121,1) 50%, rgba(255,102,0,0.5) 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .comments {
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding: 10px;
        }
        .comment {
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
            width: 40%;
            height: 40%;
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
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .media-item img,
        .media-item video {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 12px;
        }
        .media-item video:-webkit-full-screen,
        .media-item video:-moz-full-screen,
        .media-item video:fullscreen {
            max-height: 100vh !important;
            width: 100vw;
            height: 100vh;
            border-radius: 0;
            object-fit: contain;
        }
        .play-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #6a11cb 0%, #2575fc 100%);
            border: none;
            color: white;
            border-radius: 12px;
            cursor: pointer;
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 16px;
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
        .nsfw-badge {
	        display: inline-flex;
	        align-items: center;
	        margin-left: 10px;
	        padding: 5px 10px;
	        border-radius: 999px;
	        background: rgba(255, 87, 126, 0.22);
	        border: 1px solid rgba(255, 190, 205, 0.55);
	        color: #ffe1e8;
	        font-weight: 700;
	        letter-spacing: .04em;
	    }
	    .rating-disabled {
    	    color: #ffd6de;
            font-weight: 700;
    	}
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
    <a href="./main">Home</a>
    <a href="#" id="musicBtn" onclick="openModal()">Плеер</a>
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
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <br>
                <div class="post-card">
                    <a class="link" href="/profile/profile?id=<?php echo (int)$post['id_user']; ?>">
                        <img class="avatar" src="../profile/avatars/<?php echo $postAvatar; ?>" alt="Аватар" title="<?php echo $postAuthor; ?> аватар"> 
                        <?php echo $postAuthor; ?> <?php echo $isNsfwPost ? ' <span class="nsfw-badge">NSFW</span>' : ''; ?>
                    </a>
                    <hr>
	                    <h3><?php echo $postTitle; ?></h3>

                    <?php
                    $postTags = get_post_tags($conn, (int)$post['id_post']);
                    if ($postTags !== []) : ?>
                        <div class="post-tags" style="margin: 10px 0;">
                            <?php foreach ($postTags as $tag) : ?>
                                <span class="tag-link" style="
                                    display:inline-block; margin:2px 4px; padding:4px 12px;
                                    background:rgba(186,20,126,0.3); border-radius:16px;
                                    font-size:13px; color:#fff; text-decoration:none; border:1px solid rgba(255,255,255,0.2);">
                                    #<?php echo security_html($tag); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <p><?php echo $postBody; ?></p>
                    <div class="media">
                    <?php if ($mediaFiles !== []) : ?>
                        <div class="media-container">
                            <?php foreach ($mediaFiles as $media_file_index => $mediaFile) : ?>
                                <?php
	                                $extension = strtolower(pathinfo($mediaFile, PATHINFO_EXTENSION));
	                                $safeMediaFile = security_html($mediaFile);
	                                $safeMediaUrl = security_html(abyss_post_media_url($post, $mediaFile));
	                                if ($isNsfwPost) {
	                                    $safeMediaFile = security_html('../media_file?post_id=' . (int)$post['id_post'] . '&file=' . rawurlencode($mediaFile));
	                                }
	                                $mediaLabel = explode('_', $mediaFile, 2)[1] ?? $mediaFile;
                                $mediaLabel = security_html($mediaLabel);
                                ?>
                                <div class="media-item">
                                    <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'], true)) : ?>
                                        <span class="file-type">Изображение</span>
                                        <img src="./media/<?php echo $safeMediaFile; ?>" loading="lazy" alt="Медиа из поста" onclick="openPhotoModal(this, <?php echo (int)$post['id_post']; ?>, <?php echo $media_file_index; ?>)" data-post-id="<?php echo (int)$post['id_post']; ?>" data-media-index="<?php echo $media_file_index; ?>">
                                    <?php elseif ($extension === 'mp3') : ?>
                                        <button
                                            class="play-button"
                                            onclick="playButtonStart('<?php echo $postTitle . ' - ' . $mediaLabel; ?>', './media/<?php echo $safeMediaFile; ?>')"
                                        >
                                            Воспроизвести <?php echo $mediaLabel; ?>
                                        </button>
                                    <?php elseif ($extension === 'mp4') : ?>
                                        <span class="file-type">Видео</span>
                                        <video controls loading="lazy" playsinline>
                                            <source src="./media/<?php echo $safeMediaFile; ?>" type="video/mp4">
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                    <hr>
                    <p>Опубликовано: <?php echo date('H:i d.m.Y', strtotime((string)$post['data'])); ?></p>
	                    <?php if ($canRatePost) : ?>
	                    <div class="rating-container">
                        <div class="rating-buttons">
                            <button id="btn-plus-<?php echo (int)$post['id_post']; ?>" class="rating-btn rating-btn-plus" onclick="ratePost(<?php echo (int)$post['id_post']; ?>, 1)">▲</button>
                            <button id="btn-minus-<?php echo (int)$post['id_post']; ?>" class="rating-btn rating-btn-minus" onclick="ratePost(<?php echo (int)$post['id_post']; ?>, -1)">▼</button>
	                    </div>
	                    <?php else : ?>
	                        <div class="rating-container rating-disabled">NSFW-посты оценке не подлежат!</div>
	                    <?php endif; ?>
	                    <?php if ($canRatePost) : ?>
	                        <div class="rating-value">
                            <span id="rating-<?php echo (int)$post['id_post']; ?>"><?php echo round((float)$post['total_rating'], 2); ?></span>
                        </div>
	                    </div>
	                    <?php endif; ?>

	                    <div class="comments">
                        <h4>Комментарии:</h4>
                        <div id="comments" class="comments-list">
                            <?php if ($comments !== []) : ?>
                                <?php foreach ($comments as $comment) : ?>
                                    <div class="comment">
                                        <a href="../profile/profile?id=<?php echo $comment['id_user']; ?>" class="link">
                                            <img src="../profile/avatars/<?php echo $comment['avatar']; ?>" class="avatar" alt="Аватар комментария">
                                            <span class="username"><?php echo $comment['username']; ?></span>
                                        </a>
                                        <p><?php echo $comment['text']; ?></p>
                                        <p style="text-align:right;font-size:10px;margin-bottom:0;">Написан: <?php echo date('H:i d.m.Y', strtotime($comment['date'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p>Комментариев пока нет. Станьте первым!</p>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($_SESSION['user'])) : ?>
                            <hr>
                            <h5 style="text-align:center;">Попробуйте оставить свой комментарий!</h5>
                            <div class="comment-form">
                                <textarea id="commentText" placeholder="Добавить комментарий..." required minlength="10" maxlength="2048"></textarea>
                                <button id="sendComment" class="button" type="button">Отправить</button>
                            </div>
                        <?php else : ?>
                            <div class="comment-form">
                                <p>Войдите в свой <a class="link" href="../profile/login">аккаунт</a>, чтобы оставлять комментарии.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/player.js"></script>
<script src="../js/rating_.js"></script>
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
        if (!allPostsPhotos[postId]) allPostsPhotos[postId] = [];
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
    if (modalImg) modalImg.style.transform = `scale(${photoZoom}) translate(${photoOffsetX}px, ${photoOffsetY}px)`;
    const photoInfo = document.getElementById('photoInfo');
    if (photoInfo) photoInfo.textContent = `Zoom: ${Math.round(photoZoom * 100)}% | Scroll: Приблизить | Drag: Переместить`;
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
    if (contentArea) contentArea.addEventListener('click', function(e) { if (e.target === contentArea) closePhotoModal(); });
    
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
    photoZoom += (e.deltaY < 0 ? 0.1 : -0.1);
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
    currentMediaIndex = (currentMediaIndex + 1) % photos.length;
    document.getElementById('modalPhoto').src = photos[currentMediaIndex];
    resetPhotoTransform();
}

function prevPhoto() {
    if (!currentPostId || !allPostsPhotos[currentPostId]) return;
    const photos = allPostsPhotos[currentPostId];
    currentMediaIndex = (currentMediaIndex - 1 + photos.length) % photos.length;
    document.getElementById('modalPhoto').src = photos[currentMediaIndex];
    resetPhotoTransform();
}

document.addEventListener('DOMContentLoaded', initPhotoModal);

document.getElementById('sendComment')?.addEventListener('click', function () {
    const commentText = document.getElementById('commentText')?.value || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    $.ajax({
        url: 'comment_core',
        type: 'POST',
        data: {
            comment: commentText,
            post_id: <?php echo (int)$postId; ?>,
            csrf_token: csrfToken
        },
        success: function (response) {
            $('#comments').html(response);
        },
        error: function () {
            alert('При комментировании произошла ошибка.');
        }
    });
});
</script>
<?php require_once __DIR__ . '/../template/video_plugin.html'; ?>
</body>
</html>
<?php
session_write_close();
