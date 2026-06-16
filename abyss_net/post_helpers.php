<?php
declare(strict_types=1);

require_once __DIR__ . '/../template/nsfw.php';

function abyss_post_is_nsfw(array $post): bool
{
    return (int)($post['NSFW'] ?? 0) === 1;
}

function abyss_post_can_view(array $post, ?array $viewer): bool
{
    if (!abyss_post_is_nsfw($post)) {
        return true;
    }

    return nsfw_user_has_access($viewer);
}

function abyss_post_can_rate(array $post): bool
{
    return !abyss_post_is_nsfw($post);
}

function abyss_post_media_dir(bool $isNsfw): string
{
    if ($isNsfw) {
        return app_private_ensure_dir('abyss_net/media');
    }

    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'media';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function abyss_post_media_path(string $fileName, bool $isNsfw): string
{
    return rtrim(abyss_post_media_dir($isNsfw), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . basename($fileName);
}

function abyss_post_media_url(array $post, string $fileName): string
{
    $fileName = basename($fileName);
    if (abyss_post_is_nsfw($post)) {
        return '/abyss_net/media_file?post_id=' . (int)$post['id_post']
            . '&file=' . rawurlencode($fileName);
    }

    return '/abyss_net/media/' . rawurlencode($fileName);
}

function abyss_move_post_media_files(array $fileNames, bool $fromNsfw, bool $toNsfw): void
{
    if ($fromNsfw === $toNsfw) {
        return;
    }

    foreach ($fileNames as $fileName) {
        $fileName = basename(trim((string)$fileName));
        if ($fileName === '') {
            continue;
        }

        $source = abyss_post_media_path($fileName, $fromNsfw);
        $target = abyss_post_media_path($fileName, $toNsfw);
        if (!is_file($source)) {
            continue;
        }

        if (!is_file($target)) {
            @rename($source, $target);
        }
    }
}

function get_post_tags(mysqli $conn, int $postId): array
{
    $stmt = $conn->prepare(
        "SELECT pt.name
         FROM post_tag pt
         JOIN post_tags pst ON pt.id_tag = pst.id_tag
         WHERE pst.id_post = ?"
    );
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['name'];
    }
    $stmt->close();
    return $tags;
}

function save_post_tags(mysqli $conn, int $postId, array $tags): void
{
    $stmt = $conn->prepare("DELETE FROM post_tags WHERE id_post = ?");
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $stmt->close();

    foreach ($tags as $tagName) {
        $tagName = trim($tagName);
        if ($tagName === '') {
            continue;
        }

        $stmt = $conn->prepare("SELECT id_tag FROM post_tag WHERE name = ?");
        $stmt->bind_param('s', $tagName);
        $stmt->execute();
        $result = $stmt->get_result();
        $tag = $result->fetch_assoc();
        $stmt->close();

        if ($tag) {
            $tagId = (int)$tag['id_tag'];
        } else {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ]+/u', '-', $tagName));
            $slug = trim($slug, '-');
            if ($slug === '') {
                $slug = 'tag-' . uniqid();
            }

            $stmt = $conn->prepare("INSERT INTO post_tag (name, slug) VALUES (?, ?)");
            $stmt->bind_param('ss', $tagName, $slug);
            $stmt->execute();
            $tagId = (int)$stmt->insert_id;
            $stmt->close();
        }

        $stmt = $conn->prepare("INSERT IGNORE INTO post_tags (id_post, id_tag) VALUES (?, ?)");
        $stmt->bind_param('ii', $postId, $tagId);
        $stmt->execute();
        $stmt->close();
    }
}

function abyss_send_file_with_range(string $path, string $mime): never
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
    header('Cache-Control: private, max-age=1800');

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

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        http_response_code(500);
        exit('Не удалось открыть файл.');
    }

    fseek($handle, $start);
    $remaining = $length;
    while (!feof($handle) && $remaining > 0) {
        $chunkSize = min(8192, $remaining);
        $chunk = fread($handle, $chunkSize);
        if ($chunk === false) {
            break;
        }
        echo $chunk;
        flush();
        $remaining -= strlen($chunk);
    }

    fclose($handle);
    exit;
}