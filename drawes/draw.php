<?php
    session_start();
    if (!isset($_SESSION['user'])) {
        header("Location: ../profile/login");
    } else {
        require_once '../template/conn.php';
    
        $conn = mysqli_connect($host, $log, $password_sql, $database);
        if (!$conn){
            $_SESSION['base_error'] = "Ошибка соединения." . mysqli_connect_error();
        } else {
            $login = $_SESSION['user'];
            $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
            $result = $conn->query($user_query);
    
            if ($result -> num_rows > 0){
                $user = $result -> fetch_assoc();
                $permissions = $user["permissions"];
                $lvl = $user['lvl'];
                if ($lvl == 0){
                    $_SESSION["perm_error"] = "Вы были заблокированы на сайте. <br> Ваши права: " . $permissions . ".<br> Если вы считаете, что блокировка была безосновательной, обратитесь к администратору."; 
                    header("Location: ./403");
                } else if ($lvl == 1){
                    $_SESSION["perm_error"] = "Вы не подтверждены на сайте. <br> Ваши права: " . $permissions . ".<br> Обратитесь к администратору, для подтверждения аккаунта."; 
                    header("Location: ./403");
                }
            }
            mysqli_close($conn);
        }
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel = "icon" href = "../img/icon.png">
    <link rel = "stylesheet" href = "../style/style.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доска для рисования</title>
    <style>
        body{
            background-color: wite;
            background-image: none;
        }
        #canvas {
            border: 2px solid black;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        td{
            background-color: rgba(0,0,0,0.3);
        }
        .content-main{
            background-color: white;
            color: black;
        }
        button {
		    background-color: #8B0000;
		    color: #FFE4E1;
		    text-align: center;
		    cursor: pointer;
		    border-radius: 7px;
	    }
  </style>
</head>
<body>
    <div class="content-main">
        <h3>Рисуйте, и делитесь!</h3>
        <canvas id="canvas" width="800" height="600" style="float: left; background-color: rgba(0,0,0,0.3);"></canvas>
        <br>
        <button onclick="window.location.href='../index'">Home</button>//<button onclick="clearCanvas()">Очистить холст</button><br>
        <button onclick="zoomIn()">Увеличить</button>//<button onclick="zoomOut()">Уменьшить</button><br>
        <button onclick="color = '#008000'">Зелёный цвет</button><button onclick="color = '#00FF00'">Лаймовый цвет</button><br>
        <button onclick="color = '#FF0000'">Красный цвет</button><button onclick="color = '#FFFF00'">Жёлтый цвет</button><br>
        <button onclick="color = '#0000FF'">Синий цвет</button><button onclick="color = '#800080'">Фиолетовый цвет</button><br>
        <button onclick="color = '#00FFFF'">Ярко голубой цвет</button><button onclick="color = '#008B8B'">Тёмно циановый цвет</button><br>
        <button onclick="color = '#000000'">Чёрный цвет</button><button onclick="toggleEraser()">Ластик/Ручка</button><br>
        <input type="color" id="colorPicker"><br>
        <form action="save_image" id="imageForm" method="post" enctype="multipart/form-data">
            <input type="hidden" id="imageData" name="imageData">
            <label for="name">Автор рисунка:</label><br>
            <?php
                if(isset($_SESSION['user'])){
                    echo '<input type="text" id="name" name="name" minlength="3" maxlength="15" readonly value="' . $_SESSION['username'] . '"><br>';
                }
            ?>
            <input type="submit" value="Поделиться!" id="shareButton">
        </form>
        <div>
            <p>Рисунки, которыми поделились:</p>
            <table id="sharedDrawingsTable">
                <tr>
                    <th>Рисунок</th>
                    <th>Нарисовал</th>
                </tr>
                <?php
                    $jsonData = json_decode(file_get_contents('drawings.json'), true);
                    foreach ($jsonData as $entry) {
                    echo "<tr><td><img src='images/" . $entry['image'] . "'></td><td>" . $entry['drawnBy'] . "</td></tr>";
                    }
                ?>
            </table>
        </div>
    </div>
<script>
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    const shareButton = document.getElementById('shareButton');
    const imageDataInput = document.getElementById('imageData');
    
    let colorPicker = document.getElementById('colorPicker');
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;
    let color = "#000000"
    let min_width = 400
    let min_height = 200
    let max_width = 1400
    let max_height = 1200
    let isErasing = false;

    function toggleEraser() {
        isErasing = !isErasing;
    }
    
    function clearCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    };
    

    function zoomIn() {
        if(canvas.width >= max_width || canvas.height >= max_height){
            return;
        } else {
            canvas.width *= 1.1;
            canvas.height *= 1.1;
        }
    };

    function zoomOut() {
        if(canvas.width <= min_width || canvas.height <= min_height){
            return;
        } else {
            canvas.width *= 0.9;
            canvas.height *= 0.9;
        }
    };

    colorPicker.addEventListener('input', function() {
        let selectedColor = colorPicker.value;
        color = selectedColor;
    });

    canvas.addEventListener('mousedown', (e) => {
        isDrawing = true;
        [lastX, lastY] = [e.offsetX, e.offsetY];
    });

    canvas.addEventListener('mousemove', (e) => {
        if (isDrawing && !isErasing) {
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.stroke();
            [lastX, lastY] = [e.offsetX, e.offsetY];
        }
        if (isErasing) {
            ctx.clearRect(e.offsetX, e.offsetY, 10, 10);
        }
    });

    canvas.addEventListener('mouseup', () => {
        isDrawing = false;
    });
    canvas.addEventListener('touchstart', (e) => {
        isDrawing = true;
        [lastX, lastY] = [e.touches[0].clientX, e.touches[0].clientY];
    });

    canvas.addEventListener('touchmove', (e) => {
        if (isDrawing && !isErasing) {
            e.preventDefault();
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(e.touches[0].clientX, e.touches[0].clientY);
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.stroke();
            [lastX, lastY] = [e.touches[0].clientX, e.touches[0].clientY];
        }
        if (isErasing) {
            ctx.clearRect(e.touches[0].clientX, e.touches[0].clientY, 10, 10);
        }
    });

    canvas.addEventListener('touchend', () => {
        isDrawing = false;
    });

    shareButton.addEventListener('click', () => {
        const dataURL = canvas.toDataURL();
        imageDataInput.value = dataURL;
    });
</script>
</body>
</html>
<?php 
session_write_close();
?>