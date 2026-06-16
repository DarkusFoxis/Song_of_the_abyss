<?php
declare(strict_types=1);

require_once __DIR__ . '/app_config.php';

function security_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    return isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
}

function security_is_ajax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function security_bootstrap_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        security_get_csrf_token();
        return;
    }

    if (headers_sent()) {
        session_start();
        security_get_csrf_token();
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => security_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (empty($_SESSION['__session_hardened'])) {
        session_regenerate_id(true);
        $_SESSION['__session_hardened'] = time();
    }

    security_get_csrf_token();
}

function security_get_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if (!headers_sent()) {
        setcookie('csrf_token', $_SESSION['csrf_token'], [
            'expires' => 0,
            'path' => '/',
            'secure' => security_is_https(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['csrf_token'] = $_SESSION['csrf_token'];
    }

    return $_SESSION['csrf_token'];
}

function security_get_request_csrf_token(): string
{
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (is_string($headerToken) && $headerToken !== '') {
        return $headerToken;
    }

    $postToken = $_POST['csrf_token'] ?? '';
    if (is_string($postToken) && $postToken !== '') {
        return $postToken;
    }

    return '';
}

function security_is_same_origin_request(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return false;
    }

    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $headerName) {
        if (empty($_SERVER[$headerName]) || !is_string($_SERVER[$headerName])) {
            continue;
        }

        $parts = parse_url($_SERVER[$headerName]);
        if (!is_array($parts) || empty($parts['host'])) {
            continue;
        }

        if (strtolower((string)$parts['host']) === $host) {
            return true;
        }
    }

    return false;
}

function security_validate_csrf(bool $allowSameOriginFallback = false): bool
{
    security_bootstrap_session();

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $requestToken = security_get_request_csrf_token();

    if (is_string($sessionToken) && is_string($requestToken) && $requestToken !== '' && hash_equals($sessionToken, $requestToken)) {
        return true;
    }

    return $allowSameOriginFallback && security_is_same_origin_request();
}

function security_require_csrf(bool $allowSameOriginFallback = false): void
{
    if (security_validate_csrf($allowSameOriginFallback)) {
        return;
    }

    http_response_code(403);
    if (security_is_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'CSRF validation failed'], JSON_UNESCAPED_UNICODE);
    } else {
        echo 'CSRF validation failed';
    }
    exit;
}

function security_csrf_input(): string
{
    $token = security_get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function security_csrf_meta_tags(): string
{
    $token = security_get_csrf_token();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function security_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function security_detect_mime(string $filePath): ?string
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return null;
    }

    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    return is_string($mime) ? strtolower($mime) : null;
}

function security_store_uploaded_file(array $file, string $targetDir, array $allowedMimeMap, string $prefix = 'upload_', int $maxBytes = 0): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed');
    }

    if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid upload source');
    }

    $size = (int)($file['size'] ?? 0);
    if ($maxBytes > 0 && $size > $maxBytes) {
        throw new RuntimeException('File is too large');
    }

    $mime = security_detect_mime($file['tmp_name']);
    if ($mime === null || !isset($allowedMimeMap[$mime])) {
        throw new RuntimeException('Unsupported file type');
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $extension = $allowedMimeMap[$mime];
    $fileName = uniqid($prefix, true) . '.' . $extension;
    $destination = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to store upload');
    }

    return [
        'filename' => $fileName,
        'path' => $destination,
        'mime' => $mime,
        'size' => $size,
    ];
}

function security_is_private_ip(string $ip): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        if ($ip === '::1') {
            return true;
        }

        $normalized = strtolower($ip);
        return str_starts_with($normalized, 'fc')
            || str_starts_with($normalized, 'fd')
            || str_starts_with($normalized, 'fe80:')
            || str_starts_with($normalized, '::ffff:127.');
    }

    return true;
}

function security_assert_public_url(string $url, array $blockedDomains = []): array
{
    $url = trim($url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Invalid URL');
    }

    $parts = parse_url($url);
    if (!is_array($parts)) {
        throw new RuntimeException('Invalid URL');
    }

    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException('Only http and https are allowed');
    }

    $host = strtolower((string)($parts['host'] ?? ''));
    if ($host === '') {
        throw new RuntimeException('Host is required');
    }

    foreach ($blockedDomains as $blockedDomain) {
        $blockedDomain = strtolower(trim((string)$blockedDomain));
        if ($blockedDomain !== '' && ($host === $blockedDomain || str_ends_with($host, '.' . $blockedDomain))) {
            throw new RuntimeException('Forbidden domain');
        }
    }

    $ips = [];
    $records = @dns_get_record($host, DNS_A + DNS_AAAA);
    if (is_array($records)) {
        foreach ($records as $record) {
            if (!empty($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (!empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }
    }

    if ($ips === []) {
        $fallbackIps = @gethostbynamel($host);
        if (is_array($fallbackIps)) {
            $ips = array_merge($ips, $fallbackIps);
        }
    }

    if ($ips === []) {
        throw new RuntimeException('Failed to resolve host');
    }

    foreach ($ips as $ip) {
        if (security_is_private_ip((string)$ip)) {
            throw new RuntimeException('Private network destinations are not allowed');
        }
    }

    return $parts;
}
