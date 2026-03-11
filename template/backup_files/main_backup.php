<?php 
session_start();
?>
<!DOCTYPE html>
<html>
<head>
<title>Profile <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?></title>
<link rel = "icon" href = "../img/icon.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="../style/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="../js/jquery-3.7.1.min.js"></script>
<style>
    p {
        margin-bottom: 10px;
    }
    .avatar {
        width: 175px;
        height: 175px;
        
    }
    .content-main {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .profile-section {
        width: 80%;
        max-width: 800px;
        margin: 20px auto;
    }
    .profile-card {
        display: flex;
        background-color: rgba(0, 0, 0, 0.5);
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.8);
        margin: 5px;
    }
    .profile-info {
        margin-left: 10px;
    }
    .profile-actions {
        margin-top: 20px;
        text-align: center;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.7);
    }
   .modal-content {
        background: linear-gradient(135deg, #1a002b, #30004b);
        margin: 15% auto;
        margin-top: 0px;
        padding: 20px;
        border: 2px solid #4a007a;
        width: 85%;
        border-radius: 8px;
        color: white;
        position: relative;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    }
    .modal-content::before{
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, transparent 50%, rgba(100, 0, 190, 0.1) 80%, rgba(74, 0, 122, 0.2) 100%);
        pointer-events: none;
        border-radius: 8px;
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }
    .close:hover, .close:focus {
        color: white;
        text-decoration: none;
        cursor: pointer;
    }
    .xp-bar {
        position: relative;
        height: 20px;
        background: #420;
        max-width: 475px;
        width: 100%;
        border-radius: 35px;
        margin-bottom: 10px;
    }
    .xp-fill {
        position: absolute;
        border-radius: 35px;
        top: 0;
        left: 0;
        height: 100%;
        background: linear-gradient(90deg, rgba(255,118,36,1) 0%, rgba(64,16,190,1) 100%);
    }
    .xp-text {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        text-align: center;
        font-size: 13px;
        color: #ffe2aa;
        text-shadow:
            1px 1px 1px #000,
            -1px 1px 1px #000,
            1px -1px 1px #000,
            -1px -1px 1px #000;
    }
    @media (max-width: 520px){
        #telegram {
            width: 182px;
            margin-bottom: 5px;
        }
        .avatar {
            width: 100px;
            height: 100px;
        }
		.profile-section {
            width: 100%;
            margin: auto;
        }
	}
	.donate {
	    border-radius: 20px;
	    padding: 5px;
	    background: radial-gradient(circle,rgba(238, 174, 202, 0.1) 0%, rgba(148, 187, 233, 0.4) 100%);
	}
</style>
</head>
<body>
<div class="navbar">
    <a href="../index">Back</a>
    <?php if (isset($_SESSION['user'])): ?>
        <a href="./logout" class="link" style="float: right;">Выйти из аккаунта</a>
    <?php else: ?>
        <a href="./login" class="link" style="float: right;">Вход</a> <a href="./registration" class="link" style="float: right;">Регистрация</a>
    <?php endif; ?>
</div>
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="header">
                    <h3>Профиль</h3>
                </div>
                <?php
                    if(isset($_SESSION['error'])) {
                        echo "<p style='color: red; background-color: black;'>" . $_SESSION['error'] . "</p>";
                        unset($_SESSION['error']);
                    }
                    if(isset($_SESSION['great'])) {
                        echo "<p style='color: green; background-color: black;'>" . $_SESSION['great'] . "</p>";
                        unset($_SESSION['great']);
                    }
                ?>
                <div class="profile-section">
                    <?php
                    require_once '../template/conn.php';
                    $conn = mysqli_connect($host, $log, $password_sql, $database);
                    if(!$conn){
                        echo("Ошибка соединения.");
                    }
                    if (isset($_SESSION['user'])) {
                        $login = $_SESSION['user'];
                        $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
                        $result = $conn->query($user_query);
                        if ($result->num_rows > 0) {
                            $user = $result->fetch_assoc();
                            $permissions = $user["permissions"];
                            $permlvl = $user["lvl"];
                            $avatar = $user["avatar"];
                            $id = $user['id'];
                            if ($user['donate'] < 100) {
                                $border_color = '#33004b';
                            } else if ($user['donate'] < 1000) {
                                $border_color = '#005599';
                            } else if ($user['donate'] < 2000) {
                                $border_color = '#0066cc';
                            } else if ($user['donate'] < 5000) {
                                $border_color = '#ff8c00';
                            } else {
                                $border_color = '#FFD700';
                            }
                            $invent_query = "SELECT * FROM invent WHERE id_user = '$id'";
                            $result_inv = $conn->query($invent_query);
                            if ($result_inv->num_rows > 0) {
                                $invent = $result_inv->fetch_assoc();
                                $lvl = $invent['lvl'];
                                $xp = $invent['xp'];
                                $xp_max = $invent['xp_max'];
                                $_SESSION['xp'] = $xp;
                                $_SESSION['xp_max'] = $xp_max;
                                $_SESSION['inv'] = true;
                                $title_sql = "SELECT title FROM title WHERE id_title = (SELECT id_title FROM invent WHERE id_user = {$user['id']})";
                                $title_result = mysqli_query($conn, $title_sql);
                                $title = (mysqli_num_rows($title_result) > 0) ? mysqli_fetch_assoc($title_result)['title'] : "Никнейм";
                            } else {
                                $_SESSION['inv'] = false;
                                $title = "Никнейм";
                            }
                            ?>
                            <div class="profile-card" style="border: 1px solid <?php echo $border_color; ?>;">
                                <div class="profile-avatar">
                                    <img id="avatar" class="avatar" src="./avatars/<?php echo $avatar; ?>" alt="Аватар">
                                </div>
                                <div class="profile-info">
                                    <p class="profile-username"><?php echo $title . ': ' . $user["username"]; ?></p>
                                    <p class="profile-group">Группа: <?php echo $permissions; ?></p>
                                    <?php if ($permlvl > 1):?>
                                        <?php if (isset($_SESSION['inv']) && $_SESSION['inv']): ?>
                                            <div class="profile-inventory">
                                                <p>Уровень: <?php echo $lvl; ?></p>
                                                <div class="xp-bar" title="<?php echo $xp . '/' . $xp_max; ?>">
                                                    <div class="xp-fill"></div>
                                                    <div class="xp-text">0%</div>
                                                </div>
                                                <p>Монеты: <?php echo $invent['coins']; ?></p>
                                                <p>Кристаллы: <?php echo $invent['gems']; ?></p>
                                                <p>Лепестков сакуры: <?php echo $invent['sakura']; ?></p>
                                                <p class="donate">Поддержка проекта: <?php echo $user['donate']; ?> руб.</p>

                                                <?php if ($xp >= $xp_max): ?>
                                                    <p>Достаточно опыта для повышения уровня! Для повышенния уровня необходимо: 
                                                        <?php if ((($lvl + 1) % 10) == 0):?>
                                                            <?php echo (390*($lvl+1))*(($lvl+10)/10);?> монет и <?php echo 27*(($lvl+1)/10);?> кристаллов.
                                                        <?php else:?>
                                                            <?php echo (390*($lvl+1))*(($lvl+10)/10);?> монет.
                                                        <?php endif; ?>
                                                    </p>
                                                    <button class="button" onclick="lvlUp()" id="lvl_up"style="margin-bottom: 10px;">Повысить уровень</button>
                                                <?php endif; ?>
                                            </div>
                                            <button class="button" id="get_bonus">Получить бонус</button>
                                            <button class="button" id="сases">Кейсы (<span class="case_count"><?php echo $invent['kase']; ?></span>)</button>
                                            <button class="button" id="showAchivment">Достижения</button>
                                        <?php endif; ?>
                                        <div id="loading-message" style="display: none;">Выполнение запроса. Ожидайте...</div>
                                    <?php else: if ($permlvl == 0): ?>
                                        <p style='color: red; background-color: black;'>Ваш аккаунт был заблокирован по следующей причине: <?php echo $user["reason"] ?>. Пожалуйста, свяжитесь с администрацией.</p>
                                    <?php else: if ($permlvl == 1): ?>
                                        <p>Ваш аккаунт не верифицирован. Чтобы это исправить, впишите в поле ниже <b>код из письма</b>, отправленное на указанную вами при регистрации почту. Так же, вы можете написать в личные сообщения создателю для верификации.</p>
                                        <p>Писать в лс в телеграм: @DarkusFoxis</p>
                                        <p>Внимание: Если вам на почту не пришёл код, нажмите на кнопку "Отправить код ещё раз". Если вновь не пришёл, возможно с вашей почтой наблюдаются проблемы. Сообщите создателю об этом.</p>
                                        <form id='email_form'>
                                        <input type='text' id='email' name='email' required minlength='10' maxlength='10' class='input-text' plaseholder='Код из письма'>
                                        <input type='submit' id='go' value='Подтвердить' class='button'>
                                        </form>
                                        <button class='button' id="resend_code">Отправить код ещё раз</button>
                                        <p>Учтите, что если ваш аккаунт не будет верифицирован в течении недели, он будет удалён.</p>
                                        <div id='verification_result' style='color: gren; background-color: black;'></div>
                                        <script src='../js/verification1.js'></script>
                                        <?php endif;?>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div id="profileModal" class="modal">
                                <div class="modal-content">
                                    <span class="close">&times;</span>
                                    <div id="profile-content">
                                    <p>Список достижений, которые можно получить:</p>
                                        <ul>
                                            <li>Диллер: Набрать 100 очков в тетрисе казино, и правильно распечатать колоду карт.</li>
                                            <li>Энтузиаст перфекционизма: Набрать 200 очков в тетрисе казино доказав, что порядок карт в колоде имеет значение, невзирая на объяснения.</li>
                                            <li>Горе игрок: Проиграть 100 очков в тетрисе казино. КАК ОНИ В ДРУГОМ ПОРЯДКЕ?!?</li>
                                            <li>Постоянный игрок: Проиграть 200 очков в тетрисе казино. Вип статус вы не получили, за то набрали 200 кредитов.</li>
                                            <li>Dungeon Master: ♂Tetris♂ is ♂three hundred bucks♂.</li>
                                            <li>Боб строитель: Набрать 1000 и более очков в тетрисе, и пойти на стройку!</li>
                                            <li>Водила: Оседлать змею, и схавать 25 блюд!</li>
                                            <li>Заклинатель змей: Заставить змей напасть на ресторан, и сожрать 50 блюд.</li>
                                            <li>Затянуло меня: Воспользоваться ящиком квантовой запутанности.</li>
                                            <li>Воин Родос: <?php echo $_SESSION['username']?>, не забывайте ваше призвание. Наше дело ещё не подошло к концу.</li>
                                            <li>Комментатор: Прокомментировать любую запись в первый раз.</li>
                                        </ul>
                                        <p>Уникальные достижения:</p>
                                        <ul>
                                            <li>Админ: Он админ, ему можно.</li>
                                            <li>Лучший модер: Спасибо за прекрасную работу.</li>
                                            <li>Кар-кар: Лучший птиц на сие сайте. Запомните!</li>
                                            <li>Старожил: Уже столько прошло времени. Много чего произошло за период существования сайта и группы. Спасибо, что ещё с нами!</li>
                                            <li>Яичный воин: Познал яичную важность, и прибыл на страницу пасхи.</li>
                                            <li>Спидран по бану. Дважды: Поехали! О, повезло, повезло.</li>
                                            <li>Новогодний ангел: Окунись в снежное царство, и разгадай загадку!</li>
                                        </ul>
                                        <p>Секретные достижения:</p>
                                        <ul>
                                            <li>Междумирье: Где я? Спасите! Я застрял!</li>
                                            <li>Открывайте: Да свой я, свой! Пустите!</li>
                                            <li>А зачем?: Зачем я это нашёл?</li>
                                            <li>Подозрительная личность: На вашей карте замеченая странная активность...</li>
                                        </ul>
                                        <?php 
                                            $achievements_sql = "SELECT title, description FROM achievement WHERE id_user = " . $id;
                                            $achievements_result = mysqli_query($conn, $achievements_sql);

                                            if (mysqli_num_rows($achievements_result) > 0) {
                                                echo '<hr><p>Ваши достижения:</p>';
                                                if ($permlvl >= 2) {
                                                    while ($achievement = mysqli_fetch_assoc($achievements_result)) {
                                                        echo '<p style="background: linear-gradient(90deg, rgba(93,25,138,1) 0%, rgba(135,11,11,1) 50%, rgba(190,118,16,1) 100%);">' . $achievement["title"] . ': ' . $achievement["description"] . '</p>';
                                                    }
                                                } else {
                                                    if ($permlvl == 0) { 
                                                        echo '<p>К сожалению, вы заблокированны на проекте. Вам не доступны достижения, и другие возможности зарегистрированных пользователей.</p>'; 
                                                    } else {
                                                        echo '<p>Вы не верифицированны.</p>'; 
                                                    }
                                                }
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
                                        <p>Кейсы!</p>
                                        <p>У вас в наличии: <span class="case_count"><?php echo isset($invent) ? $invent['kase'] : 0; ?></span> кейсов!</p>
                                        <div id="case_result"></div>
                                        <button id="open_case" onclick="openCase(<?php echo isset($id) ? $id : 0; ?>)" class="button">Открыть кейс</button>
                                        <div id="case_history" style="display: none;"><details><summary>История открытия:</summary><div id="history"></div></details></div>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-actions">
                                <p><a href='./get_user_data' class='link'>Добровольное предоставление данных для улучшение сервиса!</a></p>
                                <p><a href="./shop" class="link">Рынок бездны</a> | <a href="./stiker" class="link">Стикеры</a> | <a href="../abyss_net/main" class="link">Abyss net-Блог</a></p>
                                <p><a href="./setting" class="link">Настройки</a> | <a href="./search_profile" class="link">Просмотр аккаунтов (В разработке)</a></p>
                                <p><a href="../feedback" class="link">Обратная связь</a><?php if ($permlvl >= 6) echo ' | <a href="./promo_codes" class="link">Промокоды</a>'; ?></p>
                                <p><a class="link" href="https://pay.cloudtips.ru/p/3c0e8e0d">Подать создателю на кофе</a></p>
                                <?php if (!isset($_SESSION['inv']) || !$_SESSION['inv']): ?>
                                    <p>Получите инвентарь после сброса! <button class="button" id="createInvent">Активировать</button></p>
                                <?php endif; ?>
                            </div>
                            <?php
                        } else {
                            header("Location: logout");
                        }
                    } else {
                        echo "<p>Данных об вошедших аккаунтах нет. Войдите или зарегистрируйтесь, чтобы это исправить!</p>";
                    }
                    mysqli_close($conn);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function lvlUp() {
    $.ajax({
        url: '../items_core',
        type: 'POST',
        data: { action : 'lvl_up'},
        beforeSend: function() {
            $('#loading-message').show();
        },
        success: function(response) {
            $('#loading-message').html(response);
        },
        error: function(xhr, status, error) {
          $('#loading-message').html('Произошла ошибка при повышении уровня. Пожалуйста, повторите попытку позже.');
          console.error(error);
        }
    });
}
$(document).ready(function() {
    $("#showAchivment").click(function() {
        $("#profileModal").show();
    });
    $("#сases").click(function() {
        $("#caseModal").show();
    });
    $(".close").click(function() {
        $(".modal").hide();
    });
    $(window).click(function(event) {
        if ($(event.target).is("#profileModal")) {
            $("#profileModal").hide();
        }
    });
    $(window).click(function(event) {
        if ($(event.target).is("#caseModal")) {
            $("#caseModal").hide();
        }
    });
    $("#createInvent").click(function() {
        $.ajax({
            url: '../items_core',
            type: 'POST',
            data: { action : 'create_invent'},
            beforeSend: function() {
                $('#loading-message').show();
            },
            success: function(response) {
                try {
                    response = JSON.parse(response);
                    
                    if (response.success) {
                      $('#loading-message').html(response.message);
            
                      if (response.reload) {
                        setTimeout(function() {
                          location.reload();
                        }, 3000);
                      }
                    } else {
                      $('#loading-message').html(response.message);
                    }
                  } catch (e) {
                    $('#loading-message').html('Произошла ошибка. Пожалуйста, повторите попытку.');
                    console.error(e);
                }
            },
            error: function(xhr, status, error) {
              $('#loading-message').html('Произошла ошибка при создании инвентаря. Пожалуйста, повторите попытку.');
              console.error(error);
            }
        });
    });
    $("#get_bonus").click(function() {
        $.ajax({
            url: '../items_core',
            type: 'POST',
            data: { action : 'get_bonus'},
            beforeSend: function() {
                $('#loading-message').show();
            },
            success: function(response) {
                $('#loading-message').html(response);
            },
            error: function(xhr, status, error) {
              $('#loading-message').html('Произошла ошибка при получении бонуса. Пожалуйста, повторите попытку позже.');
              console.error(error);
            }
        });
    });
    <?php
        if ($_SESSION['inv']){
            echo "function xp_bar_update() { \n";
            echo "let percent = 100 * " . $_SESSION['xp'] . "/" . $_SESSION['xp_max'] . "\n";
            unset($_SESSION["xp"], $_SESSION['xp_max']);
            echo "if (percent > 100) {\n";
            echo "$('.xp-fill').css('width', 100 + '%');\n";
            echo "} else { \n";
            echo "$('.xp-fill').css('width', percent + '%');\n";
            echo "}\n";
            echo "$('.xp-text').text( Math.round(percent) + ' %' );\n";
            echo "}\n";
            echo 'xp_bar_update();';
        }
        unset($_SESSION["inv"]);
    ?>
});
</script>
<script src="./js/casefix.js"></script>
</body>
</html>