<?php
if (!isset($_POST['userId'])) exit("Ошибка: id не указан.");
session_start();
$userId = $_POST['userId'];
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);

$login = $_SESSION['user'];
$user_query = "SELECT sg.lvl, u.username FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $login);
$stmt->execute();
$result_perm = $stmt->get_result();
$row_perm = $result_perm->fetch_assoc();
$stmt->close();

$sql = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $isNSFW = $row['NSFW'] ? "Присутствует" : "Отсутствует";
    $last_login = date("H:i d.m.Y", strtotime($row["last_login"]));
    $profile_html = '<div class="profile-modal-view">';

    $profile_html .= '<img src="./avatars/' . $row["avatar"] . '" class="avatar" alt="Аватар профиля">';
    $mail_addr = '';
    $mail_user_query = "SELECT username FROM mail_user WHERE user_id = ?";
    $stmt_mail = $conn->prepare($mail_user_query);
    $stmt_mail->bind_param("i", $row['id']);
    $stmt_mail->execute();
    $mail_user_result = $stmt_mail->get_result();
    if ($mail_user_result && $mail_user_result->num_rows > 0) {
        $mail_user_row = $mail_user_result->fetch_assoc();
        $mail_addr = $mail_user_row['username'] . '@abyss';
    }
    $stmt_mail->close();

    $title_sql = "SELECT t.title, i.lvl, i.xp, i.xp_max, i.coins FROM invent i LEFT JOIN title t ON i.id_title = t.id_title WHERE i.id_user = ?";
    $stmt2 = $conn->prepare($title_sql);
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $title_result = $stmt2->get_result();
    $title_data = $title_result->fetch_assoc();
    $stmt2->close();

    $profile_html .= '<div class="profile-modal-header">';
    if ($title_data && $title_data['title']) {
        $profile_html .= '<h3>' . $title_data['title'] . ': ' . htmlspecialchars($row["username"]) . '</h3>';
    } else {
        $profile_html .= '<h3>' . htmlspecialchars($row["username"]) . '</h3>';
    }
    if ($mail_addr) {
        $profile_html .= '<div style="color:#BA55D3;font-size:1.05rem;margin-bottom:4px;">Почта: <b>' . htmlspecialchars($mail_addr) . '</b></div>';
    }
    $profile_html .= '</div>';

    $profile_html .= '<div class="profile-stats">';
    $profile_html .= '<div class="profile-stat">';
    $profile_html .= '<span>Уровень</span>';
    $profile_html .= '<span>' . ($title_data['lvl'] ?? 0) . '</span>';
    $profile_html .= '</div>';
    $profile_html .= '<div class="profile-stat">';
    $profile_html .= '<span>Опыт</span>';
    $profile_html .= '<span>' . ($title_data['xp'] ?? 0) . '/' . ($title_data['xp_max'] ?? 0) . '</span>';
    $profile_html .= '</div>';
    $profile_html .= '<div class="profile-stat">';
    $profile_html .= '<span>Монеты</span>';
    $profile_html .= '<span>' . ($title_data['coins'] ?? 0) . '</span>';
    $profile_html .= '</div>';
    $profile_html .= '</div>';

    $profile_html .= '<div class="profile-info">';
    $profile_html .= '<p class="donate">Поддержка проекта: ' . $row['donate'] . ' руб.</p>';
    $profile_html .= '<p>Группа: <span class="badge">' . $row["permissions"] . '</span></p>';
    $profile_html .= '<p>Доступ к NSFW: ' . $isNSFW . '</p>';
    $profile_html .= '<p>Последний вход: ' . $last_login . '</p>';
    $profile_html .= '</div>';

    if ($row["lvl"] == "0") {
        $profile_html .= '<div class="banned">';
        $profile_html .= '<p>Пользователь заблокирован по причине:</p>';
        $profile_html .= '<p>' . htmlspecialchars($row["reason"]) . '</p>';
        $profile_html .= '</div>';
    } else {
        $profile_html .= '<div class="bio">';
        $profile_html .= '<p>' . nl2br($row["BIO"]) . '</p>';
        $profile_html .= '</div>';

        $ach_sql = "SELECT title, description FROM achievement WHERE id_user = ?";
        $stmt3 = $conn->prepare($ach_sql);
        $stmt3->bind_param("i", $userId);
        $stmt3->execute();
        $achievements = $stmt3->get_result();

        if ($achievements->num_rows > 0) {
            $profile_html .= '<div class="achievements">';
            $profile_html .= '<h4>Достижения:</h4>';
            while ($ach = $achievements->fetch_assoc()) {
                $profile_html .= '<div class="achievement">';
                $profile_html .= '<strong>' . htmlspecialchars($ach["title"]) . '</strong>: ' . htmlspecialchars($ach["description"]);
                $profile_html .= '</div>';
            }
            $profile_html .= '</div>';
        } else {
            $profile_html .= '<p>У пользователя пока нет достижений.</p>';
        }

        $profile_html .= '<a href="./profile?id=' . $row["id"] . '" class="profile-link">Открыть полный профиль</a>';
    }

    if (isset($row_perm['lvl']) && $row_perm['lvl'] == "6") {
        $profile_html .= '<div class="admin-panel">';
        $profile_html .= '<h4><i class="fas fa-shield-alt"></i> Администратору</h4>';
        $profile_html .= '<p><strong>ID:</strong> ' . $row["id"] . ' | <strong>Логин:</strong> ' . htmlspecialchars($row["login"]) . '</p>';
        $profile_html .= '<p><strong>Email:</strong> ' . htmlspecialchars($row["email"]) . '</p>';
        $profile_html .= '<p><strong>IP:</strong> ' . $row["ip"] . '</p>';
        $profile_html .= '<p><strong>Аккаунт создан:</strong> ' . date("d.m.Y H:i", strtotime($row["data_create"])) . '</p>';

        if ($row["lvl"] == "0") {
            $profile_html .= '<button onclick="unBan(' . $row["id"] . ')" class="unban-btn"><i class="fas fa-unlock"></i> Разблокировать</button>';
        } else {
            $profile_html .= '<div class="admin-actions">';
            $profile_html .= '<form id="ban_user" class="ban-form">';
            $profile_html .= '<input type="hidden" id="user_id" name="user_id" value="' . $row["id"] . '">';
            $profile_html .= '<input type="hidden" id="moder" name="moder" value="' . $row_perm["username"] . '">';
            $profile_html .= '<input type="text" id="reason" name="reason" placeholder="Причина блокировки" required>';
            $profile_html .= '<button type="submit"><i class="fas fa-ban"></i> Заблокировать</button>';
            $profile_html .= '</form>';

            $profile_html .= '<form class="group-form">';
            $profile_html .= '<input type="hidden" name="user_id" value="' . $row["id"] . '">';
            $profile_html .= '<select name="group">';
            $profile_html .= '<option value="ROOT"' . ($row["permissions"] == "ROOT" ? " selected" : "") . '>ROOT</option>';
            $profile_html .= '<option value="BETA"' . ($row["permissions"] == "BETA" ? " selected" : "") . '>BETA</option>';
            $profile_html .= '<option value="USER"' . ($row["permissions"] == "USER" ? " selected" : "") . '>USER</option>';
            $profile_html .= '<option value="GUEST"' . ($row["permissions"] == "GUEST" ? " selected" : "") . '>GUEST</option>';
            $profile_html .= '<option value="WRITER"' . ($row["permissions"] == "WRITER" ? " selected" : "") . '>WRITER</option>';
            $profile_html .= '</select>';
            $profile_html .= '<button type="submit"><i class="fas fa-sync-alt"></i> Сменить группу</button>';
            $profile_html .= '</form>';
            $profile_html .= '</div>';
        }
        $profile_html .= '</div>';
    }
    $profile_html .= '</div>';
    $profile_html .= '<script src="../js/admin1.2.js"></script>';
    
    echo $profile_html;
} else {
    echo '<div class="error">Профиль не найден.</div>';
}
mysqli_close($conn);
session_write_close();