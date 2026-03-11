<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();
$authUser = auth_require_user('/profile/login');
if(!isset($_SESSION['user'])) {
    header("Location: login");
    exit();
}
include('../template/conn.php');
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    die("Ошибка подключения: " . mysqli_connect_error());
}
$login = $_SESSION['user'];
$user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
$result = $conn->query($user_query);
if($result -> num_rows > 0){
    $user = $result -> fetch_assoc();
    $permissions = $user["permissions"];
    $lvl = $user['lvl'];
    if($lvl == 0) {
        $_SESSION["perm_error"] = "Причина отказа: Вы были заблокорованны на сайте. Ваши права: " . $permissions . ".<br> Если вы считаете, что вы были заблокированны по ошибке, обратитесь к создателю."; 
        header("Location: ../403");
        exit;
    } else if ($lvl == 1) {
        $_SESSION['perm_error'] = "Причина отказа: Вы не подтверждены на сайте. Ваши права: " . $permissions . ".<br> Обратитесь к создателю, для подтверждения аккаунта.";
        header("Location: ../403");
        exit;
    } else if ($lvl < 6) {
        $_SESSION['perm_error'] = "Причина отказа: Недостаточно прав для доступа. Ваши права: " . $permissions . "";
        header("Location: ../403");
        exit;
    }
}
$promoCodes = [];
$result = mysqli_query($conn, "SELECT * FROM code");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $promoCodes[] = $row;
    }
}

$totalHistory = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM promo"))['total'];
$historyResult = mysqli_query($conn, "SELECT * FROM promo ORDER BY date DESC LIMIT 10");
$usageHistory = [];
if ($historyResult) {
    while ($row = mysqli_fetch_assoc($historyResult)) {
        $usageHistory[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<title>Просмотр промокодов</title>
<link rel = "icon" href = "../img/icon.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="../style/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="../js/jquery-3.7.1.min.js"></script>
<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .card-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .card {
        background: rgba(0, 0, 0, 0.7);
        border-radius: 15px;
        padding: 15px;
        box-shadow: 0 5px 25px rgba(229, 36, 255, 0.3);
        border: 1px solid #3F0071;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(229, 36, 255, 0.5);
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #FFA500;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    .promo-code {
        font-size: 1.1rem;
        font-weight: bold;
        color: #BA55D3;
        letter-spacing: 1px;
    }
    .status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: bold;
    }
    .status-active {
        background: rgba(124, 252, 0, 0.2);
        color: #7CFC00;
        border: 1px solid #7CFC00;
    }
    .status-inactive {
        background: rgba(255, 69, 0, 0.2);
        color: #FF4500;
        border: 1px solid #FF4500;
    }
    
    .card-body {
        margin-bottom: 12px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
        padding-bottom: 6px;
        border-bottom: 1px dashed rgba(255, 165, 0, 0.3);
        font-size: 0.9rem;
    }
    .info-label {
        color: #9370DB;
        font-weight: bold;
    }
    .info-value {
        color: #FFE4E1;
    }
    .rewards {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    .reward-item {
        background: rgba(63, 0, 113, 0.5);
        border-radius: 8px;
        padding: 6px 10px;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
    }
    .history-item {
        background: rgba(0, 0, 0, 0.7);
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 12px;
        border-left: 3px solid #9370DB;
    }
    .section-title {
        color: #BA55D3;
        padding-left: 10px;
        margin-top: 25px;
        margin-bottom: 15px;
        font-family: serif;
        font-size: 1.6rem;
        border-left: 4px solid #FFA500;
    }
    .search-box {
        background: rgba(0, 0, 0, 0.7);
        border: 1px solid #9370DB;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 25px;
        display: flex;
        gap: 10px;
        box-shadow: 0 0 20px rgba(147, 112, 219, 0.3);
    }
    .search-box input {
        flex-grow: 1;
        padding: 10px 12px;
        background: rgba(0, 0, 0, 0.5);
        border: 1px solid #3F0071;
        border-radius: 8px;
        color: white;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.3s;
    }
    .search-box input:focus {
        border-color: #BA55D3;
    }
    .empty-message {
        text-align: center;
        color: #9370DB;
        padding: 25px;
        font-size: 1.1rem;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 10px;
    }
    .load-more-container {
        text-align: center;
        margin-top: 15px;
    }
    .load-more-btn {
        background: rgba(63, 0, 113, 0.7);
        color: white;
        border: 1px solid #9370DB;
        border-radius: 20px;
        padding: 8px 20px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .load-more-btn:hover {
        background: rgba(147, 112, 219, 0.7);
    }
    .loader {
        display: none;
        text-align: center;
        padding: 10px;
        color: #9370DB;
    }
    @media (max-width: 768px) {
        .card-container {
            grid-template-columns: 1fr;
        }
        .card {
            padding: 12px;
        }
        .promo-code {
            font-size: 1rem;
        }
        .section-title {
            font-size: 1.4rem;
        }
    }
</style>
</head>
<body>
<div class="navbar">
    <a href="./main">Back</a>
</div>
<div class="content-main">
    <div class="container">
	    <div class="row">
		    <div class="col-12">
                <div class="header">
                    <h1>Система управления промокодами</h1>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Поиск промокода или награды...">
                    </div>
                </div>
                <h2 class="section-title">Активные промокоды</h2>
                <div class="card-container" id="promoCardsContainer">
                    <?php if (count($promoCodes) > 0): ?>
                        <?php foreach ($promoCodes as $code): ?>
                        <?php
                        $status = ($code['quantity'] > 0)? '&#10004' : '&#x2718';
                        ?>
                        <div class="card" data-code="<?= strtolower($code['code']) ?>" data-rewards="<?= strtolower(implode(' ', [
                            'gems'.$code['gems'],
                            'xp'.$code['xp'],
                            'petal'.$code['petal'],
                            'coin'.$code['coin'],
                            'kase'.$code['kase'],
                            'tittle'.$code['tittle'],
                            'donate'.$code['donate']
                        ])) ?>">
                            <div class="card-header">
                                <div class="promo-code"><?= $code['code'] ?></div>
                                <div class="status status-<?= $status == '&#10004' ? 'active' : 'inactive' ?>">
                                    <?= $status ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span class="info-label">Осталось:</span>
                                    <span class="info-value">
                                        <?= $code['quantity'] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="rewards">
                                <?php if ($code['gems'] > 0): ?>
                                    <div class="reward-item">💎 <?= $code['gems'] ?> самоцветов</div>
                                <?php endif; ?>
                                <?php if ($code['xp'] > 0): ?>
                                    <div class="reward-item">⭐ <?= $code['xp'] ?> опыта</div>
                                <?php endif; ?>
                                <?php if ($code['petal'] > 0): ?>
                                    <div class="reward-item">🌸 <?= $code['petal'] ?> лепестков</div>
                                <?php endif; ?>
                                <?php if ($code['coin'] > 0): ?>
                                    <div class="reward-item">🪙 <?= $code['coin'] ?> монет</div>
                                <?php endif; ?>
                                <?php if ($code['kase'] > 0): ?>
                                    <div class="reward-item">💼 <?= $code['kase'] ?> кейсов</div>
                                <?php endif; ?>
                                <?php if ($code['tittle'] > 0): ?>
                                    <div class="reward-item">🏆 Титул: <?= $code['tittle'] ?></div>
                                <?php endif; ?>
                                <?php if ($code['donate'] > 0): ?>
                                    <div class="reward-item">💲 <?= $code['donate'] ?> донат-валюты</div>
                                <?php endif; ?>
                                <?php if ($code['stiker'] > 0): ?>
                                    <div class="reward-item">🔶 Стикер редкости: <?= $code['stiker'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-message">Нет доступных промокодов</div>
                    <?php endif; ?>
                </div>
                <h2 class="section-title">Последние активации</h2>
                <div id="historyContainer">
                    <?php if (count($usageHistory) > 0): ?>
                        <?php foreach ($usageHistory as $history): ?>
                        <div class="history-item">
                            <div class="info-row">
                                <span class="info-label">Пользователь:</span>
                                <span class="info-value">ID <?= $history['id_user'] ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Промокод:</span>
                                <span class="info-value"><?= $history['code'] ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Дата:</span>
                                <span class="info-value"><?= date('d.m.Y в H:i', strtotime($history['date'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-message">Нет данных об активациях</div>
                    <?php endif; ?>
                </div>
                <div class="loader" id="historyLoader">Загрузка...</div>
                <?php if ($totalHistory > 10): ?>
                <div class="load-more-container">
                    <button class="load-more-btn" id="loadMoreBtn">Показать ещё</button>
                </div>
                <?php endif; ?>
                <input type="hidden" id="totalHistory" value="<?= $totalHistory ?>">
                <input type="hidden" id="historyOffset" value="10">
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.card');
        let visibleCards = 0;

        cards.forEach(card => {
            const codeMatch = card.dataset.code.includes(searchTerm);
            const rewardsMatch = card.dataset.rewards.includes(searchTerm);
            if (codeMatch || rewardsMatch || searchTerm === '') {
                card.style.display = 'block';
                visibleCards++;
            } else {
                card.style.display = 'none';
            }
        });
        const container = document.getElementById('promoCardsContainer');
        let emptyMessage = container.querySelector('.empty-message');

        if (visibleCards === 0 && !emptyMessage) {
            emptyMessage = document.createElement('div');
            emptyMessage.className = 'empty-message';
            emptyMessage.textContent = 'Промокоды по вашему запросу не найдены';
            container.appendChild(emptyMessage);
        } else if (visibleCards > 0 && emptyMessage) {
            emptyMessage.remove();
        }
    });

    // Подгрузка истории активаций
    let isLoading = false;
    const historyContainer = document.getElementById('historyContainer');
    const historyLoader = document.getElementById('historyLoader');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const totalHistory = parseInt(document.getElementById('totalHistory').value);
    let historyOffset = parseInt(document.getElementById('historyOffset').value);

    function loadMoreHistory() {
        if (isLoading || historyOffset >= totalHistory) return;
        
        isLoading = true;
        historyLoader.style.display = 'block';
        if (loadMoreBtn) loadMoreBtn.style.display = 'none';
        
        fetch(`load_more_history.php?offset=${historyOffset}`)
            .then(response => response.text())
            .then(html => {
                historyContainer.innerHTML += html;
                historyOffset += 10;
                document.getElementById('historyOffset').value = historyOffset;
                historyLoader.style.display = 'none';
                isLoading = false;
                
                // Обновляем состояние кнопки
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 'block';
                    if (historyOffset >= totalHistory) {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Ошибка загрузки:', error);
                historyLoader.style.display = 'none';
                if (loadMoreBtn) loadMoreBtn.style.display = 'block';
                isLoading = false;
            });
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreHistory);
    }

    // Автоматическая подгрузка при прокрутке
    window.addEventListener('scroll', function() {
        if (isLoading || !loadMoreBtn) return;
        
        const lastItem = document.querySelector('.history-item:last-child');
        if (!lastItem) return;
        
        const rect = lastItem.getBoundingClientRect();
        if (rect.bottom <= window.innerHeight + 100) {
            loadMoreHistory();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
    });
</script>
</body>
</html>
<?php 
session_write_close();
?>



