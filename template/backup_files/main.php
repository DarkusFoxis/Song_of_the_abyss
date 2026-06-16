<?php
require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/post_helpers.php';

auth_start_session();
$viewer = auth_get_current_user();
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

$is_ajax = isset($_GET['ajax']);

$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$posts_per_page = 10;
$offset = ($current_page - 1) * $posts_per_page;

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'recent';
$tagFilter = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$canViewNsfw = nsfw_user_has_access($viewer);
if ($filter === 'nsfw' && !$canViewNsfw) {
    $filter = 'recent';
}

$baseSql = "SELECT p.*, u.username, u.avatar FROM post p JOIN users u ON p.id_user = u.id";
$nsfwFilter = "p.NSFW = 0";
$tagJoin = "";
$tagWhere = "";

$selectedTags = [];
if ($tagFilter !== '') {
    $selectedTags = array_map(function($tag) { return trim($tag); }, explode(',', $tagFilter));
    $selectedTags = array_filter($selectedTags, function($tag) { return $tag !== ''; });
    
    if (!empty($selectedTags)) {
        $tagJoin = " JOIN post_tags pst ON p.id_post = pst.id_post JOIN post_tag pt ON pst.id_tag = pt.id_tag";
        $placeholders = implode("','", array_map(function($tag) use ($conn) { 
            return $conn->real_escape_string($tag); 
        }, $selectedTags));
        $tagWhere = " AND pt.name IN ('" . $placeholders . "')";
    }
}

switch ($filter) {
    case 'top':
        $sql = "SELECT p.*, u.username, u.avatar FROM post p JOIN users u ON p.id_user = u.id" . $tagJoin . " WHERE p.NSFW = 0 AND p.total_rating > 0" . $tagWhere . " ORDER BY p.total_rating DESC LIMIT $posts_per_page OFFSET $offset";
        $total_posts_query = "SELECT COUNT(*) FROM post p" . $tagJoin . " WHERE p.NSFW = 0 AND p.total_rating > 0" . $tagWhere;
        break;
    case 'unrated':
        $sql = "SELECT p.*, u.username, u.avatar FROM post p JOIN users u ON p.id_user = u.id" . $tagJoin . " WHERE p.NSFW = 0 AND p.total_rating = 0" . $tagWhere . " ORDER BY UNIX_TIMESTAMP(p.data) DESC LIMIT $posts_per_page OFFSET $offset";
        $total_posts_query = "SELECT COUNT(*) FROM post p" . $tagJoin . " WHERE p.NSFW = 0 AND p.total_rating = 0" . $tagWhere;
        break;
    case 'abyss':
        $sql = "SELECT p.*, u.username, u.avatar FROM post p JOIN users u ON p.id_user = u.id" . $tagJoin . " WHERE p.NSFW = 0 AND p.total_rating < 0" . $tagWhere . " ORDER BY p.total_rating ASC LIMIT $posts_per_page OFFSET $offset";
        $total_posts_query = "SELECT COUNT(*) FROM post p" . $tagJoin . " WHERE p.NSFW = 0 AND p.total_rating < 0" . $tagWhere;
        break;
    case 'nsfw':
        $sql = "SELECT p.*, u.username, u.avatar FROM post p JOIN users u ON p.id_user = u.id" . $tagJoin . " WHERE p.NSFW = 1" . $tagWhere . " ORDER BY UNIX_TIMESTAMP(p.data) DESC LIMIT $posts_per_page OFFSET $offset";
        $total_posts_query = "SELECT COUNT(*) FROM post p" . $tagJoin . " WHERE p.NSFW = 1" . $tagWhere;
        break;
    default:
        $sql = "SELECT p.*, u.username, u.avatar FROM post p JOIN users u ON p.id_user = u.id" . $tagJoin . " WHERE p.NSFW = 0" . $tagWhere . " ORDER BY UNIX_TIMESTAMP(p.data) DESC LIMIT $posts_per_page OFFSET $offset";
        $total_posts_query = "SELECT COUNT(*) FROM post p" . $tagJoin . " WHERE p.NSFW = 0" . $tagWhere;
        break;
}

$result = $conn->query($sql);

$total_posts = $conn->query($total_posts_query)->fetch_assoc()['COUNT(*)'];
$total_pages = ceil($total_posts / $posts_per_page);
setlocale(LC_TIME, 'ru_RU.UTF-8');

function render_post_card(array $row, mysqli $conn): void
{
    $postId = (int)$row['id_post'];
    $isNsfwPost = abyss_post_is_nsfw($row);
    $canRatePost = abyss_post_can_rate($row);
    $commentCountQuery = "SELECT COUNT(*) AS comment_count FROM comment WHERE id_post = " . $postId;
    $commentCountResult = $conn->query($commentCountQuery);
    $commentCount = $commentCountResult ? (int)$commentCountResult->fetch_assoc()['comment_count'] : 0;
    ?>
    <div class="post-card <?php echo $isNsfwPost ? 'post-card-nsfw' : ''; ?>">
        <a class="link" href="/profile/profile?id=<?php echo (int)$row["id_user"]; ?>">
            <img class="avatar" src="../profile/avatars/<?php echo security_html(basename((string)$row['avatar'])); ?>" alt="Аватар" title="<?php echo security_html((string)$row['username']); ?> аватар" loading="lazy"> <?php echo security_html((string)$row['username']); ?>
        </a>
        <?php if ($isNsfwPost) : ?>
            <span class="nsfw-badge">NSFW</span>
        <?php endif; ?>
        <hr>
        <h3><?php echo (string)$row['title']; ?></h3>
        <?php
        $postTags = get_post_tags($conn, $postId);
        if ($postTags !== []) : ?>
            <div class="post-tags">
                <?php foreach ($postTags as $tag) : ?>
                    <span class="tag-link" onclick="filterByTag('<?php echo security_html($tag); ?>')">
                        #<?php echo security_html($tag); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p><?php echo nl2br((string)$row['post']); ?></p>
        <?php if (isset($_SESSION['user']) && $_SESSION['user'] == $row['username']) : ?>
            <?php
            $postTime = strtotime((string)$row['data']);
            $hoursDiff = (time() - $postTime) / 3600;
            $allowEdit = ($hoursDiff <= 3);
            ?>
            <div class="post-actions" style="position:absolute; top:10px; right:10px;">
                <button class="dots-btn" onclick="toggleMenu(this)">⋮</button>
                <div class="actions-menu" style="display:none; position:absolute; right:0; background:#222; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.2); z-index:10;">
                    <button onclick="editPost(<?php echo $postId; ?>)" <?php echo !$allowEdit ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                        <?php echo $allowEdit ? 'Редактировать' : 'Редакт. (заблокировано)'; ?>
                    </button>
                    <button onclick="deletePost(<?php echo $postId; ?>)">Удалить</button>
	                </div>
            </div>
        <?php endif; ?>
        <div class="media">
            <?php
            $mediaFiles = !empty($row['media']) ? explode(',', (string)$row['media']) : [];
            if ($mediaFiles !== []) : ?>
                <div class="media-container">
                    <?php foreach ($mediaFiles as $media_file_index => $mediaFile) :
                        $mediaFile = basename(trim((string)$mediaFile));
                        if ($mediaFile === '') {
                            continue;
                        }
                        $extension = strtolower(pathinfo($mediaFile, PATHINFO_EXTENSION));
                        $mediaUrl = abyss_post_media_url($row, $mediaFile);
                        $safeMediaUrl = security_html($mediaUrl);
                        $mediaLabel = security_html(explode('_', $mediaFile, 2)[1] ?? $mediaFile);
                    ?>
                        <div class="media-item">
                            <?php if (in_array($extension, ['jpg','jpeg','png','gif','webp','jfif'], true)) : ?>
                                <img src="<?php echo $safeMediaUrl; ?>" alt="Медиа из поста" loading="lazy" onclick="openPhotoModal(this, <?php echo $postId; ?>, <?php echo $media_file_index; ?>)" data-post-id="<?php echo $postId; ?>" data-media-index="<?php echo $media_file_index; ?>">
                            <?php elseif ($extension === 'mp3') : ?>
                                <button class="play-button" onclick="playButtonStart('<?php echo security_html((string)$row['title'] . ' - ' . $mediaLabel); ?>', '<?php echo $safeMediaUrl; ?>')">
                                    ▶ Воспроизвести <?php echo $mediaLabel; ?>
                                </button>
                            <?php elseif ($extension === 'mp4') : ?>
                                <video class="media" loading="lazy" playsinline>
                                    <source src="<?php echo $safeMediaUrl; ?>" type="video/mp4">
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <hr>
        <p>Опубликовано: <?php echo date("H:i d.m.Y", strtotime((string)$row['data'])); ?></p>
        <a class="link" href="post?id=<?php echo $postId; ?>">Полный пост</a>
        <a href="post?id=<?php echo $postId; ?>#comments"><div class="comment-count">
            <span><img src="./icon/comment.png" width="35" height="35" style="object-fit: cover;"> <?php echo $commentCount; ?></span>
        </div></a>
        <?php if ($canRatePost) : ?>
            <div class="rating-container">
                <div class="rating-buttons">
                    <button id="btn-plus-<?php echo $postId; ?>" class="rating-btn rating-btn-plus" onclick="ratePost(<?php echo $postId; ?>, 1)">▲</button>
                    <button id="btn-minus-<?php echo $postId; ?>" class="rating-btn rating-btn-minus" onclick="ratePost(<?php echo $postId; ?>, -1)">▼</button>
                </div>
                <div class="rating-value"><span id="rating-<?php echo $postId; ?>"><?php echo round((float)$row['total_rating'], 2); ?></span></div>
            </div>
        <?php else : ?>
            <div class="rating-container rating-disabled">NSFW-посты оценке не подлежат!</div>
        <?php endif; ?>
    </div>
    <?php
}

if ($is_ajax) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            render_post_card($row, $conn);
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
	<link rel="stylesheet" href="../style/bootstrap.min.css">
<link rel = "stylesheet" href = "../style/style.css">
    <link rel = "stylesheet" href = "../style/player.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Abyss Net - Интернет бездны, и по совместительству первая социальная сеть в бездне, где любой житель может общаться с другими душами на любом расстоянии, как и в нашем реальном мире!"/>
    <meta property="og:title" content="Abyss Net"/>
    <meta property="og:site_name" content="Song of the  abyss"/>
    <meta property="og:type" content="website"/>
    <meta property="og:url" content="https://so-ta.ru/abyss_net/main"/>
    <meta property="og:description" content="Abyss Net - Интернет бездны, и по совместительству первая социальная сеть в бездне, где любой житель может общаться с другими душами на любом расстоянии, как и в нашем реальном мире!"/>
    <?php echo security_csrf_meta_tags(); ?>
    <link rel="stylesheet" href="../style/rating_.css">
    <script src="../js/rating_.js"></script>
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
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
            padding: 0 10px;
        }
        .filter-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, rgba(102, 51, 153, 0.6), rgba(186, 20, 126, 0.6));
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 25px;
            cursor: pointer;
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .filter-btn:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .filter-btn:hover:before {
            width: 300px;
            height: 300px;
        }
        .filter-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(186, 20, 126, 0.4);
            border-color: rgba(255, 255, 255, 0.4);
        }
        .filter-btn.active {
            background: linear-gradient(135deg, #ba147e, #ff6600);
            border-color: #fff;
            box-shadow: 0 5px 20px rgba(186, 20, 126, 0.6);
            transform: scale(1.05);
        }
        .filter-btn span {
            position: relative;
            z-index: 1;
        }
        @media (max-width: 768px) {
            .filter-tabs {
                gap: 8px;
            }
            .filter-btn {
                padding: 10px 18px;
                font-size: 14px;
            }
        }
        .post-card {
            background: -webkit-gradient(linear, 0 100%, 0 0, from(rgba(186,20,126,0.5)), color-stop(0.5, rgba(60,9,121,1)), to(rgba(255,102,0,0.5)));
            background: -webkit-linear-gradient(90deg, rgba(186,20,126,0.5) 0%, rgba(60,9,121,1) 50%, rgba(255,102,0,0.5) 100%);
            background: -moz-linear-gradient(90deg, rgba(186,20,126,0.5) 0%, rgba(60,9,121,1) 50%, rgba(255,102,0,0.5) 100%);
            background: -o-linear-gradient(90deg, rgba(186,20,126,0.5) 0%, rgba(60,9,121,1) 50%, rgba(255,102,0,0.5) 100%);
            background: linear-gradient(90deg, rgba(186,20,126,0.5) 0%, rgba(60,9,121,1) 50%, rgba(255,102,0,0.5) 100%);
            border-radius: 30px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            position: relative;
        }
	    .post-card-nsfw {
	        border: 2px solid rgba(255, 87, 126, 0.75);
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
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .media-item img:hover {
            transform: scale(1.02);
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
        .media-item video {
            width: 100%;
            height: auto;
            max-height: 750px;
            border-radius: 12px;
            background: black;
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
        @media (max-width: 520px){
            .post-card {
                padding: 10px;
                margin-right: -10px;
                margin-left: -10px;
            }
            .rating-disabled {
    	        color: #ffd6de;
    	        font-weight: 500;
    	        font-size: 0.8rem;
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
        .post-tags {
            margin: 8px 0;
        }
        .post-tags .tag-link {
            display: inline-block;
            margin: 2px 4px;
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 12px;
            color: #ffd6de;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .post-tags .tag-link:hover {
            background: rgba(255, 102, 0, 0.3);
            border-color: rgba(255, 102, 0, 0.5);
            transform: scale(1.05);
        }
        .tag-search-container {
            display: flex;
            gap: 10px;
            margin: 20px 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .tag-search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            color: #fff;
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .tag-search-input:focus {
            outline: none;
            border-color: rgba(255, 102, 0, 0.6);
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 10px rgba(255, 102, 0, 0.3);
        }
        .tag-search-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .tag-search-btn, .tag-clear-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, rgba(102, 51, 153, 0.6), rgba(186, 20, 126, 0.6));
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 20px;
            cursor: pointer;
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .tag-search-btn:hover, .tag-clear-btn:hover {
            background: linear-gradient(135deg, #ba147e, #ff6600);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(186, 20, 126, 0.4);
        }
        .tag-clear-btn {
            background: linear-gradient(135deg, rgba(255, 87, 126, 0.5), rgba(255, 102, 0, 0.5));
        }
        .active-tags-display {
            display: flex;
            gap: 10px;
            margin: 15px 10px;
            flex-wrap: wrap;
            align-items: center;
            padding: 10px 15px;
            background: rgba(255, 102, 0, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(255, 102, 0, 0.3);
        }
        .active-tags-display span {
            color: #ffd6de;
            font-weight: 600;
            margin-right: 5px;
        }
        .active-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(255, 102, 0, 0.3);
            border: 1px solid rgba(255, 102, 0, 0.5);
            border-radius: 15px;
            color: #fff;
            font-size: 13px;
        }
        .remove-tag {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            padding: 0;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .remove-tag:hover {
            opacity: 1;
        }
        @media (max-width: 768px) {
            .tag-search-container {
                margin: 15px 5px;
                gap: 8px;
            }
            .tag-search-input {
                min-width: 200px;
                font-size: 13px;
                padding: 10px 12px;
            }
            .tag-search-btn, .tag-clear-btn {
                padding: 8px 16px;
                font-size: 13px;
            }
            .active-tags-display {
                margin: 10px 5px;
                padding: 8px 12px;
                font-size: 12px;
            }
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
                <div class="header">
                    <h2>Abyss Net - Блоги</h2>
                </div>

                <div class="filter-tabs">
                    <button class="filter-btn <?php echo ($filter == 'recent') ? 'active' : ''; ?>" onclick="changeFilter('recent')" data-filter="recent"><span>📅 Последние</span></button>
                    <button class="filter-btn <?php echo ($filter == 'top') ? 'active' : ''; ?>" onclick="changeFilter('top')" data-filter="top"><span>🔥 Топ посты</span></button>
                    <button class="filter-btn <?php echo ($filter == 'unrated') ? 'active' : ''; ?>" onclick="changeFilter('unrated')" data-filter="unrated"><span>❓ Без оценок</span></button>
                    <button class="filter-btn <?php echo ($filter == 'abyss') ? 'active' : ''; ?>" onclick="changeFilter('abyss')" data-filter="abyss"><span>💀 Бездна</span></button>
	                    <?php if ($canViewNsfw) : ?>
	                        <button class="filter-btn <?php echo ($filter == 'nsfw') ? 'active' : ''; ?>" onclick="changeFilter('nsfw')" data-filter="nsfw"><span>NSFW</span></button>
	                    <?php endif; ?>
	                </div>

                <div class="tag-search-container">
                    <input type="text" id="tagSearchInput" class="tag-search-input" placeholder="Поиск по тегам (через запятую): #tag1, #tag2" value="<?php echo security_html($tagFilter); ?>">
                    <button class="tag-search-btn" onclick="searchByTags()">🔍 Найти</button>
                    <?php if (!empty($selectedTags)) : ?>
                        <button class="tag-clear-btn" onclick="clearTagSearch()">✕ Очистить</button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($selectedTags)) : ?>
                    <div class="active-tags-display">
                        <span>Активные теги:</span>
                        <?php foreach ($selectedTags as $tag) : ?>
                            <span class="active-tag">
                                #<?php echo security_html($tag); ?>
                                <button class="remove-tag" onclick="removeTag('<?php echo security_html($tag); ?>')">✕</button>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

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
                    <?php while ($row = $result->fetch_assoc()) { render_post_card($row, $conn); } ?>
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
<script>
let currentPage = 1;
let isLoading = false;
let hasMorePosts = true;
let currentFilter = '<?php echo $filter; ?>';
let currentTagFilter = '<?php echo security_html($tagFilter); ?>';
let initialLoad = <?php echo $is_ajax ? 'false' : 'true'; ?>;

function changeFilter(filter) {
    currentFilter = filter;
    currentPage = 1;
    hasMorePosts = true;
    initialLoad = false;

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    document.querySelector(`.filter-btn[data-filter="${filter}"]`).classList.add('active');

    const url = new URL(window.location);
    url.searchParams.set('filter', filter);
    window.history.pushState({}, '', url);

    $('#posts-container').html('');
    $('#end-of-posts').hide();

    loadMorePosts();
}

function isBottomReached() {
    return window.innerHeight + window.scrollY >= document.body.offsetHeight - 100;
}

function loadMorePosts() {
    if (isLoading || !hasMorePosts) return;

    if (initialLoad) {
        initialLoad = false;
        currentPage = 2;
    }

    isLoading = true;

    $('#loading-indicator').show();

    const url = new URL(window.location);
    const params = new URLSearchParams(url.search);
    
    let ajaxUrl = '?page=' + currentPage + '&ajax=1&filter=' + currentFilter;
    if (params.has('tag')) {
        ajaxUrl += '&tag=' + encodeURIComponent(params.get('tag'));
    }

    $.ajax({
        url: ajaxUrl,
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
                loadUserVotes();
                currentPage++;
            }
        },
        error: function() {
            console.error('Ошибка при загрузке постов');
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        $.ajax({
            url: 'delete_post',
            type: 'POST',
            data: { id_post: id, csrf_token: csrfToken },
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

function searchByTags() {
    const input = document.getElementById('tagSearchInput');
    let tags = input.value.trim();
    
    if (tags === '') {
        clearTagSearch();
        return;
    }
    
    // Убираем # если пользователь их ввел
    tags = tags.split(',').map(tag => {
        tag = tag.trim();
        if (tag.startsWith('#')) {
            tag = tag.substring(1);
        }
        return tag;
    }).filter(tag => tag !== '').join(',');
    
    if (tags === '') {
        clearTagSearch();
        return;
    }
    
    const url = new URL(window.location);
    url.searchParams.set('tag', tags);
    url.searchParams.set('filter', currentFilter);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function filterByTag(tag) {
    // Клик по тегу в посте - добавляем его к текущему фильтру
    const input = document.getElementById('tagSearchInput');
    let currentValue = input.value.trim();
    
    // Убираем # если пользователь их ввел
    currentValue = currentValue.split(',').map(t => {
        t = t.trim();
        if (t.startsWith('#')) {
            t = t.substring(1);
        }
        return t;
    }).filter(t => t !== '');
    
    // Проверяем, есть ли уже этот тег
    if (!currentValue.includes(tag)) {
        currentValue.push(tag);
    }
    
    input.value = currentValue.map(t => '#' + t).join(', ');
    searchByTags();
}

function removeTag(tagToRemove) {
    const input = document.getElementById('tagSearchInput');
    let tags = input.value.trim().split(',').map(tag => {
        tag = tag.trim();
        if (tag.startsWith('#')) {
            tag = tag.substring(1);
        }
        return tag;
    }).filter(tag => tag !== '');
    
    tags = tags.filter(tag => tag !== tagToRemove);
    
    if (tags.length === 0) {
        clearTagSearch();
    } else {
        input.value = tags.map(t => '#' + t).join(', ');
        searchByTags();
    }
}

function clearTagSearch() {
    const url = new URL(window.location);
    url.searchParams.delete('tag');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

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
</script>
<?php  require_once __DIR__ . '/../template/video_plugin.html'; ?>
<script src="../js/player.js"></script>
</body>
</html>

