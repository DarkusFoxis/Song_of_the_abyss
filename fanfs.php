<!DOCTYPE html>
<html prefix="og:http://ogp.me/ns#">
<head>
	<title>Fanfs</title>
	<link rel = "icon" href = "./img/icon.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "./style/style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta property="og:title" content="Легенды и сказания"/>
	<meta property="og:site_name" content="Song of the  abyss"/>
	<meta property="og:type" content="website"/>
	<meta property="og:url" content="http://r90926ht.beget.tech/legend.html"/>
	<meta property="og:description" content="Не всегда же обсуждать быль? Можно и сочинить свои сказки..."/>
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
			border: 1px solid #4B0082;
			border-radius: 5px;
			padding: 15px;
			background-color: rgb(255,255,255,0.1);
		}

		.card img {
			max-width: 100%;
			height: auto;
			border-radius: 5px;
			margin-bottom: 10px;
		}
	</style>
</head>
<body>
	<div class="navbar">
		<a href="legend">Back</a>
	</div>
	<div class="content-main">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="header">
						<h3>Фанфики</h3>
					</div>
					<p>Не всегда в народе могут сочинять сказания, основанные на проишевствиях в бездне. Конечно есть и выдумки, но о таких моментально узнают, что и огорчает любителей литературы, и иследователей. Поэтому многие народы используют готовые образы, и на их основе составляют новые сказы и рассказы. Никто точно не знает, возможно ли такое в реальности, но многие желают этого. Что об этом думает правитель... Ему нравится такое творчество. Как он говорил, что подобное явление способствует развитию творчества в бездне. Кто знает, возможно книги из этого в скором времени появятся в наземном мире? Поживём и увидим...<br>Ниже представленны вселенные, по которым уже есть фанатское творчество. Выберите понравившуюся вам, и почитайте.</p>
					<div class="cards">
					    <a href="./arknights/arknights" class="link"><div class="card">
					        <img src="./img/arknights.gif" width="300" height="300" style="object-fit: cover;">
					        <p style="text-align:center;" id="text">Arknights</p>
					    </div></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php include './template/footer.html'; ?>
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