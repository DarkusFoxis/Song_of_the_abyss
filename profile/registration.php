<?php
    session_start();
    if(isset($_SESSION['user'])) {
        require_once '../template/conn.php';

        $conn = mysqli_connect($host, $log, $password_sql, $database);
        if(!$conn){
            echo("Ошибка соединения.");
        } else {
            $login = $_SESSION['user'];
            $user_query = "SELECT * FROM users WHERE login = '$login'";
            $result = $conn->query($user_query);

            if($result -> num_rows > 0){
                $_SESSION['error'] = "Вы находитесь в аккаунте.";
	            header("Location: main");
                mysqli_close($conn);
            } else{
                header("Location: logout");
                mysqli_close($conn);
            }
        }
    }
    session_write_close();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel = "icon" href = "../img/icon.png">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <title>Регистрация</title>
	<style>
		body{
			margin: 0;
			background: rgb(96,63,251);
			background: radial-gradient(circle, rgba(96,63,251,1) 0%, rgba(184,70,252,0.8225490879945728) 100%);
			color: #FFE4E1;
            font-family: 'Montserrat Alternates', sans-serif;
		}
		.input-text{
			width: 256px;
			height: 32px;
			border: 1px solid black;;
			border-radius: 4px;
			background: white;
			box-shadow: 2px 2px 5px 0px #d2d2d2;
			margin-bottom: 6px;
		}
		.login{
			height: 94vh;
			align-items: center;
			justify-content: center;
			display: flex;
		}
		.submit-button {
			width: 75%;
			height: 27px;
			border-radius: 7px;
			background: #4B0082;
			color: #FFE4E1;
			border: none;
			font-size: 16px;
			margin-top: 10px;
		}
		.link{
        	color: yellow;
        }
        .link:hover{
        	color: red;
        }
	</style>
</head>
<body>
<div class="login">
	<form action="reg" method="POST">
		<label for="login">Логин:</label><br>
		<input type="text" id="login" name="login" required minlength="3" maxlength="20" placeholder="Введите логин" class="input-text"><br>
		<label for="email">Почта</label><br>
		<input id="email" type="text" name="email" required minlength="10" maxlength="50" placeholder="Введите почту" class="input-text"><br>
		<label for="pass">Пароль</label><br>
		<input type="password" id="pass" name="password" required maxlength="15" placeholder="Введите пароль" minlength="8" class="input-text"><br>
		<label for="pass2">Повторите пароль</label><br>
		<input id="pass2" type="password" name="password2" required maxlength="15" placeholder="Повторите пароль" minlength="8" class="input-text"><br>
		<input type="checkbox" id="see_password"> Показать пароль<br>
		<center><input type="submit" class="submit-button" value="Зарегистрироваться"><br>
		Есть аккаунт? <a href="./login" class="link">Войдите</a>!</center>
	</form>
</div>
<p style='text-align: center;'>При создании аккаунта, пожалуйста, указывайте реальную почту. В случае проблем, вы сможете связаться с нами, и восстановить пароль. Так же, в случае проверки на подлинность, вы можете быть уверенным на 100%, что ваш аккаунт будет сохранён.</p>
<p style='text-align: center;'>Регистрируя аккаунт, вы соглашаетесь с <a href='./privacy' class='link'>Политикой обработки персональных данных</a></p>
<script>
    const togglePassword = document.getElementById("see_password");
    const showOrHidePassword = () => {
        const password = document.getElementById('pass');
        const password2 = document.getElementById('pass2');
        if (password.type === 'password') {
            password.type = 'text';
            password2.type = 'text';
        } else {
            password.type = 'password';
            password2.type = 'password';
        }
    };
    togglePassword.addEventListener("change", showOrHidePassword);
</script>
</body>
</html>