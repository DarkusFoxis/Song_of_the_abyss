<!DOCTYPE html>
<html prefix="og:http://ogp.me/ns#">
<head>
	<title>Arknights</title>
	<link rel = "icon" href = "../img/ark_icon.jpg">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "../style/ark_style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
	    .cards {
			display: flex;
			align-items: center;
			justify-content: center;
			flex-wrap: wrap;
			gap: 10px;
		}

		.card {
			flex: 0 0 calc(33% - 20px);
			border: 2px solid #4B0082;
			border-radius: 5px;
			padding: 15px;
			background-color: rgb(255,255,255,0.1);
			transition: border-color 0.3s ease;
		}

		.card:hover {
		  border-color: #9B0000;
		}

		.card img {
			max-width: 100%;
			height: auto;
			border-radius: 5px;
			margin-bottom: 10px;
		}
		#close {
		    opacity: 1;
		}
	</style>
</head>
<body>
	<div class="navbar">
		<a href="../fanfs">Back</a>
		<div id='special'></div>
		<a id='musicBtn'>Sound setting</a>
	</div>
	<div class="content-main">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="header">
						<h3 style="text-align: center; color: #980002; font-family: serif;">Arknights</h3>
						<div id="musicModal" class="modal">
	                    	<div id='content_music' class="modal-content">
	                        	<span class="close" id='close'>&times;</span>
	                        	<h2 id = 'title'>Настройки музыки</h2>
	                        	<div>
	                        		<button id="ache_in_pulse">ACHE in PULSE</button>
									<button id="renegate">Renegade</button>
	                        		<button id='end_like_this'>End like this</button>
	                        		<button id='last_of_me'>Last of me</button>
	                        		<button id="boiling_blood">Boiling Blood</button>
	                        		<button id="heart_forest">Heart Forest</button>
	                        		<button id="requiem">Requiem</button>
	                        	</div>
	                        	<div>
	                        		<label for="volumeSlider">Громкость:</label>
	                        		<input type="range" id="volumeSlider" min="0" max="100" value="50">
	                        	</div>
	                        	<div>
							    	<span id="currentTime">00:00</span>/<span id="totalTime">00:00</span>
							    </div>
							    <div id="slideSong"><button id="playMusic">▶</button><input type="range" id="seekSlider" min="0" max="100" value="0" step="0.1"><button id="muteMusic">⏹</button></div>
	                    	</div>
	                    </div>
					</div>
					<div id="result" style="color: white;"></div>
					<p>Arknights - вселенная с довольно интересной и в то же время трагичной историей, которая не заканчивается до сих пор. В этом мире главный герой: Доктор, вместе со своим отрядом пытается спасти мир от заболевания, называемого Орипатией. Такое заболевание поначалу позволяет вам овладеть искусством Ориджиниума, но это ненадолго...</p>
                    <p>Как только ваш организм ослабевает, а количество кристаллов в крови увеличивается, вас начинает кристаллизировать заживо. Вы постепенно теряете свой человеческий облик, превращаясь в 1 большой кристалл, который ранее был живым человеком.</p>
                    <p>Это заболевание является неизлечимым, по этой причине заражение фактически является смертным приговором, и лишь малая часть заражённых может контролировать заболевание или же его приостановить. Несмотря на то, что живые заражённые не могут заразить здоровых людей (контакт с кристаллами на теле не считается, как и переливание крови), их всё равно ущемляют и подвергают насилию. Ярким примером является регион Урсус, где все заразившиеся подвергаются ссылке в рудники ориджиниума, что только усугубляет болезнь...</p>
                    <p>Возможно, строгость сюжета и его максимальная приближенность к реализму являются основными факторами того, что правитель бездны так полюбил эту игру. Иногда жители видели записки из его дневника, где он писал о разных приключениях главных героев. Некоторым даже удалось собрать все листы в 1 большую последовательную книгу, но возможно это сказки. А может и нет, никто не знает...</p>
                    <p>Ниже представлены авторские фанфики по вселенной Arknights.</p>
					<div class="cards">
					    <a href="#" onclick="Ups()" class="link"><div class="card">
					        <img src="../img/arknights.gif" width="300" height="300" style="object-fit: cover;">
					        <p style="text-align:center;" id="text">Неправильная сторона меня</p>
					    </div></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php include '../template/footer.html'; ?>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script type="text/javascript" src="../js/arknights1.2.1.js"></script>
	<script>
		function Ups(){
			function rd() {
  			return Math.floor(Math.random() * 4);
		};
		switch(rd()){
		case 0:
			document.getElementById('text').textContent="В разработке!"
			break;
		case 1:
			document.getElementById('text').textContent="Пока пусто, наверное."
			break;
		case 2:
			document.getElementById('text').textContent="Не тыкай. Пусто."
			break;
		case 3:
			document.getElementById('text').textContent=". . ."
		}
		}
	</script>
</body>
</html>