<?php
    session_start();
    if(!isset($_SESSION['user'])) {
        header("Location: login");
        exit();
    }
    require_once '../template/conn.php';
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if ($conn->connect_error) {
        die("Ошибка подключения: " . $conn->connect_error);
    }
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $sql = "SELECT u.id, u.username, u.avatar, u.donate, i.lvl, i.gems, i.xp, i.xp_max, i.coins FROM invent i JOIN users u ON i.id_user = u.id ORDER BY i.lvl DESC, i.gems DESC, i.xp DESC, i.coins DESC LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);
    if (!$result) {
        die("Error SQL: " . $conn->error);
    }
    $leaders = array();
    while ($row = $result->fetch_assoc()) {
        $leaders[] = $row;
    }
    $totalSql = "SELECT COUNT(*) AS total FROM invent";
    $totalResult = $conn->query($totalSql);
    $totalRow = $totalResult->fetch_assoc();
    $totalRecords = $totalRow['total'];
    $totalPages = ceil($totalRecords / $limit);
    $conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Бездна лидеров</title>
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300;500&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            color: #fff;
            min-height: 100vh;
        }
        .leaderboard-grid{
            display: grid;
            justify-content: center;
        }
        .leader-card {
            max-width: 800px;
            border: 1px solid #8338ec;
            border-radius: 15px;
            margin: 15px 0;
            padding: 20px;
            position: relative;
            backdrop-filter: blur(5px);
            box-shadow: 0 0 15px rgba(58, 134, 255, 0.3);
        }
        .leader-card::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px;
            right: -2px; bottom: -2px;
            z-index: -1;
            border-radius: 17px;
        }
        .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid #8338ec;
            margin-right: 15px;
        }
        .stat-item {
            margin: 10px 0;
            display: flex;
            align-items: center;
        }
        #pagination {
            margin: 30px 0;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .page-btn {
            background: linear-gradient(45deg, #3a86ff, #8338ec);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transition: all 0.3s;
        }
        .page-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(58, 134, 255, 0.5);
        }
        @media (max-width: 768px) {
            .leader-card {
                padding: 15px;
            }
            .avatar {
                width: 50px;
                height: 50px;
            }
            .stat-item {
                flex-wrap: wrap;
            }
            #leaderboard thead {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="./search_profile">Back</a>
    <a href="./table_of_donate">Donate Leader</a>
</div>
<div class="content-main">
    <div class="container">
        <h2 class="text-center mb-4" style="text-shadow: 0 0 10px var(--glow-blue);">Бездна лидеров</h2>
        <div class="leaderboard-grid">
            <?php foreach ($leaders as $index => $leader) { ?>
            <?php if ($leader['id'] == 109) {continue;} ?>
            <?php if ($leader['donate'] < 100) {
                $border = '#33004b';
            } else if ($leader['donate'] < 1000) {
                $border = '#005599';
            } else if ($leader['donate'] < 2000) {
                $border = '#0066cc';
            } else if ($leader['donate'] < 5000) {
                $border = '#ff8c00';
            } else {
                $border = '#FFD700';
            }
            ?>
            <div class="leader-card" style="border: 2px solid <?php echo $border;?>">
                <div class="d-flex align-items-center mb-3">
                    <img src="./avatars/<?= $leader['avatar'] ?>" class="avatar">
                    <a href="./profile?id=<?= $leader['id'] ?>" class="h5 mb-0 link">
                        <?= $leader['username'] ?>
                    </a>
                </div>
                <div class="stat-item">
                    <span class="mr-3">Уровень: <?= $leader['lvl'] ?></span>
                    <span>Опыт: <?= $leader['xp'] ?>/<?= $leader['xp_max'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="mr-3">Кристаллы: <?= $leader['gems'] ?></span>
                    <span>Монеты: <?= $leader['coins'] ?></span>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php if ($totalRecords > $limit): ?>
        <div id="pagination">
            <?php if ($page > 1): ?>
            <button onclick="window.location='table_of_leader?page=<?= $page-1 ?>'" 
                    class="page-btn">Предыдущая</button>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <button onclick="window.location='table_of_leader?page=<?= $page+1 ?>'" 
                    class="page-btn">Следующая</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php 
session_write_close();
?>