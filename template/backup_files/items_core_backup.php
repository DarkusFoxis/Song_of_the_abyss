<?php
date_default_timezone_set('Europe/Moscow');
$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
if ($isAjaxRequest) {
    session_start();
    if (!isset($_SESSION['user'])) {
        echo "Вы должны быть авторизованы.";
        exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] : null;

    if ($action) {
        require_once './template/conn.php';

        $conn = mysqli_connect($host, $log, $password_sql, $database);
        if (!$conn) {
            echo "Ошибка соединения: " . mysqli_connect_error();
            exit;
        }

        $login = $_SESSION['user'];
        $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
        $result = $conn->query($user_query);
        $user = $result -> fetch_assoc();
        $userId = $user['id'];
        $query = "SELECT * FROM invent WHERE id_user = '$userId'";
        $result = mysqli_query($conn, $query);
        if ($user['lvl'] == 0) {
            echo "Вы заблокированы на проекте, поэтому возможности ограничены.";
            exit;
        } else if ($user['lvl'] == 1) {
            echo "Вы не подтверждены на сайте.";
            exit;
        }
        $username = $_SESSION['username'];

        $bonus = 1;
        $time = 8;
        $add_chance = -1;
        if ($user['lvl'] >= 3) {
            $bonus = 2;
            $time = 4;
            $add_chance = 5;
        }
        if ($user['donate'] > 0) {
            $xp_bonus_add = $user['donate'];
            $coin_bonus_add = ceil($user['donate'] / 100);
            $petal_bonus_add = ceil($user['donate'] / 500);
            $gem_bonus_add = ceil($user['donate'] / 1750);
            $kase_bonus_add = ceil($user['donate'] / 2000);
        }

        function add_xp($add_xp, $xp, $conn, $userId)
        {
            $new_xp = round($add_xp + $xp, 2);
            $sql = "UPDATE invent SET xp = '$new_xp' WHERE id_user = '$userId'";
            if (!mysqli_query($conn, $sql)) {
                return 'В SQL-запросе произошла ошибка. Значение add_xp: ' . $new_xp . '. Тип: ' . gettype($new_xp) . ' Ошибка SQL: ' . mysqli_error($conn);
            } else {
                return "Успешно получен опыт!";
            }
        }

        function add_coins($add_coins, $coins, $conn, $userId)
        {
            $new_coins = round($add_coins + $coins);
            $sql = "UPDATE invent SET coins = '$new_coins' WHERE id_user = '$userId'";
            if (!mysqli_query($conn, $sql)) {
                return 'В SQL-запросе произошла ошибка. Значение add_coins: ' . $new_coins . '. Тип: ' . gettype($new_coins) . ' Ошибка SQL: ' . mysqli_error($conn);
            } else {
                return "Успешно получены монеты!";
            }
        }

        function add_gems($add_gems, $gems, $conn, $userId, $lvl)
        {
            $new_gems = round($gems + $add_gems);
            $sql = "UPDATE invent SET gems = '$new_gems' WHERE id_user = '$userId'";
            if (!mysqli_query($conn, $sql)) {
                return 'В SQL-запросе произошла ошибка. Значение new_gems: ' . $new_gems . '. Тип: ' . gettype($new_gems) . ' Ошибка SQL: ' . mysqli_error($conn);
            } else {
                return "Успешно получены гемы!";
            }
        }

        function add_sakura($add_sakura, $sakura, $conn, $userId, $lvl)
        {
            $new_sakura = round($sakura + ($add_sakura * (1 + ($lvl /10))));
            $sql = "UPDATE invent SET sakura = '$new_sakura' WHERE id_user = '$userId'";
            if (!mysqli_query($conn, $sql)) {
                return 'В SQL-запросе произошла ошибка. Значение new_sakura: ' . $new_gems . '. Тип: ' . gettype($new_sakura) . ' Ошибка SQL: ' . mysqli_error($conn);
            } else {
                return "Успешно получены лепестки сакуры!";
            }
        }

        function add_stikers ($conn, $userId, $setRar)
        {
            if ($setRar != NULL) {
                $stiker_rar = $setRar;
            } else {
                $stiker_rand = mt_rand(0, 101);
                $stiker_rar = NULL;
            }
            $stiker_list;
            $stiker_rarity;
            $stiker_full_rarity;

            if (($stiker_rand <= 77 && $stiker_rar == NULL) || $stiker_rar == "com") {
                $stiker_list = ["avrora_default","darkus_default","minami_default","misuki_default","imran_default", "karyl", "kitsune"];
                $description_list = ["Аврора мечтала стать вет врачом.","Даркус может быть жестоким, но это не всегда, а вот извращению место найдёт везде.","Минами мечтала стать воспитательницей. Она любит детей.","Мизуки слышит, но не умеет говорить. Это было и до перерождения.","Имран изначально думал, что сила, которую он получил, можно использовать для игр с сестрой.", "Иногда Кару кажется, что над ней постоянно издеваются...","Путешевствуя по мирам, можно найти разных сущевств. Даже таких лисичек."];
                $stiker_rarity = "com";
                $stiker_full_rarity = "обычный";
            } else if (($stiker_rand >= 78 && $stiker_rand <= 90 && $stiker_rar == NULL) || $stiker_rar == "rar") {
                $stiker_list = ["avrora_halloween","avrora_reburn","misuki","old_avrora","school_avrora","school_darkus","imran_school", "kitsune"];
                $description_list = ["Аврора больше никогда не вырастит. Она и сама этого не желает.", "Аврора думала, что DarkOleFox сможет вернуть её родителей к жизни.","Мизуки является помощницей Инари. Ранее она была обычным ребёнком.","Аврора никогда не хотела войн. Она не любит крови.","Даже став помощницей DarkOleFoxa, Аврора захотела в школу. Ей нравится учиться.","Даркус долгое время, даже став демоном, продолжал ходить в школу.","Имран так и не закончил школу, даже будучи в 11 классе.", "Мизуки иногда рисует свою госпожу в разных обличьях, и этот вариант не исключение."];
                $stiker_rarity = "rar";
                $stiker_full_rarity = "редкий";
            } else if (($stiker_rand >= 91 && $stiker_rand <= 96 && $stiker_rar == NULL) || $stiker_rar == "epic") {
                $stiker_list = ["misuki","darkolefox_and_avrora","darkusfoxis","kaltsit", "senko", "shiro", "raphtalia"];
                $description_list = ["Мизуки любит такояки. Это обычное японское блюдо, которое для неё готовила её бабушка.","Когда Аврора стала помощницей DarkOleFoxa, в бездне поначалу считали её дочерью.","DarkusFoxis - создатель проекта SotA.", $_SESSION['username'] . ", вам необходимо срочно поставить укол. Больно не будет.", "Сенко знала о других странах и городах, но никогда не думала, что сможет посетить их все.", "Широ очень любит купаться, но не любит убирать пену после себя.", "Рафталия сама попала в новый мир, хоть и никогда не планировала."];
                $stiker_rarity = "epic";
                $stiker_full_rarity = "эпический";
            } else if (($stiker_rand >= 97 && $stiker_rand <= 99 && $stiker_rar == NULL) || $stiker_rar == "leg"){
                $stiker_list = ["ahri","ahri2","neko","karyl","hoshino","neko2","miku", "pigeot"];
                $description_list = ["Ари изначально не понимала, зачем её забрали из её мира (Так захотел Оригинал).","Ари не думала, что сможет стать сильнее, и приручить драконов стихий.","Просто неко.","Кяру не могла понять, как её магия работала в этом мире, но быстро освоилась.","Даркус был рад побывать на её концерте. Ему нравится её песни, и не только песни, если вы понимаете.","Просто неко 2, или кем был-бы Даркус в киберпространстве.","Даркус часто думал о том, чтобы оживить Хатцуне Мику, а потом встретил Неко Саму, и как-то забыл.","В Китае свои фламинго..."];
                $stiker_rarity = "leg";
                $stiker_full_rarity = "легендарный";
            } else {
                $stiker_list = ["avrora","karyl","frostnova", "rickroll", "senko"];
                $description_list = ["Аврора любит свою новую жизнь. Она ничего не хочет менять в ней. Она счастлива.","Кяру считала, что в этом мире нет жуков. Попытавшись выйти из дома 12 мая, она пообещала больше никогда не выходить на улицу.", $_SESSION['username'] . " не ожидал увидеть ФростНову, ведь она умерла прямо у него на руках... Кажется, Однорогий хранит много секретов...", "You have been rickrolled, haha :D", "Сенко очень любит котят и других животных."];
                $stiker_rarity = "myst";
                $stiker_full_rarity = "мистический";
            }
            $target = array_rand($stiker_list);
            $target_stiker = $stiker_list[$target];
            $target_description = $description_list[$target];
            $sql = "INSERT INTO `stikers`(`id_stikers`, `id_user`, `stikers`, `description`, `rarity`) VALUES (NULL,'$userId','$target_stiker','$target_description','$stiker_rarity')";
            if (!mysqli_query($conn, $sql)) {
                return 'В SQL-запросе произошла ошибка. Значение стикера: ' . $target_stiker . '. Редкость: ' . $stiker_rarity . ' Ошибка SQL: ' . mysqli_error($conn);
            } else {
                return " Вы получили " . $stiker_full_rarity . " стикер " . $target_stiker . "!";
            }
        }
        switch ($action) {
            case 'create_invent':
                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO `invent`(`id_user`, `id_title`, `lvl`, `xp`, `xp_max`, `coins`, `bonus_data`, `gems`, `kase`, `sakura`) VALUES ('$userId', NULL, 0, 0, 1000, 10, NOW(), 1, 5, 10)";
                    if (!mysqli_query($conn, $insertQuery)) {
                        echo json_encode(['success' => false, 'message' => 'В SQL-запросе произошла ошибка: ' . mysqli_error($conn)]);
                        exit;
                    }
                    $response = "Отлично! Ваш инвентарь готов. Через несколько секунд страница перезагрузится, и будут применены новые данные.";
                    $reload = true;
                    $success = true;
                } else{
                    $response = 'Ошибка выполнения: У вас уже есть инвентарь.';
                    $reload = false;
                    $success = false;
                }
                echo json_encode(['success' => $success, 'message' => $response, 'reload' => $reload]);
                break;
            case 'add_xp':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    echo add_xp($_POST['xp'] * $bonus, $inv_data['xp'], $conn, $userId);
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            case 'add_coins':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    echo add_coins($_POST['coin'] * $bonus, $inv_data['coins'], $conn, $userId);
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            case 'add_gems':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    echo add_gems($_POST['gems'] * $bonus, $inv_data['gems'], $conn, $userId, $inv_data['lvl']);
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            case 'add_sakura':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    echo add_sakura($_POST['sakura'] * $bonus, $inv_data['sakura'], $conn, $userId, $inv_data['lvl']);
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            case 'get_bonus':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    $currentDate = time();
                    $bonusDateTime = new DateTime($inv_data['bonus_data']);
                    $bonusDateTime->modify("+ $time hours");
                    $nextBonusTime = $bonusDateTime->getTimestamp();
                    if ($currentDate >= $nextBonusTime) {
                        $random_number = mt_rand(1, 6);
                        $stiker_random = mt_rand(0, 10);
                        if ($random_number == 1 || $random_number == 2) {
                            $random_xp = (floor(mt_rand(3, ($inv_data['xp_max'] / 8)) * (1 + ($inv_data['lvl'] /10)) / 6) * $bonus) + $xp_bonus_add;
                            $bonus_coins = round(9 * (10 + ($inv_data['lvl'] /10))) * $bonus;
                            $sql = "UPDATE invent SET xp = xp + '$random_xp', bonus_data = NOW(), coins = coins + '$bonus_coins' WHERE id_user = '$userId'";
                            if (!mysqli_query($conn, $sql)) {
                                echo 'В SQL-запросе произошла ошибка. Значение add_xp: ' . $new_xp . '. Тип: ' . gettype($new_xp) . ' Ошибка SQL: ' . mysqli_error($conn);
                                exit;
                            }
                            $bonus_text = "В сегодняшнем бонусе вы получили $random_xp опыта, и $bonus_coins монет!";
                        } else if ($random_number == 5) {
                            $random_coins = (round(mt_rand(1, 80) * (1 + ($inv_data['lvl'] /10))) * $bonus) + $coin_bonus_add;
                            $bonus_xp = (9 * (10 + ($inv_data['lvl'] /10))) * $bonus;
                            $sql = "UPDATE invent SET xp = xp + '$bonus_xp', bonus_data = NOW(), coins = coins + '$random_coins' WHERE id_user = '$userId'";
                            if (!mysqli_query($conn, $sql)) {
                                echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                                exit;
                            }
                            $bonus_text = "В сегодняшнем бонусе вы получили $random_coins монет, и $bonus_xp опыта!";
                        } else if ($random_number == 3){
                            $random_gems = (round(mt_rand(1, 12)) * $bonus) + $gem_bonus_add;
                            $bonus_xp = (19 * (10 + ($inv_data['lvl'] /10))) * $bonus;
                            $sql = "UPDATE invent SET xp = xp + '$bonus_xp', bonus_data = NOW(), gems = gems + '$random_gems' WHERE id_user = '$userId'";
                            if (!mysqli_query($conn, $sql)) {
                                echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                                exit;
                            }
                            $bonus_text = "В сегодняшнем бонусе вы получили $random_gems кристаллов, и $bonus_xp опыта!";
                        } else if ($random_number == 6) {
                            $random_skr = (round(mt_rand(1, 110) * (1 + ($inv_data['lvl'] /10))) * $bonus) + $petal_bonus_add;
                            $bonus_xp = (13 * (10 + ($inv_data['lvl'] /10))) * $bonus;
                            $sql = "UPDATE invent SET xp = xp + '$bonus_xp', bonus_data = NOW(), sakura = sakura + '$random_skr' WHERE id_user = '$userId'";
                            if (!mysqli_query($conn, $sql)) {
                                echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                                exit;
                            }
                            $bonus_text = "В сегодняшнем бонусе вы получили $random_skr лепестков сакуры, и $bonus_xp опыта!";
                        } else {
                            $random_kase = (round(mt_rand(1, 5) * (1 + ($inv_data['lvl'] / 10))) * $bonus) + $kase_bonus_add;
                            $bonus_xp = (33 * (10 + ($inv_data['lvl'] /10))) * $bonus;
                            $sql = "UPDATE invent SET xp = xp +'$new_xp', bonus_data = NOW(), kase = kase +'$random_kase' WHERE id_user = '$userId'";
                            if (!mysqli_query($conn, $sql)) {
                                echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                                exit;
                            }
                            $bonus_text = "В сегодняшнем бонусе вы получили $random_kase кейсов, и $bonus_xp опыта!";
                        }
                        if ($stiker_random == 3 || $stiker_random == 8 || $stiker_random == 1 || $stiker_random == $add_chance) {
                            $bonus_text .= add_stikers($conn, $userId, NULL);
                            echo $bonus_text;
                        } else {
                            echo $bonus_text;
                        }
                    } else {
                        echo "Ваш следующий бонус будет доступен через " . round(($nextBonusTime - $currentDate) / 3600, 1) . " часа(ов).";
                        exit;
                    }
                } else {
                    echo "У вас отсутствует инвентарь!";
                    exit;
                }
                break;
            case 'lvl_up':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    if ($inv_data['xp'] >= $inv_data['xp_max']) {
                        if ((($inv_data['lvl'] + 1) % 10) != 0) {
                            $coin_up = (390*($inv_data['lvl']+1)) * (($inv_data['lvl']+10)/10);
                            if ($coin_up <= $inv_data['coins']) {
                                $new_xp = $inv_data['xp'] - $inv_data['xp_max'];
                                $new_xp_max = $inv_data['xp_max'] + (1000 + (1000 * floor($inv_data['lvl']/10)));
                                $new_coins = $inv_data['coins'] - $coin_up;
                                $new_lvl = $inv_data['lvl'] + 1;
                                $bonusTime = new DateTime($inv_data['bonus_data']);
                                $bonusTime->modify("- $time hours");
                                $newBonusTime = $bonusTime->getTimestamp();
                                $sql = "UPDATE invent SET xp = '$new_xp', xp_max = '$new_xp_max', lvl = '$new_lvl', coins = '$new_coins', bonus_data = '$newBonusTime' WHERE id_user = '$userId'";
                                if (!mysqli_query($conn, $sql)) {
                                    echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                                    exit;
                                }
                                $lvl_up = 'Уровень успешно повышен! Перезагрузите страницу, чтобы увидеть новые данные!';
                                $lvl_up .= add_stikers($conn, $userId, NULL);
                                echo $lvl_up;
                                exit;
                            } else {
                                echo 'Невозможно повысить уровень: У вас недостаточно монет.';
                                exit;
                            }
                        } else {
                            $coin_up = 390 * ($inv_data['lvl']+1) * (($inv_data['lvl']+10)/10);
                            $gem_up = 27 * floor(($inv_data['lvl'] + 1) / 10);
                            if ($coin_up <= $inv_data['coins'] and $gem_up <= $inv_data['gems']) {
                                $new_xp = $inv_data['xp'] - $inv_data['xp_max'];
                                $new_xp_max = $inv_data['xp_max'] + (1000 + (1000 * floor($inv_data['lvl'] / 10)));
                                $new_coins = $inv_data['coins'] - $coin_up;
                                $new_gems = $inv_data['gems'] - $gem_up;
                                $new_lvl = $inv_data['lvl'] + 1;
                                $bonusTime = new DateTime($inv_data['bonus_data']);
                                $bonusTime->modify("- $time hours");
                                $newBonusTime = $bonusTime->getTimestamp();
                                $sql = "UPDATE invent SET xp = '$new_xp', xp_max = '$new_xp_max', lvl = '$new_lvl', coins = '$new_coins', gems = '$new_gems', bonus_data = '$newBonusTime' WHERE id_user = '$userId'";
                                if (!mysqli_query($conn, $sql)) {
                                    echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                                    exit;
                                }
                                $lvl_up = 'Уровень успешно повышен! Перезагрузите страницу, чтобы увидеть новые данные!';
                                $lvl_up .= add_stikers($conn, $userId, NULL);
                                echo $lvl_up;
                                exit;
                            } else {
                                echo 'Невозможно повысить уровень: У вас недостаточно монет или кристаллов.';
                                exit;
                            }
                        }
                    } else {
                        echo 'У вас недостаточно опыта для повышения уровня. Пожалуйста, наберите ещё.';
                        exit;
                    }
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            case 'group_add':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    //$xp = $_POST['xp'] * $bonus;
                    //$coins = $_POST['coins'] * $bonus;
                    //$gems = $_POST['gems'];
                    //$sql = "UPDATE invent SET xp = xp + '$xp', coins = coins + '$coins', gems = gems + '$gems' WHERE id_user = '$userId'";
                    //if (!mysqli_query($conn, $sql)) {
                    //    echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                    //    exit;
                    //}
                    echo 'Вывод из кликера временно приостановлен.';
                    exit;
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            case 'stiker_add':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    $cost;
                    $rar = $_POST["rarity"];
                    switch ($rar) {
                        case "com":
                            $cost = 105;
                            break;
                        case "rar":
                            $cost = 255;
                            break;
                        case "epic":
                            $cost = 515;
                            break;
                        case "leg":
                            $cost = 1050;
                            break;
                        case "myst":
                            $cost = 2450;
                            break;
                        case "rnd":
                            $cost = 375;
                            $rar = NULL;
                            break;
                        default:
                            $cost = NULL;
                            break;
                    }
                    if ($cost != NULL) {
                        if ($inv_data["sakura"] >= $cost) {
                            $sql = "UPDATE `invent` SET `sakura` = `sakura` - '$cost' WHERE `id_user` = '$userId'";
                            if (!mysqli_query($conn, $sql)) {
                                echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                                exit;
                            }
                            echo add_stikers($conn, $userId, $rar);
                            exit;
                        } else {
                            echo "У вас недостаточно лепестков для покупки стикеров!";
                        }
                    } else {
                        echo "Ошибка покупки стикеров.";
                    }
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            case 'stiker_game':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    echo add_stikers($conn, $userId, NULL);
                    exit;
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            case 'stiker_delete':
                if (mysqli_num_rows($result) != 0) {
                    $inv_data = $result->fetch_assoc();
                    if (isset($_POST['stikerId'])) {
                        $stikerId = $_POST['stikerId'];
                        $sql = "SELECT * FROM stikers WHERE id_stikers = '$stikerId'";
                        $result = $conn->query($sql);
                        if(mysqli_num_rows($result) != 0) {
                            $stiker_data = $result->fetch_assoc();
                            $answer;
                            switch ($stiker_data['rarity']) {
                                case "com":
                                    $sql = "UPDATE `invent` SET `coins` = `coins` + 40 WHERE `id_user` = '$userId'";
                                    $answer = "Вы получили 40 монет за продажу обычного стикера.";
                                    break;
                                case "rar":
                                    $sql = "UPDATE `invent` SET `sakura` = `sakura` + 45 WHERE `id_user` = '$userId'";
                                    $answer = "Вы получили 45 лепестков сакуры за продажу редкого стикера.";
                                    break;
                                case "epic":
                                    $sql = "UPDATE `invent` SET `sakura` = `sakura` + 140 WHERE `id_user` = '$userId'";
                                    $answer = "Вы получили 140 лепестков сакуры за продажу эпического стикера!";
                                    break;
                                case "leg":
                                    $sql = "UPDATE `invent` SET `sakura` = `sakura` + 325, `xp` = `xp` + 450 WHERE `id_user` = '$userId'";
                                    $answer = "Вы получили 325 лепестков сакуры и 500 опыта за продажу легендарного стикера!";
                                    break;
                                case "myst":
                                    $sql = "UPDATE `invent` SET `sakura` = `sakura` + 675, `xp` = `xp` + 1000, `gems` = `gems` + 1  WHERE `id_user` = '$userId'";
                                    $answer = "Вы получили 675 лепестков сакуры,  1000 опыта и 1 кристалл за продажу мистического стикера!";
                                    break;
                            }
                            if (!mysqli_query($conn, $sql)) {
                                echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                                exit;
                            }
                            $conn->query("DELETE FROM stikers WHERE id_stikers = '$stikerId'");
                            echo $answer;
                            exit;
                        }

                    } else {
                        echo "Ошибка обработки.";
                        exit;
                    }
                    if (!mysqli_query($conn, $sql)) {
                        echo 'В SQL-запросе произошла ошибка. Ошибка SQL: ' . mysqli_error($conn);
                        exit;
                    }
                    echo 'Успешно выведено!';
                    exit;
                } else {
                    echo "У вас отсутствует инвентарь!";
                }
                break;
            default:
                $response = "Неизвестная команда. Выполнение невозможно.";
                echo json_encode(['success' => false, 'message' => $response, 'reload' => false]);
                break;
        }
        mysqli_close($conn);
        exit;
    }
} else{
    header('Location: index');
    exit;
}