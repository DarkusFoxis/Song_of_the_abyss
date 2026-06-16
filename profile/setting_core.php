<?php
declare(strict_types=1);

require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/../template/nsfw.php';

auth_start_session();
auth_sync_session_from_token();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    finish_setting_request($isAjax, 'error', 'Неверный метод запроса.');
}

$authUser = auth_get_current_user();
if ($authUser === null) {
    finish_setting_request($isAjax, 'error', 'Вы должны быть авторизованы.', 'login');
}

security_require_csrf(true);

$action = (string)($_POST['action'] ?? '');
if ($action === '') {
    finish_setting_request($isAjax, 'error', 'Действие не указано.');
}

require_once __DIR__ . '/../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    finish_setting_request($isAjax, 'error', 'Ошибка соединения с базой данных.');
}

$login = (string)$authUser['login'];
$user = setting_load_user($conn, $login);
if ($user === null) {
    mysqli_close($conn);
    finish_setting_request($isAjax, 'error', 'Пользователь не найден.', 'login');
}

$userId = (int)$user['id'];

switch ($action) {
    case 'avatar':
        handle_avatar_update($conn, $login);
        break;

    case 'new_password':
        handle_password_update($conn, $login, $isAjax);
        break;

    case 'birthdate':
        handle_birthdate_update($conn, $login, $isAjax);
        break;

    case 'delete_account':
        handle_account_delete($conn, $login, $isAjax);
        break;

    case 'bio_redact':
        handle_bio_update($conn, $login, $isAjax);
        break;

    case 'nsfw':
        handle_nsfw_update($conn, $login, $isAjax);
        break;

    case 'promo':
        handle_promo_activation($conn, $user, $isAjax);
        break;

    case 'new_title':
        handle_title_update($conn, $userId, $isAjax);
        break;

    case 'transfer':
        handle_transfer($conn, $user, $isAjax);
        break;

    case 'username':
        handle_username_update($conn, $user, $isAjax);
        break;

    default:
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Неизвестное действие.');
}

mysqli_close($conn);
session_write_close();

function finish_setting_request(bool $isAjax, string $status, string $message, string $redirect = 'setting'): void
{
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $status,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($status === 'success') {
        $_SESSION['great'] = $message;
    } else {
        $_SESSION['error'] = $message;
    }

    header('Location: ' . $redirect);
    exit;
}

function setting_load_user(mysqli $conn, string $login): ?array
{
    $stmt = $conn->prepare('SELECT * FROM users WHERE login = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('s', $login);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?: null;
}

function setting_load_inventory(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM invent WHERE id_user = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?: null;
}

function handle_avatar_update(mysqli $conn, string $login): void
{
    if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Вы не загрузили файл.');
    }

    $file = $_FILES['avatar'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Не удалось загрузить файл.');
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Файл должен быть меньше 5 МБ.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Некорректный источник загрузки.');
    }

    $mime = security_detect_mime($tmpName);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if ($mime === null || !in_array($mime, $allowedMimes, true)) {
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Разрешены только JPG, PNG и WEBP.');
    }

    $imageBytes = file_get_contents($tmpName);
    $sourceImage = $imageBytes !== false ? @imagecreatefromstring($imageBytes) : false;
    if ($sourceImage === false) {
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Не удалось обработать изображение.');
    }

    $width = imagesx($sourceImage);
    $height = imagesy($sourceImage);
    if ($width <= 0 || $height <= 0) {
        imagedestroy($sourceImage);
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Некорректное изображение.');
    }

    $cropSize = min($width, $height);
    $cropX = (int)floor(($width - $cropSize) / 2);
    $cropY = (int)floor(($height - $cropSize) / 2);
    $croppedImage = imagecrop($sourceImage, [
        'x' => $cropX,
        'y' => $cropY,
        'width' => $cropSize,
        'height' => $cropSize,
    ]);

    if ($croppedImage === false) {
        imagedestroy($sourceImage);
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Не удалось обрезать изображение.');
    }

    $resizedImage = imagescale($croppedImage, 550, 550);
    if ($resizedImage === false) {
        imagedestroy($sourceImage);
        imagedestroy($croppedImage);
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Не удалось подготовить изображение.');
    }

    $stmt = $conn->prepare('SELECT avatar FROM users WHERE login = ? LIMIT 1');
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $oldAvatar = (string)($stmt->get_result()->fetch_assoc()['avatar'] ?? '');

    $uploadDir = __DIR__ . '/avatars';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        imagedestroy($sourceImage);
        imagedestroy($croppedImage);
        imagedestroy($resizedImage);
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Не удалось подготовить каталог аватаров.');
    }

    $newAvatar = preg_replace('/[^A-Za-z0-9_. -]/', '_', $login) . '_' . uniqid('', true) . '.jpg';
    $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $newAvatar;

    if (!imagejpeg($resizedImage, $uploadPath, 90)) {
        imagedestroy($sourceImage);
        imagedestroy($croppedImage);
        imagedestroy($resizedImage);
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Не удалось сохранить аватар.');
    }

    imagedestroy($sourceImage);
    imagedestroy($croppedImage);
    imagedestroy($resizedImage);

    $updateStmt = $conn->prepare('UPDATE users SET avatar = ? WHERE login = ?');
    $updateStmt->bind_param('ss', $newAvatar, $login);
    if (!$updateStmt->execute()) {
        @unlink($uploadPath);
        mysqli_close($conn);
        finish_setting_request(false, 'error', 'Не удалось обновить аватар.');
    }

    if ($oldAvatar !== '' && !in_array(basename($oldAvatar), ['avatar.png', 'avatar2.png'], true)) {
        $oldPath = $uploadDir . DIRECTORY_SEPARATOR . basename($oldAvatar);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    mysqli_close($conn);
    finish_setting_request(false, 'success', 'Аватар успешно загружен!');
}

function handle_password_update(mysqli $conn, string $login, bool $isAjax): void
{
    $oldPassword = (string)($_POST['old_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['new_password_confirm'] ?? '');

    if ($newPassword !== $confirmPassword) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Новый пароль и подтверждение не совпадают.');
    }

    if (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Новый пароль должен быть длиной от 8 до 72 символов.');
    }

    $stmt = $conn->prepare('SELECT password FROM users WHERE login = ? LIMIT 1');
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Пользователь не найден.');
    }

    $passwordHash = (string)$row['password'];
    if (!password_verify($oldPassword, $passwordHash)) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Неверный старый пароль.');
    }

    if (password_verify($newPassword, $passwordHash)) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Новый пароль должен отличаться от текущего.');
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE login = ?');
    $updateStmt->bind_param('ss', $newHash, $login);

    if (!$updateStmt->execute()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Не удалось обновить пароль.');
    }

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', 'Пароль успешно изменён!');
}

function handle_birthdate_update(mysqli $conn, string $login, bool $isAjax): void
{
    $birthdate = (string)($_POST['birthdate'] ?? '');
    $date = DateTime::createFromFormat('Y-m-d', $birthdate);
    $errors = DateTime::getLastErrors();
    if (
        $date === false
        || ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))
    ) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Некорректная дата рождения.');
    }

    $today = new DateTime('today');
    $age = (int)$today->diff($date)->y;
    if ($date > $today || $age < 3 || $age > 63) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Дата рождения вне допустимого диапазона.');
    }

    $existsStmt = $conn->prepare('SELECT 1 FROM personal_data WHERE login = ? LIMIT 1');
    $existsStmt->bind_param('s', $login);
    $existsStmt->execute();
    $exists = $existsStmt->get_result()->fetch_assoc() !== null;

    if ($exists) {
        $stmt = $conn->prepare('UPDATE personal_data SET birthdate = ? WHERE login = ?');
        $stmt->bind_param('ss', $birthdate, $login);
    } else {
        $stmt = $conn->prepare('INSERT INTO personal_data (login, birthdate) VALUES (?, ?)');
        $stmt->bind_param('ss', $login, $birthdate);
    }

    if (!$stmt->execute()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Не удалось обновить дату рождения.');
    }

    $_SESSION['age'] = $age >= 18;

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', 'Дата рождения успешно изменена!');
}

function handle_account_delete(mysqli $conn, string $login, bool $isAjax): void
{
    if ((string)($_POST['delete'] ?? '') !== '1') {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Подтвердите удаление аккаунта.');
    }

    $password = (string)($_POST['password'] ?? '');
    $stmt = $conn->prepare('SELECT password FROM users WHERE login = ? LIMIT 1');
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($password, (string)$row['password'])) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Неверный пароль.');
    }

    $deleteStmt = $conn->prepare('DELETE FROM users WHERE login = ?');
    $deleteStmt->bind_param('s', $login);
    if (!$deleteStmt->execute()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Не удалось удалить аккаунт.');
    }

    $_SESSION = [];
    auth_clear_token_cookie();
    session_regenerate_id(true);

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', 'Ваш аккаунт удалён.', 'main');
}

function handle_bio_update(mysqli $conn, string $login, bool $isAjax): void
{
    $bio = trim((string)($_POST['bio'] ?? ''));
    if ($bio === '' || mb_strlen($bio) < 10 || mb_strlen($bio) > 250) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Статус должен быть длиной от 10 до 250 символов.');
    }

    if (preg_match('/<(script|style|iframe|frame|frameset|meta|link|object|body|head|div|input|textarea|form)/i', $bio)) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Статус не должен содержать HTML-теги.');
    }

    $compactBio = preg_replace('/\s/u', '', $bio);
    if ($compactBio === null || $compactBio === '') {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Статус не должен состоять только из пробелов.');
    }

    $spacePercent = mb_strlen($bio) > 0 ? (substr_count($bio, ' ') / mb_strlen($bio)) * 100 : 0;
    if ($spacePercent > 20) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Слишком много пробелов в сообщении.');
    }

    $newLinePercent = mb_strlen($bio) > 0 ? (substr_count($bio, "\n") / mb_strlen($bio)) * 100 : 0;
    if ($newLinePercent > 15) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Слишком много переносов строки в сообщении.');
    }

    $safeBio = security_html($bio);
    $stmt = $conn->prepare('UPDATE users SET BIO = ? WHERE login = ?');
    $stmt->bind_param('ss', $safeBio, $login);

    if (!$stmt->execute()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Не удалось обновить статус.');
    }

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', 'Статус успешно изменён!');
}

function handle_nsfw_update(mysqli $conn, string $login, bool $isAjax): void
{
    $nsfw = (string)($_POST['nsfw'] ?? '');
    if (!in_array($nsfw, ['0', '1'], true)) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Некорректное значение NSFW.');
    }

    $value = (int)$nsfw;
    $user = setting_load_user($conn, $login);
    if ($user === null) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Пользователь не найден.');
    }

    if ($value === 1 && nsfw_user_is_confirm_blocked($user)) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', nsfw_manual_confirmation_notice());
    }

    if ($value === 1) {
        $stmt = $conn->prepare('UPDATE users SET NSFW = 1, AUTO_NSFW = 1, CONFIRM_NSFW = 1 WHERE login = ?');
        $stmt->bind_param('s', $login);
    } else {
        $stmt = $conn->prepare('UPDATE users SET NSFW = 0 WHERE login = ?');
        $stmt->bind_param('s', $login);
    }

    if (!$stmt->execute()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Не удалось обновить настройку NSFW.');
    }

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', 'Настройка NSFW успешно изменена!');
}

function handle_promo_activation(mysqli $conn, array $user, bool $isAjax): void
{
    $promo = trim((string)($_POST['promocode'] ?? ''));
    if ($promo === '' || mb_strlen($promo) > 150) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Некорректный промокод.');
    }

    $userId = (int)$user['id'];
    $inventory = setting_load_inventory($conn, $userId);
    if ($inventory === null) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Не найден инвентарь пользователя.');
    }

    $usedStmt = $conn->prepare('SELECT 1 FROM promo WHERE code = ? AND id_user = ? LIMIT 1');
    $usedStmt->bind_param('si', $promo, $userId);
    $usedStmt->execute();
    if ($usedStmt->get_result()->fetch_assoc()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Промокод уже использован!');
    }

    $promoStmt = $conn->prepare('SELECT * FROM code WHERE code = ? LIMIT 1');
    $promoStmt->bind_param('s', $promo);
    $promoStmt->execute();
    $promoData = $promoStmt->get_result()->fetch_assoc();
    if (!$promoData) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Такого промокода не существует.');
    }

    if ((int)$promoData['quantity'] <= 0) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Количество использований промокода закончилось.');
    }

    mysqli_begin_transaction($conn);
    $rewards = [];

    try {
        $updatePromoStmt = $conn->prepare('UPDATE code SET quantity = quantity - 1 WHERE id = ? AND quantity > 0');
        $promoId = (int)$promoData['id'];
        $updatePromoStmt->bind_param('i', $promoId);
        $updatePromoStmt->execute();
        if ($updatePromoStmt->affected_rows !== 1) {
            throw new RuntimeException('Промокод больше недоступен.');
        }

        $insertUsageStmt = $conn->prepare('INSERT INTO promo (id_usage, id_user, code, date) VALUES (NULL, ?, ?, NOW())');
        $insertUsageStmt->bind_param('is', $userId, $promo);
        $insertUsageStmt->execute();

        $rewardMap = [
            'petal' => ['table' => 'invent', 'field' => 'sakura', 'label' => 'лепестков'],
            'xp' => ['table' => 'invent', 'field' => 'xp', 'label' => 'опыта'],
            'coin' => ['table' => 'invent', 'field' => 'coins', 'label' => 'монет'],
            'kase' => ['table' => 'invent', 'field' => 'kase', 'label' => 'кейсов'],
            'donate' => ['table' => 'users', 'field' => 'donate', 'label' => 'рублей доната'],
        ];

        foreach ($rewardMap as $promoField => $meta) {
            if ($promoData[$promoField] === null) {
                continue;
            }

            $value = (int)$promoData[$promoField];
            if ($value <= 0) {
                continue;
            }

            $query = sprintf('UPDATE %s SET `%s` = `%s` + ? WHERE %s = ?', $meta['table'], $meta['field'], $meta['field'], $meta['table'] === 'invent' ? 'id_user' : 'id');
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $value, $userId);
            $stmt->execute();
            $rewards[] = $value . ' ' . $meta['label'];
        }

        if ($promoData['stiker'] !== null && trim((string)$promoData['stiker']) !== '') {
            $rewards[] = setting_add_reward_sticker($conn, $userId, (string)$promoData['stiker']);
        }

        if ($promoData['title'] !== null && trim((string)$promoData['title']) !== '') {
            $title = trim((string)$promoData['title']);
            $titleCheckStmt = $conn->prepare('SELECT 1 FROM title WHERE id_user = ? AND title = ? LIMIT 1');
            $titleCheckStmt->bind_param('is', $userId, $title);
            $titleCheckStmt->execute();
            if (!$titleCheckStmt->get_result()->fetch_assoc()) {
                $insertTitleStmt = $conn->prepare('INSERT INTO title (id_title, id_user, title) VALUES (NULL, ?, ?)');
                $insertTitleStmt->bind_param('is', $userId, $title);
                $insertTitleStmt->execute();
                $rewards[] = 'титул: ' . $title;
            }
        }

        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Ошибка при активации промокода: ' . $e->getMessage());
    }

    $message = $rewards === []
        ? 'Промокод активирован, но не содержит доступных наград.'
        : 'Промокод активирован! Получено: ' . implode(', ', $rewards);

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', $message);
}

function handle_title_update(mysqli $conn, int $userId, bool $isAjax): void
{
    $inventory = setting_load_inventory($conn, $userId);
    if ($inventory === null) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'У вас отсутствует инвентарь.');
    }

    $titleId = (string)($_POST['title'] ?? '');
    if ($titleId === 'NULL') {
        $stmt = $conn->prepare('UPDATE invent SET id_title = NULL WHERE id_user = ?');
        $stmt->bind_param('i', $userId);
    } else {
        $numericTitleId = (int)$titleId;
        if ($numericTitleId <= 0) {
            mysqli_close($conn);
            finish_setting_request($isAjax, 'error', 'Некорректный титул.');
        }

        $titleStmt = $conn->prepare('SELECT 1 FROM title WHERE id_title = ? AND id_user = ? LIMIT 1');
        $titleStmt->bind_param('ii', $numericTitleId, $userId);
        $titleStmt->execute();
        if (!$titleStmt->get_result()->fetch_assoc()) {
            mysqli_close($conn);
            finish_setting_request($isAjax, 'error', 'Титул вам не принадлежит.');
        }

        $stmt = $conn->prepare('UPDATE invent SET id_title = ? WHERE id_user = ?');
        $stmt->bind_param('ii', $numericTitleId, $userId);
    }

    if (!$stmt->execute()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Не удалось обновить титул.');
    }

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', 'Титул успешно изменён!');
}

function handle_transfer(mysqli $conn, array $user, bool $isAjax): void
{
    $senderId = (int)$user['id'];
    $recipientId = (int)($_POST['recipient_id'] ?? 0);
    $resourceType = (string)($_POST['resource_type'] ?? '');
    $amount = (int)($_POST['amount'] ?? 0);

    $allowedResources = [
        'coins' => 'coins_limit',
        'sakura' => 'sakura_limit',
    ];

    if (!isset($allowedResources[$resourceType]) || $amount <= 0 || $recipientId <= 0) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Некорректные параметры перевода.');
    }

    if ($recipientId === $senderId) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Нельзя переводить ресурсы самому себе.');
    }

    $lastTransferStmt = $conn->prepare('SELECT time FROM pay_log WHERE first_user_id = ? ORDER BY time DESC LIMIT 1');
    $lastTransferStmt->bind_param('i', $senderId);
    $lastTransferStmt->execute();
    $lastTransfer = $lastTransferStmt->get_result()->fetch_assoc();
    if ($lastTransfer) {
        $lastTime = strtotime((string)$lastTransfer['time']);
        if ($lastTime !== false && time() - $lastTime < 180) {
            mysqli_close($conn);
            finish_setting_request($isAjax, 'error', 'Перевод можно делать только раз в 3 минуты.');
        }
    }

    $toolsStmt = $conn->prepare('SELECT * FROM tools WHERE user_id = ? LIMIT 1');
    $toolsStmt->bind_param('i', $senderId);
    $toolsStmt->execute();
    $tools = $toolsStmt->get_result()->fetch_assoc();
    if (!$tools) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'У вас не активированы инструменты.');
    }

    $limitField = $allowedResources[$resourceType];
    $limit = (int)($tools[$limitField] ?? 0);
    if ($limit <= 0 || $amount > $limit) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Превышен лимит перевода. Максимум: ' . $limit);
    }

    $senderInventory = setting_load_inventory($conn, $senderId);
    $recipientInventory = setting_load_inventory($conn, $recipientId);
    if ($senderInventory === null || $recipientInventory === null) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'У одного из пользователей отсутствует инвентарь.');
    }

    if ((int)$senderInventory[$resourceType] < $amount) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Недостаточно ресурсов.');
    }

    mysqli_begin_transaction($conn);

    try {
        $senderUpdate = $conn->prepare(sprintf('UPDATE invent SET `%s` = `%s` - ? WHERE id_user = ?', $resourceType, $resourceType));
        $senderUpdate->bind_param('ii', $amount, $senderId);
        $senderUpdate->execute();

        $recipientUpdate = $conn->prepare(sprintf('UPDATE invent SET `%s` = `%s` + ? WHERE id_user = ?', $resourceType, $resourceType));
        $recipientUpdate->bind_param('ii', $amount, $recipientId);
        $recipientUpdate->execute();

        $logStmt = $conn->prepare('INSERT INTO pay_log (first_user_id, second_user_id, type, count, time) VALUES (?, ?, ?, ?, NOW())');
        $logStmt->bind_param('iisi', $senderId, $recipientId, $resourceType, $amount);
        $logStmt->execute();

        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Ошибка перевода: ' . $e->getMessage());
    }

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', 'Перевод успешно выполнен!');
}

function handle_username_update(mysqli $conn, array $user, bool $isAjax): void
{
    $login = (string)$user['login'];
    $userId = (int)$user['id'];
    $username = trim((string)($_POST['username'] ?? ''));

    if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 20) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Никнейм должен быть длиной от 3 до 20 символов.');
    }

    if (!preg_match('/^[\p{L}\p{N}_ .-]+$/u', $username)) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Никнейм содержит недопустимые символы.');
    }

    $checkStmt = $conn->prepare('SELECT 1 FROM users WHERE username = ? AND login <> ? LIMIT 1');
    $checkStmt->bind_param('ss', $username, $login);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_assoc()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Такой никнейм уже используется другим пользователем.');
    }

    $updateStmt = $conn->prepare('UPDATE users SET username = ? WHERE login = ?');
    $updateStmt->bind_param('ss', $username, $login);
    if (!$updateStmt->execute()) {
        mysqli_close($conn);
        finish_setting_request($isAjax, 'error', 'Не удалось обновить никнейм.');
    }

    $achievementTitle = 'Воин Родос';
    $achievementDescription = $username . ', не забывайте ваше призвание. Наше дело ещё не подошло к концу.';
    $achievementStmt = $conn->prepare('UPDATE achievement SET description = ? WHERE id_user = ? AND title = ?');
    $achievementStmt->bind_param('sis', $achievementDescription, $userId, $achievementTitle);
    $achievementStmt->execute();

    $_SESSION['username'] = $username;

    mysqli_close($conn);
    finish_setting_request($isAjax, 'success', 'Никнейм успешно изменён!');
}

function setting_add_reward_sticker(mysqli $conn, int $userId, string $rarity): string
{
    $rarityMap = [
        'com' => ['avrora_default', 'darkus_default', 'minami_default', 'misuki_default'],
        'rar' => ['avrora_halloween', 'avrora_reburn', 'old_avrora', 'amagi'],
        'epic' => ['darkusfoxis', 'izuna', 'sameko_saba', 'raphtalia'],
        'leg' => ['ahri', 'ahri2', 'miku', 'hoshino'],
        'myst' => ['avrora', 'frostnova', 'texas', 'mon3tr'],
    ];

    if (!isset($rarityMap[$rarity])) {
        return 'стикер';
    }

    $items = $rarityMap[$rarity];
    $stickerName = $items[array_rand($items)];
    $description = 'Наградной стикер по промокоду.';

    $stmt = $conn->prepare('INSERT INTO stikers (id_user, stikers, description, rarity) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $userId, $stickerName, $description, $rarity);
    $stmt->execute();

    return 'стикер ' . $stickerName;
}
