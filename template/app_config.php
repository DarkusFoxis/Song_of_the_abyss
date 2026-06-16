<?php
declare(strict_types=1);

if (!defined('APP_PUBLIC_ROOT')) {
    define('APP_PUBLIC_ROOT', dirname(__DIR__));
}

if (!defined('APP_PROJECT_ROOT')) {
    define('APP_PROJECT_ROOT', dirname(APP_PUBLIC_ROOT));
}

if (!defined('APP_PRIVATE_ROOT')) {
    define('APP_PRIVATE_ROOT', APP_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'private');
}

function app_normalize_path(string $path): string
{
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function app_public_path(string $relative = ''): string
{
    if ($relative === '') {
        return APP_PUBLIC_ROOT;
    }

    return APP_PUBLIC_ROOT . DIRECTORY_SEPARATOR . ltrim(app_normalize_path($relative), DIRECTORY_SEPARATOR);
}

function app_private_path(string $relative = ''): string
{
    if ($relative === '') {
        return APP_PRIVATE_ROOT;
    }

    return APP_PRIVATE_ROOT . DIRECTORY_SEPARATOR . ltrim(app_normalize_path($relative), DIRECTORY_SEPARATOR);
}

function app_private_ensure_dir(string $relative = ''): string
{
    $dir = $relative === '' ? APP_PRIVATE_ROOT : app_private_path($relative);
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    return $dir;
}

function app_load_file_secrets(): array
{
    static $secrets = null;

    if ($secrets !== null) {
        return $secrets;
    }

    $path = app_private_path('config/secrets.php');
    if (!is_file($path)) {
        $secrets = [];
        return $secrets;
    }

    $loaded = require $path;
    $secrets = is_array($loaded) ? $loaded : [];

    return $secrets;
}

function app_env_secret(string $key): ?string
{
    $envKey = strtoupper($key);
    $value = getenv($envKey);
    if ($value === false || $value === '') {
        return null;
    }

    return $value;
}

function app_secret(string $key, $default = null)
{
    $envValue = app_env_secret($key);
    if ($envValue !== null) {
        return $envValue;
    }

    $fileSecrets = app_load_file_secrets();
    if (array_key_exists($key, $fileSecrets)) {
        return $fileSecrets[$key];
    }

    return $default;
}

function app_require_secret(string $key): string
{
    $value = app_secret($key);
    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException('Missing required secret: ' . $key);
    }

    return $value;
}

function app_db_credentials(): array
{
    return [
        'host' => (string)app_secret('db_host', 'localhost'),
        'name' => app_require_secret('db_name'),
        'user' => app_require_secret('db_user'),
        'pass' => app_require_secret('db_password'),
    ];
}

function app_admin_public_key_path(): string
{
    return (string)app_secret('admin_public_key_path', app_private_path('keys/admin_public.pem'));
}

function app_admin_private_key_path(): string
{
    return (string)app_secret('admin_private_key_path', app_private_path('keys/admin_private.pem'));
}

function app_user_private_key_path(int $userId): string
{
    $dir = (string)app_secret('user_private_keys_dir', app_private_path('keys/users'));
    return rtrim(app_normalize_path($dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'user_' . $userId . '.pem';
}

function app_attachment_dir(): string
{
    return (string)app_secret('mail_attachment_dir', app_private_path('encrypted_attachments'));
}

function app_mail_attachment_path(string $fileName): string
{
    return rtrim(app_attachment_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($fileName);
}

function app_smtp_settings(): array
{
    return [
        'host' => (string)app_secret('smtp_host', 'smtp.beget.com'),
        'port' => (int)app_secret('smtp_port', 465),
        'username' => app_require_secret('smtp_username'),
        'password' => app_require_secret('smtp_password'),
        'secure' => (string)app_secret('smtp_secure', 'ssl'),
        'from_email' => app_require_secret('smtp_from_email'),
        'from_name' => (string)app_secret('smtp_from_name', 'Aurora'),
    ];
}

function app_nvidia_api_key(): string
{
    return app_require_secret('nvidia_api_key');
}
