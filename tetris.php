<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Tetris</title>
	<link rel = "icon" href = "./img/food.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "./style/style_tetris_new.css">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="description" content="Время понастольгоровать по тетрису!"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
	<div class="content-main">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="header">
						<h3 style="text-align: center; color: purple; font-family: serif;">Тетрис!</h3>
					</div>
					<div class="score">Счёт: <span id="score">0</span></div>
					<canvas width="320" height="640" id="game"></canvas><br>
					<p style="color: white; text-align: center;"><a href="tetris" class="link">Reboot</a><br><a href="travel" class="link">Games</a> or <a href="index" class="link">Home</a> or <a href="tetris_casino" class="link">Casino mode</a></p>
					<?php
					    if(!isset($_SESSION['user'])) {
					        echo '<p style="color: white; text-align: center;">Достижения недоступны для получения.<br><a href="./profile/login" class="link">Войдите</a> для получения достижений.</p>';
					    } else {
					        echo '<p style="color: white; text-align: center;"> Удачи, ' . $_SESSION['username'] . '!</p>';
					    }
					?>
				<div id='achievement'></div>
				<div id='result'></div>
				</div>
				<div id="custom-context-menu" class="context-menu">
				    <ul>
                        <li><div>Извините, но мы забрали ваше меню.<br>Нам не дали деняг за него :(</div></li>
                    </ul>
                </div>
			</div>
		</div>
	</div>
	<script src="./js/jquery-3.7.1.min.js"></script>
	<script src="./js/tetris1.2.2.js"></script>
</body>
</html>
<?php 
session_write_close();
?>