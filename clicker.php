<?php
session_start();

if(!isset($_SESSION['user'])) {
    header("Location: ./profile/login");
} else {
    require_once './template/conn.php';

    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        echo "Ошибка соединения: " . mysqli_connect_error();
        exit;
    }
    
    $login = $_SESSION['user'];
    $user_query = "SELECT * FROM users WHERE login = '$login'";
    $result = $conn->query($user_query);
    $user = $result -> fetch_assoc();
    $userId = $user['id'];
    $query = "SELECT * FROM invent WHERE id_user = '$userId'";
    $result = mysqli_query($conn, $query);
    if ($user['permissions'] == 'BANNED') {
        $_SESSION["perm_error"] = "Вы заблокированы на проекте, поэтому возможности ограничены.";
        header("Location: 403");
        exit;
    }
    if (mysqli_num_rows($result) === 0) {
        $_SESSION["perm_error"] = "У вас не активирован инвентарь. Пожалуйста, создайте его, и наберите 2-ой уровень.";
        header("Location: 403");
        exit;
    } else {
        $inv_data = $result->fetch_assoc();
        if ($inv_data['lvl'] < 2) {
            $_SESSION["perm_error"] = "У вас маленький уровень, для доступа к этой странице. Пожалуйста, наберите 2-ой уровень.";
            header("Location: 403");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Miner</title>
	<link rel = "icon" href = "./img/flower.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "./style/style_cliker_new.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Кликер? Да, он самый."/>
</head>
<body>
    <div class="content-main">
		<div class="container">
			<div class="row">
		        <div id="kicker">
                    <img id="flower" src='./img/flower.png' width="200" height="200" style="object-fit: cover; ">
                    <div id="info">
                        <span>Coins: <span id="coins">0</span></span><br>
                        <span>XP: <span id="xp">0</span></span><br>
                        <span>Gems: <span id="gems">0</span></span><br>
                        <button id="send">Send</button> <a href='index' class='link'>Home</a><br>
                        <div id="result"></div>
                    </div>
                    <div class="upgrade-container">
                        <div id="coin-upgrade"></div>
                        <div id="coin-upgrade-price"></div>
                        <button id="coin-upgrade-btn">Улучшить</button>
                        <div id="xp-upgrade"></div>
                        <div id="xp-upgrade-price"></div>
                        <button id="xp-upgrade-btn">Улучшить</button>
                        <div id="click-delay-upgrade"></div>
                        <div id="click-delay-upgrade-price"></div>
                        <button id="click-delay-upgrade-btn">Улучшить</button>
                    </div>
                </div>
                <div id="custom-context-menu" class="context-menu">
                    <ul>
                        <li>
                            <div class="context-menu-title">Правило майнинга:</div>
                            <ul>
                            <li>1) Не покидайте текущую вкладку, или ваши накопленные ресурсы обнулятся;</li>
                            <li>2) В случае ошибок, пишите администратору.</li>
                            <li>3) Пожалуйста, не используйте автокликеры. Будьте честны.</li>
                            <li>4) Веселитесь, но в рамках разумного.</li>
                        </ul>
                        </li>
                    </ul>
                </div>
			</div>
		</div>
	</div>
	<script src="./js/jquery-3.7.1.min.js"></script>
	<script src="./js/cliker1.2.5_1_release.js"></script>
</body>
</html>
<?php 
session_write_close();
?>