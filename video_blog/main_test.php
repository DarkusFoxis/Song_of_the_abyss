<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/video_layout.php';

$viewer = video_current_user();
$conn = video_db();
$canViewNsfw = nsfw_user_has_access($viewer);
$showNsfw = $canViewNsfw && (string)($_GET['show_nsfw'] ?? '0') === '1';
$visibilityWhere = $showNsfw ? 'vp.NSFW IN (0, 1)' : 'vp.NSFW = 0';
$statsWhere = $showNsfw ? 'NSFW IN (0, 1)' : 'NSFW = 0';

$videos = [];
$result = $conn->query("SELECT vp.*, u.username, u.avatar, (SELECT COUNT(*) FROM video_comments vc WHERE vc.video_id = vp.id) AS comments_count FROM video_posts vp JOIN users u ON u.id = vp.user_id WHERE vp.status = 'published' AND $visibilityWhere ORDER BY COALESCE(vp.published_at, vp.created_at) DESC LIMIT 15");

if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
}

$stats = [
    'published' => 0,
    'views' => 0,
];

$statsResult = $conn->query("SELECT COUNT(*) AS total_published, COALESCE(SUM(views), 0) AS total_views FROM video_posts WHERE status = 'published' AND $statsWhere");
if ($statsResult instanceof mysqli_result) {
    $statsRow = $statsResult->fetch_assoc();
    $stats['published'] = (int)($statsRow['total_published'] ?? 0);
    $stats['views'] = (int)($statsRow['total_views'] ?? 0);
}

mysqli_close($conn);

video_render_header(
    'Video Blog',
    'Бета-видеохостинг проекта: каталог видео, лайки, комментарии и собственная загрузка.',
    $viewer,
    'catalog'
);
?>
<section class="vb-hero">
    <h1>Video Blog</h1>
    <p class="vb-muted">
        Бета-версия видеохостинга внутри сайта. Сейчас здесь показываются последние опубликованные видео,
        а загрузка работает с месячными лимитами по роли.
    </p>
    <div class="vb-stat-grid">
        <div class="vb-stat">
            <strong><?php echo $stats['published']; ?></strong>
            <span>Опубликовано видео</span>
        </div>
        <div class="vb-stat">
            <strong><?php echo number_format($stats['views'], 0, '.', ' '); ?></strong>
            <span>Всего просмотров</span>
        </div>
        <div class="vb-stat">
            <strong>15</strong>
            <span>Видео в ленте беты</span>
        </div>
    </div>
    <div class="vb-actions">
        <a class="vb-btn-primary" href="<?php echo video_base_url('upload'); ?>">Загрузить видео</a>
        <a class="vb-btn-secondary" href="<?php echo video_base_url('studio'); ?>">Открыть мои видео</a>
    </div>
    <?php if ($canViewNsfw) : ?>
        <form class="vb-inline-check" method="get">
            <input id="show-nsfw" type="checkbox" name="show_nsfw" value="1" <?php echo $showNsfw ? 'checked' : ''; ?> onchange="this.form.submit()">
            <label for="show-nsfw">Показать NSFW</label>
        </form>
    <?php endif; ?>
</section>

<?php if ($videos === []) : ?>
    <section class="vb-empty">Пока опубликованных видео нет. Можно стать первым автором в бете.</section>
<?php else : ?>
    <section class="vb-grid">
        <?php foreach ($videos as $video) : ?>
            <article class="vb-card">
                <?php echo video_player_markup($video, true); ?>
                <div class="vb-card-top">
                    <div class="vb-author">
                        <img class="vb-avatar" src="<?php echo video_avatar_url((string)$video['avatar']); ?>" alt="avatar" loading="lazy">
                        <div>
                            <a href="/profile/profile?id=<?php echo (int)$video['user_id']; ?>">
                                <?php echo video_html((string)$video['username']); ?>
                            </a>
                            <div class="vb-note">
                                <?php echo !empty($video['published_at']) ? date('d.m.Y H:i', strtotime((string)$video['published_at'])) : date('d.m.Y H:i', strtotime((string)$video['created_at'])); ?>
                            </div>
                        </div>
                    </div>
	                    <?php if ((int)($video['NSFW'] ?? 0) === 1) : ?>
	                        <span class="vb-badge vb-nsfw-badge">NSFW</span>
	                    <?php endif; ?>
	                </div>
	                <h3><?php echo video_html((string)$video['title']); ?></h3>
                <p class="vb-muted">
                    <?php echo video_html(video_excerpt((string)$video['description'], 170)); ?>
                </p>
                <div class="vb-meta">
                    <span>Просмотры: <?php echo number_format((int)$video['views'], 0, '.', ' '); ?></span>
                    <span>Рейтинг: <?php echo number_format((float)$video['rating_total'], 2, '.', ' '); ?></span>
                    <span>Комментарии: <?php echo (int)$video['comments_count']; ?></span>
                </div>
                <div class="vb-actions">
                    <a class="vb-btn-primary" href="<?php echo video_watch_url((int)$video['id']); ?>">Смотреть</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php video_render_footer(); ?>
