<?php
/**
 * FfmpegAudioCompressor - Класс для сжатия аудиофайлов через системный FFMpeg
 * Использует прямые вызовы ffmpeg через shell_exec()
 */

class FfmpegAudioCompressor
{
    private $ffmpegPath = 'ffmpeg';

    private $ffprobePath = 'ffprobe';

    private $defaultBitrate = 128;

    private $isAvailable = false;

    /**
     * Конструктор - проверяет доступность FFMpeg
     */
    public function __construct()
    {
        $this->ffmpegPath = $this->findFFmpeg();
        $this->ffprobePath = $this->findFFprobe();
        
        if ($this->ffmpegPath && $this->ffprobePath) {
            $this->isAvailable = true;
            error_log("FfmpegAudioCompressor: FFMpeg найден - {$this->ffmpegPath}");
        } else {
            $this->isAvailable = false;
            error_log("FfmpegAudioCompressor: FFMpeg не найден в системе");
        }
    }

    /**
     * Ищет исполняемый файл ffmpeg
     */
    private function findFFmpeg()
    {
        $paths = ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/usr/bin/avconv', '/usr/local/bin/avconv'];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        $result = trim(shell_exec('which ffmpeg 2>/dev/null'));
        if (!empty($result) && file_exists($result) && is_executable($result)) {
            return $result;
        }

        $result = trim(shell_exec('which avconv 2>/dev/null'));
        if (!empty($result) && file_exists($result) && is_executable($result)) {
            return $result;
        }

        $test = shell_exec('ffmpeg -version 2>/dev/null');
        if (!empty($test)) {
            return 'ffmpeg';
        }
        return null;
    }

    /**
     * Ищет исполняемый файл ffprobe
     */
    private function findFFprobe()
    {
        $paths = ['/usr/bin/ffprobe', '/usr/local/bin/ffprobe', '/usr/bin/avprobe', '/usr/local/bin/avprobe'];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        $result = trim(shell_exec('which ffprobe 2>/dev/null'));
        if (!empty($result) && file_exists($result) && is_executable($result)) {
            return $result;
        }

        $result = trim(shell_exec('which avprobe 2>/dev/null'));
        if (!empty($result) && file_exists($result) && is_executable($result)) {
            return $result;
        }

        $test = shell_exec('ffprobe -version 2>/dev/null');
        if (!empty($test)) {
            return 'ffprobe';
        }
        return null;
    }

    /**
     * Проверяет доступность FFMpeg
     */
    public function isAvailable()
    {
        return $this->isAvailable;
    }

    /**
     * Получает версию FFMpeg
     */
    public function getVersion()
    {
        if (!$this->isAvailable) {
            return null;
        }

        $version = shell_exec(escapeshellcmd($this->ffmpegPath) . ' -version 2>&1');
        return trim($version);
    }

    /**
     * Получает информацию об аудиофайле
     */
    public function getAudioInfo($filePath)
    {
        if (!$this->isAvailable || !file_exists($filePath)) {
            return null;
        }

        try {
            $command = sprintf(
                '%s -i %s -v quiet -print_format json -show_format -show_streams 2>&1',
                escapeshellcmd($this->ffprobePath),
                escapeshellarg($filePath)
            );

            $output = shell_exec($command);
            $data = json_decode($output, true);

            if (!$data || !isset($data['format'])) {
                return null;
            }

            $format = $data['format'];
            $audioStream = null;

            if (isset($data['streams'])) {
                foreach ($data['streams'] as $stream) {
                    if (isset($stream['codec_type']) && $stream['codec_type'] === 'audio') {
                        $audioStream = $stream;
                        break;
                    }
                }
            }

            $duration = isset($format['duration']) ? (float)$format['duration'] : 0;
            $bitrate = isset($format['bit_rate']) ? (int)$format['bit_rate'] : 0;
            $codec = $audioStream['codec_name'] ?? 'unknown';
            $channels = $audioStream['channels'] ?? 0;
            $sampleRate = $audioStream['sample_rate'] ?? 0;

            return [
                'duration' => $duration,
                'duration_formatted' => $this->formatDuration($duration),
                'bitrate' => $bitrate,
                'bitrate_kbps' => $bitrate > 0 ? round($bitrate / 1000) : 0,
                'codec' => $codec,
                'channels' => $channels,
                'sample_rate' => $sampleRate,
                'file_size' => filesize($filePath),
                'file_size_formatted' => $this->formatBytes(filesize($filePath))
            ];
        } catch (Exception $e) {
            error_log("FfmpegAudioCompressor: Ошибка получения информации - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Сжимает аудиофайл с заданными параметрами
     */
    public function compress($inputPath, $outputPath, array $options = [])
    {
        $result = [
            'success' => false,
            'message' => '',
            'original_size' => 0,
            'compressed_size' => 0,
            'compression_ratio' => 0,
            'ffmpeg_used' => false
        ];

        if (!file_exists($inputPath)) {
            $result['message'] = 'Исходный файл не найден';
            return $result;
        }

        if (!$this->isAvailable) {
            $result['message'] = 'FFMpeg недоступен на сервере';
            return $result;
        }

        $result['original_size'] = filesize($inputPath);
        $outputExtension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));

        try {
            $audioInfo = $this->getAudioInfo($inputPath);
            $originalBitrate = $audioInfo['bitrate_kbps'] ?? 320;

            $quality = $options['quality'] ?? 'medium';
            $qualitySettings = [
                'low' => 96,
                'medium' => 128,
                'high' => 192
            ];
            $targetBitrate = $options['bitrate'] ?? $qualitySettings[$quality] ?? $this->defaultBitrate;

            if ($originalBitrate > 0 && $originalBitrate < $targetBitrate) {
                $targetBitrate = $originalBitrate;
            }

            // Определяем кодек и формат по расширению
            // Для временных файлов (.tmp) определяем формат по оригинальному файлу
            $actualExtension = $outputExtension;
            if ($actualExtension === 'tmp' || strpos($actualExtension, 'tmp') !== false) {
                $actualExtension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
            }
            $codec = $this->getCodecByExtension($actualExtension);
            $format = $this->getFormatByExtension($actualExtension);
            $audioChannels = $audioInfo['channels'] ?? 2;

            // Формируем команду ffmpeg с явным указанием формата (-f) и кодека (-acodec)
            $command = sprintf(
                '%s -i %s -acodec %s -b:a %dk -ac %d -ar 44100 -f %s -y %s 2>&1',
                escapeshellcmd($this->ffmpegPath),
                escapeshellarg($inputPath),
                $codec,
                $targetBitrate,
                $audioChannels,
                $format,
                escapeshellarg($outputPath)
            );

            // Выполняем сжатие
            $output = shell_exec($command);

            // Проверяем результат
            if (file_exists($outputPath)) {
                $result['compressed_size'] = filesize($outputPath);
                $result['compression_ratio'] = round(
                    (1 - $result['compressed_size'] / $result['original_size']) * 100,
                    2
                );
                $result['success'] = true;
                $result['ffmpeg_used'] = true;
                $result['message'] = sprintf(
                    'FFMpeg: %s → %s (%.2f%% экономии, %d kbps)',
                    $this->formatBytes($result['original_size']),
                    $this->formatBytes($result['compressed_size']),
                    $result['compression_ratio'],
                    $targetBitrate
                );

            } else {
                $result['message'] = 'FFMpeg не создал выходной файл: ' . ($output ?? 'неизвестная ошибка');
                error_log("FfmpegAudioCompressor: " . $result['message']);
            }

        } catch (Exception $e) {
            $result['message'] = 'Ошибка сжатия FFMpeg: ' . $e->getMessage();
            error_log("FfmpegAudioCompressor: " . $result['message']);
        }

        return $result;
    }

    /**
     * Оптимизирует аудиофайл на месте (перезаписывает оригинал)
     */
    public function optimizeInPlace($filePath, array $options = [])
    {
        $tempPath = $filePath . '.ffmpeg.tmp';
        $originalExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $result = $this->compress($filePath, $tempPath, $options);

        if ($result['success']) {
            unlink($filePath);
            rename($tempPath, $filePath);
            $result['message'] = 'Файл оптимизирован: ' . $result['message'];
        } else {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }

        return $result;
    }

    /**
     * Конвертирует аудиофайл в другой формат
     */
    public function convert($inputPath, $outputPath, $targetFormat = 'mp3', $bitrate = 128)
    {
        $result = [
            'success' => false,
            'message' => '',
            'original_size' => 0,
            'converted_size' => 0,
            'ffmpeg_used' => false
        ];

        if (!file_exists($inputPath)) {
            $result['message'] = 'Исходный файл не найден';
            return $result;
        }

        if (!$this->isAvailable) {
            $result['message'] = 'FFMpeg недоступен на сервере';
            return $result;
        }

        $result['original_size'] = filesize($inputPath);

        try {
            $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
            $codec = $this->getCodecByExtension($extension);

            $command = sprintf(
                '%s -i %s -b:a %dk -y %s 2>&1',
                escapeshellcmd($this->ffmpegPath),
                escapeshellarg($inputPath),
                $bitrate,
                escapeshellarg($outputPath)
            );

            $output = shell_exec($command);

            if (file_exists($outputPath)) {
                $result['converted_size'] = filesize($outputPath);
                $result['success'] = true;
                $result['ffmpeg_used'] = true;
                $result['message'] = sprintf(
                    'Конвертация в %s успешна: %s → %s',
                    $targetFormat,
                    $this->formatBytes($result['original_size']),
                    $this->formatBytes($result['converted_size'])
                );

            } else {
                $result['message'] = 'FFMpeg не создал выходной файл';
                error_log("FfmpegAudioCompressor: " . $result['message']);
            }

        } catch (Exception $e) {
            $result['message'] = 'Ошибка конвертации FFMpeg: ' . $e->getMessage();
            error_log("FfmpegAudioCompressor: " . $result['message']);
        }

        return $result;
    }

    /**
     * Получает кодек по расширению файла
     */
    private function getCodecByExtension($extension)
    {
        $codecs = [
            'mp3' => 'libmp3lame',
            'ogg' => 'libvorbis',
            'oga' => 'libvorbis',
            'webm' => 'libvorbis',
            'm4a' => 'aac',
            'aac' => 'aac',
            'flac' => 'flac',
            'wav' => 'pcm_s16le'
        ];
        return $codecs[$extension] ?? 'libmp3lame';
    }

    /**
     * Получает формат контейнера по расширению файла
     */
    private function getFormatByExtension($extension)
    {
        $formats = [
            'mp3' => 'mp3',
            'ogg' => 'ogg',
            'oga' => 'ogg',
            'webm' => 'webm',
            'm4a' => 'ipod',
            'aac' => 'adts',
            'flac' => 'flac',
            'wav' => 'wav'
        ];
        return $formats[$extension] ?? 'mp3';
    }

    /**
     * Форматирует длительность в секундах в строку
     */
    private function formatDuration($seconds)
    {
        $totalSeconds = (int)$seconds;
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $secs = $totalSeconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%d:%02d', $minutes, $secs);
    }

    /**
     * Форматирует размер в байтах
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
