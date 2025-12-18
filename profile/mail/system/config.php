<?php
$host = "localhost";
$db = "database";
$user = "login";
$pass = "password";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

define("ADMIN_PUBLIC_KEY", "./../../keys/admin_public.pem");
define("ADMIN_PRIVATE_KEY", "./../../keys/admin_private.pem");
define("ATTACH_DIR", "./../../encrypted_attachments/");