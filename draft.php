<?php session_start(); ?>
<!DOCTYPE html>
<html prefix="og:http://ogp.me/ns#">
<head>
	<title>Наброски/Заготовки</title>
	<link rel = "icon" href = "./img/icon.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "./style/style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Множество авторов сочинают истории, но не каждые успевают их дописать. Некоторые черновики дописываются смелыми авторами, а некоторые остаются недописанными...">
	<meta property="og:title" content="Черновики и наброски"/>
	<meta property="og:site_name" content="Song of the  abyss"/>
	<meta property="og:type" content="website"/>
	<meta property="og:url" content="http://r90926ht.beget.tech/draft"/>
	<meta property="og:description" content="Множество авторов сочинают истории, но не каждые успевают их дописать. Некоторые черновики дописываются смелыми авторами, а некоторые остаются недописанными..."/>
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
			width: 50%;
			min-width: 250px;
		}
		.card p {
			text-align: center;
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
						<h3>Наброски и черновики</h3>
					</div>
					<p>«Множество авторов сочинают истории, но не каждые успевают их дописать. Некоторые черновики дописываются смелыми авторами, а некоторые остаются недописанными. В бездне много людей, которые сочинают свои истории, поэтому большая часть набросков дописывается, но сохраняется ли их первоначальный смысл, остаётся загадкой даже для тех, кто их дописывал». На этой странице вы найдёте часть рассказов, которые ещё пишутся или дописываются. Будущее этих черновиков и за вами. Вы всегда можете написать автору со своим отзывом (только не забудьте указать, о каком рассказе вы говорите).«Множество авторов сочинают истории, но не каждые успевают их дописать. Некоторые черновики дописываются смелыми авторами, а некоторые остаются недописанными. В бездне много людей, которые сочинают свои истории, поэтому большая часть набросков дописывается, но сохраняется ли их первоначальный смысл, остаётся загадкой даже для тех, кто их дописывал».<br>На этой странице вы найдёте часть рассказов, которые ещё пишутся или дописываются. Будущее этих черновиков и за вами. Вы всегда можете написать автору со своим отзывом (только не забудьте указать, о каком рассказе вы говорите).</p>
					<div class="cards">
					    <?php 
					        if (isset($_SESSION['user'])) {
					            require_once './template/conn.php';
                                $conn = mysqli_connect($host, $log, $password_sql, $database);
                                if(!$conn){
                                    echo '<div class="card"><p>При обращении к базе данных произошла ошибка: ' . mysqli_connect_error() .'</p></div>';
                                    exit;
                                } else {
                                    $login = $_SESSION['user'];
                                    $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
                                    $result = $conn->query($user_query);
                                    if($result -> num_rows > 0){
                                        $user = $result -> fetch_assoc();
                                        if($user['lvl'] >= 3): ?>
                                            <a href="https://docs.google.com/document/d/17_PIzzWrRl36oK8s0OmvpydvfhkQIDtN/edit?usp=sharing&ouid=108396481249360154317&rtpof=true&sd=true" target="blank"  class="link"><div class="card">
                    					        <p>Вселенская война</p>
                    					        <a href="https://t.me/DarkusFoxis" class="link"><p>Автор: DarkusFoxis</p></a>
                    					    </div></a>
                    					    <a href="https://docs.google.com/document/d/1Nd0SdzsLsPpbQTyAkUQGWhbqzjLTZgNOWVdc3-_E2ro/edit?usp=sharing" target="blank" class="link"><div class="card">
                    					        <p>Ледниковая месть</p>
                    					        <a href="https://t.me/DarkusFoxis" class="link"><p>Автор: DarkusFoxis</p></a>
                    					    </div></a>
                    					    <a href="https://docs.google.com/document/d/1fOIBtTK7Nu3Bw-awlY1yo5S29IkkxdFYic1ifuxG0es/edit?usp=sharing" target="blank"  class="link"><div class="card">
                    					        <p>Неправильная сторона меня</p>
                    					        <a href="https://t.me/DarkusFoxis" class="link"><p>Автор: DarkusFoxis</p></a>
                    					    </div></a>
                    					    <a href="https://docs.google.com/document/d/1-n83uuFf3MBqlHpw6RFsciVmeJKsBtRGucSirUHPQQ0/edit?usp=sharing" target="blank"  class="link"><div class="card">
                    					        <p>Эпидемия Лилит</p>
                    					        <a href="https://t.me/DarkusFoxis" class="link"><p>Автор: DarkusFoxis</p></a>
                    					    </div></a>
                    			        <?php else: ?>
                    			            <div class="card"><p>К сожалению, этот раздел доступен только премиум пользователям. Подробнее, узнайте у <a href="https://t.me/DarkusFoxis" class="link">Создателя</a>.</p></div>
                    			        <?php endif; ?>
                                    <?php 
                                    }
                                }
					        } else {
					            echo '<div class="card"><p>Для доступа к черновикам, <a href="./profile/main" class="link">авторизуйстесь</a>.</p></div>';
					        }?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php include './template/footer.html'; ?>
</body>
</html>
<?php 
session_write_close();
?>