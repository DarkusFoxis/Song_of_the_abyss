<?php
date_default_timezone_set('Europe/Moscow');
if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
    header('Location: index');
    exit;
}
session_start();
require_once __DIR__ . '/./template/auth.php';
auth_sync_session_from_token();
$authUser = auth_get_current_user();
if ($authUser === null) {
    echo "Вы должны быть авторизованы.";
    exit;
}

require_once './template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database) or die('Ошибка соединения: ' . mysqli_connect_error());

$login = $authUser['login'];
$user = mysqli_fetch_assoc($conn->query("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'"));
if (!$user) die("Пользователь не найден");

if ($user['lvl'] == 0) die("Вы заблокированы на проекте.");
if ($user['lvl'] == 1) die("Вы не подтверждены на сайте.");

$userId = $user['id'];
$invent = mysqli_fetch_assoc($conn->query("SELECT * FROM invent WHERE id_user = '$userId'"));
if (!$invent && $_POST['action'] !== 'create_invent') die("У вас отсутствует инвентарь!");

$bonus = $user['lvl'] >= 3 ? 1.75 : 1;
$bonus_time = $user['lvl'] >= 3 ? 3 : 6;
$add_chance = $user['lvl'] >= 3 ? 5 : -1;

$donate = $user['donate'] ?? 0;
$bonus_add = [
    'xp' => ceil($donate / 25),
    'coin' => ceil($donate / 250),
    'petal' => ceil($donate / 750),
    'kase' => ceil($donate / 3500)
];

function update_resource($conn, $userId, $field, $value, $is_float = false) {
    $value = $is_float ? round($value, 2) : (int)$value;
    if (!mysqli_query($conn, "UPDATE invent SET $field = $field + $value WHERE id_user = '$userId'")) {
        die('Ошибка SQL: ' . mysqli_error($conn));
    }
    return "Успешно получено!";
}

function add_stiker($conn, $userId, $setRar = null) {
    $stickerData = [
        'com' => [
            'items' => ["avrora_default","darkus_default","minami_default","misuki_default","imran_default","karyl","jahy","mon3tr","homura","kinako"],
            'desc' => ["Аврора мечтала стать вет врачом.","Даркус может быть жестоким, но это не всегда, а вот извращению место найдёт везде.", "Минами мечтала стать воспитательницей. Она любит детей.", "Мизуки слышит, но не умеет говорить. Это было и до перерождения.", "Имран изначально думал, что сила, которую он получил, можно использовать для игр с сестрой.", "Иногда Кару кажется, что над ней постоянно издеваются...", "Джахи снова была в ярости, когда её вновь бросили в новый мир.", "Эта малютка нашла кошку и теперь они обе гуляют по миру.", "Как коты видят админа, когда он работает над сайтом:", "Кинако хочет пробраться в ваш холодильник, и украсть всю вашу рыбу!"],
            'name' => 'обычный'
        ],
        'rar' => [
            'items' => ["avrora_halloween","avrora_reburn","misuki","old_avrora","school_avrora","school_darkus","imran_school","darkolefox","amagi"],
            'desc' => ["Аврора больше никогда не вырастит. Она и сама этого не желает.", "Аврора думала, что DarkOleFox сможет вернуть её родителей к жизни.", "Мизуки является помощницей Инари. Ранее она была обычным ребёнком.", "Аврора никогда не хотела войн. Она не любит крови.", "Даже став помощницей DarkOleFoxa, Аврора захотела в школу. Ей нравится учиться.", "Даркус долгое время, даже став демоном, продолжал ходить в школу.", "Имран так и не закончил школу, даже будучи в 11 классе.", "Правитель бездны всегда следит за порядком в своём мире.", "Эта лисичка будет следовать за вами попятам. Не делайте вид, что не замечаете её.~"],
            'name' => 'редкий'
        ],
        'epic' => [
            'items' => ["misuki","darkolefox_and_avrora","darkusfoxis","kaltsit","shiro","raphtalia","darkolefox","izuna","sameko_saba"],
            'desc' => ["Мизуки любит такояки. Это обычное японское блюдо, которое для неё готовила её бабушка.","Когда Аврора стала помощницей DarkOleFoxa, в бездне поначалу считали её дочерью.","DarkusFoxis - создатель проекта SotA.", ($authUser['username'] ?? '') . ", вам необходимо срочно поставить укол. Больно не будет.", "Широ очень любит купаться, но не любит убирать пену после себя.", "Рафталия сама попала в новый мир, хоть и никогда не планировала.", "Дарколефокс давно живёт в бездне. Он ей и дал жизнь, чуть не погибнув.", "Новый год к нам мчится, скоро всё случится...", "Встречайте новенькую рыбкокошечку!"],
            'name' => 'эпический'
        ],
        'leg' => [
            'items' => ["ahri","ahri2","karyl","hoshino","miku","blet"],
            'desc' => ["Ари изначально не понимала, зачем её забрали из её мира (Так захотел Оригинал).","Ари не думала, что сможет стать сильнее, и приручить драконов стихий.","Кяру не могла понять, как её магия работала в этом мире, но быстро освоилась.","Даркус был рад побывать на её концерте. Ему нравится её песни, и не только песни, если вы понимаете.","Даркус часто думал о том, чтобы оживить Хатцуне Мику, а потом встретил Неко Саму, и как-то забыл.", "Пледа нет... Всмысле нет... Так, блЭтЪ?"],
            'name' => 'легендарный'
        ],
        'myst' => [
            'items' => ["avrora","karyl","frostnova","jahy","texas","mon3tr"],
            'desc' => ["Аврора любит свою новую жизнь. Она ничего не хочет менять в ней. Она счастлива.","Кяру считала, что в этом мире нет жуков. Попытавшись выйти из дома 12 мая, она пообещала больше никогда не выходить на улицу.", ($authUser['username'] ?? '') . " не ожидал увидеть ФростНову, ведь она умерла прямо у него на руках... Кажется, Однорогий хранит много секретов...", "Джахи готова к новым открытиям в этой вселенной! А ты?", "Техас уже есть арбуз. А ты купил его?", "Mon3tr loves you, and sends you a sweet heart♡."],
            'name' => 'мистический'
        ]
    ];

    $rar = $setRar;
    if (!$setRar) {
        $rand = mt_rand(0, 100);
        if ($rand <= 75) {
            $rar = 'com';
        } elseif ($rand <= 88) {
            $rar = 'rar';
        } elseif ($rand <= 97) {
            $rar = 'epic';
        } elseif ($rand <= 99) {
            $rar = 'leg';
        } else {
            $rar = 'myst';
        }
    }

    $data = $stickerData[$rar];
    $idx = array_rand($data['items']);
    $stiker = $data['items'][$idx];
    $desc = $data['desc'][$idx];

    $sql = "INSERT INTO stikers (id_user, stikers, description, rarity) VALUES ('$userId','$stiker','$desc','$rar')";
    return mysqli_query($conn, $sql) 
        ? "Получен {$data['name']} стикер $stiker!" 
        : 'Ошибка добавления стикера: ' . mysqli_error($conn);
}

$action = $_POST['action'] ?? 'unknown';

switch ($action) {
    case 'create_invent':
        if ($invent) {
            echo json_encode(['success' => false, 'message' => 'Инвентарь уже существует!']);
            exit;
        }
        $sql = "INSERT INTO invent (id_user, lvl, xp, xp_max, coins, bonus_data, gems, kase, sakura) VALUES ('$userId', 0, 0, 1000, 10, NOW(), 1, 5, 10)";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Инвентарь создан!', 'reload' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка: '.mysqli_error($conn)]);
        }
        break;

    case 'add_xp':
        echo update_resource($conn, $userId, 'xp', $_POST['xp'] * $bonus, true);
        break;

    case 'add_coins':
        echo update_resource($conn, $userId, 'coins', $_POST['coin'] * $bonus);
        break;

    case 'add_sakura':
        $add = $_POST['sakura'] * $bonus * (1 + $invent['lvl']/10);
        echo update_resource($conn, $userId, 'sakura', $add);
        break;

    case 'get_bonus':
        $nextBonus = strtotime($invent['bonus_data'] . " + $bonus_time hours");
        if (time() < $nextBonus) {
            $diff = $nextBonus - time();
            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);
            $seconds = $diff % 60;

            $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
            $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
            $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
            die("Следующий бонус через $hours:$minutes:$seconds");
        }
        $reward_type = mt_rand(1, 5);
        $rewards = [];
        $text = "В сегодняшнем бонусе вы получили: ";

        switch ($reward_type) {
            case 1:
                $xp_val = floor(floor(mt_rand(19, $invent['xp_max']/8) * (1+$invent['lvl']/10)/7 * $bonus) + $bonus_add['xp']);
                $coin_val = round(13 * (9 + $invent['lvl']/10) * $bonus);
                update_resource($conn, $userId, 'xp', $xp_val, true);
                update_resource($conn, $userId, 'coins', $coin_val);
                $text .= "$xp_val опыта и $coin_val монет!";
                break;
            case 2:
                $coin_val = floor(mt_rand(1,60) * (1+$invent['lvl']/10) * $bonus + $bonus_add['coin']);
                $xp_val = round(15 * (9 + $invent['lvl']/10) * $bonus);
                update_resource($conn, $userId, 'coins', $coin_val);
                update_resource($conn, $userId, 'xp', $xp_val, true);
                $text .= "$coin_val монет и $xp_val опыта!";
                break;
            case 3:
                $sakura_val = floor(mt_rand(1,35) * (1+$invent['lvl']/10) * $bonus + $bonus_add['petal']);
                $xp_val = round(21 * (9 + $invent['lvl']/10) * $bonus);
                update_resource($conn, $userId, 'sakura', $sakura_val);
                update_resource($conn, $userId, 'xp', $xp_val, true);
                $text .= "$sakura_val лепестков сакуры и $xp_val опыта!";
                break;
            case 4:
                $sakura_val = floor(mt_rand(1,55) * (1+$invent['lvl']/10) * $bonus + $bonus_add['petal']);
                $coin_val = round(17 * (9 + $invent['lvl']/10) * $bonus);
                update_resource($conn, $userId, 'sakura', $sakura_val);
                update_resource($conn, $userId, 'coins', $coin_val, true);
                $text .= "$sakura_val лепестков сакуры и $coin_val монет!";
                break;
            case 5:
                $kase_val = floor(mt_rand(1,4) * (1+$invent['lvl']/15) * $bonus) + $bonus_add['kase'];
                $sakura_val = round(11 * (8 + $invent['lvl']/10) * $bonus);
                update_resource($conn, $userId, 'kase', $kase_val);
                update_resource($conn, $userId, 'sakura', $sakura_val, true);
                $text .= "$kase_val кейсов и $sakura_val лепестков!";
                break;
        }
        $sticker_chance = mt_rand(0,10);
        if (in_array($sticker_chance, [1,3,8]) || $sticker_chance === $add_chance) {
            $text .= " " . add_stiker($conn, $userId);
        }

        mysqli_query($conn, "UPDATE invent SET bonus_data = NOW() WHERE id_user = '$userId'");
        echo $text;
        break;

    case 'lvl_up':
        if ($invent['xp'] < $invent['xp_max']) {
            die("Недостаточно опыта!");
        }
        
        $new_lvl = $invent['lvl'] + 1;
        $cost = 350 * $new_lvl * (($new_lvl + 9)/10);
        $cost_sakura = ($new_lvl % 10 === 0) ? 10000 * floor($new_lvl / 10) : 0;

        if ($invent['coins'] < $cost || ($cost_sakura && $invent['sakura'] < $cost_sakura)) {
            die("Недостаточно ресурсов!");
        }

        $updates = [
            'xp' => $invent['xp'] - $invent['xp_max'],
            'xp_max' => $invent['xp_max'] + 1000 * (1 + floor($invent['lvl']/10)),
            'lvl' => $new_lvl,
            'coins' => $invent['coins'] - $cost,
            'bonus_data' => date('Y-m-d H:i:s', strtotime($invent['bonus_data'] . " -$bonus_time hours"))
        ];
        if ($cost_gem) {
            $updates['sakura'] = $invent['sakura'] - $cost_sakura;
        }

        $set_clause = [];
        foreach ($updates as $field => $value) {
            $set_clause[] = "`$field` = '$value'";
        }
        $sql = "UPDATE invent SET " . implode(', ', $set_clause) . " WHERE id_user = '$userId'";
        
        if (!mysqli_query($conn, $sql)) {
            die('Ошибка при обновлении: ' . mysqli_error($conn));
        }

        echo "Уровень повышен! " . add_stiker($conn, $userId);
        break;

    case 'stiker_add':
        $costs = [
            'com' => 100, 
            'rar' => 225, 
            'epic' => 500, 
            'leg' => 1010, 
            'myst' => 2500, 
            'rnd' => 200
        ];
        
        $rar = $_POST['rarity'] ?? 'rnd';
        if (!isset($costs[$rar])) {
            die("Неверная редкость");
        }
        $cost = $costs[$rar];
        if ($invent['sakura'] < $cost) {
            die("Недостаточно лепестков");
        }
        
        update_resource($conn, $userId, 'sakura', -$cost);
        echo add_stiker($conn, $userId, $rar === 'rnd' ? null : $rar);
        break;

    case 'stiker_delete':
        $stikerId = $_POST['stikerId'] ?? die("Ошибка стикера");
        $result = $conn->query("SELECT * FROM stikers WHERE id_stikers = '$stikerId'");
        if (!$result || $result->num_rows === 0) {
            die("Стикер не найден");
        }
        $stiker = $result->fetch_assoc();

        $rewards = [
            'com' => ['coins' => 43],
            'rar' => ['sakura' => 47],
            'epic' => ['sakura' => 150],
            'leg' => ['sakura' => 313, 'xp' => 575],
            'myst' => ['sakura' => 665, 'xp' => 1525]
        ];
        $rewardsEx = [
            'com' => ['coins' => 86],
            'rar' => ['sakura' => 94],
            'epic' => ['sakura' => 305],
            'leg' => ['sakura' => 626, 'xp' => 1150],
            'myst' => ['sakura' => 1230, 'xp' => 3050]
        ];
        $message = [
            'com' => 'Вы получили 43 монеты за продажу обычного стикера.',
            'rar' => 'Вы получили 47 лепестков сакуры за продажу редкого стикера.',
            'epic' => 'Вы получили 150 лепестков сакуры за продажу эпического стикера!',
            'leg' => 'Вы получили 313 лепестков сакуры и 575 опыта за продажу легендарного стикера!',
            'myst' => 'Вы получили 665 лепестков сакуры, и 1525 опыта за продажу мистического стикера!',
        ];
        $messageEx = [
            'com' => 'Вы получили 86 монеты за продажу обычного, эксклюзивного стикера.',
            'rar' => 'Вы получили 94 лепестков сакуры за продажу редкого, эксклюзивного стикера.',
            'epic' => 'Вы получили 305 лепестков сакуры за продажу эпического, эксклюзивного стикера!',
            'leg' => 'Вы получили 626 лепестков сакуры и 1150 опыта за продажу легендарного, эксклюзивного стикера!',
            'myst' => 'Вы получили 1230 лепестков сакуры, и 3050 опыта за продажу мистического, эксклюзивного стикера!',
        ];
        $rarity = $stiker['rarity'];
        if (!isset($rewards[$rarity])) {
            die("Неизвестная редкость стикера");
        }
        $exclusive = $stiker['exclusive'];
        if ($exclusive) {
            foreach ($rewardsEx[$rarity] as $res => $val) {
                update_resource($conn, $userId, $res, $val , $res === 'xp');
            }
        } else {
            foreach ($rewards[$rarity] as $res => $val) {
                update_resource($conn, $userId, $res, $val, $res === 'xp');
            }
        }

        $conn->query("DELETE FROM stikers WHERE id_stikers = '$stikerId'");
        if ($exclusive) {
            echo $messageEx[$rarity];
        } else {
            echo $message[$rarity];
        }
        break;
    case 'process_deposit':
        $type = $_POST['deposit_type'];
        $result = $_POST['result'];
        if ($type == "kase") {
            if ((-1 * $result) > $invent['kase']) {
                $result = (-1 * $invent['kase']);
            }
        }
        if ($type == "coins" && $result > 100000) {
            $result = 100000;
        } else if ($type == "sakura" && $result > 6500) {
            $result = 6500;
        } else if ($type == "kase" && $result > 555) {
            $result = 555;
        }
        if (!mysqli_query($conn, "UPDATE invent SET $type = $type + $result WHERE id_user = '$userId'")) {
            die('Ошибка SQL: ' . mysqli_error($conn));
        }
        break;
    default:
        echo 'Неизвестное действие';
}
mysqli_close($conn);
session_write_close();



