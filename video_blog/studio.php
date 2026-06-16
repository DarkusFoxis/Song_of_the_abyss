<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/video_layout.php';

$viewer = video_require_user();
$conn = video_db();
$videos = video_fetch_user_videos($conn, (int)$viewer['id'], $viewer);
$policy = video_upload_policy($viewer);
$remaining = video_remaining_uploads($conn, $viewer);
$canUseNsfw = nsfw_user_has_access($viewer);
mysqli_close($conn);

video_render_header(
    'Мои видео - Video Blog',
    'Личный кабинет автора: статистика, публикация, редактирование и черновики.',
    $viewer,
    'studio'
);
?>
<section class="vb-hero">
    <h1>Мои видео</h1>
    <p class="vb-muted">
        Здесь можно смотреть статистику, обновлять описание, менять название в течение одного дня после публикации,
        переводить ролик в черновик и управлять комментариями. Если комментарии отключены, оценки тоже отключаются.
    </p>
    <div class="vb-stat-grid">
        <div class="vb-stat">
            <strong><?php echo count($videos); ?></strong>
            <span>Всего ваших видео</span>
        </div>
        <div class="vb-stat">
            <strong><?php echo $remaining; ?> / <?php echo (int)$policy['monthly_limit']; ?></strong>
            <span>Осталось загрузок в этом месяце</span>
        </div>
        <div class="vb-stat">
            <strong><?php echo $policy['label']; ?></strong>
            <span>Текущий уровень доступа</span>
        </div>
    </div>
    <div class="vb-actions">
        <a class="vb-btn-primary" href="<?php echo video_base_url('upload'); ?>">Загрузить новое видео</a>
    </div>
</section>

<?php if ($videos === []) : ?>
    <section class="vb-empty">
        У вас пока нет видео. Можно начать с первой загрузки.
    </section>
<?php else : ?>
    <section class="vb-grid">
        <?php foreach ($videos as $video) : ?>
            <?php
            $titleEditable = video_title_can_be_edited($video);
            $status = (string)$video['status'];
            $toggleStatus = $status === 'published' ? 'draft' : 'published';
            $toggleLabel = $status === 'published' ? 'Перевести в черновик' : 'Опубликовать';
            ?>
            <article class="vb-card" data-video-card="<?php echo (int)$video['id']; ?>">
                <?php echo video_player_markup($video, true); ?>
                <div class="vb-card-top">
                    <span class="vb-badge <?php echo video_status_class($status); ?>" data-status-label>
                        <?php echo video_status_label($status); ?>
                    </span>
                    <a class="vb-btn-secondary" href="<?php echo video_watch_url((int)$video['id']); ?>">Открыть</a>
                </div>

                <div class="vb-meta-list">
                    <span>Просмотры: <?php echo number_format((int)$video['views'], 0, '.', ' '); ?></span>
                    <span>Рейтинг: <?php echo number_format((float)$video['rating_total'], 2, '.', ' '); ?></span>
                    <span>Комментарии: <?php echo (int)$video['comments_count']; ?></span>
                    <span>Размер: <?php echo video_format_bytes((int)$video['final_size_bytes']); ?></span>
                </div>

                <div class="vb-field" style="margin-top:16px;">
                    <label for="title-<?php echo (int)$video['id']; ?>">Название</label>
                    <input
                        id="title-<?php echo (int)$video['id']; ?>"
                        type="text"
                        maxlength="160"
                        value="<?php echo video_html((string)$video['title']); ?>"
                        data-title
                        <?php echo $titleEditable ? '' : 'disabled'; ?>
                    >
                    <span class="vb-note">
                        <?php echo $titleEditable ? 'Название сейчас можно менять.' : 'Срок изменения названия истёк.'; ?>
                    </span>
                </div>

                <div class="vb-field" style="margin-top:14px;">
                    <label for="description-<?php echo (int)$video['id']; ?>">Описание</label>
                    <textarea id="description-<?php echo (int)$video['id']; ?>" maxlength="6000" data-description><?php echo video_html((string)$video['description']); ?></textarea>
                </div>

                <div class="vb-inline-check">
                    <input
                        id="comments-<?php echo (int)$video['id']; ?>"
                        type="checkbox"
                        data-comments
                        <?php echo (int)$video['allow_comments'] === 1 ? 'checked' : ''; ?>
                    >
                    <label for="comments-<?php echo (int)$video['id']; ?>">Комментарии и оценки включены</label>
                </div>

                <div class="vb-table-actions">
                    <button
                        type="button"
                        class="vb-btn-primary"
                        data-save-meta
                        data-video-id="<?php echo (int)$video['id']; ?>"
                    >
                        Сохранить
                    </button>
                    <button
                        type="button"
                        class="vb-btn-secondary"
                        data-toggle-status
                        data-video-id="<?php echo (int)$video['id']; ?>"
                        data-target-status="<?php echo $toggleStatus; ?>"
                    >
                        <?php echo $toggleLabel; ?>
                    </button>
                </div>
                <div class="vb-note" data-feedback></div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<script>
document.querySelectorAll('[data-save-meta]').forEach((button) => {
    button.addEventListener('click', async () => {
        const card = button.closest('[data-video-card]');
        const videoId = button.dataset.videoId;
        const titleInput = card.querySelector('[data-title]');
        const descriptionInput = card.querySelector('[data-description]');
        const commentsInput = card.querySelector('[data-comments]');
        const feedback = card.querySelector('[data-feedback]');

        const body = new URLSearchParams({
            csrf_token: window.vbGetCsrf(),
            action: 'save_meta',
            video_id: String(videoId),
            title: titleInput ? titleInput.value : '',
            description: descriptionInput ? descriptionInput.value : '',
            allow_comments: commentsInput && commentsInput.checked ? '1' : '0'
        });

        const response = await fetch('<?php echo video_base_url('api/update_video'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString(),
            credentials: 'same-origin'
        });

        const data = await window.vbReadJson(response);
        feedback.textContent = data.success ? data.message : (data.error || 'Не удалось сохранить изменения.');
    });
});

document.querySelectorAll('[data-toggle-status]').forEach((button) => {
    button.addEventListener('click', async () => {
        const card = button.closest('[data-video-card]');
        const videoId = button.dataset.videoId;
        const targetStatus = button.dataset.targetStatus;
        const feedback = card.querySelector('[data-feedback]');
        const statusLabel = card.querySelector('[data-status-label]');

        const body = new URLSearchParams({
            csrf_token: window.vbGetCsrf(),
            action: 'set_status',
            video_id: String(videoId),
            target_status: targetStatus
        });

        const response = await fetch('<?php echo video_base_url('api/update_video'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString(),
            credentials: 'same-origin'
        });

        const data = await window.vbReadJson(response);
        if (!data.success) {
            feedback.textContent = data.error || 'Не удалось изменить статус.';
            return;
        }

        feedback.textContent = data.message;
        statusLabel.textContent = data.status_label;
        statusLabel.className = 'vb-badge ' + data.status_class;

        button.dataset.targetStatus = data.next_target_status;
        button.textContent = data.next_action_label;
    });
});
</script>
<?php video_render_footer(); ?>
