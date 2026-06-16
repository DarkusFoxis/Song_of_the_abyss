<?php
declare(strict_types=1);
date_default_timezone_set('Europe/Moscow');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    header('Location: main');
    exit;
}

require_once __DIR__ . '/../template/auth.php';
auth_start_session();
auth_sync_session_from_token();
$authUser = auth_get_current_user();
if ($authUser === null) {
    echo "Вы должны быть авторизованы.";
    exit;
}

security_require_csrf(true);

require_once '../template/conn.php';

try {
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) throw new Exception("Ошибка соединения: " . mysqli_connect_error());
    
    $login = $authUser['login'];
    
    $user = getUserData($conn, $login);
    validateUserAccess($user);
    
    $action = $_POST['action'] ?? null;
    if (!$action) throw new Exception("Не указано действие.");
    
    $userId = (int)$user['id'];
    $inventory = getInventory($conn, $userId);
    
    switch ($action) {
        case 'update_price':
            handlePriceUpdate($conn);
            break;
        case 'petal_coin':
            handleResourceExchange($conn, $userId, $inventory, 'sakura', 2, 'coins');
            break;
        case 'coin_petal':
            handleResourcePurchase($conn, $userId, $inventory, 'sakura', 2, 'coins');
            break;
        case 'petal_kase':
            handleCasePurchase($conn, $userId, $inventory, $user);
            break;
        case 'premium':
            handlePremium($conn, $userId, $inventory, $user);
            break;
        case 'tools_accses':
            handleTools($conn, $userId, $inventory, $user);
            break;
        default:
            handleTitlePurchase($conn, $userId, $inventory, $action);
            break;
    }
} catch (Exception $e) {
    echo $e->getMessage();
} finally {
    mysqli_close($conn ?? null);
    exit;
}

function getUserData(mysqli $conn, string $login): array {
    $stmt = $conn->prepare("SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?");
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Пользователь не найден.");
    }

    return $result->fetch_assoc();
}

function validateUserAccess(array $user): void {
    if ($user['lvl'] == 0) {
        throw new Exception("Вы заблокированы на проекте.");
    }

    if ($user['lvl'] == 1) {
        throw new Exception("Вы не подтверждены на сайте.");
    }
}

function getInventory(mysqli $conn, int $userId): array {
    $stmt = $conn->prepare("SELECT * FROM invent WHERE id_user = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Инвентарь не активирован.");
    }

    return $result->fetch_assoc();
}

function handlePriceUpdate(mysqli $conn): void {
    $stmt = $conn->prepare("SELECT data_update FROM market LIMIT 1");
    $stmt->execute();
    $lastUpdate = $stmt->get_result()->fetch_assoc()['data_update'] ?? null;
    
    if ($lastUpdate && time() - strtotime($lastUpdate) >= 5) {
        updateMarketPrices($conn);
    }

    header('Content-Type: application/json');
    echo json_encode(getCurrentPrices($conn));
}

function updateMarketPrices(mysqli $conn): void {
    $stmt = $conn->prepare("SELECT * FROM market");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($resource = $result->fetch_assoc()) {
        $k = 0.41;
        $def = 0.5;
        $isb = 0.6;

        if ($resource['quantity'] < $resource['initial_quantity']) {
            $deficit = $resource['initial_quantity'] - $resource['quantity'];
            $ratio = $deficit / $resource['initial_quantity'];
            $price_sell = $resource['base_price_sell'] * (1 + $k) * exp($def * $ratio);
            $price_buy = ($resource['base_price_buy'] * (1 + $k) * exp(($k - 0.01) * $ratio)) * 0.7;
        } elseif ($resource['quantity'] > $resource['initial_quantity']) {
            $excess = $resource['quantity'] - $resource['initial_quantity'];
            $ratio = $excess / $resource['initial_quantity'];
            $price_sell = $resource['base_price_sell'] * (1 - $isb * $ratio);
            $price_buy = ($resource['base_price_buy'] * exp(-($k + 0.3) * $ratio) * 0.7);
        } else {
            $price_sell = $resource['base_price_sell'];
            $price_buy = $resource['base_price_buy'] * 0.7;
        }
        error_log("Calculated price_buy: " . $price_buy);
        $price_sell = max(15, $price_sell);
        $price_buy = max(3, $price_buy);

        $updateStmt = $conn->prepare("UPDATE market SET price_sell = ?, price_buy = ?, data_update = NOW() WHERE id_market = ?");
        $updateStmt->bind_param('ddi', $price_sell, $price_buy, $resource['id_market']);
        $updateStmt->execute();
    }
}

function getCurrentPrices(mysqli $conn): array {
    $prices = [];

    $stmt = $conn->prepare("SELECT price_buy, price_sell FROM market WHERE id_market = 2");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $prices['sakura_coin'] = $result['price_buy'];
    $prices['coin_sakura'] = $result['price_sell'];

    return $prices;
}

function handleResourceExchange(mysqli $conn, int $userId, array $inventory, string $resourceType, int $marketId, string $currencyType): void {
    $count = (int)($_POST['count'] ?? 0);
    if ($count <= 0) throw new Exception("Некорректное количество.");

    if ($inventory[$resourceType] < $count) {
        throw new Exception("Недостаточно ресурсов.");
    }

    $stmt = $conn->prepare("SELECT price_buy FROM market WHERE id_market = ?");
    $stmt->bind_param('i', $marketId);
    $stmt->execute();
    $price = $stmt->get_result()->fetch_assoc()['price_buy'];

    $stmt = $conn->prepare("SELECT quantity FROM market WHERE id_market = 3");
    $stmt->execute();
    $marketCoins = $stmt->get_result()->fetch_assoc()['quantity'];

    $totalCoins = $price * $count;


    if ($marketCoins < $totalCoins) {
        throw new Exception("Недостаточно монет на рынке.");
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE invent SET $resourceType = $resourceType - ?, $currencyType = $currencyType + ? WHERE id_user = ?");
        $stmt->bind_param('iii', $count, $totalCoins, $userId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE market SET quantity = quantity + ? WHERE id_market = ?");
        $stmt->bind_param('ii', $count, $marketId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE market SET quantity = quantity - ? WHERE id_market = 3");
        $stmt->bind_param('i', $totalCoins);
        $stmt->execute();

        $conn->commit();
        echo "Вы обменяли $count " . getResourceName($resourceType) . " на $totalCoins монет!";
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка транзакции: " . $e->getMessage());
    }
}

function handleResourcePurchase(mysqli $conn, int $userId, array $inventory, string $resourceType, int $marketId, string $currencyType): void {
    $count = (int)($_POST['count'] ?? 0);
    if ($count <= 0) throw new Exception("Некорректное количество.");

    $stmt = $conn->prepare("SELECT price_sell, quantity FROM market WHERE id_market = ?");
    $stmt->bind_param('i', $marketId);
    $stmt->execute();
    $marketData = $stmt->get_result()->fetch_assoc();

    $price = $marketData['price_sell'];
    $totalCost = $price * $count;

    if ($inventory[$currencyType] < $totalCost) {
        throw new Exception("Недостаточно средств.");
    }

    if ($marketData['quantity'] < $count) {
        throw new Exception("Недостаточно ресурсов на рынке.");
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE invent SET $resourceType = $resourceType + ?, $currencyType = $currencyType - ? WHERE id_user = ?");
        $stmt->bind_param('iii', $count, $totalCost, $userId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE market SET quantity = quantity - ? WHERE id_market = ?");
        $stmt->bind_param('ii', $count, $marketId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE market SET quantity = quantity + ? WHERE id_market = 3");
        $stmt->bind_param('i', $totalCost);
        $stmt->execute();

        $conn->commit();
        echo "Вы купили $count " . getResourceName($resourceType) . " за $totalCost монет!";
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка транзакции: " . $e->getMessage());
    }
}

function handleCasePurchase(mysqli $conn, int $userId, array $inventory, array $user): void {
    $count = (int)($_POST['count'] ?? 0);
    if ($count <= 0) throw new Exception("Некорректное количество.");

    $sakuraPerCase = ceil((($inventory['xp_max'] + $inventory['xp'])/950) * 10);
    $totalSakura = $sakuraPerCase * $count;

    if ($inventory['sakura'] < $totalSakura) {
        throw new Exception("Недостаточно лепестков сакуры.");
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE invent SET sakura = sakura - ?, kase = kase + ? WHERE id_user = ?");
        $stmt->bind_param('iii', $totalSakura, $count, $userId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE market SET quantity = quantity + ? WHERE id_market = 2");
        $stmt->bind_param('i', $totalSakura);
        $stmt->execute();

        $conn->commit();
        echo "Вы купили $count кейсов за $totalSakura лепестков сакуры!";
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка транзакции: " . $e->getMessage());
    }
}

function handlePremium(mysqli $conn, int $userId, array $inventory, array $user): void {
    if ($user['lvl'] >= 3) {
        throw new Exception("У вас уже есть PREMIUM статус.");
    }

    if ($user['donate'] < 250) {
        throw new Exception("Недостаточно рублей на аккаунте.");
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE users SET donate = donate - 250 WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE users SET permissions = 'PREMIUM' WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $stmt = $conn->prepare("SELECT id FROM tools WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $coins_limit = 5500000;
            $sakura_limit = 1000000;
            $stmt = $conn->prepare("UPDATE tools SET coins_limit = ?, sakura_limit = ? WHERE user_id = ?");
            $stmt->bind_param('iii', $coins_limit, $sakura_limit, $userId);
            $stmt->execute();
            
        }

        $conn->commit();
        echo "Поздравляем с приобретением PREMIUM статуса!";
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка транзакции: " . $e->getMessage());
    }
}

function handleTools(mysqli $conn, int $userId, array $inventory, array $user): void {
    $coins_limit = $user['lvl'] >= 3 ? 5500000 : 1000000;
    $sakura_limit = $user['lvl'] >= 3 ? 1000000 : 107500;

    if ($inventory['coins'] < 10) {
        throw new Exception("Недостаточно монет.");
    }

    $stmt = $conn->prepare("SELECT id FROM tools WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        throw new Exception("Доступ к инструментам уже активирован.");
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE invent SET coins = coins - 10 WHERE id_user = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO tools (user_id, coins_limit, sakura_limit) VALUES (?, ?, ?)");
        $stmt->bind_param('iii', $userId, $coins_limit, $sakura_limit);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE market SET quantity = quantity + 1050 WHERE id_market = 3");
        $stmt->execute();

        $conn->commit();
        echo "Поздравляем с приобретением доступа к инструментам!";
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка транзакции: " . $e->getMessage());
    }
}

function handleTitlePurchase(mysqli $conn, int $userId, array $inventory, string $action): void {
    $titles = [
        'shopaholic' => [
            'name' => 'Шопоголик',
            'cost' => 50000,
            'currency' => 'coins'
        ],
        'philanthropist' => [
            'name' => 'Филантроп',
            'cost' => 100000,
            'currency' => 'coins',
            'bonus' => "UPDATE invent SET kase = kase + 10"
        ],
        'rebel' => [
            'name' => 'Восставший',
            'cost' => 25000,
            'currency' => 'coins'
        ],
        'demonolog' => [
            'name' => 'Демонолог',
            'cost' => 666666,
            'currency' => 'coins'
        ],
        'maid' => [
            'name' => 'Горничная',
            'cost' => 75000,
            'currency' => 'sakura',
            'bonus' => "UPDATE market SET quantity = quantity + 7500 WHERE id_market = 2"
        ]
    ];

    if (!isset($titles[$action])) {
        throw new Exception("Неизвестный товар.");
    }

    $title = $titles[$action];

    $stmt = $conn->prepare("SELECT id_title FROM title WHERE id_user = ? AND title = ?");
    $stmt->bind_param('is', $userId, $title['name']);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("У вас уже есть этот титул.");
    }

    if ($inventory[$title['currency']] < $title['cost']) {
        throw new Exception("Недостаточно средств.");
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE invent SET {$title['currency']} = {$title['currency']} - ? WHERE id_user = ?");
        $stmt->bind_param('ii', $title['cost'], $userId);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO title (id_user, title) VALUES (?, ?)");
        $stmt->bind_param('is', $userId, $title['name']);
        $stmt->execute();

        if (isset($title['bonus'])) {
            $conn->query($title['bonus']);
        }

        $marketId = null;
        if ($title['currency'] === 'coins') {
            $marketId = 3;
        } elseif ($title['currency'] === 'sakura') {
            $marketId = 2;
        } elseif ($title['currency'] === 'gems') {
            $marketId = 1;
        }

        if ($marketId) {
            $stmt = $conn->prepare("UPDATE market SET quantity = quantity + ? WHERE id_market = ?");
            $stmt->bind_param('ii', $title['cost'], $marketId);
            $stmt->execute();
        }

        $conn->commit();
        echo "Поздравляем с приобретением титула '{$title['name']}'!";
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка транзакции: " . $e->getMessage());
    }
}

function getResourceName(string $type): string {
    switch ($type) {
        case 'sakura':
            return 'лепестков сакуры';
        case 'coins':
            return 'монет';
        default:
            return 'ресурсов';
    }
}
session_write_close();
