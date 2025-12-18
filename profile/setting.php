<?php
session_start();
if(!isset($_SESSION['user'])) {
    header("Location: login");
    exit();
}
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if($conn){
    $login = $_SESSION['user'];
    $user_query = "SELECT * FROM users WHERE login = '$login'";
    $data_query = "SELECT * FROM personal_data WHERE login = '$login'";
    $result = $conn->query($user_query);
    $result_data = $conn->query($data_query);
    if ($result_data ->num_rows != 0) {
        $data_beta = $result_data ->fetch_assoc();
        $data = $data_beta['birthdate'];
        if ($data != NULL && $data != 'NULL') {
            $birthdate = date_create($data);
            $current_date = date_create();
            $age = date_diff($birthdate, $current_date)->y; 

            if ($age < 3 || $age > 63) {
                $query = "UPDATE users SET permissions = 'BANNED', reason = 'Манипуляции с возрастом. Модератор: SERVER' WHERE login = '$login'";
                mysqli_query($conn, $query);
                $ip = $_SERVER['REMOTE_ADDR'];
                $new_query = "INSERT INTO `black_ip`(`id`, `ip`) VALUES (NULL,'$ip')";
                mysqli_query($conn, $new_query);
            } else if ($age < 18 and $age >= 3) {
                $_SESSION['age'] = false;
            } else {
                $_SESSION['age'] = true;
            }
        }
    }
    $user = $result -> fetch_assoc();
    $permissions = $user["permissions"];
    if ($permissions == "BANNED" || $permissions == "GUEST"){
        header("Location: main");
        exit;
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php echo "<title>Settings " . $_SESSION['username'] . "</title>"; ?>
	<link rel = "icon" href = "../img/icon.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "../style/style.css">
	<link rel = "stylesheet" href = "../style/style_setting.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="navbar">
    <a href="./main">Back</a>
</div>
<div class="content-main">
    <div class="settings-container">
        <nav class="settings-nav">
            <ul>
                <li><a href="#profile" class="active">Профиль</a></li>
                <li><a href="#status">Статус</a></li>
                <li><a href="#avatar">Аватар</a></li>
                <li><a href="#username">Никнейм</a></li>
                <li><a href="#birthdate">Дата рождения</a></li>
                <?php if ($_SESSION['age']): ?>
                <li><a href="#nsfw">NSFW контент</a></li>
                <?php endif; ?>
                <li><a href="#password">Пароль</a></li>
                <li><a href="#titles">Титулы</a></li>
                <li><a href="#transfer">Переводы</a></li>
                <li><a href="#promocode">Промокоды</a></li>
                <li><a href="#delete">Удаление аккаунта</a></li>
            </ul>
        </nav>
        <div class="settings-content">
            <section id="profile" class="settings-card">
                <?php
                    require_once '../template/conn.php';
                    $conn = mysqli_connect($host, $log, $password_sql, $database);
                    if(!$conn){
                        echo("Ошибка соединения с базой данных. Вход и регистрация невозможна. Причина: ");
                        echo(mysqli_connect_error());
                        exit;
                    }
                ?>
                <h3>Профиль пользователя</h3>
                <div class="profile-header">
                    <div class="avatar-container">
                        <?php
                            $login = $_SESSION['user'];
                            $user_query = "SELECT * FROM users WHERE login = '$login'";
                            $result = $conn->query($user_query);
                            $user = $result->fetch_assoc();
                            echo '<img src="./avatars/' . $user["avatar"] . '" class="avatar-preview">';
                        ?>
                        <div class="avatar-edit" onclick="document.querySelector('a[href=\'#avatar\']').click()">
                            <i>&#9998;</i>
                        </div>
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-title"><?php echo $user["username"]; ?></h2>
                        <div class="profile-meta">Группа: <?php echo $user["permissions"]; ?></div>
                        <div class="profile-meta">Логин: <?php echo $login; ?></div>
                        <?php
                            $data_query = "SELECT * FROM personal_data WHERE login = '$login'";
                            $result_data = $conn->query($data_query);
                            if ($result_data->num_rows > 0) {
                                $data_beta = $result_data->fetch_assoc();
                                if (!empty($data_beta['birthdate'])) {
                                    echo '<div class="profile-meta">Дата рождения: ' . date("d.m.Y", strtotime($data_beta['birthdate'])) . '</div>';
                                }
                            }
                        ?>
                        <div class="profile-bio">
                            <?php echo nl2br($user['BIO']); ?>
                        </div>
                    </div>
                </div>
            </section>
            <section id="status" class="settings-card" style="display:none;">
                <h3>Изменение статуса</h3>
                <form action='bio_redact' method="post">
                    <div class="form-group">
                        <label class="form-label">Ваш текущий статус:</label>
                        <div class="profile-bio"><?php echo nl2br($user['BIO']); ?></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Новый статус:</label>
                        <textarea name="bio" class="form-control" minlength="10" maxlength="250" placeholder="Расскажите о себе..." required></textarea>
                    </div>
                    <button type="submit" name="submit" class="btn">Обновить статус</button>
                </form>
            </section>
            <section id="avatar" class="settings-card" style="display:none;">
                <h3>Изменение аватара</h3>
                <div class="preview-container">
                    <span class="preview-label">Текущий аватар:</span>
                    <?php echo '<img src="./avatars/' . $user["avatar"] . '" class="avatar-preview">'; ?>
                </div>
                <form action="avatar" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Выберите новый аватар:</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*" id="avatarUpload">
                    </div>
                    <div class="preview-container">
                        <span class="preview-label">Предпросмотр:</span>
                        <img id="avatarPreview" class="avatar-preview-new">
                    </div>
                    <button type="submit" name="submit" class="btn">Загрузить аватар</button>
                </form>
                <div class="warning-box">
                    <h4>Правила аватаров:</h4>
                    <ol>
                        <li>Запрещены аватары 18+ характера;</li>
                        <li>Запрещены политические деятели;</li>
                        <li>Запрещены аниме-аватары с фурри-тематикой;</li>
                        <li>Администрация вправе заменить нарушающий аватар.</li>
                    </ol>
                </div>
            </section>
            <section id="username" class="settings-card" style="display:none;">
                <h3>Изменение никнейма</h3>
                <form action="username" method="post">
                    <div class="form-group">
                        <label class="form-label">Текущий никнейм:</label>
                        <input type="text" class="form-control" value="<?php echo $user['username']; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Новый никнейм:</label>
                        <input type="text" name="username" class="form-control" minlength="3" maxlength="20" placeholder="Введите новый никнейм" required>
                    </div>
                    <button type="submit" name="submit" class="btn">Изменить никнейм</button>
                </form>
            </section>
            <section id="birthdate" class="settings-card" style="display:none;">
                <h3>Дата рождения</h3>
                <form action="birthdate" method='post'>
                    <div class="form-group">
                        <label class="form-label">Текущая дата рождения:</label>
                        <?php
                            if (!empty($data_beta['birthdate'])) {
                                echo '<input type="text" class="form-control" value="' . date("d.m.Y", strtotime($data_beta['birthdate'])) . '" readonly>';
                            } else {
                                echo '<input type="text" class="form-control" value="Не указана" readonly>';
                            }
                        ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Новая дата рождения:</label>
                        <input type="date" name="birthdate" class="form-control" min="1900-01-01" max="2070-12-31" required>
                    </div>
                    <button type="submit" name="submit" class="btn">Обновить дату</button>
                </form>
            </section>
            <?php if ($_SESSION['age']): ?>
            <section id="nsfw" class="settings-card" style="display:none;">
                <h3>Настройки NSFW контента</h3>
                <div class="warning-box">
                    <p><strong>Внимание:</strong> NSFW контент включает материалы 18+ характера. Разрешая доступ, вы подтверждаете что вам есть 18 лет и принимаете всю ответственность за просматриваемый контент.</p>
                </div>
                <form action="nsfw" method='post'>
                    <div class="form-group">
                        <label class="form-label">Текущие настройки:</label>
                        <?php 
                            if($user['NSFW']) {
                                echo '<div class="success-box">Доступ разрешен</div>';
                            } else {
                                echo '<div class="warning-box">Доступ запрещен</div>';
                            }
                        ?>
                    </div>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="nsfw0" name="nsfw" value="0" <?php echo !$user['NSFW'] ? 'checked' : ''; ?>>
                            <label for="nsfw0">Запретить доступ к NSFW контенту</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="nsfw1" name="nsfw" value="1" <?php echo $user['NSFW'] ? 'checked' : ''; ?>>
                            <label for="nsfw1">Разрешить доступ к NSFW контенту</label>
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn">Сохранить настройки</button>
                </form>
            </section>
            <?php endif; ?>
            <section id="password" class="settings-card" style="display:none;">
                <h3>Смена пароля</h3>
                <form action="new_pass" method='post'>
                    <div class="form-group">
                        <label class="form-label">Текущий пароль:</label>
                        <input type="password" name="old_password" class="form-control" minlength="8" maxlength="15" placeholder="Введите текущий пароль" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Новый пароль:</label>
                        <input type="password" name="new_password" class="form-control" minlength="8" maxlength="15" placeholder="Введите новый пароль" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Повторите новый пароль:</label>
                        <input type="password" name="new_password_confirm" class="form-control" minlength="8" maxlength="15" placeholder="Повторите новый пароль" required>
                    </div>
                    <div class="form-group">
                        <div class="radio-item">
                            <input type="checkbox" id="showPass">
                            <label for="showPass">Показать пароли</label>
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn">Изменить пароль</button>
                </form>
            </section>
            <section id="titles" class="settings-card" style="display:none;">
                <h3>Управление титулами</h3>
                <?php
                    $invent_sql = "SELECT * FROM invent WHERE id_user = " . $user["id"];
                    $title_sql = "SELECT id_title, title FROM title WHERE id_user = " . $user["id"];
                    $invent_result = mysqli_query($conn, $invent_sql);
                    if(mysqli_num_rows($invent_result) > 0) {
                        echo '<form action="new_title" method="post">';
                        echo '<div class="radio-group">';
                        $invent = mysqli_fetch_assoc($invent_result);
                        $title_result = mysqli_query($conn, $title_sql);
                        if(mysqli_num_rows($title_result) > 0) {
                            while ($title = mysqli_fetch_assoc($title_result)) {
                                echo '<div class="radio-item">';
                                echo '<input type="radio" id="title'.$title["id_title"].'" name="title" value="'.$title["id_title"].'"';
                                if ($title["id_title"] == $invent["id_title"]) echo ' checked';
                                echo '>';
                                echo '<label for="title'.$title["id_title"].'">'.$title["title"].'</label>';
                                echo '</div>';
                            }
                            echo '<div class="radio-item">';
                            echo '<input type="radio" id="title0" name="title" value="NULL">';
                            echo '<label for="title0">Убрать титул</label>';
                            echo '</div>';
                            echo '<button type="submit" name="submit" class="btn">Применить титул</button>';
                        } else {
                            echo '<p>У вас пока нет доступных титулов.</p>';
                        }
                        echo '</div></form>';
                    } else {
                        echo '<p>Активируйте инвентарь для доступа к титулам.</p>';
                    }
                ?>
            </section>
            <section id="transfer" class="settings-card" style="display:none;">
                <h3>Переводы</h3>
                <form action="transfer" method="post">
                    <div class="form-group">
                        <label class="form-label">Получатель:</label>
                        <select id="recipientSelect" class="form-control" required>
                            <option value="" disabled selected>Выберите пользователя</option>
                            <?php
                                $current_user = $_SESSION['user'];
                                $users_query = "SELECT id, username FROM users WHERE login != '$current_user'";
                                $users_result = $conn->query($users_query);
                                while($user = $users_result->fetch_assoc()) {
                                    echo '<option value="'.$user['id'].'">'.$user['username'].'</option>';
                                }
                            ?>
                        </select>
                        <input type="hidden" name="recipient_id" id="recipientId">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Тип ресурса:</label>
                        <select name="resource_type" class="form-control" required>
                            <option value="coins">Монеты</option>
                            <option value="sakura">Лепестки сакуры</option>
                            <option value="gems">Кристаллы</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Количество:</label>
                        <input type="number" name="amount" class="form-control" min="1" required>
                    </div>
                    <button type="submit" name="submit" class="btn">Перевести</button>
                </form>
            </section>
            <section id="promocode" class="settings-card" style="display:none;">
                <h3>Промокоды</h3>
                <form action="promo" method="post">
                    <div class="form-group">
                        <label class="form-label">Введите промокод:</label>
                        <input type="text" name="promocode" class="form-control" maxlength="150" placeholder="Введите промокод" required>
                    </div>
                    <button type="submit" name="submit" class="btn">Активировать</button>
                </form>
            </section>
            <section id="delete" class="settings-card" style="display:none;">
                <h3>Удаление аккаунта</h3>
                <div class="warning-box">
                    <p><strong>Внимание!</strong> Удаление аккаунта приведет к:</p>
                    <ul>
                        <li>Безвозвратному удалению всех данных;</li>
                        <li>Потере доступа к сайту;</li>
                        <li>Удалению всех ваших материалов;</li>
                    </ul>
                    <p>Это действие необратимо! Подумайте, перед тем, как принять решение.</p>
                </div>
                <form action="delete_acc" method='post'>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="confirmDel" name="delete" value="1" required>
                            <label for="confirmDel">Я подтверждаю удаление аккаунта</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Введите пароль для подтверждения:</label>
                        <input type="password" name="password" class="form-control" required minlength="8" maxlength="15" placeholder="Ваш пароль">
                    </div>
                    <button type="submit" name="submit" class="btn" style="background: linear-gradient(45deg, #ff5252, #b33939);">Удалить аккаунт</button>
                </form>
            </section>
            <div class="settings-card" <?php echo (isset($_SESSION['error']) or isset($_SESSION['great'])) ? "style='display: block'" : "style='display: none'";?>>
                <h3>Системные уведомления</h3>
                <?php
                if(isset($_SESSION['error'])) {
                    echo "<div class='warning-box'>" . $_SESSION['error'] . "</div>";
                    unset($_SESSION['error']);
                }
                if(isset($_SESSION['great'])) {
                    echo "<div class='success-box'>" . $_SESSION['great'] . "</div>";
                    unset($_SESSION['great']);
                }
                ?>
            </div>
        </div>
    </div>
</div>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="./js/setting1.js"></script>
</body>
</html>
<?php 
session_write_close();
?>