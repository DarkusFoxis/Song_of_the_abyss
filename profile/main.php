<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : ''; ?></title>
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../style/style_profile.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/jquery-3.7.1.min.js"></script>
</head>
<body>
<div class="navbar">
    <a href="../index">Назад</a>
    <?php if (isset($_SESSION['user'])): ?>
        <a href="./logout" class="link" style="float: right;">Выйти</a>
    <?php else: ?>
        <a href="./login" class="link" style="float: right;">Вход</a> 
        <a href="./registration" class="link" style="float: right;">Регистрация</a>
    <?php endif; ?>
</div>
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="header">
                    <h3>Профиль</h3>
                </div>
                <?php if(isset($_SESSION['error'])): ?>
                    <p class="error"><?php echo $_SESSION['error']; ?></p>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <?php if(isset($_SESSION['great'])): ?>
                    <p class="success"><?php echo htmlspecialchars($_SESSION['great'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php unset($_SESSION['great']); ?>
                <?php endif; ?>
                <div class="profile-section">
                    <?php
                    require_once '../template/conn.php';
                    $conn = mysqli_connect($host, $log, $password_sql, $database);
                    if(!$conn){
                        echo "<p class='error'>Ошибка соединения с базой данных.</p>";
                    } else {
                        if (isset($_SESSION['user'])) {
                            $login = mysqli_real_escape_string($conn, $_SESSION['user']);
                            $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
                            $result = $conn->query($user_query);
                            if ($result && $result->num_rows > 0) {
                                $user = $result->fetch_assoc();
                                $permissions = htmlspecialchars($user["permissions"], ENT_QUOTES, 'UTF-8');
                                $permlvl = $user["lvl"];
                                $avatar = htmlspecialchars($user["avatar"], ENT_QUOTES, 'UTF-8');
                                $id = $user['id'];
                                $donate_amount = $user['donate'];
                                if ($donate_amount < 100) {
                                    $border_color = '#33004b';
                                } else if ($donate_amount < 1000) {
                                    $border_color = '#005599';
                                } else if ($donate_amount < 2000) {
                                    $border_color = '#0066cc';
                                } else if ($donate_amount < 5000) {
                                    $border_color = '#ff8c00';
                                } else {
                                    $border_color = '#FFD700';
                                }
                                $invent = null;
                                $title = "Никнейм";
                                $invent_query = "SELECT * FROM invent WHERE id_user = $id";
                                $result_inv = $conn->query($invent_query);
                                if ($result_inv && $result_inv->num_rows > 0) {
                                    $invent = $result_inv->fetch_assoc();
                                    $_SESSION['xp'] = $invent['xp'];
                                    $_SESSION['xp_max'] = $invent['xp_max'];
                                    $_SESSION['inv'] = true;
                                    if ($invent['id_title'] != NULL) {
                                        $title_sql = "SELECT title FROM title WHERE id_title = " . $invent['id_title'];
                                        $title_result = mysqli_query($conn, $title_sql);
                                        if ($title_result && mysqli_num_rows($title_result) > 0) {
                                            $title_row = mysqli_fetch_assoc($title_result);
                                            $title = $title_row['title'];
                                        }
                                    } else {
                                        $title = "Никнейм";
                                    }
                                    $lvl = $invent['lvl'];
                                    $coins = $invent['coins'];
                                    $gems = $invent['gems'];
                                    $sakura = $invent['sakura'];
                                    $cases = $invent['kase'];
                                    $xp = $invent['xp'];
                                    $xp_max = $invent['xp_max'];
                                } else {
                                    $_SESSION['inv'] = false;
                                }
                                ?>
                                <div class="profile-card-modern" style="border-color: <?php echo $border_color; ?>;">
                                    <div class="profile-card-header">
                                        <div class="profile-avatar-modern">
                                            <img id="avatar" class="avatar-modern" src="./avatars/<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Аватар пользователя" style="border-color: <?php echo $border_color; ?>;">
                                        </div>
                                        <div class="profile-info-modern">
                                            <h2 class="profile-username-modern"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($user["username"], ENT_QUOTES, 'UTF-8'); ?></h2>
                                            <?php
                                            $mail_addr = '';
                                            $mail_user_query = "SELECT username FROM mail_user WHERE user_id = $id";
                                            $mail_user_result = $conn->query($mail_user_query);
                                            if ($mail_user_result && $mail_user_result->num_rows > 0) {
                                                $mail_user_row = $mail_user_result->fetch_assoc();
                                                $mail_addr = $mail_user_row['username'] . '@abyss';
                                                echo '<p style="color:#BA55D3;font-size:1.05rem;margin-bottom:4px;">Почта: <b>' . htmlspecialchars($mail_addr) . '</b></p>';
                                            }
                                            ?>
                                            <p class="profile-group-modern">Группа: <?php echo htmlspecialchars($permissions, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php if ($permlvl == 0): ?>
                                                <p class="error">Ваш аккаунт был заблокирован по следующей причине: <?php echo htmlspecialchars($user["reason"], ENT_QUOTES, 'UTF-8'); ?>. Пожалуйста, свяжитесь с администрацией.</p>
                                            <?php elseif ($permlvl == 1): ?>
                                                <p style="font-size: 0.9rem; margin-top: 10px;">Ваш аккаунт не верифицирован. Введите код из письма.</p>
                                                 <form id="email_form" class="verification-form" style="margin-top: 10px;">
                                                    <input type="text" id="email" name="email" required minlength="10" maxlength="10" class="input-text" placeholder="Код из письма" style="padding: 5px; border-radius: 5px; border: 1px solid #4a007a; background: rgba(0, 0, 0, 0.5); color: white;">
                                                    <input type="submit" id="go" value="Подтвердить" class="profile-button" style="margin-left: 5px;">
                                                </form>
                                                <button class="profile-button" id="resend_code" style="margin-top: 5px;">Отправить код ещё раз</button>
                                                <div id="verification_result" style="margin-top: 10px;"></div>
                                                <script src="../js/verification1.js"></script>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        $unread_count = 0;
                                        if (isset($id)) {
                                            $conn2 = mysqli_connect($host, $log, $password_sql, $database);
                                            if ($conn2) {
                                                $mail_user_query = "SELECT id FROM mail_user WHERE user_id = $id";
                                                $mail_user_result = $conn2->query($mail_user_query);
                                                if ($mail_user_result && $mail_user_result->num_rows > 0) {
                                                    $mail_user_row = $mail_user_result->fetch_assoc();
                                                    $mail_user_id = $mail_user_row['id'];
                                                    $unread_query = "SELECT COUNT(*) AS cnt FROM mail WHERE recipient_id = $mail_user_id AND is_read = 0";
                                                    $unread_result = $conn2->query($unread_query);
                                                    if ($unread_result && $unread_result->num_rows > 0) {
                                                        $unread_row = $unread_result->fetch_assoc();
                                                        $unread_count = (int)$unread_row['cnt'];
                                                    }
                                                }
                                                mysqli_close($conn2);
                                            }
                                        }
                                        ?>
                                        <a href="./mail/main" title="Почта!">
                                            <div class="mail" style="position:relative;">
                                                <i class="fa-regular fa-envelope"></i>
                                                <?php if ($unread_count > 0): ?>
                                                    <span style="position:absolute;top:-2px;right:-2px;background:#FFD700;color:#4a00e0;border-radius:50%;padding:2px 7px;font-size:0.95rem;font-weight:bold;box-shadow:0 2px 6px #ba55d3;"> <?= $unread_count ?> </span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                    <?php if ($permlvl > 1): ?>
                                    <div class="profile-card-content">
                                        <?php if ($invent): ?>
                                            <div class="profile-stats-grid">
                                                <div class="profile-stat-item">
                                                    <div class="profile-stat-label">Уровень</div>
                                                    <div class="profile-stat-value"><?php echo $lvl; ?></div>
                                                </div>
                                                <div class="profile-stat-item">
                                                    <div class="profile-stat-label">Монеты</div>
                                                    <div class="profile-stat-value"><?php echo number_format($coins, 0, '', ' '); ?></div>
                                                </div>
                                                <div class="profile-stat-item">
                                                    <div class="profile-stat-label">Кристаллы</div>
                                                    <div class="profile-stat-value"><?php echo $gems; ?></div>
                                                </div>
                                                <div class="profile-stat-item">
                                                    <div class="profile-stat-label">Лепестки сакуры</div>
                                                    <div class="profile-stat-value"><?php echo $sakura; ?></div>
                                                </div>
                                                <div class="profile-stat-item">
                                                    <div class="profile-stat-label">Кейсы</div>
                                                    <div class="profile-stat-value"><span class="case_count"><?php echo $cases; ?></span></div>
                                                </div>
                                                <div class="profile-stat-item">
                                                    <div class="profile-stat-label">Поддержка проекта</div>
                                                    <div class="profile-stat-value donate-value"><?php echo number_format($donate_amount, 0, '', ' '); ?> руб.</div>
                                                </div>
                                            </div>

                                            <div>
                                                <div class="profile-stat-label">Прогресс до следующего уровня</div>
                                                <div class="xp-bar-modern" title="<?php echo "$xp/$xp_max"; ?>">
                                                    <div class="xp-fill-modern" id="xp-fill-modern" style="width: <?php echo min(100, round(($xp / $xp_max) * 100)); ?>%;"></div>
                                                    <div class="xp-text-modern" id="xp-text-modern"><?php echo min(100, round(($xp / $xp_max) * 100)); ?>%</div>
                                                </div>
                                            </div>

                                            <?php if ($xp >= $xp_max):
                                                $next_lvl = $lvl + 1;
                                                $cost_coins = (390 * $next_lvl) * (($next_lvl + 10) / 10);
                                                $cost_gems = 0;
                                                if (($next_lvl % 10) == 0) {
                                                    $cost_gems = 27 * ($next_lvl / 10);
                                                }
                                            ?>
                                                <p style="margin: 15px 0 5px 0; font-size: 0.95rem;">
                                                    Достаточно опыта для повышения уровня! Необходимо:
                                                    <b><?php echo number_format($cost_coins, 0, '', ' '); ?> монет<?php if($cost_gems > 0): ?> и <?php echo $cost_gems; ?> кристаллов<?php endif; ?></b>.
                                                </p>
                                            <?php endif; ?>

                                            <div class="profile-actions-modern">
                                                 <?php if ($xp >= $xp_max): ?>
                                                    <button class="profile-button" id="lvl_up">Повысить уровень</button>
                                                <?php endif; ?>
                                                <button class="profile-button" id="get_bonus">Получить бонус</button>
                                                <button class="profile-button" id="cases">Кейсы (<span class="case_count"><?php echo $cases; ?></span>)</button>
                                                <button class="profile-button" id="showAchivment">Достижения</button>
                                            </div>
                                            <div id="loading-message" class="loading" style="margin-top: 15px; text-align: center;"></div>

                                        <?php else: ?>
                                            <div class="profile-inventory-not-found">
                                                <p>Инвентарь не найден.</p>
                                                <button class="profile-button" id="createInvent">Создать инвентарь</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div id="profileModal" class="modal">
                                    <div class="modal-content">
                                        <span class="close">&times;</span>
                                        <div id="profile-content">
                                            <h4>Достижения</h4>
                                            <p>Список достижений, которые можно получить:</p>
                                            <ul class="achievement-list">
                                                <li>Диллер: Набрать 100 очков в тетрисе казино и правильно распечатать колоду карт.</li>
                                                <li>Энтузиаст перфекционизма: Набрать 200 очков в тетрисе казино, доказав, что порядок карт в колоде имеет значение, невзирая на объяснения.</li>
                                                <li>Горе игрок: Проиграть 100 очков в тетрисе казино. КАК ОНИ В ДРУГОМ ПОРЯДКЕ?!?</li>
                                                <li>Постоянный игрок: Проиграть 200 очков в тетрисе казино. Вип-статус вы не получили, зато набрали 200 кредитов.</li>
                                                <li>Dungeon Master: ♂Tetris♂ is ♂three hundred bucks♂.</li>
                                                <li>Боб строитель: Набрать 1000 и более очков в тетрисе и пойти на стройку!</li>
                                                <li>Водила: Оседлать змею и схавать 25 блюд!</li>
                                                <li>Заклинатель змей: Заставить змей напасть на ресторан и сожрать 50 блюд.</li>
                                                <li>Затянуло меня: Воспользоваться ящиком квантовой запутанности.</li>
                                                <li>Воин Родос: <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8')?>, не забывайте ваше призвание. Наше дело ещё не подошло к концу.</li>
                                                <li>Комментатор: Прокомментировать любую запись в первый раз.</li>
                                            </ul>
                                            <p>Уникальные достижения:</p>
                                            <ul class="achievement-list">
                                                <li>Админ: Он админ, ему можно.</li>
                                                <li>Лучший модер: Спасибо за прекрасную работу.</li>
                                                <li>Кар-кар: Лучший птиц на сие сайте. Запомните!</li>
                                                <li>Старожил: Уже столько прошло времени. Много чего произошло за период существования сайта и группы. Спасибо, что ещё с нами!</li>
                                                <li>Яичный воин: Познал яичную важность и прибыл на страницу пасхи.</li>
                                                <li>Спидран по бану. Дважды: Поехали! О, повезло, повезло.</li>
                                                <li>Новогодний ангел: Окунись в снежное царство и разгадай загадку!</li>
                                            </ul>
                                            <p>Секретные достижения:</p>
                                            <ul class="achievement-list">
                                                <li>Междумирье: Где я? Спасите! Я застрял!</li>
                                                <li>Открывайте: Да свой я, свой! Пустите!</li>
                                                <li>А зачем?: Зачем я это нашёл?</li>
                                                <li>Подозрительная личность: На вашей карте замеченая странна активность...</li>
                                                <li>Король кофеина: Пытается у чайника выпросить кофе, не понимая, что чайник не делает кофе.</li>
                                            </ul>
                                            <?php 
                                                $achievements_sql = "SELECT title, description FROM achievement WHERE id_user = $id";
                                                $achievements_result = mysqli_query($conn, $achievements_sql);
                                                if ($achievements_result && mysqli_num_rows($achievements_result) > 0) {
                                                    echo '<hr><p>Ваши достижения:</p>';
                                                    echo '<div class="user-achievements">';
                                                    while ($achievement = mysqli_fetch_assoc($achievements_result)) {
                                                        echo '<p>' . htmlspecialchars($achievement["title"], ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($achievement["description"], ENT_QUOTES, 'UTF-8') . '</p>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<p>У вас пока нет достижений.</p>';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div id="caseModal" class="modal">
                                    <div class="modal-content">
                                        <span class="close">&times;</span>
                                        <div id="case-content">
                                            <h4>Кейсы</h4>
                                            <p>У вас в наличии: <span class="case_count"><?php echo $cases; ?></span> кейсов!</p>
                                            <div id="case_result"></div>
                                            <button class="button" onclick="openCase(<?php echo $id; ?>)">Открыть кейс</button>
                                            <div id="case_history" style="display: none;">
                                                <details>
                                                    <summary>История открытия:</summary>
                                                    <div id="history"></div>
                                                </details>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="profile-actions">
                                    <h4>Дополнительные действия</h4>
                                    <ul>
                                        <!--<li><a href="./get_user_data">Добровольное предоставление данных для улучшения сервиса!</a></li>-->
                                        <li><a href="./setting">Настройки</a></li>
                                        <li><a href="./search_profile">Просмотр аккаунтов</a></li>
                                        <li><a href="./shop">Рынок бездны</a></li>
                                        <li><a href="./stiker">Стикеры</a></li>
                                        <li><a href="../abyss_net/main">Abyss net-Блог</a></li>
                                        <li><a href="../feedback">Обратная связь</a></li>
                                        <li><a href="./ai/art_chat">Генерация картинок (BETA)</a></li>
                                        <li><a href="./ai/lite_ai">РП с нейросетью (BETA)</a></li>
                                        <li><a href="./ai/ai_tools_beta">Нейросеть Агент (BETA)</a></li>
                                        <?php if ($permlvl >= 6): ?>
                                            <li><a href="./promo_codes">Промокоды</a></li>
                                        <?php endif; ?>
                                        <li><a href="https://pay.cloudtips.ru/p/3c0e8e0d">Подать создателю на кофе</a></li>
                                    </ul>
                                    <?php if (!$invent): ?>
                                        <p style="text-align: center; margin-top: 15px;"><button class="button" id="createInventBottom">Получить инвентарь после сброса</button></p>
                                    <?php endif; ?>
                                </div>
                                <?php
                            } else {
                                echo "<p class='error'>Ошибка: Пользователь не найден. Возможно, произошёл сбой. <a href='logout' class='link'>Выйдите и войдите снова</a>.</p>";
                            }
                        } else {
                            echo "<p>Данных об вошедших аккаунтах нет. <a href='./login' class='link'>Войдите</a> или <a href='./registration' class='link'>зарегистрируйтесь</a>, чтобы это исправить!</p>";
                        }
                        mysqli_close($conn);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    <?php 
        unset($_SESSION['xp'], $_SESSION['xp_max'], $_SESSION['inv']); 
    ?>
    $("#showAchivment").click(function() { $("#profileModal").show(); });
    $("#cases").click(function() { $("#caseModal").show(); });
    $(".close").click(function() { $(".modal").hide(); });
    $(window).click(function(event) {
        if (event.target.id === "profileModal") $("#profileModal").hide();
        if (event.target.id === "caseModal") $("#caseModal").hide();
    });
    function performAjaxAction(action, buttonId, successCallback) {
        const $button = $(buttonId);
        const $loading = $('#loading-message');
        const originalText = $button.text();
        $loading.text('Выполнение запроса. Ожидайте...').show();
        $button.prop('disabled', true).text('Загрузка...');
        $.ajax({
            url: '../items_core',
            type: 'POST',
            data: { action: action },
            success: function(response) {
                $loading.html(response);
                if (successCallback) successCallback(response);
            },
            error: function(xhr, status, error) {
                $loading.html('Произошла ошибка. Пожалуйста, повторите попытку позже.');
                console.error("AJAX Error:", status, error);
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    $("#lvl_up").click(function() { performAjaxAction('lvl_up', '#lvl_up'); });
    $("#get_bonus").click(function() { performAjaxAction('get_bonus', '#get_bonus'); });
    const $createInventBtn = $("#createInvent, #createInventBottom");
    $createInventBtn.click(function() {
        performAjaxAction('create_invent', $createInventBtn, function(response) {
            try {
                const data = JSON.parse(response);
                $('#loading-message').html(data.message);
                if (data.reload) {
                    setTimeout(function() { location.reload(); }, 3000);
                }
            } catch (e) {
                $('#loading-message').html('Произошел сбой при обработке ответа. Перезагрузите страницу.');
                console.error("JSON Parse Error:", e);
            }
        });
    });
    function openCase(userId) {
        const $result = $('#case_result');
        const $history = $('#history');
        const $historyContainer = $('#case_history');
        const $caseCount = $('.case_count');
        $result.html('<p class="loading">Открытие кейса...</p>');
        $.ajax({
            url: '../items_core',
            type: 'POST',
            data: { action: 'open_case', user_id: userId },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $result.html(`<p class="success">${data.message}</p>`);
                        $caseCount.text(data.new_case_count);
                        if (data.history) {
                            $history.html(data.history);
                            $historyContainer.show();
                        }
                    } else {
                        $result.html(`<p class="error">${data.message}</p>`);
                    }
                } catch (e) {
                    $result.html('<p class="error">Ошибка обработки результата открытия кейса.</p>');
                    console.error("Open Case JSON Error:", e);
                }
            },
            error: function(xhr, status, error) {
                $result.html('<p class="error">Ошибка при открытии кейса. Попробуйте позже.</p>');
                console.error("Open Case AJAX Error:", status, error);
            }
        });
    };
});
</script>
<script src="./js/casefix.js"></script>
</body>
</html>
<?php 
session_write_close();
?>