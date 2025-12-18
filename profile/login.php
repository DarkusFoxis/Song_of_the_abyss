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
        <title>Вход</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
	    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
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
				background: #4B0082	;
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
			<form action="log" method="POST">
				<label for="login">Логин:</label><br>
				<input type="text" id="login" name="login" required maxlength="20" placeholder="Введите логин" class="input-text"><br>
				<label for="pass">Пароль</label><br>
				<input type="password" id="pass" name="password" required maxlength="15" placeholder="Введите пароль" minlength="8" class="input-text"><br>
				<input type="checkbox" id="see_password"> Показать пароль<br>
				<center><input type="submit" class="submit-button" value="Вход"><br>
				Нет аккаунта? <a href="./registration" class="link">Создайте</a>!</center>
			</form>
		</div>
		<script>
            const togglePassword = document.getElementById("see_password");
            
            const showOrHidePassword = () => {
                const password = document.getElementById('pass');
                if (password.type === 'password') {
                    password.type = 'text';
                } else {
                    password.type = 'password';
                }
            };
            
            togglePassword.addEventListener("change", showOrHidePassword);
		</script>
    </body>
</html>