<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login");
    exit();
}
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    $_SESSION['form_error'] = "Ошибка соединения: " . mysqli_connect_error();
    exit();
}
$login = $_SESSION['user'];
$user_query = "SELECT sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if ($user['lvl'] == 0) {
        $_SESSION["perm_error"] = "Ваш аккаунт заблокирован";
        header("Location: ../403");
        exit();
    } elseif ($user['lvl'] == 1) {
        $_SESSION['perm_error'] = "Аккаунт не подтвержден";
        header("Location: ../403");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск профилей</title>
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <link rel = "stylesheet" href = "../style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat Alternates', sans-serif;
            background-color: #0d001a;
            color: #e6e6ff;
        }
        .content-main {
            padding: 10px;
            box-sizing: border-box;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            background-color: rgba(20, 0, 40, 0.9);
            border-bottom: 1px solid #4d0099;
        }
        .navbar a {
            color: #b3b3ff;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .navbar a i {
            margin-right: 5px;
        }
        .search-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding: 15px;
            background: rgba(20, 0, 40, 0.7);
            border-radius: 8px;
            margin: 10px;
        }
        .search-header h2 {
            margin: 0;
            font-size: 1.2rem;
        }
        .search-box {
            display: flex;
            width: 100%;
            gap: 5px;
        }
        .search-input {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #4d0099;
            border-radius: 20px;
            color: white;
            padding: 8px 12px;
            width: 100%;
            font-size: 0.9rem;
        }
        .search-btn {
            background: linear-gradient(to right, #8e2de2, #4a00e0);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(280px, 100%), 1fr));
            gap: 15px;
            padding: 10px;
            box-sizing: border-box;
        }
        .profile-card {
            background: linear-gradient(135deg, #1a0030, #2d004d);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            height: auto;
            min-height: 120px;
        }
        .profile-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(149, 0, 255, 0.3);
        }
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff00cc, #3333ff);
        }
        .profile-content {
            display: flex;
            padding: 12px;
            height: 100%;
            align-items: center;
        }
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(149, 0, 255, 0.5);
            margin-right: 10px;
        }
        .profile-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }
        .profile-name {
            font-size: 1rem;
            font-weight: 600;
            color: #e6e6ff;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .profile-meta {
            font-size: 0.8rem;
            color: #b3b3ff;
            margin-bottom: 6px;
        }
        .view-btn {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            border: none;
            border-radius: 18px;
            padding: 5px 12px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            max-width: 120px;
        }
        .view-btn:hover {
            background: linear-gradient(to right, #2575fc, #6a11cb);
            box-shadow: 0 3px 8px rgba(37, 117, 252, 0.4);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            overflow-y: auto;
            padding: 10px;
            box-sizing: border-box;
        }
        .modal-content {
            background: linear-gradient(to bottom, #0f0020, #1e003a);
            max-width: 95%;
            margin: 20px auto;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(149, 0, 255, 0.5);
            border: 1px solid rgba(149, 0, 255, 0.3);
            overflow: hidden;
            position: relative;
            padding: 20px;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #aaa;
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s;
        }
        .close-btn:hover {
            color: #fff;
        }
        .profile-badge {
            display: inline-block;
            background: linear-gradient(to right, #ff8c00, #ff0080);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-left: 6px;
        }
        .profile-modal-view {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .profile-modal-view .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(149, 0, 255, 0.5);
            margin: 0 auto 10px;
        }
        .profile-modal-header {
            text-align: center;
            margin-bottom: 12px;
        }
        .profile-modal-header h3 {
            margin: 8px 0 4px;
            color: #e6e6ff;
            font-size: 1.5rem;
        }
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .profile-stat {
            background: rgba(100, 0, 200, 0.2);
            padding: 8px 12px;
            border-radius: 8px;
            text-align: center;
            min-width: 70px;
        }
        .profile-stat span:first-child {
            display: block;
            font-size: 0.8rem;
            color: #b3b3ff;
        }
        .profile-stat span:last-child {
            font-size: 1.2rem;
            font-weight: bold;
            color: #fff;
        }
        .profile-info {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .profile-info p {
            margin: 6px 0;
            color: #ccc;
            font-size: 0.9rem;
        }
        .profile-info .donate {
            background: linear-gradient(90deg, rgba(255,215,0,0.2), rgba(255,140,0,0.2));
            padding: 6px 10px;
            border-radius: 18px;
            text-align: center;
            font-weight: bold;
            color: #ffd700;
            font-size: 0.9rem;
        }
        .badge {
            background: linear-gradient(to right, #8e2de2, #4a00e0);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 4px;
        }
        .banned {
            background: rgba(255, 0, 0, 0.2);
            padding: 8px;
            border-radius: 6px;
            text-align: center;
            margin: 8px 0;
            color: #ff6666;
            border: 1px solid #ff0000;
            font-size: 0.9rem;
        }
        .bio {
            background: rgba(0, 30, 60, 0.3);
            padding: 12px;
            border-radius: 6px;
            margin: 12px 0;
            border-left: 3px solid #4a00e0;
            color: white;
            font-size: 0.9rem;
        }
        .achievements {
            margin: 15px 0;
            color: white;
        }
        .achievement {
            background: rgba(100, 0, 200, 0.2);
            padding: 8px;
            border-radius: 6px;
            margin: 6px 0;
            border-left: 2px solid #8e2de2;
            font-size: 0.9rem;
        }
        .profile-link {
            display: block;
            text-align: center;
            background: linear-gradient(to right, #8e2de2, #4a00e0);
            color: white;
            padding: 8px;
            border-radius: 20px;
            text-decoration: none;
            margin: 15px 0;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        .profile-link:hover {
            background: linear-gradient(to right, #4a00e0, #8e2de2);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(142, 45, 226, 0.4);
        }
        .admin-panel {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #ff0000;
            border-radius: 6px;
            padding: 12px;
            margin-top: 15px;
            color: white;
        }
        .admin-panel h4 {
            color: #ff6666;
            margin-top: 0;
            font-size: 1rem;
        }
        .admin-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: 8px;
        }
        .ban-form, .group-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .ban-form input[type="text"],
        .group-form select {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #4d0099;
            border-radius: 20px;
            color: white;
            padding: 8px 12px;
            width: 100%;
            font-size: 0.9rem;
        }
        .ban-form button,
        .group-form button,
        .unban-btn {
            background: linear-gradient(to right, #ff416c, #ff4b2b);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        .group-form button {
            background: linear-gradient(to right, #11998e, #38ef7d);
        }
        .unban-btn {
            background: linear-gradient(to right, #11998e, #38ef7d);
            width: 100%;
        }
        .ban-form button:hover,
        .group-form button:hover,
        .unban-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
        }
        @media (max-width: 360px) {
            .search-header {
                padding: 10px;
            }
            .search-input, .search-btn {
                font-size: 0.85rem;
                padding: 6px 10px;
            }
            .profile-content {
                padding: 10px;
            }
            .avatar {
                width: 85px;
                height: 85px;
            }
            .profile-name {
                font-size: 0.9rem;
            }
            .profile-meta {
                font-size: 0.75rem;
            }
            .view-btn {
                font-size: 0.8rem;
                padding: 4px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="./main"><i class="fas fa-arrow-left"></i> Назад</a>
        <a href='./table_of_leader'><i class="fas fa-trophy"></i> Таблица лидеров</a>
    </div>
    <div class="content-main">
        <div class="search-header">
            <h2><i class="fas fa-search"></i> Поиск профилей</h2>
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Поиск по имени...">
                <button class="search-btn">Найти</button>
            </div>
        </div>
        <div class="profile-grid">
            <?php
            $sql = "SELECT u.id, u.username, u.avatar, u.donate, i.lvl FROM users u LEFT JOIN invent i ON u.id = i.id_user";
            $result = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($result)):
                $donate = $row['donate'];
                $badge = '';
                if ($donate >= 5000) {
                    $badge = '<span class="profile-badge"><i class="fas fa-crown"></i></span>';
                } elseif ($donate >= 2000) {
                    $badge = '<span class="profile-badge"><i class="fas fa-star"></i></span>';
                } elseif ($donate >= 1000) {
                    $badge = '<span class="profile-badge"><i class="fas fa-gem"></i></span>';
                }
            ?>
            <div class="profile-card">
                <div class="profile-content">
                    <img src="./avatars/<?= $row['avatar'] ?>" class="avatar" alt="Аватар"  loading="lazy">
                    <div class="profile-details">
                        <div class="profile-name">
                            <?= htmlspecialchars($row['username']) ?>
                            <?= $badge ?>
                        </div>
                        <div class="profile-meta">
                            Уровень: <?= $row['lvl'] ?? 0 ?> | ID: <?= $row['id'] ?>
                        </div>
                        <button class="view-btn" data-id="<?= $row['id'] ?>">
                            <i class="fas fa-user-circle"></i> Просмотр
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div id="profile-content"></div>
        </div>
    </div>
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script>
    $(document).ready(function() {
        $(".view-btn").click(function() {
            const userId = $(this).data('id');
            $.ajax({
                url: "load_profile",
                type: "POST",
                data: { userId: userId },
                success: function(response) {
                    $("#profile-content").html(response);
                    $("#profileModal").show();
                },
                error: function() {
                    alert("Ошибка загрузки профиля");
                }
            });
        });
        $(".search-btn").click(searchProfiles);
        $(".search-input").keypress(function(e) {
            if (e.which == 13) searchProfiles();
        });
        function searchProfiles() {
            const searchTerm = $(".search-input").val().toLowerCase();
            $(".profile-card").each(function() {
                const name = $(this).find(".profile-name").text().toLowerCase();
                $(this).toggle(name.includes(searchTerm));
            });
        }
        $(".close-btn").click(function() {
            $("#profileModal").hide();
        });
        $(document).click(function(e) {
            if ($(e.target).is("#profileModal")) {
                $("#profileModal").hide();
            }
        });
    });
    </script>
</body>
</html>
<?php mysqli_close($conn); session_write_close();?>