<?php
if (isset($_POST['userId'])) {
    session_start();
    $userId = $_POST['userId'];
    require_once '../template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);

    $sql = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE id = '$userId'";
    $result = mysqli_query($conn, $sql);
    $login = $_SESSION['user'];
    $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
    $result_perm = $conn->query($user_query);
    $row_perm = $result_perm->fetch_assoc();
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['NSFW']){
            $row['NSFW'] = "Присутствует";
        } else {
            $row['NSFW'] = "Отсутствует";
        }
        $profile_html = '<div class=" .profile_modal">';
        $profile_html .= '<img src="./avatars/' . $row["avatar"] . '" class="avatar" align="left">';
        $title_sql = "SELECT title FROM title WHERE id_title = (SELECT id_title FROM invent WHERE id_user = '$userId')";
        $invent_sql = "SELECT * FROM invent WHERE id_user = '$userId'";
        $title_result = mysqli_query($conn, $title_sql);
        $invent_result = mysqli_query($conn, $invent_sql);
        $user_login = $row['login'];
        $pers_data = "SELECT * FROM `personal_data` WHERE login = '$user_login'";
        $pers_result = mysqli_query($conn, $pers_data);

        if (mysqli_num_rows($title_result) > 0) {
            $title_row = mysqli_fetch_assoc($title_result);
            $invent = mysqli_fetch_assoc($invent_result);
            setlocale(LC_TIME, 'ru_RU.UTF-8');
            $title = $title_row['title'];
            $profile_html .= '<p>' . $title . ': ' . $row["username"] . '</p>';
            $profile_html .= '<p>Уровень: ' . $invent['lvl'] . '   Опыт: ' . $invent['xp'] . '/' . $invent['xp_max'] . '   Монет: ' . $invent['coins'] . '</p>';
        } else {
            $profile_html .= '<p>Никнейм: ' . $row["username"] . '</p>';
            if (mysqli_num_rows($invent_result) > 0) {
                $invent = mysqli_fetch_assoc($invent_result);
                $profile_html .= '<p>Уровень: ' . $invent['lvl'] . '   Опыт: ' . $invent['xp'] . '/' . $invent['xp_max'] . '   Монет: ' . $invent['coins'] . '</p>';
            }
        }
        $profile_html .= '<p class="donate">Поддержка проекта: ' . $row['donate'] . ' руб.</p>';
        $profile_html .= '<p>Группа на сайте: ' . $row["permissions"] . '</p>';
        $profile_html .= '<p>Доступ к NSFW контенту: ' . $row["NSFW"] . '</p>';
        $profile_html .= '<p>Последний вход: ' . date("H:i d.m.Y", strtotime($row["last_login"])) . '</p>';
        $profile_html .= '<hr>';
        $achievements_sql = "SELECT title, description FROM achievement WHERE id_user = " . $row["id"];
        $achievements_result = mysqli_query($conn, $achievements_sql);
        if ($row["lvl"] == "0") {
            $profile_html .= '<p>Пользователь был заблокирован по следующей причине: ' . $row["reason"] . '.</p>';
        } else {
            $profile_html .= '<p>Статус: ' . nl2br($row["BIO"]) . '</p>';
            if (mysqli_num_rows($achievements_result) > 0) {
                $profile_html .= '<hr><p>Достижения:</p>';
                while ($achievement = mysqli_fetch_assoc($achievements_result)) {
                    $profile_html .= '<p style="background: linear-gradient(90deg, rgba(93,25,138,1) 0%, rgba(135,11,11,1) 50%, rgba(190,118,16,1) 100%);">' . $achievement["title"] . ': ' . $achievement["description"] . '</p>';
                }
            } else {
                $profile_html .= '<p>У пользователя пока нет достижений. Но это пока.</p>';
            }
            $profile_html .= '<a href="./profile?id=' . $row["id"] . '" class="link">Открыть полный профиль</a>';
        }
        if ($row_perm['lvl'] == "6") {
            $profile_html .= '<hr>';
            $profile_html .= '<p>Для рут:</p>';
            $profile_html .= '<p>ID: ' . $row["id"] . '</p>';
            $profile_html .= '<p>Логин: ' . $user_login . '</p>';
            if (mysqli_num_rows($pers_result) > 0) {
                $personal_data = $invent = mysqli_fetch_assoc($pers_result);
                if ($personal_data['birthdate'] != NULL) {
                    $profile_html .= '<p>Дата рождения: ' . $personal_data['birthdate'] . '</p>';
                }
                if ($personal_data['telegram'] != NULL) {
                    $profile_html .= '<p>Телеграм: ' . $personal_data['telegram'] . '</p>';
                }
                
            }
            $profile_html .= '<p>Почта: ' . $row["email"] . '</p>';
            $profile_html .= '<p>IP: ' . $row["ip"] . '</p>';
            $profile_html .= '<p>Дата создания аккаунта: ' . date("H:i:s d.m.Y", strtotime($row["data_create"])) . '</p>';
            $profile_html .= '<hr>';
            if ($row["lvl"] == "0") {
                $profile_html .= '<button class="button" onclick="unBan(\'' . $row["id"] . '\')">Разблокировать</button>';
            } else {
                $profile_html .= 'Заблокировать пользователя:';
                $profile_html .= '<form id="ban_user">';
                $profile_html .= '<label for="user_id">ID пользователя: </label>';
                $profile_html .= '<input id="user_id" name="user_id" type="text" readonly value="' . $row["id"] . '" required><br>';
                $profile_html .= '<label for="reason">Причина блокировки: </label>';
                $profile_html .= '<input type="text" id="reason" name="reason" list="ban_reason" required minlength="5" maxlength="50" class="input-text"><br>';
                $profile_html .= '<label for="moder">Модератор: </label>';
                $profile_html .= '<input type="text" id="moder" name="moder" required value="' . $_SESSION["username"] . '"><br>';
                $profile_html .= '<input type="submit" id="go_ban" value="Блокировать" class="button">';
                $profile_html .= '</form>';
                $profile_html .= '<hr>';
                $profile_html .= 'Сменить права:';
                $profile_html .= '<form id="switch_group">';
                $profile_html .= '<label for="user_id">ID пользователя: </label>';
                $profile_html .= '<input id="user_id" name="user_id" type="text" readonly value="' . $row["id"] . '" required><br>';
                $profile_html .= '<label for="group">Группа: </label>';
                $profile_html .= '<input type="text" id="group" name="group" list="group_list" required maxlength="15" value="' . $row["permissions"] . '" class="input-text"><br>';
                $profile_html .= '<input type="submit" id="go" value="Сменить группу" class="button">';
                $profile_html .= '</form>';
            }

            $profile_html .= '<script src="../js/admin1.2.js"></script>';
        }
        $profile_html .= '</div>';

        echo $profile_html;
    } else {
        echo "Профиль не найден.";
    }

    mysqli_close($conn);
} else {
    echo "Ошибка: id не указан.";
}