<?php
session_start();

if(!isset($_SESSION['user'])) {
    header("Location: ../profile/login");
} else {
    require_once '../template/conn.php';
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        echo "Ошибка соединения: " . mysqli_connect_error();
        exit;
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $black = "SELECT * FROM black_ip WHERE ip = '$ip_address'";
    $result = $conn->query($black);
    if ($result->num_rows > 0) {
        unset($_SESSION['user']);
        unset($_SESSION['username']);
        $_SESSION['error'] = "При входе в аккаунт произошла ошибка. Кажется, ваше местоположение было заблокированно.";
        session_destroy();
        header("Location: ../profile/main");
        exit;
    }

    $login = $_SESSION['user'];
    $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
    $result = $conn->query($user_query);
    $user = $result -> fetch_assoc();
    $userId = $user['id'];
    $query = "SELECT * FROM invent WHERE id_user = '$userId'";
    $result = mysqli_query($conn, $query);
    if ($user['lvl'] == 0) {
        $_SESSION["perm_error"] = "Вы заблокированны на сайте. Ваши возможности ограничены.";
        header("Location: ../403");
        exit;
    } else if ($user['lvl'] == 1) {
        $_SESSION["perm_error"] = "Вы не верифицированны на сайте.";
        header("Location: ../403");
        exit;
    }
    if (mysqli_num_rows($result) === 0) {
        $_SESSION["perm_error"] = "У вас не активирован инвентарь. Пожалуйста, создайте его.";
        header("Location: ../403");
        exit;
    }
    $value = 250;
    $simv_count = 1500;
    $mb = 15;
    $count = 3;
    if ($user['lvl'] >= 3) {
        $value = 0;
        $simv_count = 3000;
        $mb = 30;
        $count = 6;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактор поста</title>
    <link rel = "icon" href = "../img/icon.png">
	<link rel = "stylesheet" href = "../style/style.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .container {
            margin: 0 auto;
        }
        .navbar {
            clear: both;
            overflow: hidden;
            background-color: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0px 1rem 0px;
        }
        form {
            background: linear-gradient(90deg, rgba(186,20,126,0.5) 0%, rgba(60,9,121,1) 50%, rgba(255,102,0,0.5) 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        input[type="submit"] {
            background-color: #9966cc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            item-align
        }
    </style>
</head>
<body>
    <div class="navbar">
		<a href="#" onclick="window.history.back()">Back</a>
		<a href="#">Редактор постов V1.3</a>
	</div>
    <div class="content-main">
		<div class="container">
			<div class="row">
				<div class="col-12">
				    <div class="header">
						<h2>Редактор постов</h2>
					</div>
					<div class="container">
					    <details><summary>Перед написанием поста, ознакомьтесь с кратким перечнем правил:</summary>
					    <ol>
					        <li>Обсуждение политики запрещено. Так же, файлы содержащие изображение политики/политических деятелей запрещена.</li>
					        <li>Пиар/Реклама, не обсуждённая с администратором запрещена.</li>
					        <li>Обсуждение тем 18+ так же запрещено. Так же, прикреплённые файлы, содержащие 18+ темы запрещены.</li>
					        <li>Посты, нарущающие законодательство РФ так же запрещено, в не зависимости от вашего местоположения.</li>
					    </ol>
					    <p>Несоблюдение правил влечёт удаление поста, без возврата средств, потраченных на его публикацию. Так же, повторное нарушение влечёт блокировку аккаунта. Публикуя пост, вы соглашаетесь с правилами.</p></details>
					    <form id="post-form" method="post" enctype="multipart/form-data">
                            <label for="title">Заголовок:</label>
                            <input type="text" id="title" name="title" required placeholder="Что у вас нового?" minlength="5" maxlength="150">
                
                            <label for="post">Текст:</label>
                            <textarea id="post" name="post" maxlength="<?php echo $simv_count; ?>" placeholder="Поделитесь своими эмоциями! Напишите текст вашего поста! Максимум: <?php echo $simv_count; ?> символов"></textarea>
                
                            <label for="media">Медиа (Изображения, аудио до: <?php echo $mb; ?> мегабайт, видео: <?php echo $mb * 2; ?> мегабайт) и  до <?php echo $count; ?> штук:</label>
                            <input type="file" id="media" name="media[]" accept="image/*, audio/mpeg, video/mp4" multiple>
                
                            <center><input type="submit" value="Опубликовать (<?php echo $value;?> монет)"></center>
                        </form>
                        <div id="response"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#post-form").submit(function(event) {
                event.preventDefault();
                var formData = new FormData(this);
                $("#response").html("Отправка поста, ожидайте...");
                $.ajax({
                    url: "upload_core",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $("#response").html(response);
                    },
                    error: function(xhr, status, error) {
                        $("#response").html("Ошибка: " + error);
                    }
                });
            });
        });
    </script>
</body>
</html>
