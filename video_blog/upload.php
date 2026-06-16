<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/video_layout.php';

$viewer = video_require_user();
$conn = video_db();
$policy = video_upload_policy($viewer);
$remaining = video_remaining_uploads($conn, $viewer);
$used = video_get_monthly_usage($conn, (int)$viewer['id']);
$canUseNsfw = nsfw_user_has_access($viewer);
mysqli_close($conn);

video_render_header(
    'Загрузка видео - Video Blog',
    'Загрузка и обработка видео в beta-модуль видеохостинга.',
    $viewer,
    'upload'
);
?>
<section class="vb-hero">
    <h1>Загрузка видео</h1>
    <p class="vb-muted">
        После обработки видео сохраняется как черновик и становится доступным только вам.
        Публикация произойдёт только после отдельного подтверждения.
    </p>
    <div class="vb-stat-grid">
        <div class="vb-stat">
            <strong><?php echo $policy['label']; ?></strong>
            <span>Текущий уровень доступа</span>
        </div>
        <div class="vb-stat">
            <strong><?php echo $remaining; ?> / <?php echo (int)$policy['monthly_limit']; ?></strong>
            <span>Осталось загрузок в этом месяце</span>
        </div>
        <div class="vb-stat">
            <strong><?php echo video_format_bytes((int)$policy['max_source_bytes']); ?></strong>
            <span>Максимальный размер исходника</span>
        </div>
    </div>
</section>

<section class="vb-form-panel">
    <h2>Новая загрузка</h2>
    <?php if (!$policy['can_upload']) : ?>
        <div class="vb-message error">У вашей роли нет доступа к загрузке видео.</div>
    <?php elseif ($remaining <= 0) : ?>
        <div class="vb-message error">
            Вы уже использовали весь месячный лимит: <?php echo $used; ?> из <?php echo (int)$policy['monthly_limit']; ?>.
        </div>
    <?php endif; ?>

    <form id="upload-form">
        <div class="vb-form-grid">
            <div class="vb-field">
                <label for="video-file">Видео</label>
                <input
                    id="video-file"
                    name="video"
                    type="file"
                    accept="video/mp4,video/webm,video/quicktime,video/x-matroska"
                    <?php echo ($remaining > 0 && $policy['can_upload']) ? '' : 'disabled'; ?>
                    required
                >
                <span class="vb-note">Поддерживаются mp4, webm, mov, mkv.</span>
            </div>

            <div class="vb-field">
                <label for="video-title">Название</label>
                <input
                    id="video-title"
                    name="title"
                    type="text"
                    maxlength="160"
                    placeholder="Введите название видео"
                    <?php echo ($remaining > 0 && $policy['can_upload']) ? '' : 'disabled'; ?>
                >
            </div>

            <div class="vb-field">
                <label for="cover-file">Своя обложка</label>
                <input
                    id="cover-file"
                    name="cover"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    <?php echo ($remaining > 0 && $policy['can_upload']) ? '' : 'disabled'; ?>
                >
                <span class="vb-note">Если ничего не выбрать, будет взят случайный кадр из видео.</span>
            </div>
        </div>

        <div class="vb-field" style="margin-top:18px;">
            <label for="video-description">Описание</label>
            <textarea
                id="video-description"
                name="description"
                maxlength="6000"
                placeholder="Опишите видео"
                <?php echo ($remaining > 0 && $policy['can_upload']) ? '' : 'disabled'; ?>
            ></textarea>
        </div>

        <div class="vb-inline-check">
            <input
                id="allow-comments"
                name="allow_comments"
                type="checkbox"
                checked
                <?php echo ($remaining > 0 && $policy['can_upload']) ? '' : 'disabled'; ?>
            >
            <label for="allow-comments">Разрешить комментарии и оценки</label>
        </div>

	        <?php if ($canUseNsfw) : ?>
	            <div class="vb-inline-check">
	                <input
	                    id="video-nsfw"
	                    name="nsfw"
	                    type="checkbox"
	                    value="1"
	                    <?php echo ($remaining > 0 && $policy['can_upload']) ? '' : 'disabled'; ?>
	                >
	                <label for="video-nsfw">NSFW</label>
	            </div>
	            <span class="vb-note">NSFW-видео нельзя оценивать, лимит размера меньше на 10%, а загрузка тратит 2 месячных слота.</span>
	        <?php endif; ?>

	        <div class="vb-video-preview" id="video-preview">
            <video id="preview-player" controls playsinline data-df-native="1"></video>
        </div>

        <div class="vb-actions">
            <button
                type="submit"
                id="start-upload"
                class="vb-btn-primary"
                <?php echo ($remaining > 0 && $policy['can_upload']) ? '' : 'disabled'; ?>
            >
                Подтвердить и начать загрузку
            </button>
        </div>
    </form>

    <div class="vb-progress-wrap" id="upload-status" style="display:none;">
        <div class="vb-big-progress"><div id="upload-progress-bar"></div></div>
        <div id="upload-status-text" class="vb-note">Подготовка...</div>
    </div>

    <div id="upload-result" style="display:none; margin-top:18px;">
        <div class="vb-message success" id="upload-result-message"></div>
        <div class="vb-actions">
            <button type="button" id="publish-video" class="vb-btn-primary">Опубликовать</button>
            <a class="vb-btn-secondary" id="open-preview" href="#">Открыть страницу видео</a>
            <a class="vb-btn-secondary" href="<?php echo video_base_url('studio'); ?>">Перейти в мои видео</a>
        </div>
    </div>
</section>

<script>
let readyVideoId = 0;
let uploadPollTimer = 0;
let uploadPollToken = '';
let uploadPollInFlight = false;
let previewObjectUrl = '';
let displayedUploadProgress = 0;
let uploadRequestInProgress = false;

const uploadStatusUrl = '<?php echo video_base_url('api/upload_status.php'); ?>';
const uploadStatusStartDelay = 1200;
const uploadStatusDelay = 2500;
const uploadStatusBackoffDelay = 5000;

function makeUploadToken() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID().replace(/[^A-Za-z0-9_-]/g, '');
    }

    if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
        const values = new Uint32Array(4);
        window.crypto.getRandomValues(values);
        return Array.from(values, (value) => value.toString(36)).join('');
    }

    return 'upload' + Date.now().toString(36) + Math.random().toString(36).slice(2, 14);
}

function stopUploadPolling() {
    if (uploadPollTimer) {
        window.clearTimeout(uploadPollTimer);
        uploadPollTimer = 0;
    }

    uploadPollToken = '';
    uploadPollInFlight = false;
}

function setUploadProgress(percent, message, force = false) {
    const progressBar = document.getElementById('upload-progress-bar');
    const statusText = document.getElementById('upload-status-text');
    const safePercent = Math.max(0, Math.min(100, Math.round(Number(percent) || 0)));
    const nextPercent = force ? safePercent : Math.max(displayedUploadProgress, safePercent);

    displayedUploadProgress = nextPercent;
    progressBar.style.width = nextPercent + '%';
    if (message) {
        statusText.textContent = message;
    }
}

function scheduleUploadPolling(uploadToken, delay) {
    if (!uploadToken || uploadPollToken !== uploadToken) {
        return;
    }

    if (uploadPollTimer) {
        window.clearTimeout(uploadPollTimer);
    }

    uploadPollTimer = window.setTimeout(() => {
        uploadPollTimer = 0;
        readUploadStatus(uploadToken);
    }, delay);
}

async function readUploadStatus(uploadToken) {
    if (!uploadToken || uploadPollInFlight || uploadPollToken !== uploadToken) {
        return;
    }

    uploadPollInFlight = true;

    try {
        const response = await fetch(
            uploadStatusUrl + '?token=' + encodeURIComponent(uploadToken) + '&_=' + Date.now(),
            {
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );
        const data = await window.vbReadJson(response);
        if (uploadPollToken !== uploadToken) {
            return;
        }

        if (!response.ok && response.status !== 202) {
            scheduleUploadPolling(uploadToken, uploadStatusBackoffDelay);
            return;
        }

        if (!data.success) {
            scheduleUploadPolling(uploadToken, uploadStatusBackoffDelay);
            return;
        }

        setUploadProgress(data.progress, data.message || 'Идёт обработка видео...');

        if (data.video_id) {
            readyVideoId = data.video_id;
        }

        if (data.status === 'ready' || data.status === 'failed') {
            stopUploadPolling();
            return;
        }

        scheduleUploadPolling(
            uploadToken,
            data.status === 'pending' ? uploadStatusStartDelay : uploadStatusDelay
        );
    } catch (error) {
        if (uploadPollToken === uploadToken) {
            scheduleUploadPolling(uploadToken, uploadStatusBackoffDelay);
        }
    } finally {
        uploadPollInFlight = false;
    }
}

function startUploadPolling(uploadToken) {
    stopUploadPolling();
    uploadPollToken = uploadToken;
    scheduleUploadPolling(uploadToken, uploadStatusStartDelay);
}

document.getElementById('video-file')?.addEventListener('change', (event) => {
    const file = event.target.files?.[0];
    const preview = document.getElementById('video-preview');
    const player = document.getElementById('preview-player');

    if (!file) {
        if (previewObjectUrl) {
            URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = '';
        }
        preview.style.display = 'none';
        player.removeAttribute('src');
        player.load();
        return;
    }

    if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
    }

    previewObjectUrl = URL.createObjectURL(file);
    player.src = previewObjectUrl;
    preview.style.display = 'block';

    if (!document.getElementById('video-title').value.trim()) {
        const dotIndex = file.name.lastIndexOf('.');
        document.getElementById('video-title').value = dotIndex > 0 ? file.name.slice(0, dotIndex) : file.name;
    }
});

document.getElementById('upload-form')?.addEventListener('submit', (event) => {
    event.preventDefault();

    if (uploadRequestInProgress) {
        return;
    }

    const form = event.currentTarget;
    const videoFile = document.getElementById('video-file').files?.[0];
    if (!videoFile) {
        alert('Сначала выберите видео.');
        return;
    }

    uploadRequestInProgress = true;

    const formData = new FormData(form);
    const uploadToken = makeUploadToken();
    formData.set('csrf_token', window.vbGetCsrf());
    formData.set('upload_token', uploadToken);

    const statusWrap = document.getElementById('upload-status');
    const startButton = document.getElementById('start-upload');
    const resultWrap = document.getElementById('upload-result');
    const publishButton = document.getElementById('publish-video');

    readyVideoId = 0;
    displayedUploadProgress = 0;
    stopUploadPolling();
    statusWrap.style.display = 'flex';
    setUploadProgress(0, 'Начинается загрузка...', true);
    resultWrap.style.display = 'none';
    publishButton.disabled = false;
    startButton.disabled = true;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo video_base_url('api/upload_video.php'); ?>');
    xhr.withCredentials = true;

    xhr.upload.addEventListener('progress', (e) => {
        if (!e.lengthComputable) {
            return;
        }

        const uploadPart = Math.round((e.loaded / e.total) * 30);
        setUploadProgress(uploadPart, 'Загрузка файла: ' + Math.round((e.loaded / e.total) * 100) + '%');
    });

    xhr.upload.addEventListener('load', () => {
        setUploadProgress(32, 'Файл загружен на сервер. Начинается обработка...');
        startUploadPolling(uploadToken);
    });

    xhr.onreadystatechange = async () => {
        if (xhr.readyState !== 4) {
            return;
        }

        stopUploadPolling();
        let data = { success: false, error: 'Некорректный ответ сервера.' };
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
        }

        if (!data.success) {
            setUploadProgress(0, data.error || 'Ошибка обработки видео.', true);
            uploadRequestInProgress = false;
            resultWrap.style.display = 'none';
            startButton.disabled = false;
            return;
        }

        setUploadProgress(100, 'Обработка завершена. Видео ждёт подтверждения публикации.');
        uploadRequestInProgress = false;
        readyVideoId = data.video_id;

        resultWrap.style.display = 'block';
        document.getElementById('upload-result-message').textContent = data.message;
        document.getElementById('open-preview').href = data.watch_url;
    };

    xhr.onerror = () => {
        stopUploadPolling();
        uploadRequestInProgress = false;
        setUploadProgress(0, 'Не удалось завершить загрузку. Проверьте соединение и попробуйте снова.', true);
        startButton.disabled = false;
        resultWrap.style.display = 'none';
    };

    xhr.onabort = () => {
        stopUploadPolling();
        uploadRequestInProgress = false;
        setUploadProgress(0, 'Загрузка была прервана.', true);
        startButton.disabled = false;
        resultWrap.style.display = 'none';
    };

    xhr.send(formData);
});

document.getElementById('publish-video')?.addEventListener('click', async () => {
    if (!readyVideoId) {
        return;
    }

    const body = new URLSearchParams({
        csrf_token: window.vbGetCsrf(),
        video_id: String(readyVideoId)
    });

    const response = await fetch('<?php echo video_base_url('api/publish_video.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: body.toString(),
        credentials: 'same-origin'
    });

    const data = await window.vbReadJson(response);
    if (!data.success) {
        alert(data.error || 'Не удалось опубликовать видео.');
        return;
    }

    document.getElementById('upload-result-message').textContent = data.message;
    document.getElementById('open-preview').href = data.watch_url;
    document.getElementById('publish-video').disabled = true;
});

window.addEventListener('beforeunload', () => {
    stopUploadPolling();
    if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = '';
    }
});
</script>
<?php video_render_footer(); ?>
