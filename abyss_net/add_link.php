<?php
session_start();
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    error_log("Ошибка подключения к БД: " . mysqli_connect_error());
    die("Ошибка подключения к базе данных. Попробуйте позже.");
}
if (!isset($_SESSION['user'])) {
    header("Location: ../profile/login");
    exit;
}
$login = $_SESSION['user'];
$sql_user = "SELECT u.id, u.permissions, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?";
$stmt_user = mysqli_prepare($conn, $sql_user);
if (!$stmt_user) {
    error_log("Ошибка подготовки запроса пользователя: " . mysqli_error($conn));
    die("Ошибка системы. Попробуйте позже.");
}
mysqli_stmt_bind_param($stmt_user, "s", $login);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);
mysqli_stmt_close($stmt_user);
if (!$user) {
    session_destroy();
    header("Location: ../profile/login");
    exit;
}
$userId = $user['id'];
$userLevel = $user['lvl'];
$tools = "SELECT * FROM tools WHERE user_id = ?";
$tools_query = mysqli_prepare($conn, $tools);
mysqli_stmt_bind_param($tools_query, "i", $userId);
mysqli_stmt_execute($tools_query);
$tools_result = mysqli_stmt_get_result($tools_query);
$tools_enable = mysqli_fetch_assoc($tools_result);
mysqli_stmt_close($tools_query);
if (!$tools_enable) {
    $_SESSION["error"] = "Вы не приобрели доступ к инструментарию сайта!";
    header("Location: ../profile/main");
    exit;
}
if (!$tools_enable['add_link']) {
    $_SESSION["perm_error"] = "У вас отключён доступ к этому функционалу за нарушение правил, или вы отключили самостоятельно.";
    header("Location: ../403");
    exit;
}
$message = '';
$error = '';
$hideUser = false;
$nsfwInput = '0';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $url = trim($_POST['url'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $keywords = trim($_POST['keywords'] ?? '');
    $hideUserInput = $_POST['hide_user'] ?? '0';
    $nsfwInput = $_POST['nsfw'] ?? '0';

    if (empty($url) || empty($title) || empty($keywords)) {
         $error = "Пожалуйста, заполните все обязательные поля.";
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = "Некорректный URL адрес.";
    } else {
        $forbiddenDomains = include 'forbidden_domains.php';
        if (!is_array($forbiddenDomains)) {
            error_log("Ошибка: forbidden_domains.php должен возвращать массив.");
            $forbiddenDomains = ['vk.com', 'vkontakte.ru', 'userapi.com', 'tiktok.com', 'tiktokcdn.com', 'musical.ly'];
        }
        $host = parse_url($url, PHP_URL_HOST);
        $isForbidden = false;
        if ($host) {
             foreach ($forbiddenDomains as $domain) {
                 if (stripos($host, $domain) !== false) {
                    $isForbidden = true;
                    break;
                 }
             }
        }
        if ($isForbidden) {
            $error = "Добавление ссылок с этого ресурса запрещено.";
        } else {
            $sql_check = "SELECT id FROM url WHERE url = ?";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            if (!$stmt_check) {
                error_log("Ошибка подготовки запроса проверки URL: " . mysqli_error($conn));
                $error = "Ошибка системы. Попробуйте позже.";
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $url);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                if (mysqli_num_rows($result_check) > 0) {
                    $error = "Эта ссылка уже существует в базе.";
                }
                mysqli_stmt_close($stmt_check);
            }
            if (empty($error)) {
                $hideUser = ($hideUserInput === '1' && $userLevel > 3);
                if ($hideUser) {
                    $sql_insert = "INSERT INTO url (url, title, description, keywords, date_add, id_user, nsfw) VALUES (?, ?, ?, ?, NOW(), NULL, ?)";
                    $stmt_insert = mysqli_prepare($conn, $sql_insert);
                    if ($stmt_insert) {
                         mysqli_stmt_bind_param($stmt_insert, "ssssi", $url, $title, $description, $keywords, $nsfwInput);
                    }
                } else {
                    $sql_insert = "INSERT INTO url (url, title, description, keywords, date_add, id_user, nsfw) VALUES (?, ?, ?, ?, NOW(), ?, ?)";
                    $stmt_insert = mysqli_prepare($conn, $sql_insert);
                    if ($stmt_insert) {
                         mysqli_stmt_bind_param($stmt_insert, "ssssii", $url, $title, $description, $keywords, $userId, $nsfwInput);
                    }
                }
                if (!$stmt_insert) {
                    error_log("Ошибка подготовки запроса вставки: " . mysqli_error($conn));
                    $error = "Ошибка системы. Попробуйте позже.";
                } else {
                    if (mysqli_stmt_execute($stmt_insert)) {
                        $message = "Ссылка успешно добавлена!";
                        $_POST = array();
                        $nsfwInput = '0';
                    } else {
                        error_log("Ошибка выполнения запроса вставки: " . mysqli_stmt_error($stmt_insert));
                        $error = "Не удалось добавить ссылку. Попробуйте позже.";
                    }
                    mysqli_stmt_close($stmt_insert);
                }
            }
        }
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Link</title>
<link rel="icon" href="../img/icon.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel = "stylesheet" href = "../style/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
<script src="../js/jquery-3.7.1.min.js"></script>
<style>
.add-link-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 30px;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 20px;
    box-shadow: 0 0 50px rgba(147, 112, 219, 0.3);
    border: 1px solid rgba(229, 36, 255, 0.5);
}
.form-title {
    color: #BA55D3;
    text-align: center;
    margin-bottom: 30px;
    font-size: 2rem;
    text-shadow: 0 0 10px rgba(186, 85, 211, 0.5);
}
.form-group label {
    color: #9370DB;
    font-weight: bold;
}
.form-control {
    background: rgba(0, 0, 0, 0.8) !important;
    color: #fff !important;
    border: 2px solid #9370DB !important;
    padding: 12px 15px !important;
    border-radius: 15px !important;
    box-shadow: 0 0 15px rgba(147, 112, 219, 0.3) inset;
    transition: all 0.3s ease;
}
.form-control:focus {
    border-color: rgba(229, 36, 255, 1) !important;
    box-shadow: 0 0 25px rgba(229, 36, 255, 0.4) inset !important;
}
.checkbox-label {
    color: #FFA500;
    font-weight: normal;
    display: flex;
    align-items: center;
    cursor: pointer;
}
.checkbox-label input {
    margin-right: 10px;
    width: 20px;
    height: 20px;
}
.btn-submit {
    padding: 12px 40px !important;
    border-radius: 50px !important;
    font-weight: bold;
    font-size: 18px;
    margin-top: 20px;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.9) 0%, rgba(29, 29, 29, 0.9) 100%) !important;
    border: 2px solid rgba(229, 36, 255, 1) !important;
    color: rgba(229, 36, 255, 1);
    width: 100%;
    transition: all 0.3s ease;
}
.btn-submit:hover {
    background: rgba(229, 36, 255, 0.9) !important;
    color: white !important;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.7);
}
.message {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}
.success {
    background: rgba(46, 204, 113, 0.2);
    border: 1px solid #2ecc71;
    color: #2ecc71;
}
.error {
    background: rgba(231, 76, 60, 0.2);
    border: 1px solid #e74c3c;
    color: #e74c3c;
}
.info-box {
    background: rgba(30, 30, 30, 0.7);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    border-left: 3px solid #FFA500;
    color: #FFE4E1;
}
.info-box h4 {
    color: #BA55D3;
    margin-bottom: 10px;
}
.forbidden-attempts {
    color: #ff5252;
    font-weight: bold;
    margin-top: 5px;
    font-size: 0.9rem;
}
</style>
</head>
<body>
<div class="navbar">
    <a href="./search">Abyss Search</a>
    <a href="./main">Main</a>
</div>
<div class="content-main">
    <div class="add-link-container">
        <h2 class="form-title">Добавить новую ссылку в Abyss Search</h2>
        <?php if (!empty($message)): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="info-box">
            <h4>Информация:</h4>
            <p>Ваш уровень прав: <strong><?= (int)$userLevel ?></strong> (<?= $userLevel > 3 ? 'можете скрыть авторство' : 'недостаточно прав для скрытия авторства' ?>)</p>
            <p>Поля помеченные <span style="color:#e524ff">*</span> обязательны для заполнения</p>
        </div>
        <form method="POST" action="" id="addLinkForm">
            <div class="form-group">
                <label for="url">URL ссылки <span style="color:#e524ff">*</span></label>
                <input type="url" class="form-control" id="url" name="url" placeholder="https://example.com" value="<?= isset($_POST['url']) ? htmlspecialchars($_POST['url']) : '' ?>" required>
                <div id="forbiddenMessage" class="forbidden-attempts"></div>
            </div>
            <div class="form-group">
                <label for="title">Заголовок <span style="color:#e524ff">*</span></label>
                <input type="text" class="form-control" id="title" name="title" placeholder="Краткое описание ссылки" value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Подробное описание содержимого ссылки. Это поможет лучше и быстрее находить ссылку"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>
            <div class="form-group">
                <label for="keywords">Ключевые слова (теги) <span style="color:#e524ff">*</span></label>
                <input type="text" class="form-control" id="keywords" name="keywords" placeholder="через запятую: php, javascript, космос" value="<?= isset($_POST['keywords']) ? htmlspecialchars($_POST['keywords']) : '' ?>" required>
                <small class="form-text" style="color: #9370DB;">Укажите релевантные слова для поиска</small>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="nsfw" value="1"
                        <?= ($nsfwInput === '1') ? 'checked' : '' ?>>
                    NSFW (не безопасный) контент
                </label>
            </div>
            <?php if ($userLevel > 3): ?>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="hide_user" value="1"
                        <?= ($hideUserInput === '1') ? 'checked' : '' ?>>
                    Не указывать меня как автора ссылки
                </label>
            </div>
            <?php endif; ?>
            <button type="submit" class="button btn-submit">Добавить во вселенную</button>
        </form>
    </div>
</div>
<script>
    $(document).ready(function() {
        const urlInput = $('#url');
        const titleInput = $('#title');
        const descriptionInput = $('#description');
        const form = $('#addLinkForm');
        const forbiddenMessage = $('#forbiddenMessage');
        let forbiddenDomains = [];
        let forbiddenAttempts = parseInt(localStorage.getItem('forbiddenAttempts') || 0);
        let currentXhr = null;
        updateAttemptsCounter();
        async function loadForbiddenDomains() {
            try {
                const response = await $.ajax({
                    url: './system/get_forbidden_domains',
                    type: 'GET',
                    dataType: 'json'
                });
                if (Array.isArray(response.domains)) {
                    forbiddenDomains = response.domains.map(d => d.toLowerCase());
                    console.log('Список запрещённых доменов загружен.');
                } else {
                    console.error('Неверный формат данных из get_forbidden_domains');
                }
            } catch (error) {
                console.error('Ошибка сети при загрузке списка запрещённых доменов:', error);
            }
        }
        loadForbiddenDomains();
        async function fetchMetadata(url) {
            if (currentXhr && currentXhr.readyState !== 4) {
                currentXhr.abort();
            }
            let domain;
            try {
                domain = new URL(url).hostname.replace(/^www\./, '');
            } catch(e) {
                return;
            }
            const isForbidden = forbiddenDomains.some(d => domain.includes(d));
            if (isForbidden) {
                titleInput.val(domain);
                descriptionInput.val('');
                return;
            }
            try {
                currentXhr = $.ajax({
                    url: `./system/get_metadata?url=${encodeURIComponent(url)}`,
                    type: 'GET',
                    dataType: 'json'
                });
                const data = await currentXhr;
                titleInput.val(data.title || domain);
                descriptionInput.val(data.description || '');
            } catch (error) {
                if (error.statusText !== 'abort' && error.statusText !== 'error') {
                    titleInput.val(domain);
                    descriptionInput.val('');
                }
            }
        }
        if (urlInput.length && titleInput.length && descriptionInput.length) {
            urlInput.on('blur', function() {
                const url = urlInput.val().trim();
                if (!url) return;
                fetchMetadata(url).catch(console.error);
            });
        }
        form.on('submit', function(e) {
            const url = urlInput.val().trim().toLowerCase();
            const isForbidden = forbiddenDomains.some(domain => {
                return url.includes(domain);
            });
            if (isForbidden) {
                e.preventDefault();
                forbiddenAttempts++;
                localStorage.setItem('forbiddenAttempts', forbiddenAttempts);
                updateAttemptsCounter();
                if (forbiddenAttempts >= 3) {
                    const isAdult = confirm("Вам есть 18 и более лет?");
                    if (isAdult) {
                        alert("Мы не работаем с этими ресурсами. СОВСЕМ!!! ВОТ ВООБЩЕ!!!");
                        forbiddenAttempts = 0;
                        localStorage.setItem('forbiddenAttempts', 0);
                        updateAttemptsCounter();
                    } else {
                        alert("Вы не можете добавлять ссылки с этих ресурсов.");
                    }
                } else {
                    alert("Наш сервис не работает с этими ресурсами!");
                }
            }
        });
        function updateAttemptsCounter() {
            if (forbiddenAttempts > 0) {
                forbiddenMessage.text(`Попыток добавления запрещенных ресурсов: ${forbiddenAttempts}/3`);
            } else {
                forbiddenMessage.text('');
            }
        }
    });
</script>
</body>
</html>
<?php 
session_write_close();
?>