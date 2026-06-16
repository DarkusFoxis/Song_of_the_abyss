<?php
require_once __DIR__ . '/../template/auth.php';
require_once __DIR__ . '/post_helpers.php';
auth_start_session();
auth_sync_session_from_token();
$authUser = auth_require_user('/profile/login');
$canUseNsfw = false;

if(!isset($_SESSION['user'])) {
    header("Location: ../profile/login");
} else {
    require_once '../template/conn.php';
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        echo "Ошибка соединения: " . mysqli_connect_error();
        exit;
    }
    mysqli_set_charset($conn, 'utf8mb4');

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $blackStmt = $conn->prepare("SELECT 1 FROM black_ip WHERE ip = ? LIMIT 1");
    $blackStmt->bind_param('s', $ip_address);
    $blackStmt->execute();
    $blackResult = $blackStmt->get_result();
    $isBlockedIp = $blackResult && $blackResult->num_rows > 0;
    $blackStmt->close();
    if ($isBlockedIp) {
        unset($_SESSION['user']);
        unset($_SESSION['username']);
        $_SESSION['error'] = "При входе в аккаунт произошла ошибка. Кажется, ваше местоположение было заблокированно.";
        session_destroy();
        header("Location: ../profile/main");
        exit;
    }

    $login = $_SESSION['user'];
    $userStmt = $conn->prepare(
        "SELECT u.*, sg.lvl
         FROM users u
         JOIN site_group sg ON u.permissions = sg.name
         WHERE u.login = ?
         LIMIT 1"
    );
    $userStmt->bind_param('s', $login);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    if (!$user) {
        session_destroy();
        header("Location: ../profile/login");
        exit;
    }
    $canUseNsfw = nsfw_user_has_access($user);
    $userId = (int)$user['id'];
    $inventoryStmt = $conn->prepare("SELECT id_user FROM invent WHERE id_user = ? LIMIT 1");
    $inventoryStmt->bind_param('i', $userId);
    $inventoryStmt->execute();
    $inventoryResult = $inventoryStmt->get_result();
    if ($user['lvl'] == 0) {
        $inventoryStmt->close();
        $_SESSION["perm_error"] = "Вы заблокированны на сайте. Ваши возможности ограничены.";
        header("Location: ../403");
        exit;
    } else if ($user['lvl'] == 1) {
        $inventoryStmt->close();
        $_SESSION["perm_error"] = "Вы не верифицированны на сайте.";
        header("Location: ../403");
        exit;
    }
    if (!$inventoryResult || $inventoryResult->num_rows === 0) {
        $inventoryStmt->close();
        $_SESSION["perm_error"] = "У вас не активирован инвентарь. Пожалуйста, создайте его.";
        header("Location: ../403");
        exit;
    }
    $inventoryStmt->close();
    $simv_count = 3000;
    $mb = 12;
    $count = 3;
    if ($user['lvl'] >= 3) {
        $simv_count = 6000;
        $mb = 24;
        $count = 9;
    }
    $edit_mode = false;
    $edit_post = null;
    $disable_edit = false;

    if (isset($_GET['id'])) {
        $edit_id = intval($_GET['id']);
        $editStmt = $conn->prepare("SELECT * FROM post WHERE id_post = ? LIMIT 1");
        $editStmt->bind_param('i', $edit_id);
        $editStmt->execute();
        $edit_result = $editStmt->get_result();

        if ($edit_result && $edit_result->num_rows > 0) {
            $edit_post = $edit_result->fetch_assoc();

            if ($edit_post['id_user'] == $userId) {
                $edit_mode = true;

                $post_time = strtotime($edit_post['data']);
                $current_time = time();
                $hours_diff = ($current_time - $post_time) / 3600;
                
                if ($hours_diff > 3) {
                    $disable_edit = true;
                }
            }
        }
        $editStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактор поста</title>
    <link rel = "icon" href = "../img/icon.png">
    <link rel="stylesheet" href="../style/all.min.css">
    <link rel = "stylesheet" href = "../style/style.css">
        <link rel="stylesheet" href="../style/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300;400;600;700&amp;display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php echo security_csrf_meta_tags(); ?>
    <style>
        .container {
            margin: 0 auto;
        }
        .navbar {
            clear: both;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0 1rem;
        }
        .navbar a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .navbar a i {
            font-size: 14px;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        .navbar a:hover i { opacity: 1; }
        form {
            background: rgba(60, 9, 121, 0.35);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 24px;
            transition: all 0.35s ease;
        }
        form:hover {
            border-color: rgba(186, 20, 126, 0.25);
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.85);
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            color: #fff;
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 14px;
            margin-bottom: 14px;
            transition: all 0.3s ease;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: rgba(186, 20, 126, 0.5);
            box-shadow: 0 0 20px rgba(186, 20, 126, 0.2);
        }
        input[type="file"] {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px dashed rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        input[type="file"]:hover {
            border-color: rgba(186, 20, 126, 0.4);
            background: rgba(0, 0, 0, 0.3);
        }
        input[type="submit"] {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 12px 28px;
            border-radius: 14px;
            cursor: pointer;
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        input[type="submit"]:hover {
            background: rgba(186, 20, 126, 0.3);
            border-color: rgba(186, 20, 126, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(186, 20, 126, 0.25);
        }
        .edit-disabled {
            opacity: 0.7;
            pointer-events: none;
            position: relative;
        }
        .edit-disabled::after {
            content: "Редактирование заблокировано (пост старше 3 часов)";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(40, 10, 70, 0.85);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff6b6b;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            padding: 20px;
            border-radius: 20px;
            z-index: 100;
        }
        #tags {
            font-size: 14px;
        }
        label[style*="display:flex"] {
            display: flex !important;
            align-items: center;
            gap: 8px;
            margin: 12px 0;
            font-weight: 600;
        }
        label[style*="display:flex"] input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #ba147e;
        }
        details {
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 0;
            transition: all 0.3s;
        }
        details:hover {
            border-color: rgba(186, 20, 126, 0.2);
        }
        details summary {
            padding: 12px 16px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            list-style: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        details summary::-webkit-details-marker {
            display: none;
        }
        details summary::before {
            content: '\f05a';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: rgba(186, 20, 126, 0.6);
            font-size: 16px;
        }
        details[open] summary {
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 8px;
        }
        details ol {
            padding: 12px 16px 12px 32px;
        }
        details li {
            margin-bottom: 8px;
            line-height: 1.5;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        details p {
            margin-left: 15px;
            margin-bottom: 8px;
            line-height: 1.5;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        .media-file-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(102, 51, 153, 0.3);
            color: #fff;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 14px;
            border: 1px solid rgba(102, 51, 153, 0.4);
        }
        .media-file-badge i {
            opacity: 0.7;
        }
        .media-remove-btn {
            background: rgba(186, 20, 126, 0.3);
            border: 1px solid rgba(186, 20, 126, 0.4);
            color: #fff;
            padding: 6px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Montserrat Alternates', sans-serif;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .media-remove-btn:hover {
            background: rgba(186, 20, 126, 0.5);
            border-color: rgba(186, 20, 126, 0.6);
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="#" onclick="window.history.back()"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="#"><i class="fas fa-edit"></i> Редактор постов V1.6</a>
</div>
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="header">
                    <h2><i class="fas fa-pen-fancy"></i> Редактор постов</h2>
                </div>
                <div class="container">
                    <details><summary>Перед написанием поста, ознакомьтесь с кратким перечнем правил:</summary>
                    <ol>
                        <li>Политика запрещена. Файлы содержащие изображение политики/политических деятелей так же запрещены.</li>
                        <li>Пиар/Реклама, не обсуждённая с администратором запрещена.</li>
                        <li>Посты, нарущающие законодательство, запрещены в не зависимости от вашего местожиnельства.</li>
                        <li>Эротика, 18+ контент, контент по обходу блокировок, драки, и контент серии "Премия Дарвина" без откровенно шокирующих сцен требуют метки NSFW. Без неё ваш пост могут удалить.</li>
                    </ol>
                    <p>Несоблюдение правил влечёт удаление поста. Так же, повторное нарушение влечёт блокировку аккаунта. Публикуя пост, вы соглашаетесь с правилами, даже если пробежали глазами, прочитали не вникая, или во время чтения в один глаз влетело, а из уха вылетело.</p></details>
                    <div class="<?= $disable_edit ? 'edit-disabled' : '' ?>">
                        <form id="post-form" method="post" enctype="multipart/form-data">
                            <?php echo security_csrf_input(); ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_mode ? $edit_post['id_post'] : ''; ?>">
                            <label for="title"><i class="fas fa-heading"></i> Заголовок:</label>
                            <input type="text" id="title" name="title" required placeholder="Что у вас нового?" minlength="5" maxlength="150" value="<?php echo $edit_mode ? htmlspecialchars($edit_post['title']) : ''; ?>">

                            <label for="post"><i class="fas fa-align-left"></i> Текст:</label>
                            <textarea id="post" name="post" maxlength="<?php echo $simv_count; ?>" placeholder="Поделитесь своими эмоциями! Напишите текст вашего поста! Максимум: <?php echo $simv_count; ?> символов"><?php echo $edit_mode ? htmlspecialchars(str_replace('<br />', "\n", $edit_post['post'])) : ''; ?></textarea>

                            <label for="tags"><i class="fas fa-tags"></i> Теги (через запятую, макс. 5):</label>
                            <input type="text" id="tags" name="tags" maxlength="250" placeholder="например: арт, музыка, мемы, теории"
                               value="<?php
                               if ($edit_mode && isset($edit_post['id_post'])) {
                                   $editTags = get_post_tags($conn, (int)$edit_post['id_post']);
                                   echo htmlspecialchars(implode(', ', $editTags));
                               }
                               ?>">
                            <p style="font-size:13px;color:#aaa;">Максимум 5 тегов, до 30 символов каждый. Только буквы, цифры и подчёркивания.</p>

                            <label for="media"><i class="fas fa-paperclip"></i> Медиа (Изображения, аудио до: <?php echo $mb; ?> мегабайт, видео: <?php echo $mb * 1.5; ?> мегабайт) и  до <?php echo $count; ?> штук:</label>
                                    <input type="file" id="media" name="media[]" accept="image/*, audio/mpeg, video/mp4" multiple>
                                    <?php if ($canUseNsfw) : ?>
                                        <label style="display:flex;align-items:center;gap:8px;margin:12px 0;">
                                            <input type="checkbox" name="nsfw" value="1" <?php echo ($edit_mode && (int)($edit_post['NSFW'] ?? 0) === 1) ? 'checked' : ''; ?>>
                                            <span>NSFW</span>
                                        </label>
                                        <p style="font-size:13px;color:#ffd6de;">Для NSFW-постов лимит размера вложений меньше на 25%, а оценки отключены.</p>
                                    <?php endif; ?>

                            <?php if ($edit_mode && !empty($edit_post['media'])): ?>
                                <div id="current-media">
                                    <p>Текущие вложения:</p>
                                    <?php foreach (explode(',', $edit_post['media']) as $file): ?>
                                        <div style="margin-bottom:8px; display:flex; align-items:center; gap:10px;">
                                            <span class="media-file-badge"><i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($file); ?></span>
                                            <button type="button" class="media-remove-btn" onclick="removeMedia('<?php echo htmlspecialchars($file); ?>', <?php echo $edit_post['id_post']; ?>, this)"><i class="fas fa-trash-alt"></i> Удалить</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <center><input type="submit" value="<?php echo $edit_mode ? 'Сохранить изменения' : 'Опубликовать'; ?>"></center>
                        </form>
                    </div>
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
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            if (!formData.get('csrf_token')) {
                formData.append('csrf_token', csrfToken);
            }
            $("#response").html("Отправка поста, ожидайте...");
            var url = "upload_core";
            if ($("input[name='edit_id']").val()) {
                url = "upload_core?edit=1";
            }
            $.ajax({
                url: url,
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
    window.removeMedia = function(file, postId, btn) {
        if (confirm('Удалить вложение?')) {
            $.ajax({
                url: 'remove_media',
                type: 'POST',
                data: { file: file, post_id: postId, csrf_token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
                success: function(response) {
                    $(btn).parent().remove();
                    alert(response);
                },
                error: function(xhr, status, error) {
                    alert('Ошибка удаления вложения: ' + error);
                }
            });
        }
    }
</script>
</body>
</html>