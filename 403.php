<?php session_start();?>
<!DOCTYPE html>
<html>
<head>
	<title>403</title>
	<link rel = "icon" href = "./img/icon.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		body{
			background-color: black;
			color: #FFE4E1;
			font-family: 'Montserrat Alternates', sans-serif;
		}
		.link{
			color: yellow;
		}
		.link:hover{
			color: red;
		}
		button {
          background: transparent;
          border: 1px solid #ccc;
          color: #ccc;
          padding: 10px 20px;
          font-size: 16px;
          cursor: pointer;
          transition: all 0.3s ease;
        }
        
        button:hover {
          background: #ccc;
          color: #333;
        }
	</style>
</head>
<body>
	<div class="header">
		<h3 style="text-align: center; color: purple; font-family: serif;">403<br>Доступ ограничен.</h3>
	</div>
	<div class="content-main">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<br>
					<p>Ой... Кажется, измерение, в которое вы пытаетесь переместиться, не доступно для вас. Хм... Может, вы хотите попасть туда, куда можно только создателю? Напишите нам в <a href="https://t.me/DarkusFoxis" class="link">Телеграмм</a>, а пока, вернитесь в <a href="./index" class="link">родное измерение</a>. Лучше не пытайтесь попасть туда, куда вам не стоит...</p>
					<?php
					    if(isset($_SESSION['perm_error'])) {
                            echo "<p>" . $_SESSION['perm_error'] . "</p>";
                            unset($_SESSION['perm_error']);
                        } else {
                            echo "<p>Причина отказа: Попытка доступа к защищённым разделам хостинга, или вы попали сюда просто так.</p>";
                        }
					?>
					<button id="butt">Постучать</button>
					<div id='result'></div>
				</div>
			</div>
		</div>
	</div>
	<script src="./js/jquery-3.7.1.min.js"></script>
	<script>
	    const achievementButton = document.getElementById('butt');
        achievementButton.addEventListener('click', () => {
            $.ajax({
                url: 'achievement_core',
                type: 'POST',
                data: { achievement: 'code403' },
                success: function(response) {
                    const resultDiv = document.getElementById('result');
                    resultDiv.textContent = response;
                    resultDiv.style.color = 'white';
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка AJAX: ' + error);
                }
            });
        });
        $(document).keydown(function(e) {
            if ((e.ctrlKey && e.shiftKey && e.which === 73) || e.which === 123) {
                e.preventDefault();
                location.href = './403';
            }
        });
	</script>
</body>
</html>
<?php 
session_write_close();
?>