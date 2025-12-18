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
    <meta charset="UTF-8">
    <title>Рассказы Бездны</title>
    <link rel = "icon" href = "../img/icon.png">
	<link rel="icon" href="../img/icon.png" type="image/png">
	<link rel = "stylesheet" href = "./css/style.css">
	<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">-->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&amp;display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <div class="nav">
        <h1>Редактор рассказов</h1>
        <div class="nav-links">
            <a href="./main">Главная</a>
            <a href="#">Мои рассказы</a>
            <a href="#">Правила</a>
        </div>
    </div>
    <div class="container">
        <input type="text" id="storyTitle" maxlength="150" placeholder="Название рассказа...">

        <div class="additional-fields">
            <div class="description-field field-group">
                <label for="storyDescription">Краткое описание (до 500 символов) (Не поддерживается Markdown):</label>
                <textarea id="storyDescription" maxlength="500" placeholder="Краткое описание для карточки рассказа..."></textarea>
                <div class="char-counter"><span id="charCount">0</span>/500 символов</div>
            </div>
            
            <div class="field-group">
                <label for="ageRating">Возрастное ограничение:</label>
                <select id="ageRating">
                    <option value="0">Без ограничений</option>
                    <option value="12">12+</option>
                    <option value="16">16+</option>
                    <option value="18">18+</option>
                </select>
            </div>
            
            <div class="field-group cover-upload-container">
                <label>Иконка рассказа:</label>
                <label for="coverUpload" class="cover-label">
                    <span id="uploadText">Нажмите для загрузки изображения</span>
                    <input type="file" id="coverUpload" accept="image/*">
                </label>
                <div class="cover-preview" id="coverPreview"></div>
            </div>
        </div>

        <div class="editor-wrapper">
            <div class="toolbar">
                <button class="toolbar-btn" onclick="insertMarkdown('**', '**')">
                    <i class="icon-bold"></i> Жирный
                </button>
                <button class="toolbar-btn" onclick="insertMarkdown('*', '*')">
                    <i class="icon-italic"></i> Курсив
                </button>
                <button class="toolbar-btn" onclick="insertMarkdown('__', '__')">
                    <i class="icon-underline"></i> Подчёркивание
                </button>
                <button class="toolbar-btn" onclick="insertMarkdown('# ', '')">
                    <i class="icon-header"></i> Заголовок
                </button>
                <button class="toolbar-btn" onclick="insertMarkdown('<center>', '</center>')">
                    <i class="icon-center"></i> Центрировать
                </button>
                <button class="toolbar-btn" onclick="insertMarkdown('- ', '')">
                    <i class="icon-list"></i> Список
                </button>
                <button class="toolbar-btn" onclick="insertMarkdown('> ', '')">
                    <i class="icon-quote"></i> Цитата
                </button>
            </div>
            <textarea id="storyMarkdown" placeholder="Начни писать свою историю...&#10;(Поддерживается Markdown)"></textarea>
        </div>

        <div class="editor-box">
            <div class="preview-box">
                <div class="preview-content" id="preview"></div>
            </div>
        </div>

        <div class="controls">
            <button onclick="saveDraft()">Сохранить черновик</button>
            <button onclick="publishStory()">Опубликовать</button>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="./js/redact.js"></script>
</body>
</html>
<?php 
session_write_close();
?>