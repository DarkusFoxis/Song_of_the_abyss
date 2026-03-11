<?php
require_once __DIR__ . '/../template/auth.php';

auth_start_session();

$currentUser = auth_get_current_user();
if ($currentUser !== null) {
    $_SESSION['error'] = 'Вы находитесь в аккаунте.';
    header('Location: main');
    exit;
}

$redirectTarget = '';
if (isset($_GET['redirect']) && is_string($_GET['redirect']) && auth_is_safe_redirect($_GET['redirect'])) {
    $redirectTarget = trim($_GET['redirect']);
}

if ($redirectTarget === '' && isset($_SESSION['auth_redirect']) && is_string($_SESSION['auth_redirect']) && auth_is_safe_redirect($_SESSION['auth_redirect'])) {
    $redirectTarget = trim($_SESSION['auth_redirect']);
}

if ($redirectTarget === '' && !empty($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER'])) {
    $fromReferer = auth_extract_local_path_from_referer($_SERVER['HTTP_REFERER']);
    if ($fromReferer !== null && !preg_match('#/profile/(login|registration|main|log|reg|logout)$#', $fromReferer)) {
        $redirectTarget = $fromReferer;
    }
}

if ($redirectTarget !== '') {
    $_SESSION['auth_redirect'] = $redirectTarget;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="icon" href="../img/icon.png">
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
                border: 1px solid black;
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
            <form action="log" method="POST">
                <?php if ($redirectTarget !== ''): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
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
            const togglePassword = document.getElementById('see_password');

            const showOrHidePassword = () => {
                const password = document.getElementById('pass');
                if (password.type === 'password') {
                    password.type = 'text';
                } else {
                    password.type = 'password';
                }
            };

            togglePassword.addEventListener('change', showOrHidePassword);
        </script>
    </body>
</html>

