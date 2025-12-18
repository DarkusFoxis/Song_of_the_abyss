<?php
    session_start();
    if (!isset($_SESSION['user'])) {
        header("Location: login");
        exit();
    }
?>
<!DOCTYPE html>
<html prefix="og:http://ogp.me/ns#">
<head>
	<title>Стикеры!</title>
	<link rel = "icon" href = "../img/icon.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "../style/style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<meta name="description" content="Время собирать стирекы! Ознакомьтесь с теми, которые вы можете найти, и в последствии заказать!"/>
	<script src="../js/jquery-3.7.1.min.js"></script>
	<style>
	    .main-container {
			display: flex;
			flex-wrap: wrap;
			gap: 20px;
		}
		.rarity-sidebar {
			flex: 0 0 250px;
			background: rgba(0,0,0,0.3);
			border-radius: 10px;
			padding: 20px;
			border: 1px solid rgba(75, 0, 130, 0.5);
		}
		.content-area {
			flex: 1;
			min-width: 300px;
		}
		.rarity-item {
			padding: 12px 15px;
			margin-bottom: 10px;
			border-radius: 8px;
			cursor: pointer;
			transition: all 0.3s ease;
			display: flex;
			align-items: center;
		}
		.rarity-item:hover {
			background: rgba(255,255,255,0.1);
		}
		.rarity-item.active {
			background: rgba(75, 0, 130, 0.5);
			box-shadow: 0 0 10px rgba(147, 112, 219, 0.6);
		}
		.rarity-indicator {
			width: 20px;
			height: 20px;
			border-radius: 50%;
			margin-right: 12px;
			display: inline-block;
		}
		.cards {
			display: flex;
			flex-wrap: wrap;
			gap: 7px;
			justify-content: flex-start;
		}
		.card {
			border: 1px solid #4B0082;
			border-radius: 10px;
			background-color: rgba(255,255,255,0.1);
			width: calc(25% - 6px);
			box-sizing: border-box;
			overflow: hidden;
			transition: transform 0.3s ease;
		}
		.card:hover {
			transform: translateY(-5px);
			box-shadow: 0 5px 15px rgba(75, 0, 130, 0.4);
		}
		.card-content {
			padding: 10px;
		}
		.card-media {
			width: 100%;
			height: 200px;
			display: flex;
			align-items: center;
			justify-content: center;
			overflow: hidden;
			background: rgba(0,0,0,0.2);
		}
		.card-media img,
		.card-media video {
			max-width: 100%;
			max-height: 100%;
			object-fit: contain;
		}
	    .description {
		    height: 130px;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 6;
			-webkit-box-orient: vertical;
			font-size: 0.9em;
			margin: 10px 0;
	    }
		.sticker-name {
			font-weight: bold;
			font-size: 1.1em;
			margin-bottom: 5px;
			color: #E0B0FF;
		}
		.sticker-rarity {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 15px;
			font-size: 0.8em;
			margin-bottom: 8px;
		}
		.rarity-com {background-color: #808080;}
		.rarity-rar {background-color: #1E90FF;}
		.rarity-epic {background-color: #9370DB;}
		.rarity-leg {background-color: #FF4500;}
		.rarity-myst {background-color: #DC143C;}
		.sticker-count {
			font-size: 0.9em;
			margin: 5px 0;
		}
		button {
            border: none;
            border-radius: 35px;
            padding: 7px 10px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            cursor: pointer;
            color: white;
            background-color: #4B0082;
            margin: 0 3px 5px 3px;
			transition: background-color 0.3s;
        }
		button:hover {
			background-color: #5A1A9A;
		}
        .buy_button {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
			margin: 20px 0;
        }
        #result{
            color: yellow;
			text-align: center;
			margin: 15px 0;
			font-weight: bold;
        }
		.search-box {
			position: relative;
			margin-bottom: 20px;
		}
		.search-box input {
			width: 100%;
			padding: 10px 15px;
			border-radius: 30px;
			border: 1px solid #4B0082;
			background: rgba(0,0,0,0.2);
			color: white;
			font-size: 1em;
		}
		.search-box input:focus {
			outline: none;
			border-color: #9370DB;
			box-shadow: 0 0 5px rgba(147, 112, 219, 0.5);
		}
		.rarity-title {
			font-size: 1.2em;
			margin-bottom: 15px;
			padding-bottom: 10px;
			border-bottom: 1px solid rgba(255,255,255,0.2);
			color: #E0B0FF;
		}
		@media (max-width: 1200px) {
			.card {
				width: calc(33.333% - 15px);
			}
		}
		@media (max-width: 992px) {
			.rarity-sidebar {
				flex: 0 0 100%;
				order: 1;
			}
			.content-area {
				flex: 0 0 100%;
				order: 2;
			}
		}
		@media (max-width: 768px) {
			.card {
				width: calc(50% - 15px);
			}
		}
		@media (max-width: 480px) {
			.card {
				width: 100%;
			}
		}
	</style>
</head>
<body>
<div class="navbar">
	<a href="./main">Home</a>
</div>
<div class="content-main">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<div class="header">
					<h3>Стикеры!</h3>
				</div>
				<p>Добро пожаловать на страницу со стикерами! Собирайте их, участвуя в различных активностях на сайте, или просто покупай по кнопкам ниже. Давай проясним вопрос: Стикеры бывают 5-ти редкостей, и имеют его идентификатор. Расшифруем:</p>
				<ul>
				    <li>com - Обычный (12 уникальных).</li>
				    <li>rar - Редкий (11 уникальных).</li>
				    <li>epic - Эпический (10 уникальных).</li>
				    <li>leg - Легендарный (10 уникальных).</li>
				    <li>myst - Мистический (8 уникальных).</li>
				    <li>Всего - 51 уникальный стикер!</li>
				</ul>
				<p>У каждой редкости свой шанс получения и своя стоимость, поэтому будь аккуратнее, покупая рандомный стикер. Общее количество стикеров может меняться, поэтому собирай и копи. Куда их тратить? Это уже вопрос времени. Стоимость стикеров может меняться, как и стоимость лепестков, поэтому однажды это может быть хорошим вложением.<br>А пока желаю удачи!</p>
				<p class="buy_button"><button onclick='stikerByu("com")'>Обычный(100 лепестков)</button> <button onclick='stikerByu("rar")'>Редкий(250 лепестков)</button> <button onclick='stikerByu("epic")'>Эпический(550 лепестков)</button> <button onclick='stikerByu("leg")'>Легендарный(1150 лепестков)</button> <button onclick='stikerByu("myst")'>Мистический(2700 лепестков)</button> <button onclick='stikerByu("rnd")'>Рандом(270 лепестков)</button></p>
				<p id="result"></p>
				
				<div class="main-container">
					<div class="rarity-sidebar">
						<div class="rarity-title">Фильтр по редкости</div>
						<div class="rarity-item active" data-rarity="all">
							<span class="rarity-indicator" style="background: linear-gradient(135deg, #808080, #1E90FF, #9370DB, #FF4500, #DC143C);"></span>
							Все стикеры
						</div>
						<div class="rarity-item" data-rarity="com">
							<span class="rarity-indicator" style="background-color:#808080"></span>
							Обычные
						</div>
						<div class="rarity-item" data-rarity="rar">
							<span class="rarity-indicator" style="background-color:#1E90FF"></span>
							Редкие
						</div>
						<div class="rarity-item" data-rarity="epic">
							<span class="rarity-indicator" style="background-color:#9370DB"></span>
							Эпические
						</div>
						<div class="rarity-item" data-rarity="leg">
							<span class="rarity-indicator" style="background-color:#FF4500"></span>
							Легендарные
						</div>
						<div class="rarity-item" data-rarity="myst">
							<span class="rarity-indicator" style="background-color:#DC143C"></span>
							Мистические
						</div>
						<div class="search-box">
							<input type="text" id="searchInput" placeholder="Поиск по названию...">
						</div>
					</div>
					<div class="content-area">
						<div class="cards" id="stiker_container"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
    $(document).ready(function() {
        loadStiker("first");
		$('.rarity-item').click(function() {
			$('.rarity-item').removeClass('active');
			$(this).addClass('active');
			loadStiker();
		});
		$('#searchInput').on('input', function() {
			loadStiker();
		});
    });
	function getActiveRarity() {
		return $('.rarity-item.active').data('rarity');
	}
	function getSearchText() {
		return $('#searchInput').val();
	}
    function loadStiker(action = ""){
        var rarity = getActiveRarity();
        var rarities = [];
        if (rarity !== 'all') {
            rarities = [rarity];
        }
        $.ajax({
            url: './stiker_loader',
            type: 'GET',
            data: {
				action: action,
				search: getSearchText(),
				rarities: rarities
			},
            success: function(response) {
                $("#stiker_container").html(response);
            },
            error: function(xhr, status, error) {
                alert('Произошла ошибка при загрузке стикеров.');
                console.log(error);
            }
        });
    }
    function stikerByu(rarity){
        $.ajax({
            url: '../items_core',
            type: 'POST',
            data: {action: "stiker_add", rarity:rarity},
            success: function(response) {
                $("#result").html(response);
                loadStiker();
            },
            error: function(xhr, status, error) {
                alert('Произошла ошибка при покупке стикера.');
                console.log(error);
            }
        });
    }
	function sellStikers(id) {
		$.ajax({
            url: '../items_core',
            type: 'POST',
            data: {action: "stiker_sell", id: id},
            success: function(response) {
                $("#result").html(response);
                loadStiker();
            },
            error: function(xhr, status, error) {
                alert('Произошла ошибка при продаже стикера.');
                console.log(error);
            }
        });
	}
</script>
</body>
</html>
<?php 
session_write_close();
?>