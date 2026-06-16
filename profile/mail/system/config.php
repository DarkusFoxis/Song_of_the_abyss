<?php
require_once __DIR__ . '/../../../template/app_config.php';

$dbConfig = app_db_credentials();
$host = $dbConfig['host'];
$db = $dbConfig['name'];
$user = $dbConfig['user'];
$pass = $dbConfig['pass'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

define("ADMIN_PUBLIC_KEY", app_admin_public_key_path());
define("ADMIN_PRIVATE_KEY", app_admin_private_key_path());
define("ATTACH_DIR", rtrim(app_attachment_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
