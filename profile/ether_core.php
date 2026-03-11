<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Error connection: " . mysqli_connect_error();
    exit;
}

try {
    $authUser = auth_get_current_user();
    if ($authUser === null) {
        throw new Exception("Вы не авторизованы.");
    }

    $login = $authUser['login'];

    $user = getUserData($conn, $login);
    validateUserAccess($user);

    $inventory = getInventory($conn, $user['id']);

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'update_prices':
            updateEtherPrices($conn);
            header('Content-Type: application/json');
            echo json_encode(getCurrentEtherPrices($conn));
            break;
        case 'calculate_price':
            handleCalculatePrice($conn);
            break;
        case 'sell_ether_coins':
            handleEtherSell($conn, $user['id'], $inventory, $user, 'coins', 3);
            break;
        case 'sell_ether_petal':
            handleEtherSell($conn, $user['id'], $inventory, $user, 'sakura', 2);
            break;
        case 'buy_ether_coins':
            handleEtherPurchase($conn, $user['id'], $inventory, 'coins', 3);
            break;
        case 'buy_ether_petal':
            handleEtherPurchase($conn, $user['id'], $inventory, 'sakura', 2);
            break;
        default:
            throw new Exception("Неизвестное действие.");
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
} catch (mysqli_sql_exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
    exit;
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

function updateEtherPrices(mysqli $conn): void {
    $stmt = $conn->prepare("SELECT * FROM abyss_ether WHERE id = 1");
    $stmt->execute();
    $ether = $stmt->get_result()->fetch_assoc();

    if (!$ether) {
        throw new Exception("Данные Эфира бездны не найдены.");
    }

    $k = 1;
    $def = 2;
    $isb = 0.70;

    $current = floatval($ether['count']);
    $initial = floatval($ether['initial']);

    foreach (['coins', 'petal'] as $resource) {
        $base_price = floatval($ether[$resource . '_start']);

        if ($current < $initial) {
            $deficit = $initial - $current;
            $ratio = $deficit / $initial;
            $multiplier = 500000;
            $price = $base_price * (1 + $k + $multiplier * $ratio);
        } elseif ($current > $initial) {
            $excess = $current - $initial;
            $ratio = $excess / $initial;
            $price = max(1, $base_price * (1 - $isb * $ratio));
        } else {
            $price = $base_price;
        }

        $updateStmt = $conn->prepare("UPDATE abyss_ether SET $resource = ? WHERE id = 1");
        $updateStmt->bind_param('d', $price);
        $updateStmt->execute();
    }
}

function getCurrentEtherPrices(mysqli $conn): array {
    $stmt = $conn->prepare("SELECT * FROM abyss_ether WHERE id = 1");
    $stmt->execute();
    $ether = $stmt->get_result()->fetch_assoc();

    $prices = [
        'coins' => [
            'price' => floatval($ether['coins']),
            'count' => floatval($ether['count']),
            'initial' => floatval($ether['initial'])
        ],
        'petal' => [
            'price' => floatval($ether['petal']),
            'count' => floatval($ether['count']),
            'initial' => floatval($ether['initial'])
        ]
    ];

    return $prices;
}

function handleCalculatePrice(mysqli $conn): void {
    $resource = $_POST['resource'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $type = $_POST['type'] ?? 'buy';

    if ($amount <= 0 || $amount > 1000) {
        throw new Exception("Некорректное количество.");
    }

    $stmt = $conn->prepare("SELECT * FROM abyss_ether WHERE id = 1");
    $stmt->execute();
    $ether = $stmt->get_result()->fetch_assoc();

    $resourceField = ($resource === 'petal') ? 'petal' : $resource;
    $pricePerMicroEther = floatval($ether[$resourceField]);

    if ($type === 'buy') {
        $totalCost = ceil($amount * 10000000 * $pricePerMicroEther);
    } else {
        $totalCost = floor($amount * 10000000 * $pricePerMicroEther * 0.5);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'amount' => $amount,
        'price' => $totalCost,
        'price_formatted' => number_format($totalCost, 0, '.', ' ')
    ]);
}

function handleEtherSell(mysqli $conn, int $userId, array $inventory, array $user, string $resourceType, int $marketId): void {
    $count = floatval($_POST['count'] ?? 0);
    if ($count <= 0 || $count > 1000) throw new Exception("Некорректное количество.");

    $userEther = floatval($user['abyss_ether']);

    if ($userEther < $count) {
        throw new Exception("Недостаточно Эфира бездны.");
    }

    $stmt = $conn->prepare("SELECT * FROM abyss_ether WHERE id = 1");
    $stmt->execute();
    $ether = $stmt->get_result()->fetch_assoc();

    $resourceField = ($resourceType === 'sakura') ? 'petal' : $resourceType;
    $pricePerMicroEther = floatval($ether[$resourceField]);

    $totalResource = floor($count * 10000000 * $pricePerMicroEther * 0.5);

    $stmt = $conn->prepare("SELECT quantity FROM market WHERE id_market = ?");
    $stmt->bind_param('i', $marketId);
    $stmt->execute();
    $marketQuantity = $stmt->get_result()->fetch_assoc()['quantity'];

    if ($marketQuantity < $totalResource) {
        throw new Exception("Недостаточно ресурсов на рынке.");
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE users SET abyss_ether = abyss_ether - ? WHERE id = ?");
        $stmt->bind_param('di', $count, $userId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE invent SET $resourceType = $resourceType + ? WHERE id_user = ?");
        $stmt->bind_param('ii', $totalResource, $userId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE abyss_ether SET count = count + ? WHERE id = 1");
        $stmt->bind_param('d', $count);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE market SET quantity = quantity - ? WHERE id_market = ?");
        $stmt->bind_param('ii', $totalResource, $marketId);
        $stmt->execute();

        updateEtherPrices($conn);

        $conn->commit();

        $resourceNames = [
            'coins' => 'монет',
            'sakura' => 'лепестков сакуры',
            'gems' => 'кристаллов'
        ];

        echo "Вы продали " . number_format($count, 7) . " Эфира бездны за " . number_format($totalResource) . " " . $resourceNames[$resourceType] . "!";
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка транзакции: " . $e->getMessage());
    }
}

function handleEtherPurchase(mysqli $conn, int $userId, array $inventory, string $resourceType, int $marketId): void {
    $count = floatval($_POST['count'] ?? 0);
    if ($count <= 0 || $count > 999999) throw new Exception("Некорректное количество.");

    $stmt = $conn->prepare("SELECT * FROM abyss_ether WHERE id = 1");
    $stmt->execute();
    $ether = $stmt->get_result()->fetch_assoc();

    $resourceField = ($resourceType === 'sakura') ? 'petal' : $resourceType;
    $pricePerMicroEther = floatval($ether[$resourceField]);

    $totalCost = ceil($count * 10000000 * $pricePerMicroEther);

    if ($inventory[$resourceType] < $totalCost) {
        throw new Exception("Недостаточно ресурсов.");
    }

    $etherCount = floatval($ether['count']);

    if ($etherCount < $count) {
        throw new Exception("Недостаточно Эфира на рынке.");
    }

    $marketResource = ($resourceType === 'sakura') ? floor($totalCost * 0.3) : floor($totalCost * 1);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE invent SET $resourceType = $resourceType - ? WHERE id_user = ?");
        $stmt->bind_param('ii', $totalCost, $userId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE users SET abyss_ether = abyss_ether + ? WHERE id = ?");
        $stmt->bind_param('di', $count, $userId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE abyss_ether SET count = count - ? WHERE id = 1");
        $stmt->bind_param('d', $count);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE market SET quantity = quantity + ? WHERE id_market = ?");
        $stmt->bind_param('ii', $marketResource, $marketId);
        $stmt->execute();

        updateEtherPrices($conn);

        $conn->commit();

        $resourceNames = [
            'coins' => 'монет',
            'sakura' => 'лепестков сакуры',
        ];

        echo "Вы купили " . number_format($count, 7) . " Эфира бездны за " . number_format($totalCost) . " " . $resourceNames[$resourceType] . "!";
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка транзакции: " . $e->getMessage());
    }
}
session_write_close();

