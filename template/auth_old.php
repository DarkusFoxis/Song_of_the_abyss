<?php

if (!defined('AUTH_TOKEN_COOKIE')) {
    define('AUTH_TOKEN_COOKIE', 'bear_token');
}

if (!defined('AUTH_TOKEN_TTL')) {
    define('AUTH_TOKEN_TTL', 31536000);
}

function auth_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function auth_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443;
}

function auth_set_token_cookie(string $token): void
{
    if (headers_sent()) {
        return;
    }

    $params = [
        'expires' => time() + AUTH_TOKEN_TTL,
        'path' => '/',
        'secure' => auth_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    setcookie(AUTH_TOKEN_COOKIE, $token, $params);
    $_COOKIE[AUTH_TOKEN_COOKIE] = $token;
}

function auth_clear_token_cookie(): void
{
    if (!headers_sent()) {
        $params = [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => auth_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        setcookie(AUTH_TOKEN_COOKIE, '', $params);
    }

    unset($_COOKIE[AUTH_TOKEN_COOKIE]);
}

function auth_generate_token(string $login): string
{
    return hash('sha256', $login . '|' . bin2hex(random_bytes(32)) . '|' . microtime(true));
}

function auth_get_db_connection(): ?mysqli
{
    global $hosts, $logn, $passwords_sql, $dbase;
    
    require_once __DIR__ . '/conn_sys.php';

    $conndb = mysqli_connect($hosts, $logn, $passwords_sql, $dbase);
    if (!$conndb) {
        return null;
    }

    mysqli_set_charset($conndb, 'utf8mb4');
    return $conndb;
}

function auth_get_user_by_login(mysqli $conndb, string $login): ?array
{
    $sql = "SELECT u.*, sg.lvl 
            FROM users u 
            LEFT JOIN site_group sg ON u.permissions = sg.name
            WHERE u.login = ?
            LIMIT 1";
    $stmt = $conndb->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user ?: null;
}

function auth_get_user_by_token(mysqli $conndb, string $token): ?array
{
    $sql = "SELECT u.*, sg.lvl 
            FROM users u 
            LEFT JOIN site_group sg ON u.permissions = sg.name
            WHERE u.token = ?
            LIMIT 1";
    $stmt = $conndb->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user ?: null;
}

function auth_ensure_user_token(mysqli $conndb, string $login, ?string $existingToken = null): ?string
{
    $token = (string)$existingToken;

    if ($token === '') {
        $stmt = $conndb->prepare("SELECT token FROM users WHERE login = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $token = isset($row['token']) ? (string)$row['token'] : '';
    }

    if ($token !== '') {
        return $token;
    }

    $newToken = auth_generate_token($login);
    $stmt = $conndb->prepare("UPDATE users SET token = ? WHERE login = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $newToken, $login);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok ? $newToken : null;
}

function auth_set_session_user(array $user): void
{
    auth_start_session();
    $_SESSION['user'] = $user['login'];
    $_SESSION['username'] = $user['username'];
}

function auth_clear_session_user(): void
{
    auth_start_session();
    unset($_SESSION['user'], $_SESSION['username']);
}

function auth_is_safe_redirect(string $target): bool
{
    if ($target === '' || strpos($target, "\0") !== false) {
        return false;
    }

    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $target)) {
        return false;
    }

    if (strpos($target, '//') === 0) {
        return false;
    }

    return true;
}

function auth_extract_local_path_from_referer(string $referer): ?string
{
    $parts = parse_url($referer);
    if (!$parts || empty($parts['host']) || empty($_SERVER['HTTP_HOST'])) {
        return null;
    }

    if (strtolower($parts['host']) !== strtolower($_SERVER['HTTP_HOST'])) {
        return null;
    }

    $path = isset($parts['path']) ? $parts['path'] : '/';
    $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
    $target = $path . $query;

    return auth_is_safe_redirect($target) ? $target : null;
}

function auth_get_redirect_target(string $default = '/profile/main'): string
{
    auth_start_session();

    $candidates = [];

    if (isset($_POST['redirect']) && is_string($_POST['redirect'])) {
        $candidates[] = $_POST['redirect'];
    }
    if (isset($_GET['redirect']) && is_string($_GET['redirect'])) {
        $candidates[] = $_GET['redirect'];
    }
    if (isset($_SESSION['auth_redirect']) && is_string($_SESSION['auth_redirect'])) {
        $candidates[] = $_SESSION['auth_redirect'];
    }
    if (!empty($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER'])) {
        $fromRef = auth_extract_local_path_from_referer($_SERVER['HTTP_REFERER']);
        if ($fromRef !== null) {
            $candidates[] = $fromRef;
        }
    }

    unset($_SESSION['auth_redirect']);

    $forbiddenTargets = [
        '/profile/login',
        '/profile/log',
        '/profile/registration',
        '/profile/reg',
        '/profile/logout',
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if ($candidate === '' || !auth_is_safe_redirect($candidate)) {
            continue;
        }

        $isForbidden = false;
        foreach ($forbiddenTargets as $forbidden) {
            if (strpos($candidate, $forbidden) === 0) {
                $isForbidden = true;
                break;
            }
        }

        if (!$isForbidden) {
            return $candidate;
        }
    }

    return $default;
}

function auth_sync_session_from_token(): ?array
{
    auth_start_session();

    $token = isset($_COOKIE[AUTH_TOKEN_COOKIE]) ? (string)$_COOKIE[AUTH_TOKEN_COOKIE] : '';
    $conndb = auth_get_db_connection();
    if (!$conndb) {
        return null;
    }

    if ($token !== '') {
        $user = auth_get_user_by_token($conndb, $token);
        if (!$user) {
            mysqli_close($conndb);
            auth_clear_session_user();
            auth_clear_token_cookie();
            return null;
        }

        auth_set_session_user($user);
        mysqli_close($conndb);
        return $user;
    }

    if (isset($_SESSION['user']) && is_string($_SESSION['user']) && $_SESSION['user'] !== '') {
        $user = auth_get_user_by_login($conndb, $_SESSION['user']);
        if ($user) {
            $newToken = auth_ensure_user_token($conndb, $user['login'], isset($user['token']) ? (string)$user['token'] : null);
            if ($newToken) {
                auth_set_token_cookie($newToken);
            }
            auth_set_session_user($user);
            mysqli_close($conndb);
            return $user;
        }
        auth_clear_session_user();
    }

    mysqli_close($conndb);
    return null;
}

function auth_get_current_user(): ?array
{
    $user = auth_sync_session_from_token();
    if ($user !== null) {
        return $user;
    }

    auth_start_session();
    if (!isset($_SESSION['user']) || !is_string($_SESSION['user']) || $_SESSION['user'] === '') {
        return null;
    }

    $conndb = auth_get_db_connection();
    if (!$conndb) {
        return null;
    }

    $login = $_SESSION['user'];
    $user = auth_get_user_by_login($conndb, $login);
    if (!$user) {
        mysqli_close($conndb);
        auth_clear_session_user();
        return null;
    }

    $token = auth_ensure_user_token($conndb, $user['login'], isset($user['token']) ? (string)$user['token'] : null);
    if ($token !== null && $token !== '') {
        auth_set_token_cookie($token);
    }

    mysqli_close($conndb);
    auth_set_session_user($user);
    return $user;
}

function auth_require_user(string $loginPath = '/profile/login'): array
{
    $user = auth_get_current_user();
    if ($user !== null) {
        return $user;
    }

    auth_start_session();
    $target = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';
    if (!auth_is_safe_redirect($target)) {
        $target = '/';
    }
    $_SESSION['auth_redirect'] = $target;

    $separator = (strpos($loginPath, '?') === false) ? '?' : '&';
    header('Location: ' . $loginPath . $separator . 'redirect=' . urlencode($target));
    exit;
}

function auth_login_user(mysqli $conndb, array $user): bool
{
    $login = isset($user['login']) ? (string)$user['login'] : '';
    if ($login === '') {
        return false;
    }

    $token = auth_ensure_user_token($conndb, $login, isset($user['token']) ? (string)$user['token'] : null);
    if ($token === null || $token === '') {
        return false;
    }

    auth_set_token_cookie($token);
    auth_set_session_user($user);
    return true;
}

function auth_logout_user(): void
{
    auth_start_session();
    auth_clear_session_user();
    auth_clear_token_cookie();
    session_destroy();
}