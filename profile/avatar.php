<?php
session_start();
require_once '../template/conn.php';
$pdo = new PDO("mysql:host=$host;dbname=$database", $log, $password_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && isset($_SESSION['user'])) {
    $username = $_SESSION['user'];

    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE login = :username");
    $stmt->execute(['username' => $username]);
    $old_avatar = $stmt->fetchColumn();
    if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['size'] > 0) {
        if ($_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
            $upload_dir = 'avatars/';

            $avatar_name = $_FILES['avatar']['name'];
            $avatar_ext = pathinfo($avatar_name, PATHINFO_EXTENSION);
            if ($avatar_ext == "png" or $avatar_ext == "jpg" or $avatar_ext == "webp" or $avatar_ext == "jpeg" or $avatar_ext == "PNG") {
                $new_avatar = $username . '_' . uniqid() . '.' . $avatar_ext;
                
                if ($old_avatar) {
                    if ($old_avatar !== "avatar.png"){
                        unlink($upload_dir . $old_avatar);
                        $stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE username = :username");
                        $stmt->execute(['username' => $username]);
                    }
                }
            
                $upload_path = $upload_dir . $new_avatar;
            
                $source_image = imagecreatefromstring(file_get_contents($_FILES['avatar']['tmp_name']));
                $width = imagesx($source_image);
                $height = imagesy($source_image);
            
                $new_width = $new_height = 550;
                $crop_width = $crop_height = min($width, $height);
            
                $cropped_image = imagecrop($source_image, ['x' => ($width - $crop_width) / 2, 'y' => ($height - $crop_height) / 2, 'width' => $crop_width, 'height' => $crop_height]);
                $resized_image = imagescale($cropped_image, $new_width, $new_height);

                imagejpeg($resized_image, $upload_path);
            
                imagedestroy($source_image);
                imagedestroy($cropped_image);
                imagedestroy($resized_image);
            
                $stmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE login = :username");
                $stmt->execute(['avatar' => $new_avatar, 'username' => $username]);
            
                $_SESSION["great"] = "Аватар успешно загружен!";
                header("Location: setting");
                exit();
            } else {
                $_SESSION["error"] = "Неизвестное расширение файла. Используйте jpg(jpeg), png или webp.";
                header("Location: setting");
                exit();
            }
        } else {
            $_SESSION["error"] = "Файл слишком большой. Пожалуйста, выберите файл менее 5-ти мегабайт.";
            header("Location: setting");
            exit();
        }
    } else{
        $_SESSION["error"] = "Вы не загрузили файл.";
            header("Location: setting");
            exit();
    }
}