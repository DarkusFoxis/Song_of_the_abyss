<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/video_layout.php';

$viewer = video_current_user();
$videoId = (int)($_GET['id'] ?? 0);

if ($videoId <= 0) {
    http_response_code(404);
    exit('Видео не найдено.');
}

$conn = video_db();
$video = video_fetch_video($conn, $videoId, $viewer);

if ($video === null) {
    mysqli_close($conn);
    http_response_code(404);
    exit('Видео не найдено или недоступно.');
}

if (!video_can_view($video, $viewer)) {
    mysqli_close($conn);
    http_response_code(403);
    video_render_header('Доступ запрещён - Video Blog', nsfw_access_denied_notice(), $viewer, 'catalog');
    echo '<section class="vb-message error">' . nsfw_access_denied_notice() . '</section>';
    video_render_footer();
    exit;
}

$viewSessionKey = 'vb_viewed_' . $videoId;
$alreadyViewed = !empty($_SESSION[$viewSessionKey]);
video_increment_view($conn, $video, $viewer);
if (($video['status'] ?? '') === 'published' && !$alreadyViewed && !($viewer !== null && (int)$viewer['id'] === (int)$video['user_id'])) {
    $video['views'] = (int)$video['views'] + 1;
}
$comments = video_fetch_comments($conn, $videoId);
mysqli_close($conn);

$canInteract = video_comments_enabled($video) && $viewer !== null;
$canRate = video_rating_enabled($video) && $viewer !== null;
$description = trim((string)$video['description']) !== '' ? (string)$video['description'] : 'Описание пока не добавлено.';

video_render_header(
    (string)$video['title'] . ' - Video Blog',
    video_excerpt((string)$description, 180),
    $viewer,
    'catalog'
);
?>
<section class="vb-detail">
    <div class="vb-detail-grid">
        <div>
            <?php if (($video['status'] ?? '') !== 'published') : ?>
                <div class="vb-message">
                    Это видео пока не опубликовано. Сейчас оно доступно только владельцу или администрации.
                </div>
            <?php endif; ?>

            <?php echo video_player_markup($video); ?>

            <h1><?php echo video_html((string)$video['title']); ?></h1>
            <div class="vb-card-top">
                <div class="vb-author">
                    <img class="vb-avatar" src="<?php echo video_avatar_url((string)$video['avatar']); ?>" alt="avatar">
                    <div>
                        <a href="/profile/profile?id=<?php echo (int)$video['user_id']; ?>">
                            <?php echo video_html((string)$video['username']); ?>
                        </a>
                        <div class="vb-note">
                            Статус: <?php echo video_status_label((string)$video['status']); ?>
                        </div>
                    </div>
                </div>
                <span class="vb-badge <?php echo video_status_class((string)$video['status']); ?>">
                    <?php echo video_status_label((string)$video['status']); ?>
                </span>
            </div>

            <div class="vb-meta-list">
                <span>Длительность: <?php echo video_format_duration((float)$video['duration_seconds']); ?></span>
                <span>Просмотры: <span id="video-views"><?php echo number_format((int)$video['views'], 0, '.', ' '); ?></span></span>
                <span>Размер: <?php echo video_format_bytes((int)$video['final_size_bytes']); ?></span>
                <span>Разрешение: <?php echo (int)$video['width']; ?>x<?php echo (int)$video['height']; ?></span>
                <span>Дата: <?php echo !empty($video['published_at']) ? date('d.m.Y H:i', strtotime((string)$video['published_at'])) : date('d.m.Y H:i', strtotime((string)$video['created_at'])); ?></span>
            </div>

            <div class="vb-actions">
                <div class="vb-rating-box">
                    <button
                        type="button"
                        id="rate-up"
                        class="vb-btn-primary"
                        <?php echo $canRate ? '' : 'disabled'; ?>
                    >
                        Like
                    </button>
                    <button
                        type="button"
                        id="rate-down"
                        class="vb-btn-secondary"
                        <?php echo $canRate ? '' : 'disabled'; ?>
                    >
                        Dislike
                    </button>
                    <span class="vb-badge">Оценка: <strong id="rating-total" style="margin-left:6px;"><?php echo number_format((float)$video['rating_total'], 2, '.', ' '); ?></strong></span>
                </div>
                <?php if (!$canRate && video_rating_enabled($video) && $viewer === null) : ?>
                    <span class="vb-note">Чтобы голосовать, нужно войти в аккаунт.</span>
                <?php elseif (!video_rating_enabled($video)) : ?>
                    <span class="vb-note">Для этого видео оценки отключены.</span>
                <?php endif; ?>
            </div>

            <div class="vb-form-panel" style="margin-top:18px;">
                <h2>Описание</h2>
                <div class="vb-comment-text"><?php echo video_multiline_html($description); ?></div>
            </div>
        </div>

        <aside class="vb-side-stack">
            <div class="vb-form-panel">
                <h2>Комментарии</h2>
                <div id="comments-box" class="vb-comments-list">
                    <?php if ($comments === []) : ?>
                        <div class="vb-empty">Комментариев пока нет.</div>
                    <?php else : ?>
                        <?php foreach ($comments as $comment) : ?>
                            <?php echo video_render_comment($comment); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="vb-form-panel">
                <h2>Оставить комментарий</h2>
                <?php if ($canInteract) : ?>
                    <div class="vb-field">
                        <label for="comment-text">Текст комментария</label>
                        <textarea id="comment-text" maxlength="2048" placeholder="Напишите комментарий..."></textarea>
                    </div>
                    <div class="vb-actions">
                        <button type="button" id="send-comment" class="vb-btn-primary">Отправить</button>
                    </div>
                <?php elseif (video_comments_enabled($video)) : ?>
                    <p class="vb-note">Чтобы комментировать, нужно войти в аккаунт.</p>
                <?php else : ?>
                    <p class="vb-note">Комментарии для этого видео отключены.</p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<script>
const videoId = <?php echo (int)$video['id']; ?>;

document.getElementById('rate-up')?.addEventListener('click', async () => {
    await submitVote(1);
});

document.getElementById('rate-down')?.addEventListener('click', async () => {
    await submitVote(-1);
});

async function submitVote(vote) {
    const body = new URLSearchParams({
        csrf_token: window.vbGetCsrf(),
        video_id: String(videoId),
        vote: String(vote)
    });

    const response = await fetch('<?php echo video_base_url('api/rate_video.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: body.toString(),
        credentials: 'same-origin'
    });

    const data = await window.vbReadJson(response);
    if (!data.success) {
        alert(data.error || 'Не удалось сохранить голос.');
        return;
    }

    document.getElementById('rating-total').textContent = data.rating;
}

document.getElementById('send-comment')?.addEventListener('click', async () => {
    const textarea = document.getElementById('comment-text');
    const text = textarea.value.trim();
    if (text.length < 1) {
        alert('Комментарий не должен быть пустым.');
        return;
    }

    const body = new URLSearchParams({
        csrf_token: window.vbGetCsrf(),
        video_id: String(videoId),
        comment: text
    });

    const response = await fetch('<?php echo video_base_url('api/comment_video.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: body.toString(),
        credentials: 'same-origin'
    });

    const data = await window.vbReadJson(response);
    if (!data.success) {
        alert(data.error || 'Не удалось отправить комментарий.');
        return;
    }

    textarea.value = '';
    const commentsBox = document.getElementById('comments-box');
    if (commentsBox.querySelector('.vb-empty')) {
        commentsBox.innerHTML = '';
    }
    commentsBox.insertAdjacentHTML('beforeend', data.comment_html);
}
);
</script>
<?php video_render_footer(); ?>
