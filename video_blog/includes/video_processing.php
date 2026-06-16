<?php
declare(strict_types=1);

require_once __DIR__ . '/video_bootstrap.php';

function video_find_binary(string $binary): ?string
{
    static $cache = [];

    if (array_key_exists($binary, $cache)) {
        return $cache[$binary];
    }

    $candidates = [$binary];

    if (DIRECTORY_SEPARATOR === '\\') {
        $output = [];
        $code = 0;
        @exec('where ' . escapeshellarg($binary), $output, $code);
        if ($code === 0 && $output !== []) {
            $candidates = array_merge($output, $candidates);
        }
    } else {
        $output = [];
        $code = 0;
        @exec('command -v ' . escapeshellarg($binary), $output, $code);
        if ($code === 0 && $output !== []) {
            $candidates = array_merge($output, $candidates);
        }
    }

    foreach ($candidates as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate === '') {
            continue;
        }

        $output = [];
        $code = 0;
        @exec(escapeshellarg($candidate) . ' -version 2>&1', $output, $code);
        if ($code === 0 || $output !== []) {
            $cache[$binary] = $candidate;
            return $candidate;
        }
    }

    $cache[$binary] = null;
    return null;
}

function video_ffmpeg_binary(): string
{
    $binary = video_find_binary('ffmpeg');
    if ($binary === null) {
        throw new RuntimeException('FFmpeg не найден на сервере.');
    }

    return $binary;
}

function video_ffprobe_binary(): string
{
    $binary = video_find_binary('ffprobe');
    if ($binary === null) {
        throw new RuntimeException('FFprobe не найден на сервере.');
    }

    return $binary;
}

function video_exec_command(string $command, ?int &$exitCode = null): string
{
    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);
    return implode(PHP_EOL, $output);
}

function video_probe_media(string $filePath): array
{
    $command = escapeshellarg(video_ffprobe_binary())
        . ' -v quiet -print_format json -show_format -show_streams '
        . escapeshellarg($filePath);

    $output = video_exec_command($command, $exitCode);
    if ($exitCode !== 0 && trim($output) === '') {
        throw new RuntimeException('Не удалось получить метаданные видео.');
    }

    $data = json_decode($output, true);
    if (!is_array($data)) {
        throw new RuntimeException('FFprobe вернул некорректный ответ.');
    }

    $videoStream = null;
    $audioStream = null;
    foreach (($data['streams'] ?? []) as $stream) {
        if (($stream['codec_type'] ?? '') === 'video' && $videoStream === null) {
            $videoStream = $stream;
        }
        if (($stream['codec_type'] ?? '') === 'audio' && $audioStream === null) {
            $audioStream = $stream;
        }
    }

    $format = $data['format'] ?? [];

    return [
        'duration' => (float)($format['duration'] ?? 0),
        'size' => (int)($format['size'] ?? filesize($filePath)),
        'bit_rate' => (int)($format['bit_rate'] ?? 0),
        'width' => (int)($videoStream['width'] ?? 0),
        'height' => (int)($videoStream['height'] ?? 0),
        'has_audio' => $audioStream !== null,
        'video_codec' => (string)($videoStream['codec_name'] ?? ''),
        'audio_codec' => (string)($audioStream['codec_name'] ?? ''),
    ];
}

function video_should_passthrough_webm(string $sourceMime, int $sourceSize): bool
{
    return $sourceMime === 'video/webm' && $sourceSize <= 25 * 1024 * 1024;
}

function video_extension_for_mime(string $mime): string
{
    static $map = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'video/x-matroska' => 'mkv',
    ];

    return $map[$mime] ?? 'bin';
}

function video_can_keep_source_mime(string $mime): bool
{
    return in_array($mime, ['video/mp4', 'video/webm'], true);
}

function video_run_ffmpeg_with_progress(string $command, float $durationSeconds, ?callable $progressCallback = null): string
{
    if (!function_exists('proc_open')) {
        $output = video_exec_command($command, $exitCode);
        if ($progressCallback !== null) {
            $progressCallback(1.0);
        }

        if ($exitCode !== 0) {
            throw new RuntimeException($output !== '' ? $output : 'FFmpeg завершился с ошибкой.');
        }

        return $output;
    }

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        $output = video_exec_command($command, $exitCode);
        if ($progressCallback !== null) {
            $progressCallback(1.0);
        }

        if ($exitCode !== 0) {
            throw new RuntimeException($output !== '' ? $output : 'FFmpeg завершился с ошибкой.');
        }

        return $output;
    }

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdoutBuffer = '';
    $stderrBuffer = '';
    $lastRatio = -1.0;
    $sawProgressEnd = false;
    $finalStatus = null;
    $durationSeconds = max(0.1, $durationSeconds);

    while (true) {
        $status = proc_get_status($process);
        $finalStatus = $status;

        $stdoutChunk = stream_get_contents($pipes[1]);
        if (is_string($stdoutChunk) && $stdoutChunk !== '') {
            $stdoutBuffer .= $stdoutChunk;
            $lines = preg_split("/\r\n|\n|\r/", $stdoutChunk);
            foreach ($lines as $line) {
                if (!is_string($line) || $line === '') {
                    continue;
                }

                if (preg_match('/^out_time_(?:ms|us)=(\d+)$/', $line, $matches)) {
                    $microseconds = (float)$matches[1];
                    if (str_starts_with($line, 'out_time_ms=')) {
                        $microseconds *= 1000.0;
                    }

                    $ratio = max(0.0, min(1.0, ($microseconds / 1000000.0) / $durationSeconds));
                    if ($progressCallback !== null && ($ratio - $lastRatio >= 0.01 || $ratio >= 1.0)) {
                        $lastRatio = $ratio;
                        $progressCallback($ratio);
                    }
                }

                if ($line === 'progress=end') {
                    $sawProgressEnd = true;
                }
            }
        }

        $stderrChunk = stream_get_contents($pipes[2]);
        if (is_string($stderrChunk) && $stderrChunk !== '') {
            $stderrBuffer .= $stderrChunk;
        }

        if (!$status['running']) {
            break;
        }

        usleep(150000);
    }

    $stdoutBuffer .= stream_get_contents($pipes[1]) ?: '';
    $stderrBuffer .= stream_get_contents($pipes[2]) ?: '';

    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if (
        $exitCode === -1
        && is_array($finalStatus)
        && isset($finalStatus['exitcode'])
        && is_int($finalStatus['exitcode'])
        && $finalStatus['exitcode'] >= 0
    ) {
        $exitCode = $finalStatus['exitcode'];
    }

    if ($exitCode === -1 && $sawProgressEnd) {
        $exitCode = 0;
    }

    if ($progressCallback !== null) {
        $progressCallback(1.0);
    }

    if ($exitCode !== 0) {
        $output = trim($stderrBuffer . PHP_EOL . $stdoutBuffer);
        throw new RuntimeException($output !== '' ? $output : 'FFmpeg завершился с ошибкой.');
    }

    return trim($stderrBuffer . PHP_EOL . $stdoutBuffer);
}

function video_process_to_webm(string $inputPath, string $outputPath, string $sourceMime, int $sourceSize, ?callable $progressCallback = null): array
{
    if (video_should_passthrough_webm($sourceMime, $sourceSize)) {
        if (!copy($inputPath, $outputPath)) {
            throw new RuntimeException('Не удалось сохранить готовый webm-файл.');
        }

        if ($progressCallback !== null) {
            $progressCallback(1.0);
        }

        $meta = video_probe_media($outputPath);
        return [
            'mode' => 'copied',
            'path' => $outputPath,
            'meta' => $meta,
            'final_mime' => 'video/webm',
        ];
    }

    $isLargeWebm = $sourceMime === 'video/webm' && $sourceSize > 25 * 1024 * 1024;
    $crf = $isLargeWebm ? 37 : 34;
    $audioBitrate = $isLargeWebm ? 80 : 96;

    $sourceMeta = video_probe_media($inputPath);

    $command = escapeshellarg(video_ffmpeg_binary())
        . ' -y -i ' . escapeshellarg($inputPath)
        . ' -map 0:v:0 -map 0:a?'
        . ' -c:v libvpx-vp9 -pix_fmt yuv420p -deadline good -cpu-used 4 -row-mt 1'
        . ' -crf ' . $crf . ' -b:v 0'
        . ' -c:a libopus -b:a ' . $audioBitrate . 'k'
        . ' -progress pipe:1 -nostats'
        . ' ' . escapeshellarg($outputPath);

    try {
        video_run_ffmpeg_with_progress(
            $command,
            (float)($sourceMeta['duration'] ?? 0),
            $progressCallback
        );
    } catch (Throwable $e) {
        throw new RuntimeException(
            'FFmpeg не смог обработать видео.'
            . ($e->getMessage() !== '' ? ' Детали: ' . $e->getMessage() : '')
        );
    }

    if (!is_file($outputPath) || filesize($outputPath) <= 0) {
        throw new RuntimeException('FFmpeg не создал итоговый видеофайл.');
    }

    $meta = video_probe_media($outputPath);

    return [
        'mode' => $isLargeWebm ? 'optimized_webm' : 'transcoded',
        'path' => $outputPath,
        'meta' => $meta,
        'final_mime' => 'video/webm',
    ];
}

function video_move_source_to_processed(string $inputPath, string $processedDir, string $sourceMime): array
{
    $extension = video_extension_for_mime($sourceMime);
    $finalFileName = video_random_name('video', $extension);
    $finalPath = rtrim($processedDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $finalFileName;

    $moved = @rename($inputPath, $finalPath);
    if (!$moved) {
        if (!@copy($inputPath, $finalPath)) {
            throw new RuntimeException('Не удалось сохранить итоговый видеофайл.');
        }
        @unlink($inputPath);
    }

    try {
        $meta = video_probe_media($finalPath);
    } catch (Throwable $e) {
        if (is_file($finalPath)) {
            @unlink($finalPath);
        }
        throw $e;
    }

    return [
        'mode' => 'kept_source',
        'path' => $finalPath,
        'filename' => $finalFileName,
        'meta' => $meta,
        'final_mime' => $sourceMime,
    ];
}

function video_prepare_final_video(
    string $inputPath,
    string $sourceMime,
    int $sourceSize,
    string $processedDir,
    ?callable $progressCallback = null
): array {
    if (video_should_passthrough_webm($sourceMime, $sourceSize)) {
        if ($progressCallback !== null) {
            $progressCallback(1.0);
        }

        return video_move_source_to_processed($inputPath, $processedDir, $sourceMime);
    }

    $webmFileName = video_random_name('video', 'webm');
    $webmPath = rtrim($processedDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $webmFileName;

    $processed = video_process_to_webm(
        $inputPath,
        $webmPath,
        $sourceMime,
        $sourceSize,
        $progressCallback
    );

    $encodedSize = (int)($processed['meta']['size'] ?? (is_file($webmPath) ? filesize($webmPath) : 0));
    $originalSize = is_file($inputPath) ? (int)filesize($inputPath) : $sourceSize;

    if (video_can_keep_source_mime($sourceMime) && $originalSize > 0 && $originalSize < $encodedSize) {
        if (is_file($webmPath)) {
            @unlink($webmPath);
        }

        return video_move_source_to_processed($inputPath, $processedDir, $sourceMime);
    }

    $processed['path'] = $webmPath;
    $processed['filename'] = $webmFileName;

    return $processed;
}

function video_generate_frame_cover(string $videoPath, string $outputPath, ?float $duration = null): void
{
    $duration = $duration ?? 0.0;
    $captureAt = 1.0;

    if ($duration > 6) {
        $captureAt = min(max($duration * 0.35, 1.0), $duration - 1.0);
    } elseif ($duration > 1) {
        $captureAt = max(0.5, $duration / 2);
    }

    $command = escapeshellarg(video_ffmpeg_binary())
        . ' -y -ss ' . escapeshellarg((string)$captureAt)
        . ' -i ' . escapeshellarg($videoPath)
        . ' -frames:v 1 -q:v 4 '
        . escapeshellarg($outputPath);

    $output = video_exec_command($command, $exitCode);
    if ($exitCode !== 0 || !is_file($outputPath) || filesize($outputPath) <= 0) {
        throw new RuntimeException(
            'Не удалось создать обложку из кадра.'
            . ($output !== '' ? ' Детали: ' . $output : '')
        );
    }
}
function video_probe_metadata(string $filePath): array
{
    $escapedPath = escapeshellarg($filePath);
    $cmd = 'ffprobe -v quiet -print_format json -show_format -show_streams ' . $escapedPath . ' 2>/dev/null';
    $output = shell_exec($cmd);

    $defaults = [
        'duration' => 0.0,
        'width'    => 0,
        'height'   => 0,
        'size'     => filesize($filePath),
    ];

    if ($output === null || $output === false) {
        return $defaults;
    }

    $data = json_decode($output, true);
    if (!is_array($data)) {
        return $defaults;
    }

    $duration = 0.0;
    $width    = 0;
    $height   = 0;

    if (isset($data['format']['duration'])) {
        $duration = (float)$data['format']['duration'];
    }

    if (isset($data['streams']) && is_array($data['streams'])) {
        foreach ($data['streams'] as $stream) {
            if (($stream['codec_type'] ?? '') === 'video') {
                $width  = (int)($stream['width']  ?? 0);
                $height = (int)($stream['height'] ?? 0);
                break;
            }
        }
    }

    return [
        'duration' => $duration,
        'width'    => $width,
        'height'   => $height,
        'size'     => filesize($filePath),
    ];
}