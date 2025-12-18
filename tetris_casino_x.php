<?php
    session_start();
    if(!isset($_SESSION['user'])) {
        header("Location: ./profile/login");
        exit();
    }
    require_once('./template/get_user_data.php');
    
?>
<!DOCTYPE html>
<html>
<head>
	<title>Tetris Casino!</title>
	<link rel = "icon" href = "./img/food.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "./style/style_tetris_new.css">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="description" content="Это казино а не тетрис какой-то..."/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
	    .penalty-mode {
            animation: penaltyPulse 1s infinite;
        }

        #timer {
            color: white;
        }

        @keyframes penaltyPulse {
            0% { background-color: rgba(255, 0, 0, 0.1); }
            50% { background-color: rgba(255, 0, 0, 0.3); }
            100% { background-color: rgba(255, 0, 0, 0.1); }
        }
	</style>
</head>
<body>
	<div class="content-main">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="header">
						<h3 style="text-align: center; color: purple; font-family: serif;"><s>Тетрис</s>Казино X<small>(максимум казино)</small>!</h3>
					</div>
					<div id="depositModal" class="modal" style="display: block; background-color: rgba(0,0,0,0.8);">
						<div class="modal-content" style="background-color: #222; color: white; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 10px;">
							<h3 style="text-align: center;">Сделайте ставку</h3>

							<form id="depositForm">
								<div class="form-group">
									<label for="depositType">Тип ставки:</label>
									<select class="form-control" id="depositType" name="depositType">
										<option value="xp">Опыт</option>
										<option value="coins">Монеты</option>
										<option value="sakura">Лепестки сакуры</option>
										<option value="kase">Кейсы</option>
									</select>
								</div>

								<div class="form-group" id="depositAmountGroup">
									<label for="depositAmount">Ставка:</label>
									<input type="number" class="form-control" id="depositAmount" name="depositAmount" min="10" required>
								</div>

								<div class="form-check">
									<input type="checkbox" class="form-check-input" id="ageCheck" required>
									<label class="form-check-label" for="ageCheck">Мне исполнилось 18 лет</label>
								</div>

								<div class="form-check">
									<input type="checkbox" class="form-check-input" id="languageCheck" required>
									<label class="form-check-label" for="languageCheck">Я согласен(на) с тем, что в игре присутствует ненормативная лексика</label>
								</div>

								<button type="button" class="btn btn-primary btn-block mt-3" id="enterCasinoBtn">Войти в казино</button>
							</form>
						</div>
					</div>

					<div class="game-content" style="display: none;">
						<div class="score">Счёт: <span id="score">0</span></div>
						<div id="timer">Время: 00:00</div>
						<canvas width="400" height="800" id="game"></canvas><br>
						<p style="color: white; text-align: center;">
							<a href="tetris_casino_x" class="link">Reboot</a>/
							<a href="#" class="link" onclick="stopSong()">StopSong</a>/
							<a href="#" class="link" onclick="playRandomMusic()">PlaySound</a><br>
							<a href="travel" class="link">Games</a> or 
							<a href="index" class="link">Home</a> or 
							<a href="tetris" class="link">Normal mode</a>
						</p>
						<?php 
						    if(!isset($_SESSION['user'])) {
						        echo '<p style="color: white; text-align: center;">Достижения недоступны для получения.<br><a href="./profile/login" class="link">Войдите</a> для получения достижений.</p>';
						    } else {
						        echo '<p style="color: white; text-align: center;">Внимание на колоду, ' . $_SESSION['username'] . '!</p>';
						    }
						?>
						<div id='achievement'></div>
						<div id='result'></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="./js/jquery-3.7.1.min.js"></script>
	<script>
		var userResources = {
			coins: <?php echo $coin; ?>,
			sakura: <?php echo $sakura; ?>,
			kase: <?php echo $kase; ?>
		};
	</script>
	<script src="./js/tetris_casino_x_1.3.3_realese.js"></script>
</body>
</html>