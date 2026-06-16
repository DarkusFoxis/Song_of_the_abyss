<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Moscow');

if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
    header('Location: index');
    exit;
}

require_once __DIR__ . '/template/auth.php';

auth_start_session();
auth_sync_session_from_token();

$authUser = auth_get_current_user();
if ($authUser === null) {
    echo 'Вы должны быть авторизованы.';
    exit;
}

security_require_csrf(true);

require_once __DIR__ . '/template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    http_response_code(500);
    echo 'Ошибка соединения: ' . mysqli_connect_error();
    exit;
}

$action = (string)($_POST['action'] ?? 'unknown');
$login = (string)$authUser['login'];
$user = load_user($conn, $login);

if ($user === null) {
    mysqli_close($conn);
    echo 'Пользователь не найден.';
    exit;
}

$userLevel = (int)($user['lvl'] ?? 0);
if ($userLevel === 0) {
    mysqli_close($conn);
    echo 'Вы заблокированы на проекте.';
    exit;
}

if ($userLevel === 1) {
    mysqli_close($conn);
    echo 'Вы не подтверждены на сайте.';
    exit;
}

$userId = (int)$user['id'];
$inventory = load_inventory($conn, $userId);
if ($action !== 'create_invent' && $inventory === null) {
    mysqli_close($conn);
    echo 'У вас отсутствует инвентарь!';
    exit;
}

$bonusMultiplier = $userLevel >= 3 ? 1.75 : 1.0;
$bonusHours = $userLevel >= 3 ? 3 : 6;
$bonusStickerChance = $userLevel >= 3 ? 5 : -1;
$donate = (int)($user['donate'] ?? 0);
$bonusAdd = [
    'xp' => (int)ceil($donate / 25),
    'coin' => (int)ceil($donate / 250),
    'petal' => (int)ceil($donate / 750),
    'kase' => (int)ceil($donate / 3500),
];

switch ($action) {
    case 'create_invent':
        if ($inventory !== null) {
            send_items_json(false, 'Инвентарь уже существует!');
        }

        $stmt = $conn->prepare(
            'INSERT INTO invent (id_user, lvl, xp, xp_max, coins, bonus_data, gems, kase, sakura) VALUES (?, 0, 0, 1000, 10, NOW(), 1, 5, 10)'
        );
        $stmt->bind_param('i', $userId);

        if (!$stmt->execute()) {
            send_items_json(false, 'Ошибка создания инвентаря: ' . $stmt->error);
        }

        send_items_json(true, 'Инвентарь создан!', true);
        break;

    case 'add_xp':
        $xpValue = (int)floor($_POST['xp'] * (1 + $lvl / 10) / 7 * $bonusMultiplier + $bonusAdd['xp']);
        update_inventory_field($conn, $userId, 'xp', $xpValue, true);
        break;
    case 'add_coins':
        $coinValue = (int)floor($_POST['coin'] * (1 + $lvl / 10) * $bonusMultiplier + $bonusAdd['coin']);
        update_inventory_field($conn, $userId, 'coins', $coinValue, false);
        break;
    case 'add_sakura':
        $petalValue = (int)floor($_POST['sakura'] * (1 + $lvl / 10) * $bonusMultiplier + $bonusAdd['petal']);
        update_inventory_field($conn, $userId, 'sakura', $petalValue, false);
        break;
    case 'add_multiply':
        $xpValue = (int)floor($_POST['xp'] * (1 + $lvl / 10) / 7 * $bonusMultiplier + $bonusAdd['xp']);
        update_inventory_field($conn, $userId, 'xp', $xpValue, true);
        $coinValue = (int)floor($_POST['coin'] * (1 + $lvl / 10) * $bonusMultiplier + $bonusAdd['coin']);
        update_inventory_field($conn, $userId, 'coins', $coinValue, false);
        $petalValue = (int)floor($_POST['sakura'] * (1 + $lvl / 10) * $bonusMultiplier + $bonusAdd['petal']);
        update_inventory_field($conn, $userId, 'sakura', $petalValue, false);
        break;
    case 'get_bonus':
        $nextBonus = strtotime((string)$inventory['bonus_data'] . ' +' . $bonusHours . ' hours');
        if ($nextBonus !== false && time() < $nextBonus) {
            $diff = $nextBonus - time();
            $hours = str_pad((string)floor($diff / 3600), 2, '0', STR_PAD_LEFT);
            $minutes = str_pad((string)floor(($diff % 3600) / 60), 2, '0', STR_PAD_LEFT);
            $seconds = str_pad((string)($diff % 60), 2, '0', STR_PAD_LEFT);
            echo 'Следующий бонус через ' . $hours . ':' . $minutes . ':' . $seconds;
            break;
        }

        $rewardType = mt_rand(1, 5);
        $lvl = (int)$inventory['lvl'];
        $xpMax = max((float)$inventory['xp_max'], 100.0);
        $text = 'В сегодняшнем бонусе вы получили: ';

        switch ($rewardType) {
            case 1:
                $xpValue = (int)floor(floor(mt_rand(19, (int)max(19, floor($xpMax / 8))) * (1 + $lvl / 10) / 7 * $bonusMultiplier) + $bonusAdd['xp']);
                $coinValue = (int)round(13 * (9 + $lvl / 10) * $bonusMultiplier);
                update_inventory_field($conn, $userId, 'xp', $xpValue, true);
                update_inventory_field($conn, $userId, 'coins', $coinValue, false);
                $text .= $xpValue . ' опыта и ' . $coinValue . ' монет!';
                break;

            case 2:
                $coinValue = (int)floor(mt_rand(1, 60) * (1 + $lvl / 10) * $bonusMultiplier + $bonusAdd['coin']);
                $xpValue = (int)round(15 * (9 + $lvl / 10) * $bonusMultiplier);
                update_inventory_field($conn, $userId, 'coins', $coinValue, false);
                update_inventory_field($conn, $userId, 'xp', $xpValue, true);
                $text .= $coinValue . ' монет и ' . $xpValue . ' опыта!';
                break;

            case 3:
                $petalValue = (int)floor(mt_rand(1, 35) * (1 + $lvl / 10) * $bonusMultiplier + $bonusAdd['petal']);
                $xpValue = (int)round(21 * (9 + $lvl / 10) * $bonusMultiplier);
                update_inventory_field($conn, $userId, 'sakura', $petalValue, false);
                update_inventory_field($conn, $userId, 'xp', $xpValue, true);
                $text .= $petalValue . ' лепестков сакуры и ' . $xpValue . ' опыта!';
                break;

            case 4:
                $petalValue = (int)floor(mt_rand(1, 55) * (1 + $lvl / 10) * $bonusMultiplier + $bonusAdd['petal']);
                $coinValue = (int)round(17 * (9 + $lvl / 10) * $bonusMultiplier);
                update_inventory_field($conn, $userId, 'sakura', $petalValue, false);
                update_inventory_field($conn, $userId, 'coins', $coinValue, false);
                $text .= $petalValue . ' лепестков сакуры и ' . $coinValue . ' монет!';
                break;

            case 5:
                $caseValue = (int)floor(mt_rand(1, 4) * (1 + $lvl / 15) * $bonusMultiplier) + $bonusAdd['kase'];
                $petalValue = (int)round(11 * (8 + $lvl / 10) * $bonusMultiplier);
                update_inventory_field($conn, $userId, 'kase', $caseValue, false);
                update_inventory_field($conn, $userId, 'sakura', $petalValue, false);
                $text .= $caseValue . ' кейсов и ' . $petalValue . ' лепестков!';
                break;
        }

        $stickerChance = mt_rand(0, 10);
        if (in_array($stickerChance, [1, 3, 8], true) || $stickerChance === $bonusStickerChance) {
            $text .= ' ' . add_sticker($conn, $userId);
        }

        $bonusStmt = $conn->prepare('UPDATE invent SET bonus_data = NOW() WHERE id_user = ?');
        $bonusStmt->bind_param('i', $userId);
        $bonusStmt->execute();

        echo $text;
        break;

    case 'lvl_up':
        $currentXp = (float)$inventory['xp'];
        $xpMax = (float)$inventory['xp_max'];
        if ($currentXp < $xpMax) {
            echo 'Недостаточно опыта!';
            break;
        }

        $currentLvl = (int)$inventory['lvl'];
        $newLvl = $currentLvl + 1;
        $coinCost = (int)round(350 * $newLvl * (($newLvl + 9) / 10));
        $petalCost = $newLvl % 10 === 0 ? 10000 * (int)floor($newLvl / 10) : 0;

        if ((int)$inventory['coins'] < $coinCost || ($petalCost > 0 && (int)$inventory['sakura'] < $petalCost)) {
            echo 'Недостаточно ресурсов!';
            break;
        }

        $newXp = max(0.0, $currentXp - $xpMax);
        $newXpMax = $xpMax + 1000 * (1 + (int)floor($currentLvl / 10));
        $stmt = $conn->prepare(
            'UPDATE invent SET xp = ?, xp_max = ?, lvl = ?, coins = coins - ?, sakura = sakura - ?, bonus_data = ? WHERE id_user = ?'
        );
        $newBonusDate = date('Y-m-d H:i:s', strtotime((string)$inventory['bonus_data'] . ' -' . $bonusHours . ' hours'));
        $stmt->bind_param('ddiiisi', $newXp, $newXpMax, $newLvl, $coinCost, $petalCost, $newBonusDate, $userId);
        if (!$stmt->execute()) {
            echo 'Ошибка при обновлении уровня: ' . $stmt->error;
            break;
        }

        echo 'Уровень повышен! ' . add_sticker($conn, $userId);
        break;

    case 'group_add':
        echo 'Вывод из кликера временно приостановлен.';
        break;

    case 'stiker_add':
        $costs = [
            'com' => 100,
            'rar' => 225,
            'epic' => 500,
            'leg' => 1010,
            'myst' => 2500,
            'rnd' => 200,
        ];
        $rarity = (string)($_POST['rarity'] ?? 'rnd');
        if (!isset($costs[$rarity])) {
            echo 'Неверная редкость.';
            break;
        }

        $cost = $costs[$rarity];
        if ((int)$inventory['sakura'] < $cost) {
            echo 'У вас недостаточно лепестков для покупки стикера.';
            break;
        }

        update_inventory_field($conn, $userId, 'sakura', -$cost, false);
        echo add_sticker($conn, $userId, $rarity === 'rnd' ? null : $rarity);
        break;

    case 'stiker_game':
        echo add_sticker($conn, $userId);
        break;

    case 'stiker_delete':
    case 'stiker_sell':
        $stickerId = (int)($_POST['stikerId'] ?? $_POST['id'] ?? 0);
        if ($stickerId <= 0) {
            echo 'Ошибка обработки стикера.';
            break;
        }

        $stickerStmt = $conn->prepare('SELECT id_stikers, rarity, exclusive FROM stikers WHERE id_stikers = ? AND id_user = ? LIMIT 1');
        $stickerStmt->bind_param('ii', $stickerId, $userId);
        $stickerStmt->execute();
        $sticker = $stickerStmt->get_result()->fetch_assoc();
        if (!$sticker) {
            echo 'Стикер не найден.';
            break;
        }

        $rewards = [
            'com' => ['coins' => 43],
            'rar' => ['sakura' => 47],
            'epic' => ['sakura' => 150],
            'leg' => ['sakura' => 313, 'xp' => 575],
            'myst' => ['sakura' => 665, 'xp' => 1525],
        ];
        $exclusiveRewards = [
            'com' => ['coins' => 86],
            'rar' => ['sakura' => 94],
            'epic' => ['sakura' => 305],
            'leg' => ['sakura' => 626, 'xp' => 1150],
            'myst' => ['sakura' => 1230, 'xp' => 3050],
        ];
        $messages = [
            'com' => 'Вы получили 43 монеты за продажу обычного стикера.',
            'rar' => 'Вы получили 47 лепестков сакуры за продажу редкого стикера.',
            'epic' => 'Вы получили 150 лепестков сакуры за продажу эпического стикера!',
            'leg' => 'Вы получили 313 лепестков сакуры и 575 опыта за продажу легендарного стикера!',
            'myst' => 'Вы получили 665 лепестков сакуры и 1525 опыта за продажу мистического стикера!',
        ];
        $exclusiveMessages = [
            'com' => 'Вы получили 86 монет за продажу обычного эксклюзивного стикера.',
            'rar' => 'Вы получили 94 лепестка сакуры за продажу редкого эксклюзивного стикера.',
            'epic' => 'Вы получили 305 лепестков сакуры за продажу эпического эксклюзивного стикера!',
            'leg' => 'Вы получили 626 лепестков сакуры и 1150 опыта за продажу легендарного эксклюзивного стикера!',
            'myst' => 'Вы получили 1230 лепестков сакуры и 3050 опыта за продажу мистического эксклюзивного стикера!',
        ];

        $rewardSet = !empty($sticker['exclusive']) ? $exclusiveRewards : $rewards;
        $messageSet = !empty($sticker['exclusive']) ? $exclusiveMessages : $messages;
        $rarity = (string)$sticker['rarity'];
        if (!isset($rewardSet[$rarity])) {
            echo 'Неизвестная редкость стикера.';
            break;
        }

        foreach ($rewardSet[$rarity] as $field => $value) {
            update_inventory_field($conn, $userId, $field, (float)$value, $field === 'xp');
        }

        $deleteStmt = $conn->prepare('DELETE FROM stikers WHERE id_stikers = ? AND id_user = ?');
        $deleteStmt->bind_param('ii', $stickerId, $userId);
        $deleteStmt->execute();

        echo $messageSet[$rarity];
        break;

    case 'process_deposit':
        echo 'Клиентские депозиты отключены. Требуется серверный расчёт результата.';
        break;
        $field = (string)($_POST['deposit_type'] ?? '');
        $allowedFields = [
            'xp' => ['max_gain' => 15000, 'allow_negative' => false],
            'coins' => ['max_gain' => 100000, 'allow_negative' => false],
            'sakura' => ['max_gain' => 6500, 'allow_negative' => false],
            'kase' => ['max_gain' => 555, 'allow_negative' => true],
        ];

        if (!isset($allowedFields[$field])) {
            echo 'Недопустимый тип депозита.';
            break;
        }

        $result = (float)($_POST['result'] ?? 0);
        $result = min($result, (float)$allowedFields[$field]['max_gain']);
        if ($result < 0 && !$allowedFields[$field]['allow_negative']) {
            echo 'Недопустимое значение депозита.';
            break;
        }

        if ($field === 'kase' && $result < 0 && abs($result) > (float)$inventory['kase']) {
            $result = -(float)$inventory['kase'];
        }

        update_inventory_field($conn, $userId, $field, $result, $field === 'xp');
        echo 'OK';
        break;

    default:
        send_items_json(false, 'Неизвестная команда.', false);
}

mysqli_close($conn);
session_write_close();

function send_items_json(bool $success, string $message, bool $reload = false): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'reload' => $reload,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function load_user(mysqli $conn, string $login): ?array
{
    $stmt = $conn->prepare('SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('s', $login);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?: null;
}

function load_inventory(mysqli $conn, int $userId): ?array
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

function clamp_float(float $value, float $min, float $max): float
{
    if ($value < $min) {
        return $min;
    }

    if ($value > $max) {
        return $max;
    }

    return $value;
}

function update_inventory_field(mysqli $conn, int $userId, string $field, float $delta, bool $isFloat): string
{
    $allowedFields = ['xp', 'coins', 'sakura', 'kase'];
    if (!in_array($field, $allowedFields, true)) {
        return 'Недопустимый ресурс.';
    }

    $query = sprintf('UPDATE invent SET `%s` = `%s` + ? WHERE id_user = ?', $field, $field);
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        return 'Ошибка SQL: ' . mysqli_error($conn);
    }

    if ($isFloat) {
        $value = round($delta, 2);
        $stmt->bind_param('di', $value, $userId);
    } else {
        $value = (int)round($delta);
        $stmt->bind_param('ii', $value, $userId);
    }

    if (!$stmt->execute()) {
        return 'Ошибка SQL: ' . $stmt->error;
    }

    return 'Успешно получено!';
}

function add_sticker(mysqli $conn, int $userId, ?string $forcedRarity = null): string
{
    $stickerData = [
        'com' => [
            'name' => 'обычный',
            'items' => ['avrora_default', 'darkus_default', 'minami_default', 'misuki_default', 'imran_default', 'karyl', 'jahy', 'mon3tr', 'homura', 'kinako'],
        ],
        'rar' => [
            'name' => 'редкий',
            'items' => ['avrora_halloween', 'avrora_reburn', 'misuki', 'old_avrora', 'school_avrora', 'school_darkus', 'imran_school', 'darkolefox', 'amagi'],
        ],
        'epic' => [
            'name' => 'эпический',
            'items' => ['misuki', 'darkolefox_and_avrora', 'darkusfoxis', 'kaltsit', 'shiro', 'raphtalia', 'darkolefox', 'izuna', 'sameko_saba'],
        ],
        'leg' => [
            'name' => 'легендарный',
            'items' => ['ahri', 'ahri2', 'karyl', 'hoshino', 'miku', 'blet'],
        ],
        'myst' => [
            'name' => 'мистический',
            'items' => ['avrora', 'karyl', 'frostnova', 'jahy', 'texas', 'mon3tr'],
        ],
    ];

    $rarity = $forcedRarity;
    if ($rarity === null) {
        $rand = mt_rand(0, 100);
        if ($rand <= 75) {
            $rarity = 'com';
        } elseif ($rand <= 88) {
            $rarity = 'rar';
        } elseif ($rand <= 97) {
            $rarity = 'epic';
        } elseif ($rand <= 99) {
            $rarity = 'leg';
        } else {
            $rarity = 'myst';
        }
    }

    if (!isset($stickerData[$rarity])) {
        return 'Ошибка добавления стикера.';
    }

    $data = $stickerData[$rarity];
    $items = $data['items'];
    $index = array_rand($items);
    $stickerName = $items[$index];
    $description = 'Коллекционный стикер редкости "' . $data['name'] . '".';

    $stmt = $conn->prepare('INSERT INTO stikers (id_user, stikers, description, rarity) VALUES (?, ?, ?, ?)');
    if ($stmt === false) {
        return 'Ошибка подготовки стикера: ' . mysqli_error($conn);
    }

    $stmt->bind_param('isss', $userId, $stickerName, $description, $rarity);
    if (!$stmt->execute()) {
        return 'Ошибка добавления стикера: ' . $stmt->error;
    }

    return 'Получен ' . $data['name'] . ' стикер ' . $stickerName . '!';
}
