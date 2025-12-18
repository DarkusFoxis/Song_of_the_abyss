<?php session_start(); ?>
<!DOCTYPE html>
<html prefix="og:http://ogp.me/ns#">
<head>
<title>Легенды и сказания</title>
<link rel = "icon" href = "./img/icon.png">
<link rel="icon" href="./img/icon.png" type="image/png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel = "stylesheet" href = "./style/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Даже в бездне есть свои легенды и сказания. Некоторые ушли за пределы бездны, а какие-то остались в народе, и услышать некоторые из них возможно только за чашкой чая...">
<meta property="og:title" content="Легенды и сказания"/>
<meta property="og:site_name" content="Song of the  abyss"/>
<meta property="og:type" content="website"/>
<meta property="og:url" content="https://so-ta.ru/legend"/>
<meta property="og:description" content="Даже в бездне есть свои легенды и сказания. Некоторые ушли за пределы бездны, а какие-то остались в народе, и услышать некоторые из них возможно только за чашкой чая..."/>
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
	<a href="index">Home</a>
	<!--<a href="wiki/main">Wiki</a>-->
</div>
<div class="content-main">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<div class="header">
					<h3>Легенды и сказания</h3>
				</div>
				<p>В мире Бездны слагают множество историй - их можно услышать на каждом шагу. Какие-то растворяются в народе как байки и легенды, а какие-то записываются в большие книги. Каждая из этих историй может так или иначе переплетаться с несколькими другими, поэтому их почти никогда не забывают, и каждый ребёнок в Бездне хоть и одну, да знает легенду, которую ему рассказывали родители на ночь. Хоть эти сказания и, как правило, остаются исключительно среди жителей Бездны, иногда они самыми разными путями вырываются за ее рамки, благодаря чему с ними можно ознакомиться и живым. Потому, предлагаем и вам ознакомиться с несколькими из таких сказаний.</p>
				<div class="cards">
				    <a href="./univers" class="link"><div class="card">
				        <img src="./img/unv.png" alt="Иконка Вселенской войны" title="Главная серия рассказов" width="235" height="235" style="object-fit: cover;">
				        <p style="text-align:center;">Вселенская война</p>
				    </div></a>
				    <a href="./revenge" class="link"><div class="card">
				        <img src="./img/revenge.png" alt="Иконка Ледниковой мести" title="Скоро релиз..." width="235" height="235" style="object-fit: cover;">
				        <p style="text-align:center;">Ледниковая месть</p>
				    </div></a>
				    <a href="./other" class="link"><div class="card">
				        <img src="./img/icon_lit.png" alt="Иконка Альтернативных рассказов" title="Рассказы, не полный сюжет" width="235" height="235" style="object-fit: cover;">
				        <p style="text-align:center;">Альтернативный сюжет</p>
				    </div></a>
				    <a href="./fanfs" class="link"><div class="card">
				        <img src="./img/tma.png" alt="Иконка фанфиков" title="Любимые вселенные" width="235" height="235" style="object-fit: cover;">
				        <p style="text-align:center;">Фанфики</p>
				    </div></a>
				    <a href="./draft" class="link"><div class="card">
				        <img src="./img/icon_lit.png" alt="Иконка черновиков и набросков" title="Черновики и наброски" width="235" height="235" style="object-fit: cover;">
				        <p style="text-align:center;">Черновики, наброски</p>
				    </div></a>
				    <a href="./story/main" class="link"><div class="card">
				        <img src="./img/icon_lit.png" alt="Иконка рассказов бездны " title="Рассказы бездны" width="235" height="235" style="object-fit: cover;">
				        <p style="text-align:center;">Рассказы бездны</p>
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
  		return Math.floor(Math.random() * 6);
	};
	switch(rd()){
	case 0:
		document.getElementById('text').textContent="В разработке!";
		break;
	case 1:
		document.getElementById('text').textContent="Пока пусто, наверное.";
		break;
	case 2:
		document.getElementById('text').textContent="Не кликай. Пусто.";
		break;
	case 3:
		document.getElementById('text').textContent=". . .";
		break;
	case 4:
	    document.getElementById('text').textContent="Я это cделаю, но не сегодня.";
	    break;
	case 5:
	    document.getElementById('text').textContent="Не хочу делать."
	}
	}
</script>
</body>
</html>
<?php 
session_write_close();
?>