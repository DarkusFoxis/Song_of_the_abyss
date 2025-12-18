<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == "POST"){
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        echo("Ошибка соединения.");
    } else {
        $userId = $_POST['userId'];
        $stmt = $conn->prepare("SELECT id_items, name, description, chance FROM items");
        $stmt->execute();
        $stmt->bind_result($id_items, $name, $description, $chance);
        $items = [];
        while ($stmt->fetch()) {
            $items[] = [
                'id_items' => $id_items,
                'name' => $name,
                'description' => $description,
                'chance' => $chance
            ];
        }
        $query = "SELECT * FROM invent WHERE id_user = '$userId'";
        $result = mysqli_query($conn, $query);
        $inv_data = $result->fetch_assoc();

        $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.id = '$userId'";
        $result = mysqli_query($conn, $user_query);
        $user = $result->fetch_assoc();
        $bonus = 1;
        if ($user['lvl'] > 2) {
            $bonus = 2;
        }

        if ($user['donate'] > 0) {
            $xp_bonus = ceil($user['donate'] / 10);
            $coin_bonus = ceil($user['donate'] / 100);
            $petal_bonus = ceil($user['donate'] / 500);
            $gem_bonus = ceil($user['donate'] / 2000);
            $kase_bonus = ceil($user['donate'] / 2500);
        }

        function generateRandomItem($items) {
            $totalChance = 0;
            foreach ($items as $item) {
                $totalChance += $item['chance'];
            }

            $randomNumber = mt_rand(0, 10000) / 100;

            $currentChance = 0;
            foreach ($items as $item) {
                $currentChance += $item['chance'];
                if ($randomNumber <= $currentChance) {
                    return $item;
                }
            }
            return 'null';
        }

        $query = "SELECT * FROM invent WHERE id_user = '$userId'";
        $result = mysqli_query($conn, $query);
        $caseBd = $result->fetch_assoc();

        $action = isset($_POST['action']) ? $_POST['action'] : null;

        switch ($action) {
            case "open":
                $randomItem = generateRandomItem($items);
                if ($caseBd['kase'] > 0) {
                    switch ($randomItem['name']) {
                        case 'title':
                            $description = $randomItem['description'];
                            $query = "SELECT * FROM title WHERE id_user = '$userId' AND title = '$description'";
                            $result = mysqli_query($conn, $query);
        
                            if (mysqli_num_rows($result) === 0) {
                                $titleQuery = "INSERT INTO `title`(`id_title`, `id_user`, `title`) VALUES (NULL,'$userId','$description')";
                                if (!mysqli_query($conn, $titleQuery)) {
                                    echo 'Произошла ошибка: '. mysqli_error($conn);
                                } else {
                                    echo 'Вы получили титул "' . $description . '"!';
                                }
                            } else {
                                $sakura = (80 * $bonus) * ceil(((10 + $inv_data['lvl']) / 10));
                                $query = "UPDATE `invent` SET `sakura`= `sakura` + '$sakura' WHERE `id_user` = '$userId'";
                                if (!mysqli_query($conn, $query)) {
                                    echo 'Произошла ошибка: '. mysqli_error($conn);
                                } else {
                                    echo 'У вас есть титул ' . $description . ', поэтому вы получаете ' . $sakura . ' лепестков сакуры!';
                                }
                            }
                            $query = "UPDATE `invent` SET `kase` = `kase` - '1'  WHERE `id_user` = '$userId'";
                            mysqli_query($conn, $query);
                            break;
                        case 'coins':
                            $randomCoins = (mt_rand(2, 80 * ceil(((1 + $inv_data['lvl']) / 5))) * $bonus) + $coin_bonus;
                            $query = "UPDATE `invent` SET `coins`= `coins` + '$randomCoins' WHERE `id_user` = '$userId'";
                            if (!mysqli_query($conn, $query)) {
                                echo 'Произошла ошибка: '. mysqli_error($conn);
                            } else {
                                echo 'Вы получили ' . $randomCoins . ' монет!';
                            }
                            $query = "UPDATE `invent` SET `kase` = `kase` - '1'  WHERE `id_user` = '$userId'";
                            mysqli_query($conn, $query);
                            break;
                        case 'exp':
                            $randomXp = (mt_rand(2, 149 * ceil(((1 + $inv_data['lvl']) / 4))) * $bonus) + $xp_bonus;
                            $query = "UPDATE `invent` SET `xp`= `xp` + '$randomXp' WHERE `id_user` = '$userId'";
                           if (!mysqli_query($conn, $query)) {
                                echo 'Произошла ошибка: '. mysqli_error($conn);
                            } else {
                                echo 'Вы получили ' . $randomXp . ' опыта!';
                            }
                            $query = "UPDATE `invent` SET `kase` = `kase` - '1'  WHERE `id_user` = '$userId'";
                            mysqli_query($conn, $query);
                            break;
                        case 'gems':
                            $randomGems = (mt_rand(1, 5)) + $gem_bonus;
                            $query = "UPDATE `invent` SET `gems`= `gems` + '$randomGems' WHERE `id_user` = '$userId'";
                            if (!mysqli_query($conn, $query)) {
                                echo 'Произошла ошибка: '. mysqli_error($conn);
                            } else {
                                echo 'Вы получили ' . $randomGems . ' кристаллов!';
                            }
                            $query = "UPDATE `invent` SET `kase` = `kase` - '1'  WHERE `id_user` = '$userId'";
                            mysqli_query($conn, $query);
                            break;
                        case 'sakura':
                            $randomSakura = (mt_rand(2, 37 * ceil(((1 + $inv_data['lvl']) / 5))) * $bonus) + $petal_bonus;
                            $query = "UPDATE `invent` SET `sakura`= `sakura` + '$randomSakura' WHERE `id_user` = '$userId'";
                            if (!mysqli_query($conn, $query)) {
                                echo 'Произошла ошибка: '. mysqli_error($conn);
                            } else {
                                echo 'Вы получили ' . $randomSakura . ' лепестков сакуры!';
                            }
                            $query = "UPDATE `invent` SET `kase` = `kase` - '1'  WHERE `id_user` = '$userId'";
                            mysqli_query($conn, $query);
                            break;
                        case 'treasures':
                            $randomSakura = ceil((mt_rand(2, 15 * ceil(((1 + $inv_data['lvl']) / 5))) * $bonus) + ($petal_bonus / 5));
                            $randomGems = ceil(mt_rand(1, 3) + ($gem_bonus / 2));
                            $randomXp = ceil((mt_rand(2, 93 * ceil(((1 + $inv_data['lvl']) / 5))) * $bonus) + ($xp_bonus / 5));
                            $randomCoins = ceil((mt_rand(2, 54 * ceil(((1 + $inv_data['lvl']) / 5))) * $bonus) + ($coin_bonus / 5));
                            $query = "UPDATE `invent` SET `sakura`= `sakura` + '$randomSakura', `gems`= `gems` + '$randomGems', `xp`= `xp` + '$randomXp', `coins`= `coins` + '$randomCoins' WHERE `id_user` = '$userId'";
                            if (!mysqli_query($conn, $query)) {
                                echo 'Произошла ошибка: '. mysqli_error($conn);
                            } else {
                                echo 'Вы нашли сокровище! Вы получили: ' . $randomSakura . ' лепестков сакуры, ' . $randomGems . ' кристаллов, ' . $randomXp . ' опыта, и ' . $randomCoins . ' монет!';
                            }
                            $query = "UPDATE `invent` SET `kase` = `kase` - '1'  WHERE `id_user` = '$userId'";
                            mysqli_query($conn, $query);
                            break;
                        default:
                            echo 'Произошла ошибка. Возможно, кейс был пустым.';
                            break;
                    }
                } else if ($caseBd['kase'] == 0) {
                    echo 'У вас нет кейсов.';
                } else {
                    echo 'Всмысле вы бомж по кейсам?...';
                }
                break;
            case "get_count":
                echo $caseBd['kase'];
                break;
        }
    }
}
session_write_close();