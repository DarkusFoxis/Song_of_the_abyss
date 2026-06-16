<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/video_bootstrap.php';

$viewer = video_require_user();

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    video_json_error('Метод запроса не поддерживается.', 405);
}

$token = video_normalize_upload_token((string)($_GET['token'] ?? ''));
if ($token === '') {
    video_json_error('Некорректный токен загрузки.');
}

$status = video_read_upload_status($token);
if ($status === null) {
    video_json_response([
        'success' => true,
        'status' => 'pending',
        'progress' => 0,
        'message' => 'Статус обработки подготавливается.',
        'video_id' => 0,
        'watch_url' => '',
        'updated_at' => '',
    ], 202);
}

$statusUserId = (int)($status['user_id'] ?? 0);
if ($statusUserId > 0 && $statusUserId !== (int)$viewer['id'] && !video_is_staff($viewer)) {
    video_json_error('У вас нет доступа к этому статусу.', 403);
}

video_json_response([
    'success' => true,
    'status' => (string)($status['status'] ?? 'processing'),
    'progress' => max(0, min(100, (int)($status['progress'] ?? 0))),
    'message' => (string)($status['message'] ?? ''),
    'video_id' => isset($status['video_id']) ? (int)$status['video_id'] : 0,
    'watch_url' => isset($status['watch_url']) ? (string)$status['watch_url'] : '',
    'updated_at' => (string)($status['updated_at'] ?? ''),
]);
session_write_close();