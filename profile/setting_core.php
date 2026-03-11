<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Неверный метод запроса.";
    header("Location: setting");
    exit;
}

if (!isset($_POST['action'])) {
    $_SESSION['error'] = "Действие не указано.";
    header("Location: setting");
    exit;
}

$action = $_POST['action'];
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function sendJsonResponse($status, $message) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message
    ]);
    exit;
}

$authUser = auth_get_current_user();
if ($authUser === null) {
    if ($is_ajax) {
        sendJsonResponse('error', '�� ������ ���� ������������.');
    }
    $_SESSION['error'] = "�� ������ ���� ������������.";
    header("Location: login");
    exit;
}

if ($action !== 'avatar') {
    require_once '../template/conn.php';
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        if ($is_ajax) {
            sendJsonResponse('error', 'Ошибка соединения с базой данных.');
        }
        $_SESSION['error'] = "Ошибка соединения с базой данных.";
        header("Location: setting");
        exit;
    }
}

switch ($action) {

    case 'avatar':
        require_once '../template/conn.php';
        $pdo = new PDO("mysql:host=$host;dbname=$database", $log, $password_sql);

        if (isset($_FILES['avatar']) && isset($_SESSION['user'])) {
            $username = $_SESSION['user'];

            $stmt = $pdo->prepare("SELECT avatar FROM users WHERE login = :username");
            $stmt->execute(['username' => $username]);
            $old_avatar = $stmt->fetchColumn();

            if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['size'] > 0) {
                if ($_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
                    $upload_dir = 'avatars/';
                    $avatar_name = $_FILES['avatar']['name'];
                    $avatar_ext = pathinfo($avatar_name, PATHINFO_EXTENSION);

                    if ($avatar_ext == "png" or $avatar_ext == "jpg" or $avatar_ext == "webp" or $avatar_ext == "jpeg" or $avatar_ext == "PNG") {
                        $new_avatar = $username . '_' . uniqid() . '.' . $avatar_ext;

                        if ($old_avatar) {
                            if ($old_avatar !== "avatar.png") {
                                unlink($upload_dir . $old_avatar);
                                $stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE username = :username");
                                $stmt->execute(['username' => $username]);
                            }
                        }

                        $upload_path = $upload_dir . $new_avatar;
                        $source_image = imagecreatefromstring(file_get_contents($_FILES['avatar']['tmp_name']));
                        $width = imagesx($source_image);
                        $height = imagesy($source_image);
                        $new_width = $new_height = 550;
                        $crop_width = $crop_height = min($width, $height);
                        $cropped_image = imagecrop($source_image, ['x' => ($width - $crop_width) / 2, 'y' => ($height - $crop_height) / 2, 'width' => $crop_width, 'height' => $crop_height]);
                        $resized_image = imagescale($cropped_image, $new_width, $new_height);
                        imagejpeg($resized_image, $upload_path);
                        imagedestroy($source_image);
                        imagedestroy($cropped_image);
                        imagedestroy($resized_image);

                        $stmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE login = :username");
                        $stmt->execute(['avatar' => $new_avatar, 'username' => $username]);

                        $_SESSION["great"] = "Аватар успешно загружен!";
                        header("Location: setting");
                        exit();
                    } else {
                        $_SESSION["error"] = "Неизвестное расширение файла. Используйте jpg(jpeg), png или webp.";
                        header("Location: setting");
                        exit();
                    }
                } else {
                    $_SESSION["error"] = "Файл слишком большой. Пожалуйста, выберите файл менее 5-ти мегабайт.";
                    header("Location: setting");
                    exit();
                }
            } else {
                $_SESSION["error"] = "Вы не загрузили файл.";
                header("Location: setting");
                exit();
            }
        }
        break;

    case 'new_password':
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $password_bd = $user["password"];
            $old_password = mysqli_real_escape_string($conn, $_POST['old_password']);
            $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);

            if (password_verify($old_password, $password_bd)) {
                if (password_verify($new_password, $password_bd)) {
                    $_SESSION['error'] = "Вы не можете изменить пароль на точно такой же. Придумайте новый.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $conn->query("UPDATE users SET password = '$hashed_password' WHERE login = '$login'");
                    $_SESSION['great'] = "Пароль успешно изменён!";
                }
            } else {
                $_SESSION['error'] = "Неверный старый пароль.";
            }
        } else {
            $_SESSION['error'] = "Ошибка при чтении базы данных.";
        }

        mysqli_close($conn);
        header("Location: setting");
        exit;
        break;

    case 'birthdate':
        $new_birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);

        $update_query = "UPDATE personal_data SET birthdate = '$new_birthdate' WHERE login = '$login'";
        if ($conn->query($update_query) === TRUE) {
            $_SESSION["great"] = "Дата рождения успешно изменена!";
        } else {
            $_SESSION["error"] = "Ошибка при обновлении даты рождения: " . $conn->error;
        }

        mysqli_close($conn);
        header("Location: setting");
        exit;
        break;

    case 'delete_account':
        $user = $_SESSION['user'];
        $data = $conn->query("SELECT * FROM users WHERE login = '$user'");

        if ($data->num_rows > 0) {
            $row = $data->fetch_assoc();
            $password_bd = $row["password"];
            $password = mysqli_real_escape_string($conn, $_POST['password']);

            if (password_verify($password, $password_bd)) {
                $final = $conn->query("DELETE FROM users WHERE login = '$user'");
                if ($final) {
                    unset($_SESSION['user']);
                    unset($_SESSION['username']);
                    $_SESSION['great'] = "Ваш аккаунт удалён. Надеемся на нашу скорую встречу вновь!";
                } else {
                    echo "Error SQL: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['error'] = "Неверный пароль.";
            }
        } else {
            $_SESSION['error'] = "Пользователь не найден.";
        }

        header("Location: main");
        exit;
        break;

    case 'bio_redact':
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);
        $user = $result->fetch_assoc();

        if (preg_match('/<(script|style|iframe|frame|frameset|meta|link|object|a|b|s|body|head|div|input|rextarea|form)/i', $bio) || (strlen(preg_replace('/\s/', '', $bio)) / strlen($bio) < 0.5)) {
            $_SESSION["error"] = "Ваш статус содержит HTML теги или большое количество пробелов. Пожалуйста, удалите их и попробуйте снова.";
            header("Location: setting");
            exit;
        }

        $post_spaces = substr_count($bio, ' ');
        $space_percent = round(($post_spaces / mb_strlen($bio)) * 100, 2);
        if ($space_percent > 20) {
            $_SESSION["error"] = "Ошибка: Слишком много пробелов в сообщении.";
            header("Location: setting");
            exit;
        }

        $enter_count = substr_count($bio, '\r\n');
        $enter_percent = round(($enter_count / mb_strlen($bio)) * 100, 2);
        if ($enter_percent > 15) {
            $_SESSION["error"] = "Ошибка: Слишком много переходов на новую строку в сообщении.";
            header("Location: setting");
            exit;
        }

        $bio = htmlspecialchars($bio);
        $update_query = "UPDATE users SET BIO = '$bio' WHERE login = '$login'";
        if ($conn->query($update_query) === TRUE) {
            $_SESSION["great"] = "Статус успешно изменен!";
            header("Location: setting");
            exit;
        } else {
            $_SESSION["error"] = "Ошибка при обновлении статуса: " . $conn->error;
            header("Location: setting");
            exit;
        }
        break;

    case 'nsfw':
        $nsfw = mysqli_real_escape_string($conn, $_POST['nsfw']);
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);
        $user = $result->fetch_assoc();

        $update_query = "UPDATE users SET NSFW = '$nsfw' WHERE login = '$login'";
        if ($conn->query($update_query) === TRUE) {
            $_SESSION["great"] = "Доступ к NSFW успешно изменен!";
            header("Location: setting");
        } else {
            $_SESSION["error"] = "Ошибка при обновлении доступа к NSFW: " . $conn->error;
            header("Location: setting");
        }
        exit;
        break;

    case 'promo':
        require_once '../template/invent_api.php';

        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user["id"];
            $promo = mysqli_real_escape_string($conn, $_POST['promocode']);

            $promo_query = "SELECT * FROM code WHERE code = '$promo'";
            $promo_result = $conn->query($promo_query);

            if ($promo_result->num_rows > 0) {
                $promocode = $promo_result->fetch_assoc();

                $usage_qurery = "SELECT * FROM promo WHERE code = '$promo' AND id_user = '$user_id'";
                $usage_result = $conn->query($usage_qurery);

                if ($usage_result->num_rows > 0) {
                    mysqli_close($conn);
                    if ($is_ajax) sendJsonResponse('error', 'Промокод уже использован!');
                    $_SESSION['error'] = "Промокод уже использован!";
                    header("Location: setting");
                    exit;
                }

                if ($promocode['quantity'] <= 0) {
                    mysqli_close($conn);
                    if ($is_ajax) sendJsonResponse('error', 'Количество использования промокода закончилось!');
                    $_SESSION['error'] = "Количество использования промокода закончилось!";
                    header("Location: setting");
                    exit;
                }

                mysqli_begin_transaction($conn);
                $rewards = [];

                try {
                    $update_promo = "UPDATE code SET quantity = quantity - 1 WHERE id = '{$promocode['id']}'";
                    $conn->query($update_promo);

                    $insert_used = "INSERT INTO promo (id_usage, id_user, code, date) VALUES (NULL, '$user_id', '$promo', NOW())";
                    $conn->query($insert_used);

                    if ($promocode['petal'] !== null) {
                        $update_invent = "UPDATE invent SET sakura = sakura + {$promocode['petal']} WHERE id_user = '$user_id'";
                        $conn->query($update_invent);
                        $rewards[] = "{$promocode['petal']} лепестков";
                    }

                    if ($promocode['xp'] !== null) {
                        $update_invent = "UPDATE invent SET xp = xp + {$promocode['xp']} WHERE id_user = '$user_id'";
                        $conn->query($update_invent);
                        $rewards[] = "{$promocode['xp']} опыта";
                    }

                    if ($promocode['coin'] !== null) {
                        $update_invent = "UPDATE invent SET coins = coins + {$promocode['coin']} WHERE id_user = '$user_id'";
                        $conn->query($update_invent);
                        $rewards[] = "{$promocode['coin']} монет";
                    }

                    if ($promocode['kase'] !== null) {
                        $update_invent = "UPDATE invent SET kase = kase + {$promocode['kase']} WHERE id_user = '$user_id'";
                        $conn->query($update_invent);
                        $rewards[] = "{$promocode['kase']} кейсов";
                    }

                    if ($promocode['donate'] !== null) {
                        $update_invent = "UPDATE users SET donate = donate + {$promocode['donate']} WHERE id = '$user_id'";
                        $conn->query($update_invent);
                        $rewards[] = "{$promocode['donate']} рублей доната";
                    }

                    if ($promocode['stiker'] !== null) {
                        $rewards[] = add_stikers($conn, $user_id, $promocode['stiker']);
                    }

                    if ($promocode['title'] !== null) {
                        $title = mysqli_real_escape_string($conn, $promocode['title']);
                        $user_title_check = "SELECT * FROM title WHERE id_user = '$user_id' AND title = '$title'";
                        $user_title_result = $conn->query($user_title_check);
                        if ($user_title_result->num_rows == 0) {
                            $add_title = "INSERT INTO `title`(`id_title`, `id_user`, `title`) VALUES (NULL,'$user_id','$title')";
                            $conn->query($add_title);
                            $rewards[] = "титул: $title";
                        }
                    }

                    mysqli_commit($conn);

                    $message = !empty($rewards) 
                        ? "Промокод активирован! Получено: " . implode(", ", $rewards)
                        : "Промокод не содержит доступных наград";

                    mysqli_close($conn);
                    if ($is_ajax) sendJsonResponse('success', $message);
                    $_SESSION['great'] = $message;
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    mysqli_close($conn);
                    $error_msg = "Ошибка при активации промокода: " . $e->getMessage();
                    if ($is_ajax) sendJsonResponse('error', $error_msg);
                    $_SESSION['error'] = $error_msg;
                }
            } else {
                mysqli_close($conn);
                if ($is_ajax) sendJsonResponse('error', 'Такого промокода не существует.');
                $_SESSION['error'] = "Такого промокода не существует.";
            }
        } else {
            mysqli_close($conn);
            if ($is_ajax) sendJsonResponse('error', 'Ошибка при чтении базы данных.');
            $_SESSION['error'] = "Ошибка при чтении базы данных.";
        }

        header("Location: setting");
        exit;
        break;

    case 'new_title':
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);

        if ($result->num_rows > 0) {
            $title = $_POST['title'];
            $user = $result->fetch_assoc();
            $id = $user['id'];

            $invent_sql = "SELECT * FROM invent WHERE id_user = " . $user["id"];
            $invent_result = mysqli_query($conn, $invent_sql);
            $invent = mysqli_fetch_assoc($invent_result);

            if ($title == "NULL") {
                $update_title = "UPDATE `invent` SET `id_title` = NULL WHERE `invent`.`id_user` = '$id'";
            } else {
                $update_title = "UPDATE invent SET id_title = '$title' WHERE id_user = '$id'";
            }

            if ($conn->query($update_title) === TRUE) {
                $_SESSION["great"] = "Титул успешно изменён!";
            } else {
                $_SESSION["error"] = "Ошибка при обновлении титула: " . $conn->error;
            }
        } else {
            $_SESSION["error"] = "У вас отсутствует инвентарь, пожалуйста, подключите его на главной странице.";
        }

        mysqli_close($conn);
        header("Location: setting");
        exit;
        break;

    case 'transfer':
        $sender_login = $_SESSION['user'];
        $recipient_id = intval($_POST['recipient_id']);
        $resource_type = mysqli_real_escape_string($conn, $_POST['resource_type']);
        $amount = intval($_POST['amount']);

        $last_transfer = "SELECT time FROM pay_log WHERE first_user_id = (SELECT id FROM users WHERE login = '$sender_login') ORDER BY time DESC LIMIT 1";
        $result = $conn->query($last_transfer);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_time = strtotime($row['time']);
            if (time() - $last_time < 180) {
                mysqli_close($conn);
                if ($is_ajax) sendJsonResponse('error', 'Вы можете делать перевод только раз в 3 минуты.');
                $_SESSION['error'] = "Вы можете делать перевод только раз в 3 минуты.";
                header("Location: setting");
                exit;
            }
        }

        $tools_query = "SELECT * FROM tools WHERE user_id = (SELECT id FROM users WHERE login = '$sender_login')";
        $tools_result = $conn->query($tools_query);

        if ($tools_result->num_rows == 0) {
            mysqli_close($conn);
            if ($is_ajax) sendJsonResponse('error', 'У вас не активированы инструменты.');
            $_SESSION['error'] = "У вас не активированы инструменты.";
            header("Location: setting");
            exit;
        }

        $tools = $tools_result->fetch_assoc();
        $limit_field = $resource_type . '_limit';

        if (!isset($tools[$limit_field])) {
            mysqli_close($conn);
            if ($is_ajax) sendJsonResponse('error', 'Неверный тип ресурса.');
            $_SESSION['error'] = "Неверный тип ресурса.";
            header("Location: setting");
            exit;
        }

        if ($amount > $tools[$limit_field]) {
            mysqli_close($conn);
            if ($is_ajax) sendJsonResponse('error', 'Превышен лимит перевода. Максимум: ' . $tools[$limit_field]);
            $_SESSION['error'] = "Превышен лимит перевода. Максимум: " . $tools[$limit_field];
            header("Location: setting");
            exit;
        }

        $invent_query = "SELECT $resource_type FROM invent WHERE id_user = (SELECT id FROM users WHERE login = '$sender_login')";
        $invent_result = $conn->query($invent_query);
        $invent = $invent_result->fetch_assoc();

        if ($invent[$resource_type] < $amount) {
            mysqli_close($conn);
            if ($is_ajax) sendJsonResponse('error', 'Недостаточно ресурсов.');
            $_SESSION['error'] = "Недостаточно ресурсов.";
            header("Location: setting");
            exit;
        }

        $conn->begin_transaction();

        try {
            $update_sender = "UPDATE invent SET $resource_type = $resource_type - $amount WHERE id_user = (SELECT id FROM users WHERE login = '$sender_login')";
            $conn->query($update_sender);

            $update_recipient = "UPDATE invent SET $resource_type = $resource_type + $amount WHERE id_user = $recipient_id";
            $conn->query($update_recipient);

            $sender_id = $conn->query("SELECT id FROM users WHERE login = '$sender_login'")->fetch_assoc()['id'];
            $insert_log = "INSERT INTO pay_log (first_user_id, second_user_id, type, count, time) VALUES ($sender_id, $recipient_id, '$resource_type', $amount, NOW())";
            $conn->query($insert_log);

            $conn->commit();
            mysqli_close($conn);

            if ($is_ajax) sendJsonResponse('success', 'Перевод успешно выполнен!');
            $_SESSION['great'] = "Перевод успешно выполнен!";
        } catch (Exception $e) {
            $conn->rollback();
            mysqli_close($conn);
            $error_msg = "Ошибка при выполнении перевода: " . $e->getMessage();
            if ($is_ajax) sendJsonResponse('error', $error_msg);
            $_SESSION['error'] = $error_msg;
        }

        header("Location: setting");
        exit;
        break;

    case 'username':
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $login = $_SESSION['user'];
        $user_query = "SELECT * FROM users WHERE login = '$login'";
        $result = $conn->query($user_query);
        $user = $result->fetch_assoc();
        $userId = $user['id'];

        $username_query = "SELECT * FROM users WHERE username = '$username'";
        $result2 = $conn->query($username_query);

        if ($result2->num_rows == 0) {
            $update_query = "UPDATE users SET username = '$username' WHERE login = '$login'";
            if ($conn->query($update_query) === TRUE) {
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Воин Родос'";
                $result = mysqli_query($conn, $query);
                if (mysqli_num_rows($result) !== 0) {
                    $title_update_query = "UPDATE achievement SET description = '$username, не забывайте ваше призвание. Наше дело ещё не подошло к концу.' WHERE id_user = '$userId' AND title = 'Воин Родос'";
                    $conn->query($title_update_query);
                }

                $_SESSION["great"] = "Никнейм успешно изменен!";
                header("Location: setting");
            } else {
                $_SESSION["error"] = "Ошибка при обновлении никнейма: " . $conn->error;
                header("Location: setting");
            }
        } else {
            $_SESSION["error"] = "Такой никнейм используется другим пользователем. Пожалуйста, попробуй другой никнейм.";
            header("Location: setting");
        }
        exit;
        break;

    default:
        mysqli_close($conn);
        if ($is_ajax) sendJsonResponse('error', 'Неизвестное действие.');
        $_SESSION['error'] = "Неизвестное действие.";
        header("Location: setting");
        exit;
}

if (isset($conn)) {
    mysqli_close($conn);
}

