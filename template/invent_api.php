<?php
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
        $description_list = ["Аврора мечтала стать вет врачом.","Даркус может быть жестоким, но это не всегда, а вот извращению место найдёт везде.", "Минами мечтала стать воспитательницей. Она любит детей.", "Мизуки слышит, но не умеет говорить. Это было и до перерождения.", "Имран изначально думал, что сила, которую он получил, можно использовать для игр с сестрой.", "Иногда Кару кажется, что над ней постоянно издеваются...", "Путешествуя по мирам, можно найти разных существ. Даже таких лисичек.", "Джахи снова была в ярости, когда её вновь бросили в новый мир.", "Эта малютка нашла кошку и теперь они обе гуляют по миру.", "Ася только проснулась, но уже понимает, что жить в этом мире будет не просто.", "Как коты видят админа, когда он работает над сайтом:", "Кинако хочет пробраться в ваш холодильник, и украсть всю вашу рыбу!"];
        $stiker_rarity = "com";
        $stiker_full_rarity = "обычный";
    } else if (($stiker_rand >= 78 && $stiker_rand <= 90 && $stiker_rar == NULL) || $stiker_rar == "rar") {
        $stiker_list = ["avrora_halloween","avrora_reburn","misuki","old_avrora","school_avrora","school_darkus","imran_school", "kitsune"];
        $description_list = ["Аврора больше никогда не вырастит. Она и сама этого не желает.", "Аврора думала, что DarkOleFox сможет вернуть её родителей к жизни.", "Мизуки является помощницей Инари. Ранее она была обычным ребёнком.", "Аврора никогда не хотела войн. Она не любит крови.", "Даже став помощницей DarkOleFoxa, Аврора захотела в школу. Ей нравится учиться.", "Даркус долгое время, даже став демоном, продолжал ходить в школу.", "Имран так и не закончил школу, даже будучи в 11 классе.", "Мизуки иногда рисует свою госпожу в разных обличьях и этот вариант не исключение.","Правитель бездны всегда следит за порядком в своём мире.", "Эта лисичка будет следовать за вами попятам. Не делайте вид, что не замечаете её.~", "Бывшая телеведущая решила присоединится к нашему миру. Что она расскажет сегодня?"];
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
        return $stiker_full_rarity . " стикер " . $target_stiker . "!";
    }
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