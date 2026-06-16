<?php
declare(strict_types=1);

require_once __DIR__ . '/../../template/auth.php';
require_once __DIR__ . '/../../template/conn.php';
require_once __DIR__ . '/../../template/nsfw.php';

auth_start_session();
auth_sync_session_from_token();

function video_db(): mysqli
{
    global $host, $log, $password_sql, $database;

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if ($conn === false) {
        throw new RuntimeException('Не удалось подключиться к базе данных.');
    }

    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

function video_current_user(): ?array
{
    $user = auth_get_current_user();
    return is_array($user) ? $user : null;
}

function video_require_user(): array
{
    $user = auth_require_user('/profile/login');
    if ((int)($user['lvl'] ?? 0) < 2) {
        http_response_code(403);
        exit('Доступ к видеохостингу ограничен для этой роли.');
    }

    return $user;
}

function video_is_staff(?array $user): bool
{
    return (int)($user['lvl'] ?? 0) >= 5;
}

function video_upload_policy(?array $user): array
{
    $level = (int)($user['lvl'] ?? 0);

    if ($level >= 6) {
        return [
            'tier' => 'root',
            'label' => 'Root',
            'monthly_limit' => 15,
            'max_source_bytes' => 185 * 1024 * 1024,
            'can_upload' => true,
        ];
    }

    if ($level >= 3) {
        return [
            'tier' => 'premium',
            'label' => 'Premium',
            'monthly_limit' => 5,
            'max_source_bytes' => 55 * 1024 * 1024,
            'can_upload' => true,
        ];
    }

    if ($level >= 2) {
        return [
            'tier' => 'user',
            'label' => 'User',
            'monthly_limit' => 3,
            'max_source_bytes' => 27 * 1024 * 1024,
            'can_upload' => true,
        ];
    }

    return [
        'tier' => 'blocked',
        'label' => 'No access',
        'monthly_limit' => 0,
        'max_source_bytes' => 0,
        'can_upload' => false,
    ];
}

function video_month_key(?DateTimeInterface $date = null): string
{
    $date = $date ?? new DateTimeImmutable('now');
    return $date->format('Y-m');
}

function video_get_monthly_usage(mysqli $conn, int $userId, ?string $monthKey = null): int
{
    $monthKey = $monthKey ?? video_month_key();

    $stmt = $conn->prepare('SELECT uploads_used FROM video_upload_monthly WHERE user_id = ? AND `year_month` = ? LIMIT 1');
    $stmt->bind_param('is', $userId, $monthKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['uploads_used'] ?? 0);
}

function video_upload_cost(bool $isNsfw): int
{
    return $isNsfw ? 2 : 1;
}

function video_effective_max_source_bytes(array $policy, bool $isNsfw): int
{
    $maxBytes = (int)($policy['max_source_bytes'] ?? 0);
    if ($isNsfw) {
        return (int)floor($maxBytes * 0.9);
    }

    return $maxBytes;
}

function video_register_monthly_upload(mysqli $conn, int $userId, ?string $monthKey = null, int $cost = 1): void
{
    $monthKey = $monthKey ?? video_month_key();
    $cost = max(1, $cost);

    $stmt = $conn->prepare(
        'INSERT INTO video_upload_monthly (user_id, `year_month`, uploads_used, last_upload_at)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            uploads_used = uploads_used + VALUES(uploads_used),
            last_upload_at = NOW()'
    );
    $stmt->bind_param('isi', $userId, $monthKey, $cost);
    $stmt->execute();
    $stmt->close();
}

function video_remaining_uploads(mysqli $conn, array $user): int
{
    $policy = video_upload_policy($user);
    if (!$policy['can_upload']) {
        return 0;
    }

    $used = video_get_monthly_usage($conn, (int)$user['id']);
    return max(0, (int)$policy['monthly_limit'] - $used);
}

function video_base_url(string $path = ''): string
{
    $base = '/video_blog';
    if ($path === '') {
        return $base;
    }

    $path = ltrim($path, '/');
    if ($path !== '' && !str_starts_with($path, 'api/')) {
        $path = preg_replace('/\.php(?=($|\?))/', '', $path) ?? $path;
    }

    return $base . '/' . $path;
}

function video_private_dir(string $relative = ''): string
{
    $relative = trim(str_replace('\\', '/', $relative), '/');
    $path = $relative === '' ? 'video_blog' : 'video_blog/' . $relative;

    return app_private_ensure_dir($path);
}

function video_random_name(string $prefix, string $extension): string
{
    $extension = strtolower(ltrim($extension, '.'));
    return $prefix . '_' . bin2hex(random_bytes(12)) . ($extension !== '' ? '.' . $extension : '');
}

function video_normalize_upload_token(?string $token): string
{
    $token = trim((string)$token);
    if ($token === '' || !preg_match('/^[A-Za-z0-9_-]{12,80}$/', $token)) {
        return '';
    }

    return $token;
}

function video_upload_status_path(string $token): string
{
    $token = video_normalize_upload_token($token);
    if ($token === '') {
        throw new RuntimeException('Некорректный upload token.');
    }

    return rtrim(video_private_dir('status'), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . 'upload_' . $token . '.json';
}

function video_write_upload_status(string $token, array $payload): void
{
    $token = video_normalize_upload_token($token);
    if ($token === '') {
        return;
    }

    $payload['updated_at'] = date('c');
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return;
    }

    $path = video_upload_status_path($token);
    $tempPath = $path . '.tmp';
    file_put_contents($tempPath, $json, LOCK_EX);
    @rename($tempPath, $path);
}

function video_read_upload_status(string $token): ?array
{
    $token = video_normalize_upload_token($token);
    if ($token === '') {
        return null;
    }

    $path = video_upload_status_path($token);
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function video_delete_upload_status(string $token): void
{
    $token = video_normalize_upload_token($token);
    if ($token === '') {
        return;
    }

    $path = video_upload_status_path($token);
    if (is_file($path)) {
        @unlink($path);
    }
}

function video_html(?string $value): string
{
    return security_html((string)$value);
}

function video_multiline_html(?string $value): string
{
    return nl2br(video_html((string)$value), false);
}

function video_excerpt(?string $text, int $limit = 180): string
{
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }

    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit - 3)) . '...';
}

function video_format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $value = max(0, $bytes);
    $power = $value > 0 ? (int)floor(log($value, 1024)) : 0;
    $power = max(0, min($power, count($units) - 1));
    $normalized = $value / (1024 ** $power);

    return number_format($normalized, $power === 0 ? 0 : 2, '.', ' ') . ' ' . $units[$power];
}

function video_format_duration(float $seconds): string
{
    $seconds = max(0, (int)round($seconds));
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;

    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }

    return sprintf('%d:%02d', $minutes, $secs);
}

function video_status_label(string $status): string
{
    $map = [
        'processing' => 'Обработка',
        'ready' => 'Готово к публикации',
        'published' => 'Опубликовано',
        'draft' => 'Черновик',
        'failed' => 'Ошибка',
        'hidden' => 'Скрыто',
    ];

    return $map[$status] ?? 'Неизвестно';
}

function video_status_class(string $status): string
{
    return match ($status) {
        'published' => 'status-published',
        'ready' => 'status-ready',
        'draft' => 'status-draft',
        'failed' => 'status-failed',
        default => 'status-processing',
    };
}

function video_title_can_be_edited(array $video): bool
{
    if (empty($video['published_at'])) {
        return true;
    }

    $publishedAt = strtotime((string)$video['published_at']);
    if ($publishedAt === false) {
        return true;
    }

    return (time() - $publishedAt) <= 86400;
}

function video_can_manage(array $video, ?array $user): bool
{
    if ($user === null) {
        return false;
    }

    return video_is_staff($user) || (int)$video['user_id'] === (int)$user['id'];
}

function video_can_view(array $video, ?array $user): bool
{
    if ((int)($video['NSFW'] ?? 0) === 1 && !nsfw_user_has_access($user)) {
        return false;
    }

    if (($video['status'] ?? '') === 'published') {
        return true;
    }

    return video_can_manage($video, $user);
}

function video_comments_enabled(array $video): bool
{
    return (int)($video['allow_comments'] ?? 0) === 1 && ($video['status'] ?? '') === 'published';
}

function video_rating_enabled(array $video): bool
{
    return (int)($video['NSFW'] ?? 0) !== 1
        && (int)($video['allow_rating'] ?? 0) === 1
        && ($video['status'] ?? '') === 'published';
}

function video_stream_url(int $videoId): string
{
    return video_base_url('stream?id=' . $videoId);
}

function video_cover_url(int $videoId): string
{
    return video_base_url('cover?id=' . $videoId);
}

function video_watch_url(int $videoId): string
{
    return video_base_url('watch?id=' . $videoId);
}

function video_avatar_url(?string $avatar): string
{
    $avatar = basename((string)$avatar);
    return '/profile/avatars/' . $avatar;
}

function video_json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function video_json_error(string $message, int $status = 400): never
{
    video_json_response([
        'success' => false,
        'error' => $message,
    ], $status);
}

function video_require_post_request(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        video_json_error('Метод запроса не поддерживается.', 405);
    }
}

function video_post_bool(string $field, bool $default = false): bool
{
    if (!array_key_exists($field, $_POST)) {
        return $default;
    }

    $value = $_POST[$field];
    if (is_bool($value)) {
        return $value;
    }

    return in_array((string)$value, ['1', 'true', 'on', 'yes'], true);
}

function video_fetch_video(mysqli $conn, int $videoId, ?array $viewer = null): ?array
{
    $stmt = $conn->prepare(
        'SELECT vp.*, u.login, u.username, u.avatar,
                (SELECT COUNT(*) FROM video_comments vc WHERE vc.video_id = vp.id) AS comments_count
         FROM video_posts vp
         JOIN users u ON u.id = vp.user_id
         WHERE vp.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $video = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$video) {
        return null;
    }

    $video['user_vote'] = 0;

    if ($viewer !== null) {
        $voteStmt = $conn->prepare(
            'SELECT vote_value
             FROM video_ratings
             WHERE video_id = ? AND user_id = ?
             LIMIT 1'
        );
        $viewerId = (int)$viewer['id'];
        $voteStmt->bind_param('ii', $videoId, $viewerId);
        $voteStmt->execute();
        $vote = $voteStmt->get_result()->fetch_assoc();
        $voteStmt->close();
        $video['user_vote'] = (int)($vote['vote_value'] ?? 0);
    }

    return $video;
}

function video_fetch_user_videos(mysqli $conn, int $userId, ?array $viewer = null): array
{
    $stmt = $conn->prepare(
        'SELECT vp.*,
                (SELECT COUNT(*) FROM video_comments vc WHERE vc.video_id = vp.id) AS comments_count
         FROM video_posts vp
         WHERE vp.user_id = ?
         ORDER BY vp.created_at DESC'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $videos = [];
    while ($row = $result->fetch_assoc()) {
        if ((int)($row['NSFW'] ?? 0) === 1 && !nsfw_user_has_access($viewer)) {
            continue;
        }
        $videos[] = $row;
    }

    $stmt->close();
    return $videos;
}

function video_fetch_comments(mysqli $conn, int $videoId): array
{
    $stmt = $conn->prepare(
        'SELECT vc.*, u.username, u.avatar
         FROM video_comments vc
         JOIN users u ON u.id = vc.user_id
         WHERE vc.video_id = ?
         ORDER BY vc.created_at ASC'
    );
    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    $stmt->close();
    return $comments;
}

function video_render_comment(array $comment): string
{
    $avatar = video_avatar_url((string)$comment['avatar']);
    $username = video_html((string)$comment['username']);
    $text = video_multiline_html((string)$comment['text']);
    $date = strtotime((string)$comment['created_at']);
    $dateLabel = $date !== false ? date('H:i d.m.Y', $date) : video_html((string)$comment['created_at']);
    $profileUrl = '/profile/profile?id=' . (int)$comment['user_id'];

    return '<article class="vb-comment">'
        . '<a class="vb-comment-author" href="' . $profileUrl . '">'
        . '<img class="vb-avatar vb-avatar-small" src="' . $avatar . '" alt="avatar">'
        . '<span>' . $username . '</span>'
        . '</a>'
        . '<div class="vb-comment-text">' . $text . '</div>'
        . '<div class="vb-comment-date">' . video_html($dateLabel) . '</div>'
        . '</article>';
}

function video_increment_view(mysqli $conn, array $video, ?array $viewer): void
{
    if (($video['status'] ?? '') !== 'published') {
        return;
    }

    if ($viewer !== null && (int)$viewer['id'] === (int)$video['user_id']) {
        return;
    }

    $sessionKey = 'vb_viewed_' . (int)$video['id'];
    if (!empty($_SESSION[$sessionKey])) {
        return;
    }

    $stmt = $conn->prepare('UPDATE video_posts SET views = views + 1 WHERE id = ?');
    $videoId = (int)$video['id'];
    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $stmt->close();

    $_SESSION[$sessionKey] = true;
}

function video_send_file_with_range(string $path, string $mime): never
{
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        exit('Файл не найден.');
    }

    $size = filesize($path);
    $start = 0;
    $end = max(0, $size - 1);

    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, max-age=3600');

    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/i', (string)$_SERVER['HTTP_RANGE'], $matches)) {
        if ($matches[1] !== '') {
            $start = (int)$matches[1];
        }

        if ($matches[2] !== '') {
            $end = (int)$matches[2];
        }

        if ($end >= $size) {
            $end = $size - 1;
        }

        if ($start > $end || $start < 0) {
            header('Content-Range: bytes */' . $size);
            http_response_code(416);
            exit;
        }

        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }

    $length = $end - $start + 1;
    header('Content-Length: ' . $length);

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        http_response_code(500);
        exit('Не удалось открыть файл.');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    fseek($handle, $start);
    $remaining = $length;
    $chunkSize = 8192;

    while (!feof($handle) && $remaining > 0) {
        $read = $remaining > $chunkSize ? $chunkSize : $remaining;
        $buffer = fread($handle, $read);
        if ($buffer === false) {
            break;
        }

        echo $buffer;
        flush();
        $remaining -= strlen($buffer);
    }

    fclose($handle);
    exit;
}

function video_send_image(string $path, string $mime): never
{
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        exit('Изображение не найдено.');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=3600');
    readfile($path);
    exit;
}
