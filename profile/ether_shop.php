<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();
$authUser = auth_require_user('/profile/login');
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Error connection: " . mysqli_connect_error();
    exit;
}

if (!isset($_SESSION['user'])) {
    header('Location: ./login');
    exit;
}

$login = $_SESSION['user'];

$stmt = $conn->prepare("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?");
$stmt->bind_param('s', $login);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM invent WHERE id_user = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$inventory = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM abyss_ether WHERE id = 1");
$stmt->execute();
$ether_data = $stmt->get_result()->fetch_assoc();

mysqli_close($conn);

$price_coins_buy = ceil($ether_data['coins'] * 10000000);
$price_coins_sell = floor($ether_data['coins'] * 10000000);
$price_petal_buy = ceil($ether_data['petal'] * 10000000);
$price_petal_sell = floor($ether_data['petal'] * 10000000);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Рынок Эфира бездны</title>
<link rel="icon" href="../img/icon.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../style/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body {
        background-size: 600% 600%;
        font-family: 'Montserrat Alternates', sans-serif;
        color: #FFE4E1;
        padding: 20px;
        min-height: 100vh;
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .header {
        background-color: rgba(0, 0, 0, 0.5);
        padding: 25px;
        margin-bottom: 25px;
        border-radius: 10px;
        border: 2px solid rgba(229, 36, 255, 0.3);
        text-align: center;
    }
    .header h1 {
        margin: 0 0 20px 0;
        color: #9370DB;
        font-size: 32px;
    }
    .balance-info {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .balance-item {
        background: rgba(0, 0, 0, 0.6);
        padding: 10px 20px;
        border-radius: 8px;
        border: 1px solid rgba(229, 36, 255, 0.3);
        font-size: 14px;
    }
    .balance-item i {
        margin-right: 8px;
        color: rgba(229, 36, 255, 1);
    }
    .section {
        background-color: rgba(0, 0, 0, 0.5);
        padding: 25px;
        margin-bottom: 25px;
        border-radius: 10px;
        border: 2px solid rgba(229, 36, 255, 0.3);
    }
    .section h2 {
        margin: 0 0 20px 0;
        color: #BA55D3;
        font-size: 24px;
        padding-bottom: 10px;
        border-bottom: 2px solid #FFA500;
    }
    .section h2 i {
        margin-right: 10px;
    }
    .pool-info {
        background: rgba(0, 0, 0, 0.4);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid rgba(229, 36, 255, 0.2);
    }
    .pool-info h3 {
        margin: 0 0 15px 0;
        color: rgba(229, 36, 255, 1);
        font-size: 18px;
    }
    .pool-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }
    .pool-stat {
        background: rgba(0, 0, 0, 0.5);
        padding: 12px;
        border-radius: 6px;
        text-align: center;
        font-size: 13px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .pool-stat strong {
        display: block;
        font-size: 16px;
        color: #9370DB;
        margin-top: 5px;
    }
    .exchange-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
    }
    .exchange-item {
        background: rgba(0, 0, 0, 0.4);
        padding: 20px;
        border-radius: 10px;
        border: 1px solid rgba(229, 36, 255, 0.3);
    }
    .exchange-item h3 {
        margin: 0 0 15px 0;
        color: rgba(229, 36, 255, 1);
        font-size: 20px;
    }
    .exchange-item h3 i {
        margin-right: 10px;
    }
    .price-display {
        background: rgba(0, 0, 0, 0.5);
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 6px;
        border-left: 3px solid #FFA500;
    }
    .price-display p {
        margin: 5px 0;
        font-size: 13px;
        color: #FFE4E1;
    }
    .price-display strong {
        color: #9370DB;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        color: #BA55D3;
        font-weight: bold;
    }
    .quick-select {
        display: flex;
        gap: 5px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .quick-btn {
        padding: 6px 12px;
        background: rgba(0, 0, 0, 0.6);
        border: 1px solid rgba(229, 36, 255, 0.5);
        border-radius: 4px;
        color: rgba(229, 36, 255, 1);
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s;
    }
    .quick-btn:hover {
        background: rgba(229, 36, 255, 0.3);
        border-color: rgba(229, 36, 255, 1);
    }
    .form-group input[type="number"] {
        width: 100%;
        padding: 12px;
        background: rgba(0, 0, 0, 0.6);
        border: 1px solid rgba(229, 36, 255, 0.3);
        border-radius: 6px;
        color: #FFE4E1;
        font-size: 14px;
        box-sizing: border-box;
    }
    .form-group input[type="number"]:focus {
        outline: none;
        border-color: rgba(229, 36, 255, 1);
        box-shadow: 0 0 10px rgba(229, 36, 255, 0.3);
    }
    .cost-display {
        background: rgba(0, 0, 0, 0.5);
        padding: 10px;
        margin: 10px 0;
        border-radius: 6px;
        text-align: center;
        font-size: 14px;
        min-height: 20px;
        border: 1px solid rgba(229, 36, 255, 0.2);
        color: #FFE4E1;
    }
    .cost-display strong {
        color: #9370DB;
        font-size: 16px;
    }
    .button {
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.8) 0%, rgba(29, 29, 29, 0.8) 100%);
        border: 2px solid rgba(229, 36, 255, 1);
        border-radius: 35px;
        color: rgba(229, 36, 255, 1);
        padding: 12px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        width: 100%;
        margin-top: 8px;
    }
    .button:hover {
        background: rgba(229, 36, 255, 0.9);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.7);
    }
    .button:active {
        transform: translateY(0);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    }
    .button i {
        margin-right: 8px;
    }
    .btn-refresh {
        max-width: 300px;
        margin: 0 auto 20px auto;
        display: block;
        border-color: #FFA500;
        color: #FFA500;
    }
    .btn-refresh:hover {
        background: #FFA500;
        color: #000;
    }
    .alert {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 10px;
        display: none;
        z-index: 1000;
        max-width: 400px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        border: 2px solid;
    }
    .alert.success {
        background: rgba(0, 128, 0, 0.9);
        border-color: #00FF00;
        color: white;
    }
    .alert.error {
        background: rgba(139, 0, 0, 0.9);
        border-color: #FF0000;
        color: white;
    }
    .placeholder {
        text-align: center;
        padding: 50px 20px;
        background: rgba(0, 0, 0, 0.3);
        border: 2px dashed rgba(229, 36, 255, 0.5);
        border-radius: 10px;
    }
    .placeholder i {
        font-size: 48px;
        color: rgba(229, 36, 255, 1);
        margin-bottom: 20px;
    }
    .placeholder h3 {
        color: rgba(229, 36, 255, 1);
        margin: 15px 0;
        font-size: 22px;
    }
    .placeholder p {
        color: #BA55D3;
        font-size: 14px;
    }
    @media (max-width: 768px) {
        .exchange-grid {
            grid-template-columns: 1fr;
        }
        .balance-info {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>
</head>
<body>
<div class="navbar">
    <a href="./main">Back</a>
    <a href="#" onclick="updatePrices()">Update Prices</a>
</div>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-fire"></i> Рынок Эфира бездны</h1>
        <div class="balance-info">
            <div class="balance-item">
                <i class="fas fa-bolt"></i> <strong>Эфир:</strong> <?= number_format($user['abyss_ether'], 7) ?>
            </div>
            <div class="balance-item">
                <i class="fas fa-coins"></i> <strong>Монеты:</strong> <?= number_format($inventory['coins']) ?>
            </div>
            <div class="balance-item">
                <i class="fas fa-leaf"></i> <strong>Лепестки:</strong> <?= number_format($inventory['sakura']) ?>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="pool-info">
            <h3><i class="fas fa-database"></i> Информация о пуле</h3>
            <div class="pool-stats">
                <div class="pool-stat">Всего Эфира:<strong><?= number_format($ether_data['count'], 7) ?></strong></div>
                <div class="pool-stat">Цена (лепестки):<strong><?= number_format($ether_data['petal'], 2) ?></strong></div>
            </div>
        </div>
        <button class="button btn-refresh" onclick="updatePrices()"><i class="fas fa-sync-alt"></i> Обновить цены</button>
        <h2><i class="fas fa-exchange-alt"></i> Обмен ресурсов</h2>

        <div class="exchange-grid">
            <div class="exchange-item">
                <h3><i class="fas fa-leaf"></i> Лепестки сакуры</h3>
                <div class="price-display">
                    <p>Купить 1 эфир: <strong><?= number_format($price_petal_buy) ?></strong> лепестков</p>
                    <p>Продать 1 эфир: <strong><?= number_format($price_petal_sell) ?></strong> лепестков (коммиссия 50%)</p>
                </div>

                <div class="form-group">
                    <label>Количество Эфира:</label>
                    <div class="quick-select">
                        <button class="quick-btn" onclick="setAmount('petal', 1)">1</button>
                        <button class="quick-btn" onclick="setAmount('petal', 0.1)">0.1</button>
                        <button class="quick-btn" onclick="setAmount('petal', 0.0001)">0.0001</button>
                        <button class="quick-btn" onclick="setAmount('petal', 0.0000001)">0.0000001</button>
                    </div>
                    <input type="number" id="amount-petal" placeholder="Введите количество" step="0.0000001" min="0.0000001" max="1000" oninput="calculateCost('petal')"></div>

                <div class="cost-display" id="cost-buy-petal">Введите количество</div>
                <button class="button" onclick="buyEther('petal')"><i class="fas fa-shopping-cart"></i> Купить</button>

                <div class="cost-display" id="cost-sell-petal">Введите количество</div>
                <button class="button" onclick="sellEther('petal')"><i class="fas fa-hand-holding-usd"></i> Продать</button>
            </div>
        </div>
    </div>

    <div class="section">
        <h2><i class="fas fa-store"></i> Товары за Эфир</h2>
        <div class="placeholder">
            <i class="fas fa-box-open"></i>
            <h3>Скоро здесь появятся товары</h3>
            <p>В разработке находятся уникальные предметы и услуги,<br>которые можно будет приобрести за Эфир бездны!</p>
        </div>
    </div>
</div>

<div class="alert" id="alert"></div>

<script>
    function formatInputValue(input) {
        let value = input.value;
        if (value.includes('e') || value.includes('E')) {
            let num = parseFloat(value);
            if (!isNaN(num)) {
                value = num.toFixed(7);
                value = value.replace(/\.?0+$/, '');
                input.value = value;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[type="number"]');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                formatInputValue(this);
            });
            input.addEventListener('change', function() {
                formatInputValue(this);
            });
        });
    });

    function setAmount(resource, value) {
        const input = document.getElementById('amount-' + resource);
        input.value = value.toFixed(7).replace(/\.?0+$/, '');
        calculateCost(resource);
    }

    function calculateCost(resource) {
        const amount = parseFloat(document.getElementById('amount-' + resource).value);

        if (!amount || amount <= 0) {
            document.getElementById('cost-buy-' + resource).innerHTML = 'Введите количество';
            document.getElementById('cost-sell-' + resource).innerHTML = 'Введите количество';
            return;
        }

        const formDataBuy = new FormData();
        formDataBuy.append('action', 'calculate_price');
        formDataBuy.append('resource', resource);
        formDataBuy.append('amount', amount);
        formDataBuy.append('type', 'buy');

        fetch('./ether_core', {
            method: 'POST',
            body: formDataBuy
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const resourceNames = {
                    'coins': 'монет',
                    'petal': 'лепестков',
                };
                document.getElementById('cost-buy-' + resource).innerHTML = 
                    'Стоимость: <strong>' + data.price_formatted + '</strong> ' + resourceNames[resource];
            }
        })
        .catch(error => console.error('Ошибка:', error));

        const formDataSell = new FormData();
        formDataSell.append('action', 'calculate_price');
        formDataSell.append('resource', resource);
        formDataSell.append('amount', amount);
        formDataSell.append('type', 'sell');

        fetch('./ether_core', {
            method: 'POST',
            body: formDataSell
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const resourceNames = {
                    'coins': 'монет',
                    'petal': 'лепестков',
                };
                document.getElementById('cost-sell-' + resource).innerHTML = 
                    'Получите: <strong>' + data.price_formatted + '</strong> ' + resourceNames[resource];
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function updatePrices() {
        const btn = document.querySelector('.btn-refresh');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обновление...';

        fetch('./ether_core?action=update_prices')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showAlert(data.error, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Обновить цены';
                } else {
                    showAlert('Цены успешно обновлены!', 'success');
                    updatePagePrices(data);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Обновить цены';
                }
            })
            .catch(error => {
                showAlert('Ошибка: ' + error, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Обновить цены';
            });
    }

    function updatePagePrices(prices) {
        const poolStats = document.querySelectorAll('.pool-stat strong');
        if (poolStats.length >= 2) {
            poolStats[0].textContent = parseFloat(prices.petal.count).toLocaleString('ru-RU', {minimumFractionDigits: 4, maximumFractionDigits: 7});
            poolStats[1].textContent = parseFloat(prices.petal.price).toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    
        const pricePetalBuy = Math.ceil(prices.petal.price * 10000000);
        const pricePetalSell = Math.floor(prices.petal.price * 10000000);
        const priceDisplays = document.querySelectorAll('.price-display');
        if (priceDisplays[0]) {
            priceDisplays[0].innerHTML = `<p>Купить 1 эфир: <strong>${pricePetalBuy.toLocaleString('ru-RU')}</strong> лепестков</p>
                    <p>Продать 1 эфир: <strong>${pricePetalSell.toLocaleString('ru-RU')}</strong> лепестков (коммиссия 40%)</p>`;
        }
    
        const input = document.getElementById('amount-petal');
        if (input && input.value) {
            calculateCost('petal');
        }
    }

    function buyEther(resource) {
        const amount = parseFloat(document.getElementById('amount-' + resource).value);

        if (!amount || amount <= 0) {
            showAlert('Введите корректное количество!', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'buy_ether_' + resource);
        formData.append('count', amount);

        fetch('./ether_core', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            try {
                const json = JSON.parse(data);
                if (json.error) {
                    showAlert(json.error, 'error');
                }
            } catch (e) {
                showAlert(data, 'success');
                updatePrices()
            }
        })
        .catch(error => showAlert('Ошибка: ' + error, 'error'));
    }

    function sellEther(resource) {
        const amount = parseFloat(document.getElementById('amount-' + resource).value);

        if (!amount || amount <= 0) {
            showAlert('Введите корректное количество!', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'sell_ether_' + resource);
        formData.append('count', amount);

        fetch('./ether_core', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            try {
                const json = JSON.parse(data);
                if (json.error) {
                    showAlert(json.error, 'error');
                }
            } catch (e) {
                showAlert(data, 'success');
                updatePrices()
            }
        })
        .catch(error => showAlert('Ошибка: ' + error, 'error'));
    }

    function showAlert(text, type) {
        const alert = document.getElementById('alert');
        alert.textContent = text;
        alert.className = 'alert ' + type;
        alert.style.display = 'block';

        setTimeout(() => {
            alert.style.display = 'none';
        }, 3000);
    }
</script>
</body>
</html>



