<?php
session_start();
if (file_exists('../../template/conn.php')) {
    require_once'../../template/conn.php';
} else {
    define('DB_CONNECTION_ERROR', 'Файл конфигурации БД conn.php не найден.');
}
function get_db_connection() {
    if (defined('DB_CONNECTION_ERROR')) return null;
    global $host, $log, $password_sql, $database;
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn || $conn->connect_error) {
        return null;
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}
function display_upload_form($user_lvl, $user_id, $user_login, $username, $cooldown_active = false) {
    $can_upload_cover = ($user_lvl >= 3);
    $disabled_attr = $cooldown_active ? 'disabled' : '';
    $cooldown_message = $cooldown_active ? '<p style="color: #FF8C00; font-weight: bold; margin-bottom: 15px;">Вы сможете загрузить следующий трек через некоторое время.</p>' : '';
    $form_html = <<<HTML
    <div class="upload-form-container">
        <h2>Загрузить новую песню</h2>
        <p class="user-greeting">Вы вошли как: <strong>{$username}</strong></p>
        {$cooldown_message}
        <form action="upload_handler.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="{$user_id}"> 
            <input type="hidden" name="user_login" value="{$user_login}">
            <div class="form-group">
                <label for="audio_name">Название трека:</label>
                <input type="text" id="audio_name" name="audio_name" class="form-control" required {$disabled_attr}>
            </div>
            
            <div class="form-group">
                <label for="author_name">Исполнитель:</label>
                <input type="text" id="author_name" name="author_name" class="form-control" required {$disabled_attr}>
            </div>
            <div class="form-group">
                <label for="audio_file">Аудиофайл (mp3, wav, ogg):</label>
                <input type="file" id="audio_file" name="audio_file" class="form-control-file" accept=".mp3,.wav,.ogg" required {$disabled_attr}>
            </div>
HTML;
    if ($can_upload_cover) {
        $form_html .= <<<HTML
            <div class="form-group">
                <label for="cover_file">Обложка (jpg, png, необязательно):</label>
                <input type="file" id="cover_file" name="cover_file" class="form-control-file" accept=".jpg,.jpeg,.png" {$disabled_attr}>
            </div>
HTML;
    } else {
        $form_html .= "<p class=\"info-text\"><em>Загрузка обложек доступна для Премиум пользователей.</em></p>";
    }
    $form_html .= <<<HTML
            <div class="form-group checkbox-group">
                <input type="checkbox" id="self_author" name="self_author" value="1" {$disabled_attr} class="custom-checkbox-input">
                <label for="self_author" class="custom-checkbox-label">Я автор этого трека</label>
            </div>
            <button type="submit" class="btn btn-primary" {$disabled_attr}>Загрузить</button>
        </form>
        <hr class="form-divider">
        <form action="logout.php" method="post" style="margin-top: 15px;">
            <button type="submit" class="btn btn-secondary">Выйти из аккаунта</button>
        </form>
    </div>
HTML;
    return $form_html;
}
function display_html_wrapper($content) {
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Загрузка аудио</title>';
    echo '<style>
    body {
        background-color: rgba(10, 5, 30, 0.85);
        color: #E0E0E0;
        font-family: "Montserrat Alternates", sans-serif;
        margin: 0;
        padding: 20px;
        overflow-x: hidden;
    }
    html { scrollbar-width: thin; scrollbar-color: #9966cc #333; }
    .upload-form-container {
        background-color: rgba(25, 15, 55, 0.7);
        padding: 25px;
        border-radius: 8px;
        border: 1px solid rgba(157, 78, 221, 0.5);
        box-shadow: 0 0 20px rgba(157, 78, 221, 0.3);
    }
    h2 {
        color: #BA55D3;
        text-align: center;
        margin-bottom: 20px;
        font-weight: 300;
    }
    .user-greeting { font-size: 0.9em; margin-bottom: 15px; color: #ccc; text-align: center; }
    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        color: #C0C0C0;
        font-size: 0.95em;
    }
    .form-control, .form-control-file {
        width: 100%;
        padding: 10px;
        background-color: #000;
        color: #E0E0E0;
        border: 1px solid #3F0071;
        border-radius: 5px;
        box-sizing: border-box;
        font-family: inherit;
        font-size: 1em;
    }
    .form-control:focus {
        border-color: #9D4EDD;
        box-shadow: 0 0 8px rgba(157, 78, 221, 0.5);
        outline: none;
    }
    .form-control-file {
         padding: 5px;
    }
    .checkbox-group {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
    }
    .custom-checkbox-input {
        margin-right: 10px;
        width: 18px;
        height: 18px;
        accent-color: #9D4EDD;
        cursor: pointer;
    }
    .custom-checkbox-label { margin-bottom: 0; cursor: pointer; user-select: none; }
    .btn {
        padding: 12px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 1em;
        cursor: pointer;
        transition: all 0.3s ease;
        border-radius: 25px;
        font-family: inherit;
        border: 2px solid transparent;
        width: 100%;
        box-sizing: border-box;
    }
    .btn-primary {
        background: linear-gradient(135deg, #3F0071 0%, #9D4EDD 100%);
        color: white;
        border-color: #9D4EDD; 
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #5A00A3 0%, #B566F0 100%);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6);
        transform: translateY(-1px);
    }
    .btn-primary:disabled {
        background: #555;
        border-color: #444;
        color: #999;
        cursor: not-allowed;
        opacity: 0.7;
    }
    .btn-secondary {
        background-color: #4A4A4A;
        color: #D0D0D0;
        border: 1px solid #666;
    }
    .btn-secondary:hover {
        background-color: #5A5A5A;
        color: #FFF;
    }
    .form-divider { margin-top: 25px; margin-bottom: 15px; border: 0; border-top: 1px solid rgba(157, 78, 221, 0.3); }
    .info-text { font-size:0.85em; color: #aaa; margin-bottom:15px; margin-top: -10px; }
    .message-box { padding: 15px; margin: 0 0 20px 0; border-radius: 5px; text-align: center; font-size: 0.95em; }
    .error { background-color: rgba(200, 0, 0, 0.3); color: #ffcdd2; border: 1px solid rgba(200,0,0,0.5); }
    .success { background-color: rgba(0, 150, 0, 0.2); color: #c8e6c9; border: 1px solid rgba(0,150,0,0.4); }
    .info { background-color: rgba(21, 101, 192, 0.2); color: #bbdefb; border: 1px solid rgba(21,101,192,0.4); }
    </style>';
    echo '</head><body>';
    echo $content;
    echo '</body></html>';
}
//Основная логика.
if (isset($_SESSION['error'])) {
    display_html_wrapper('<div class="message-box error">'.htmlspecialchars($_SESSION['error']).'</div><p style="text-align:center;"><a href="login.html" target="_self">Повторить вход</a></p>');
    unset($_SESSION['error']);
} else if (isset($_SESSION['success_log'])) {
    display_html_wrapper('<div class="message-box success">'.htmlspecialchars($_SESSION['success_log']).'<script>setTimeout(function() { window.location.href = "core.php?"; }, 3000);</script>');
    unset($_SESSION['success_log']);
} else if (isset($_SESSION['success'])) {
    $success_message_text = htmlspecialchars($_SESSION['success']);
    unset($_SESSION['success']);
    $script = "<script>";
    $script .= "try {";
    $script .= "setTimeout(function() {";
    $script .= "var refreshButtons = window.parent && window.parent.document.getElementsByClassName('refreshTracksBtn');";
    $script .= "if (refreshButtons && refreshButtons.length > 0) {";
    $script .= "refreshButtons[0].click();";
    $script .= "}";
    $script .= "if (window.parent && typeof window.parent.closeUploadIframe === 'function') {";
    $script .= "window.parent.closeUploadIframe();";
    $script .= "} else {"; 
    $script .= "/* window.location.href = 'about:blank'; */";
    $script .= "}";
    $script .= "}, 1500);";
    $script .= "} catch (e) { console.error('Error in core.php success script:', e); }";
    $script .= "</script>";
    $content_for_wrapper = '<div class="message-box success">'.$success_message_text.'</div>';
    $content_for_wrapper .= '<p style="text-align:center;">Список треков будет обновлен, и окно закроется...</p>';
    $content_for_wrapper .= $script;
    display_html_wrapper($content_for_wrapper);
} else {
    if (!isset($_SESSION['user'])) {
        header('Location: login.html');
        exit;
    }
    $conn = get_db_connection();
    if (!$conn) {
        $error_message = defined('DB_CONNECTION_ERROR') ? DB_CONNECTION_ERROR : 'Не удалось подключиться к базе данных.';
        display_html_wrapper('<div class="message-box error">'.htmlspecialchars($error_message).'</div>');
        exit;
    }
    $user_login_session = $_SESSION['user'];
    $username_session = $_SESSION['username'] ?? 'Пользователь';
    $escaped_login = mysqli_real_escape_string($conn, $user_login_session);
    $user_query_sql = "SELECT u.id, u.login, u.permissions, sg.lvl, COALESCE(max_audio.latest_upload_time, 0) AS last_upload_ts FROM users u JOIN site_group sg ON u.permissions = sg.name LEFT JOIN (SELECT user_id, MAX(data_upload) AS latest_upload_time FROM audio GROUP BY user_id) max_audio ON u.id = max_audio.user_id WHERE u.login = '{$escaped_login}'";
    $user_result = mysqli_query($conn, $user_query_sql);
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user_data = mysqli_fetch_assoc($user_result);
        $user_lvl = (int)$user_data['lvl'];
        $user_id = (int)$user_data['id'];
        $last_upload_ts = (int)$user_data['last_upload_ts'];
        mysqli_close($conn);
        if ($user_lvl === 0 || $user_lvl === 1) {
            display_html_wrapper('<div class="message-box info">Извините, у вас не достаточно прав для загрузки треков.</div>');
        } else {
            $cooldown_active = false;
            if ($user_lvl != 6) {
                $current_time = time();
                $cooldown_period = 5 * 60;
                if (($current_time - $last_upload_ts) < $cooldown_period) {
                    $cooldown_active = true;
                }
            }
            $form_content = display_upload_form($user_lvl, $user_id, $user_login_session, $username_session, $cooldown_active);
            display_html_wrapper($form_content);
        }
    } else {
        mysqli_close($conn);
        $_SESSION['error'] = 'Ошибка получения данных пользователя. Пожалуйста, войдите снова.';
        session_unset(); 
        session_destroy();
        header('Location: core.php');
        exit;
    }
}
session_write_close();