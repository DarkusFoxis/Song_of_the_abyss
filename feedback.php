<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(!isset($_SESSION['user'])) {
        header("Location: ./profile/login");
    } else {
        require_once './template/conn.php';
        $conn = mysqli_connect($host, $log, $password_sql, $database);
        if(!$conn){
            $_SESSION['form_error'] = "Ошибка соединения. Пожалуйста, сообщите ошибку создателю: " . mysqli_connect_error();
        } else {
            $login = $_SESSION['user'];
            $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
            $result = $conn->query($user_query);
            if($result -> num_rows > 0){
                $user = $result -> fetch_assoc();
                $permissions = $user["permissions"];
                $lvl = $user["lvl"];
                if($lvl < 2){
                    $_SESSION["perm_error"] = "Причина отказа: Вы были заблокированны на сайте или не верифицированны. Ваши права: " . $permissions . ".<br> Если вы считаете, что произошла ошибка, обратитесь к создателю."; 
                    header("Location: 403");
                } else{
                    $username = $_POST['username'];
                    $ticket = $_POST['ticket'];
                    $file = 'json/ticket.json';
                    if (!file_exists($file)) {
                        file_put_contents($file, json_encode([]));
                    }
                    
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $jsonString = file_get_contents($file);
                    $data = json_decode($jsonString, true);
                    $userAlreadySubmitted = false;
                    foreach ($data as $ticketID => $ticketData) {
                        if ($ticketData['ip_address'] === $ip_address && $ticketData['done'] === 'No') {
                            $userAlreadySubmitted = true;
                            break;
                        }
                    }
                    $ticket = $_POST['ticket'];
                    $num_chars = strlen($ticket);
                    $num_spaces = substr_count($ticket, ' ');
                    $percent_spaces = $num_spaces / $num_chars * 100;
                    
                    if ($userAlreadySubmitted || $percent_spaces > 50) {
                        $_SESSION['form_error'] = 'Кажется, вы уже отправляли сообщение, и он ещё не проверен, или ваше сообщение имеет много пробелов чем текста. Пожалуйста, перепишите ваше сообщение, или свяжитесь с администратором.';
                    } else {
                        $newTicketID = count($data);
                        $data['ID:' . $newTicketID] = array('ip_address' => $ip_address,'username' => htmlspecialchars($username), 'ticket' => htmlspecialchars($ticket), 'done' => 'No');
                        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                        $_SESSION['form_submitted'] = true;
                    }
                    header("Location: feedback");
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html prefix="og:http://ogp.me/ns#">
<head>
    <title>Связаться</title>
    <link rel = "icon" href = "./img/icon.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "./style/style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
	    hr{
	        clear:both;
	        display:block;
	        background-color: #FFA500;
        }
	</style>
</head>
<body>
    <div class="navbar">
		<a href="index">Home</a>
		<a href="#">Version:1.0</a>
	</div>
    <div class="content-main">
		<div class="container">
			<div class="row">
				<div class="col-12">
				    <div class="header">
						<h2>Отправить отзыв/Сообщить о баге</h2>
					</div>
					<p>На этой странице вы можете оставить отзыв о сайте/проекте или сообщить о баге. Каждый ваш отзыв, оставленный здесь, смогут просмотреть все. Если вы сообщаете о баге, то можете ожидать его исправления в ближайшее время. Мы читаем каждый отзыв и с нетерпением ожидаем новых.</p>
					<p>Правила публикации:</p>
					<ol>
					    <li>Нецензурные выражения запрещены.</li>
                        <li>Если вы указываете баги/опечатки, то точно указывайте страницу и описывайте ситуацию как можно подробнее.</li>
					    <li>Не оставляйте более 1-го отзыва.</li>
					</ol>
					<hr color="#00FFFF">
                    <h3>Отправить отзыв/сообщить об ошибке</h3>
                    <form action="feedback" method="post">
                        <!--<label for="username">Имя пользователя:</label>-->
                        <?php
                            if(isset($_SESSION['user'])){
                                echo '<input type="hidden" id="username" name="username" minlength="3" maxlength="15" readonly value="' . $_SESSION['username'] . '">';
                            } else {
                                echo '<label for="username">Имя пользователя:</label>';
                                echo '<input type="text" id="username" name="username" minlength="3" maxlength="15" placeholder="Введите ваш никнейм" required><br>';
                            }
                        ?>
                        <p>Предложение или сообщение о баге:</p>
                        <textarea rows="4" cols="60" id="ticket" name="ticket" minlength="250" maxlength="750" placeholder="Введите ваше сообщение" required></textarea><br>
                        <input type="submit" class='button' value="Отправить">
                    </form>
                    <?php
                        if(isset($_SESSION['form_error'])) {
                            echo "<p style='background-color: black; color: red;'>" . $_SESSION['form_error'] . "</p>";
                            unset($_SESSION['form_error']); // Очищаем сообщение об ошибке из сессии
                        } else if(isset($_SESSION['form_submitted']) && $_SESSION['form_submitted'] === true) {
                            echo "<p style='background-color: black; color: green;'>Успешно записанно!</p>";
                            unset($_SESSION['form_submitted']); // Очищаем сообщение об успешной отправке из сессии
                        }
                    ?>
                    <h3>Существующие предложения и баг репорты:</h3>
                    <?php
                        $jsonString = file_get_contents('json/ticket.json');
                        $data = json_decode($jsonString, true);
                        echo '<table border="1">';
                        echo '<tr><th>№</th><th>Имя/Ник</th><th>Сообщение</th><th>Прочитано создателем?</th></tr>';
                        foreach ($data as $ticketID => $ticketData) {
                            echo '<tr>';
                            echo '<td>' . $ticketID . '</td>';
                            echo '<td>' . $ticketData['username'] . '</td>';
                            echo '<td>' . htmlspecialchars($ticketData['ticket'], ENT_QUOTES, 'UTF-8') . '</td>';
                            echo '<td>' . $ticketData['done'] . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
session_write_close();
?>