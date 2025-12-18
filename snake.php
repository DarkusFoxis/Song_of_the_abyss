<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Snake Game</title>
    <link rel="icon" href="./img/food.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./style/style_snake.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="О да, наконец-то змейка!"/>
</head>
<body>
	<div class="navbar">
	    <a href="travel">Game</a>
		<a href="index">Home</a>
	</div>
	<div class="content-main">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="header">
                        <h3 style="text-align: center; color: purple; font-family: serif;">Змейка!</h3>
                    </div>
                    <div id='achievement'></div>
                    <canvas id="game" width="608" height="608">Извините, но игра скорее всего не сможет работать у вас... Сожалеем.</canvas>
                    <div class="start-text">Нажмите клавишу или свайп для начала</div>
					<?php 
					    if(!isset($_SESSION['user'])) {
					        echo '<p>Достижения недоступны для получения.<br><a href="./profile/login" class="link">Войдите</a> для получения достижений.</p>';
					    } else {
					        echo '<p> Слезте с змеи, ' . $_SESSION['username'] . '!</p>';
					    }
					?>
					<br>
					<details><summary>Управление:</summary>
					<p>Телефон/ПК</p>
					<p>Свапы/WASD или стрелочки - движение.</p>
					<p>Двойной тап/Пробел - Пауза</p></details>
					<div id='coin'>Монет на выводе: 0</div>
                    <div id='withdrawal_coin'></div>
                    <div id='result'></div>
				</div>
			</div>
		</div>
	</div>
	<script src="./js/jquery-3.7.1.min.js"></script>
	<script src="./js/snake1.js"></script>
</body>
</html>
<?php 
session_write_close();
?>