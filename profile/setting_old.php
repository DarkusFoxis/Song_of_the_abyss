<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();
$authUser = auth_require_user('/profile/login');
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php echo "<title>Settings " . $_SESSION['username'] . "</title>"; ?>
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/style_setting1.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<div class="navbar">
    <a href="./main">← Назад</a>
</div>
<div class="content-main">
    <div class="settings-container">
        <nav class="settings-nav">
            <div class="settings-nav-title">
                <span class="icon">⚙</span>
                <span>Настройки</span>
            </div>
            <ul>
                <li><a href="#profile" class="active">
                    <span class="nav-icon">👤</span>
                    <span>Профиль</span>
                </a></li>
                <li><a href="#status">
                    <span class="nav-icon">💬</span>
                    <span>Статус</span>
                </a></li>
                <li><a href="#avatar">
                    <span class="nav-icon">🖼</span>
                    <span>Аватар</span>
                </a></li>
                <li><a href="#username">
                    <span class="nav-icon">✏</span>
                    <span>Никнейм</span>
                </a></li>
                <li><a href="#birthdate">
                    <span class="nav-icon">🎂</span>
                    <span>Дата рождения</span>
                </a></li>
                <?php if ($_SESSION['age']): ?>
                <li><a href="#nsfw">
                    <span class="nav-icon">🔞</span>
                    <span>NSFW</span>
                </a></li>
                <?php endif; ?>
                <li><a href="#password">
                    <span class="nav-icon">🔐</span>
                    <span>Пароль</span>
                </a></li>
                <li><a href="#titles">
                    <span class="nav-icon">👑</span>
                    <span>Титулы</span>
                </a></li>
                <li><a href="#transfer">
                    <span class="nav-icon">💸</span>
                    <span>Переводы</span>
                </a></li>
                <li><a href="#promocode">
                    <span class="nav-icon">🎁</span>
                    <span>Промокоды</span>
                </a></li>
                <li><a href="#delete">
                    <span class="nav-icon">🗑</span>
                    <span>Удаление</span>
                </a></li>
            </ul>
        </nav>

        <div class="settings-content">
            <?php
                require_once '../template/conn.php';
                $conn = mysqli_connect($host, $log, $password_sql, $database);
                if(!$conn){
                    echo("<div class='warning-box'>Ошибка соединения с базой данных. Причина: " . mysqli_connect_error() . "</div>");
                    exit;
                }
                $login = $_SESSION['user'];
                $user_query = "SELECT * FROM users WHERE login = '$login'";
                $result = $conn->query($user_query);
                $user = $result->fetch_assoc();
                
                $data_query = "SELECT * FROM personal_data WHERE login = '$login'";
                $result_data = $conn->query($data_query);
                $data_beta = null;
                if ($result_data->num_rows > 0) {
                    $data_beta = $result_data->fetch_assoc();
                }
            ?>

            <section id="profile" class="settings-card">
                <div class="abyss-orb orb-1"></div>
                <div class="abyss-orb orb-2"></div>
                <h3><span class="card-icon">👤</span>Профиль пользователя</h3>
                <div class="profile-header">
                    <div class="avatar-container">
                        <img src="./avatars/<?php echo $user['avatar']; ?>" class="avatar-preview" alt="Avatar">
                        <div class="avatar-edit" onclick="document.querySelector('a[href=\'#avatar\']').click()" title="Изменить аватар">✏</div>
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-title"><?php echo htmlspecialchars($user["username"]); ?></h2>
                        <div class="profile-meta">
                            <span class="meta-icon">🎭</span>
                            <span>Группа:</span>
                            <span class="profile-meta-value"><?php echo $user["permissions"]; ?></span>
                        </div>
                        <div class="profile-meta">
                            <span class="meta-icon">🔑</span>
                            <span>Логин:</span>
                            <span class="profile-meta-value"><?php echo htmlspecialchars($login); ?></span>
                        </div>
                        <?php if ($data_beta && !empty($data_beta['birthdate'])): ?>
                        <div class="profile-meta">
                            <span class="meta-icon">🎂</span>
                            <span>Дата рождения:</span>
                            <span class="profile-meta-value"><?php echo date("d.m.Y", strtotime($data_beta['birthdate'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="profile-bio">
                            <?php echo nl2br($user['BIO']); ?>
                        </div>
                    </div>
                </div>
            </section>

            <section id="status" class="settings-card hidden">
                <h3><span class="card-icon">💬</span>Изменение статуса</h3>
                <form action="setting_core" method="post">
<input type="hidden" name="action" value="bio_redact">
                    <div class="form-group">
                        <label class="form-label">Ваш текущий статус:</label>
                        <div class="profile-bio"><?php echo nl2br($user['BIO']); ?></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Новый статус:</label>
                        <textarea name="bio" class="form-control" minlength="10" maxlength="250" placeholder="Расскажите о себе..." required></textarea>
                    </div>
                    <button type="submit" name="submit" class="btn"><span class="btn-icon">💾</span>Обновить статус</button>
                </form>
            </section>

            <section id="avatar" class="settings-card hidden">
                <h3><span class="card-icon">🖼</span>Изменение аватара</h3>
                <div class="preview-container">
                    <span class="preview-label">Текущий аватар:</span>
                    <img src="./avatars/<?php echo $user['avatar']; ?>" class="avatar-preview" alt="Current Avatar">
                </div>
                <form action="setting_core" method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="avatar">
                    <div class="form-group">
                        <label class="form-label">Выберите новый аватар:</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*" id="avatarUpload">
                    </div>
                    <div class="preview-container">
                        <span class="preview-label">Предпросмотр:</span>
                        <img id="avatarPreview" class="avatar-preview-new" alt="Preview">
                    </div>
                    <button type="submit" name="submit" class="btn"><span class="btn-icon">📤</span>Загрузить аватар</button>
                </form>
                <div class="warning-box">
                    <h4>📜 Правила аватаров:</h4>
                    <ol>
                        <li>Запрещены аватары 18+ характера;</li>
                        <li>Запрещены политические деятели;</li>
                        <li>Запрещены аватары с фурри-тематикой;</li>
                        <li>Администрация вправе заменить нарушающий аватар.</li>
                    </ol>
                </div>
            </section>

            <section id="username" class="settings-card hidden">
                <h3><span class="card-icon">✏</span>Изменение никнейма</h3>
                <form action="setting_core" method="post">
<input type="hidden" name="action" value="username">
                    <div class="form-group">
                        <label class="form-label">Текущий никнейм:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Новый никнейм:</label>
                        <input type="text" name="username" class="form-control" minlength="3" maxlength="20" placeholder="Введите новый никнейм" required>
                    </div>
                    <button type="submit" name="submit" class="btn"><span class="btn-icon">💾</span>Изменить никнейм</button>
                </form>
            </section>

            <section id="birthdate" class="settings-card hidden">
                <h3><span class="card-icon">🎂</span>Дата рождения</h3>
                <form action="setting_core" method="post">
<input type="hidden" name="action" value="birthdate">
                    <div class="form-group">
                        <label class="form-label">Текущая дата рождения:</label>
                        <?php if ($data_beta && !empty($data_beta['birthdate'])): ?>
                            <input type="text" class="form-control" value="<?php echo date("d.m.Y", strtotime($data_beta['birthdate'])); ?>" readonly>
                        <?php else: ?>
                            <input type="text" class="form-control" value="Не указана" readonly>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Новая дата рождения:</label>
                        <input type="date" name="birthdate" class="form-control" min="1900-01-01" max="2070-12-31" required>
                    </div>
                    <button type="submit" name="submit" class="btn"><span class="btn-icon">💾</span>Обновить дату</button>
                </form>
            </section>

            <?php if ($_SESSION['age']): ?>
            <section id="nsfw" class="settings-card hidden">
                <h3><span class="card-icon">🔞</span>Настройки NSFW контента</h3>
                <div class="warning-box">
                    <p><strong>⚠ Внимание:</strong> NSFW контент включает материалы 18+ характера. Разрешая доступ, вы подтверждаете что вам есть 18 лет и принимаете всю ответственность за просматриваемый контент.</p>
                </div>
                <form action="setting_core" method="post">
<input type="hidden" name="action" value="nsfw">
                    <div class="form-group">
                        <label class="form-label">Текущие настройки:</label>
                        <?php if($user['NSFW']): ?>
                            <div class="success-box">✅ Доступ разрешен</div>
                        <?php else: ?>
                            <div class="warning-box">🚫 Доступ запрещен</div>
                        <?php endif; ?>
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
                    <button type="submit" name="submit" class="btn"><span class="btn-icon">💾</span>Сохранить настройки</button>
                </form>
            </section>
            <?php endif; ?>

            <section id="password" class="settings-card hidden">
                <h3><span class="card-icon">🔐</span>Смена пароля</h3>
                <form action="setting_core" method="post">
<input type="hidden" name="action" value="new_password">
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
                    <button type="submit" name="submit" class="btn"><span class="btn-icon">🔐</span>Изменить пароль</button>
                </form>
            </section>

            <section id="titles" class="settings-card hidden">
                <h3><span class="card-icon">👑</span>Управление титулами</h3>
                <?php
                    $invent_sql = "SELECT * FROM invent WHERE id_user = " . $user["id"];
                    $title_sql = "SELECT id_title, title FROM title WHERE id_user = " . $user["id"];
                    $invent_result = mysqli_query($conn, $invent_sql);
                    if(mysqli_num_rows($invent_result) > 0) {
                        echo '<form action="setting_core" method="post">
<input type="hidden" name="action" value="new_title">';
                        echo '<div class="radio-group titles-grid">';
                        $invent = mysqli_fetch_assoc($invent_result);
                        $title_result = mysqli_query($conn, $title_sql);
                        if(mysqli_num_rows($title_result) > 0) {
                            while ($title = mysqli_fetch_assoc($title_result)) {
                                echo '<div class="radio-item">';
                                echo '<input type="radio" id="title'.$title["id_title"].'" name="title" value="'.$title["id_title"].'"';
                                if ($title["id_title"] == $invent["id_title"]) echo ' checked';
                                echo '>';
                                echo '<label for="title'.$title["id_title"].'">'.htmlspecialchars($title["title"]).'</label>';
                                echo '</div>';
                            }
                            echo '<div class="radio-item">';
                            echo '<input type="radio" id="title0" name="title" value="NULL"';
                            if ($invent["id_title"] == NULL) echo ' checked';
                            echo '>';
                            echo '<label for="title0">❌ Убрать титул</label>';
                            echo '</div>';
                            echo '</div>';
                            echo '<button type="submit" name="submit" class="btn"><span class="btn-icon">👑</span> Применить титул</button>';
                        } else {
                            echo '<p style="color: var(--abyss-muted);">У вас пока нет доступных титулов.</p>';
                        }
                        echo '</form>';
                    } else {
                        echo '<div class="warning-box"><p>Активируйте инвентарь для доступа к титулам.</p></div>';
                    }
                ?>
            </section>

            <section id="transfer" class="settings-card hidden">
                <h3><span class="card-icon">💸</span>Переводы</h3>

                <div id="transferInfo" class="transfer-info" style="display: none;">
                    <div class="transfer-info-item">
                        <span class="info-icon">👤</span>
                        <div>
                            <span class="info-label">Получатель:</span>
                            <span class="info-value highlight" id="transferRecipientName">—</span>
                        </div>
                    </div>
                    <div class="transfer-info-item">
                        <span class="info-icon">💎</span>
                        <div>
                            <span class="info-label">Ресурс:</span>
                            <span class="info-value" id="transferResourceType">—</span>
                        </div>
                    </div>
                    <div class="transfer-info-item">
                        <span class="info-icon">📊</span>
                        <div>
                            <span class="info-label">Сумма:</span>
                            <span class="info-value highlight" id="transferAmount">—</span>
                        </div>
                    </div>
                </div>

                <form id="transferForm" action="transfer" method="post">
                    <div class="form-group">
                        <label class="form-label">Получатель:</label>
                        <select id="recipientSelect" class="form-control" required>
                            <option value="" disabled selected>— Выберите пользователя —</option>
                            <?php
                                $current_user = $_SESSION['user'];
                                $users_query = "SELECT id, username FROM users WHERE login != '$current_user'";
                                $users_result = $conn->query($users_query);
                                while($u = $users_result->fetch_assoc()) {
                                    echo '<option value="'.$u['id'].'" data-name="'.htmlspecialchars($u['username']).'">'.htmlspecialchars($u['username']).'</option>';
                                }
                            ?>
                        </select>
                        <input type="hidden" name="recipient_id" id="recipientId">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Тип ресурса:</label>
                        <select name="resource_type" id="resourceType" class="form-control" required>
                            <option value="coins" data-label="🪙 Монеты">🪙 Монеты</option>
                            <option value="sakura" data-label="🌸 Лепестки сакуры">🌸 Лепестки сакуры</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Количество:</label>
                        <input type="number" name="amount" id="transferAmountInput" class="form-control" min="1" placeholder="Введите сумму" required>
                    </div>
                    <button type="submit" name="submit" class="btn"><span class="btn-icon">📤</span>Перевести</button>
                </form>
            </section>

            <section id="promocode" class="settings-card hidden">
                <h3><span class="card-icon">🎁</span>Промокоды</h3>
                <form id="promoForm" action="promo" method="post">
                    <div class="form-group">
                        <label class="form-label">Введите промокод:</label>
                        <input type="text" name="promocode" class="form-control" maxlength="150" required>
                    </div>
                    <button type="submit" name="submit" class="btn"><span class="btn-icon">🎁</span>Активировать</button>
                </form>
            </section>

            <section id="delete" class="settings-card hidden">
                <h3><span class="card-icon">🗑</span>Удаление аккаунта</h3>
                <div class="warning-box">
                    <h4>⚠ Внимание! Это действие необратимо!</h4>
                    <p>Удаление аккаунта приведет к:</p>
                    <ul>
                        <li>Безвозвратному удалению всех данных;</li>
                        <li>Потере доступа к сайту;</li>
                        <li>Удалению всех ваших материалов;</li>
                    </ul>
                    <p><strong>Подумайте, перед тем, как принять решение.</strong></p>
                </div>
                <form action="setting_core" method="post">
<input type="hidden" name="action" value="delete_account">
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="confirmDel" name="delete" value="1" required>
                            <label for="confirmDel">Я подтверждаю удаление аккаунта и осознаю последствия</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Введите пароль для подтверждения:</label>
                        <input type="password" name="password" class="form-control" required minlength="8" maxlength="15" placeholder="Ваш пароль">
                    </div>
                    <button type="submit" name="submit" class="btn btn-danger"><span class="btn-icon">🗑</span>Удалить аккаунт</button>
                </form>
            </section>

            <?php if (isset($_SESSION['error']) || isset($_SESSION['great'])): ?>
            <div class="settings-card">
                <h3><span class="card-icon">📢</span>Системные уведомления</h3>
                <?php
                if(isset($_SESSION['error'])) {
                    echo "<div class='warning-box'>" . htmlspecialchars($_SESSION['error']) . "</div>";
                    unset($_SESSION['error']);
                }
                if(isset($_SESSION['great'])) {
                    echo "<div class='success-box'>" . htmlspecialchars($_SESSION['great']) . "</div>";
                    unset($_SESSION['great']);
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="./js/setting2.js"></script>
</body>
</html>
<?php 
mysqli_close($conn);
session_write_close();
?>



