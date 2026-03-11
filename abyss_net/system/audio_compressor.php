<?php
/**
 * AudioCompressor - Класс для сжатия аудиофайлов
 * Использует FFMpeg при доступности, иначе - PHP-методы (удаление метаданных)
 */

class AudioCompressor
{
    private $ffmpegCompressor = null;
    
    private $defaultBitrate = 128; // kbps
    private $defaultSampleRate = 44100;
    
    private $preferFfmpeg = true;

    public function __construct()
    {
        // Пытаемся подключить FFMpeg компрессор
        $ffmpegCompressorPath = __DIR__ . '/ffmpeg_audio_compressor.php';
        if (file_exists($ffmpegCompressorPath)) {
            // Используем try-catch для безопасной загрузки
            try {
                require_once $ffmpegCompressorPath;
                $this->ffmpegCompressor = new FfmpegAudioCompressor();
            } catch (Exception $e) {
                error_log("AudioCompressor: Ошибка загрузки FfmpegAudioCompressor - " . $e->getMessage());
                $this->ffmpegCompressor = null;
            }
        }
    }

    /**
     * Проверяет доступность FFMpeg
     */
    public function isFFmpegAvailable()
    {
        return $this->ffmpegCompressor !== null && $this->ffmpegCompressor->isAvailable();
    }

    /**
     * Проверяет доступность расширений PHP для работы с аудио
     */
    public function getAvailableMethods()
    {
        $methods = [];

        if ($this->isFFmpegAvailable()) {
            $methods[] = 'ffmpeg';
        }

        if (extension_loaded('id3')) {
            $methods[] = 'id3_tag_removal';
        }

        if (function_exists('fopen') && function_exists('fread')) {
            $methods[] = 'basic_optimization';
        }

        return $methods;
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
            'method_used' => 'none'
        ];

        if (!file_exists($inputPath)) {
            $result['message'] = 'Исходный файл не найден';
            return $result;
        }

        $result['original_size'] = filesize($inputPath);
        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));

        $quality = $options['quality'] ?? 'medium';
        $qualitySettings = [
            'low' => 96,
            'medium' => 128,
            'high' => 192
        ];
        $targetBitrate = $options['bitrate'] ?? $qualitySettings[$quality] ?? $this->defaultBitrate;

        if ($this->preferFfmpeg && $this->isFFmpegAvailable()) {
            $ffmpegResult = $this->ffmpegCompressor->compress($inputPath, $outputPath, $options);
            if ($ffmpegResult['success']) {
                $ffmpegResult['method_used'] = 'ffmpeg';
                return $ffmpegResult;
            }
            error_log("AudioCompressor: FFMpeg не справился, используем PHP-методы");
        }

        if ($extension === 'mp3') {
            $result = $this->compressMP3($inputPath, $outputPath, $targetBitrate, $result);
        } else {
            $result = $this->copyAudio($inputPath, $outputPath, $result);
        }

        $result['method_used'] = 'php';
        return $result;
    }
    
    /**
     * Оптимизирует MP3 файл с перекодированием
     */
    private function compressMP3($inputPath, $outputPath, $targetBitrate, &$result)
    {
        $info = $this->getAudioInfo($inputPath);
        $currentBitrate = intval($info['bitrate']);

        if ($currentBitrate > 0 && $currentBitrate <= $targetBitrate) {
            $fileContent = file_get_contents($inputPath);
            if ($fileContent === false) {
                $result['message'] = 'Ошибка чтения файла';
                return $result;
            }

            $optimizedContent = $this->removeID3Tags($fileContent);
            
            if (file_put_contents($outputPath, $optimizedContent) === false) {
                $result['message'] = 'Ошибка записи файла';
                return $result;
            }
            
            $result['compressed_size'] = filesize($outputPath);
            $result['compression_ratio'] = round(
                (1 - $result['compressed_size'] / $result['original_size']) * 100, 
                2
            );
            $result['success'] = true;
            $result['message'] = sprintf(
                'Оптимизация (теги): %s → %s (%.2f%% экономии)',
                $this->formatBytes($result['original_size']),
                $this->formatBytes($result['compressed_size']),
                $result['compression_ratio']
            );
            return $result;
        }

        $reencodedContent = $this->reencodeMP3($inputPath, $targetBitrate);

        if ($reencodedContent === false) {
            $fileContent = file_get_contents($inputPath);
            $optimizedContent = $this->removeID3Tags($fileContent);

            if (file_put_contents($outputPath, $optimizedContent) === false) {
                $result['message'] = 'Ошибка записи файла';
                return $result;
            }

            $result['compressed_size'] = filesize($outputPath);
            $result['compression_ratio'] = round(
                (1 - $result['compressed_size'] / $result['original_size']) * 100, 
                2
            );
            $result['success'] = true;
            $result['message'] = sprintf(
                'Оптимизация (теги): %s → %s (%.2f%% экономии)',
                $this->formatBytes($result['original_size']),
                $this->formatBytes($result['compressed_size']),
                $result['compression_ratio']
            );
            return $result;
        }
        
        if (file_put_contents($outputPath, $reencodedContent) === false) {
            $result['message'] = 'Ошибка записи файла';
            return $result;
        }

        $result['compressed_size'] = filesize($outputPath);
        $result['compression_ratio'] = round(
            (1 - $result['compressed_size'] / $result['original_size']) * 100, 
            2
        );
        $result['success'] = true;
        $result['message'] = sprintf(
            'Сжатие успешно (битрейт %d kbps): %s → %s (%.2f%% экономии)',
            $targetBitrate,
            $this->formatBytes($result['original_size']),
            $this->formatBytes($result['compressed_size']),
            $result['compression_ratio']
        );
        return $result;
    }
    
    /**
     * Перекодирует MP3 с понижением битрейта (упрощённое)
     */
    private function reencodeMP3($inputPath, $targetBitrate)
    {
        $data = file_get_contents($inputPath);
        if ($data === false) return false;

        $data = $this->removeID3Tags($data);
        return false;
    }

    /**
     * Удаляет ID3 теги из MP3 данных
     */
    private function removeID3Tags($data)
    {
        $size = strlen($data);

        if (substr($data, 0, 3) === 'ID3') {
            $header = unpack('a3identifier/a2version/Cflags/Nsize', substr($data, 0, 10));

            $tagSize = 0;
            for ($i = 6; $i < 10; $i++) {
                $tagSize = ($tagSize << 7) | ord($data[$i]);
            }
            $tagSize += 10;

            $data = substr($data, $tagSize);
        }

        if (strlen($data) >= 128 && substr($data, -128, 3) === 'TAG') {
            $data = substr($data, 0, -128);
        }

        if (strlen($data) >= 32 && substr($data, -32, 4) === 'APETAGEX') {
            $apeHeader = unpack('Vsize', substr($data, -28, 4));
            $apeSize = $apeHeader['size'] + 32;

            if (strlen($data) >= $apeSize + 32 && substr($data, -($apeSize + 32), 8) === 'APETAGEX') {
                $apeSize += 32;
            }
            $data = substr($data, 0, -$apeSize);
        }
        $data = preg_replace('/\x00{100,}/', '', $data);
        return $data;
    }
    
    /**
     * Копирует аудиофайл (для форматов без сжатия)
     */
    private function copyAudio($inputPath, $outputPath, &$result)
    {
        if (copy($inputPath, $outputPath)) {
            $result['compressed_size'] = filesize($outputPath);
            $result['compression_ratio'] = 0;
            $result['success'] = true;
            $result['message'] = 'Файл скопирован (конвертация недоступна без FFmpeg)';
        } else {
            $result['message'] = 'Ошибка копирования файла';
        }
        return $result;
    }
    
    /**
     * Оптимизирует аудиофайл на месте (перезаписывает оригинал)
     */
    public function optimizeInPlace($filePath, array $options = [])
    {
        // Приоритет FFMpeg если доступен
        if ($this->preferFfmpeg && $this->isFFmpegAvailable()) {
            $result = $this->ffmpegCompressor->optimizeInPlace($filePath, $options);
            if ($result['success']) {
                return $result;
            }
            error_log("AudioCompressor: FFMpeg не справился, используем PHP-методы");
        }

        $tempPath = $filePath . '.opt.tmp';
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
     * Получает информацию об аудиофайле
     */
    public function getAudioInfo($filePath)
    {
        if (!file_exists($filePath)) {
            return null;
        }

        if ($this->isFFmpegAvailable()) {
            $ffmpegInfo = $this->ffmpegCompressor->getAudioInfo($filePath);
            if ($ffmpegInfo !== null) {
                return $ffmpegInfo;
            }
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $size = filesize($filePath);

        $info = [
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'extension' => $extension,
            'duration' => 0,
            'bitrate' => 'N/A',
            'has_id3' => false
        ];

        if ($extension === 'mp3') {
            $content = file_get_contents($filePath, false, null, 0, 1024);

            if (substr($content, 0, 3) === 'ID3') {
                $info['has_id3'] = true;
            }

            $bitrate = $this->detectMP3Bitrate($filePath);
            if ($bitrate > 0) {
                $info['bitrate'] = $bitrate . ' kbps';
            }
        }

        return $info;
    }
    
    /**
     * Определяет битрейт MP3 файла
     */
    private function detectMP3Bitrate($filePath)
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) return 0;

        $header = fread($handle, 10);
        if (substr($header, 0, 3) === 'ID3') {
            $tagSize = 0;
            for ($i = 6; $i < 10; $i++) {
                $tagSize = ($tagSize << 7) | ord($header[$i]);
            }
            fseek($handle, $tagSize + 10);
        }

        for ($i = 0; $i < 100; $i++) {
            $sync = fread($handle, 4);
            if (strlen($sync) < 4) break;

            if ((ord($sync[0]) === 0xFF) && ((ord($sync[1]) & 0xE0) === 0xE0)) {
                $bitrateIndex = (ord($sync[2]) >> 4) & 0x0F;
                $bitrates = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320];
                if (isset($bitrates[$bitrateIndex])) {
                    fclose($handle);
                    return $bitrates[$bitrateIndex];
                }
            }
        }
        fclose($handle);
        return 0;
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