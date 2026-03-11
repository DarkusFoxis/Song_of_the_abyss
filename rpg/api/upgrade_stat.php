<?php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

$input = json_decode(file_get_contents('php://input'), true);
$stat = $input['stat'] ?? '';

$allowed_stats = ['hp', 'phys_attack', 'mag_attack', 'phys_defense', 'mag_absorb'];
if (!in_array($stat, $allowed_stats)) {
    json_error('Неверный параметр для прокачки.');
}

try {
    $conn->begin_transaction();

    $rpg = get_or_create_rpg_profile($conn, $user['id']);
    $max_stats = get_max_stats($rpg['level']);

    $current_value = 0;
    $max_value = 0;
    $db_field = '';

    switch ($stat) {
        case 'hp':
            $current_value = $rpg['hp_max'];
            $max_value = $max_stats['hp_max'];
            $db_field = 'hp_max';
            break;
        case 'phys_attack':
            $current_value = $rpg['phys_attack'];
            $max_value = $max_stats['phys_attack_max'];
            $db_field = 'phys_attack';
            break;
        case 'mag_attack':
            $current_value = $rpg['mag_attack'];
            $max_value = $max_stats['mag_attack_max'];
            $db_field = 'mag_attack';
            break;
        case 'phys_defense':
            $current_value = $rpg['phys_defense_max'];
            $max_value = $max_stats['phys_defense_max'];
            $db_field = 'phys_defense_max';
            break;
        case 'mag_absorb':
            $current_value = $rpg['mag_absorb'];
            $max_value = $max_stats['mag_absorb_max'];
            $db_field = 'mag_absorb';
            break;
    }

    if ($current_value >= $max_value) {
        $conn->rollback();
        json_error("Характеристика '{$stat}' уже на максимуме для текущего уровня.");
    }

    $cost = get_upgrade_cost($stat, $rpg['level'], $rpg['mag_absorb']);
    $upgrade_value = get_upgrade_value($stat, $rpg['level']);

    $stmt = $conn->prepare("SELECT coins, sakura FROM invent WHERE id_user = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $invent = $stmt->get_result()->fetch_assoc();

    if (!$invent) {
        $conn->rollback();
        json_error('Инвентарь не найден.');
    }

    if ($invent['coins'] < $cost['coins'] || $invent['sakura'] < $cost['petals']) {
        $conn->rollback();
        json_error('Недостаточно ресурсов. Нужно: ' . $cost['coins'] . ' монет, ' . $cost['petals'] . ' лепестков.');
    }

    $new_value = min($current_value + $upgrade_value, $max_value);

    if ($stat === 'hp') {
        $diff = $new_value - $current_value;
        $stmt = $conn->prepare("UPDATE rpg_user SET hp_max = ?, hp = LEAST(hp + ?, ?) WHERE id_user = ?");
        $stmt->bind_param('iiii', $new_value, $diff, $new_value, $user['id']);
    } elseif ($stat === 'phys_defense') {
        $diff = $new_value - $current_value;
        $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense_max = ?, phys_defense = LEAST(phys_defense + ?, ?) WHERE id_user = ?");
        $stmt->bind_param('iiii', $new_value, $diff, $new_value, $user['id']);
    } else {
        $stmt = $conn->prepare("UPDATE rpg_user SET {$db_field} = ? WHERE id_user = ?");
        $stmt->bind_param('ii', $new_value, $user['id']);
    }
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE invent SET coins = coins - ?, sakura = sakura - ? WHERE id_user = ?");
    $stmt->bind_param('iii', $cost['coins'], $cost['petals'], $user['id']);
    $stmt->execute();

    $conn->commit();

    rpg_log("User {$user['id']} upgraded {$stat}: {$current_value} -> {$new_value}. Cost: {$cost['coins']} coins, {$cost['petals']} petals.");

    json_response([
        'success' => true,
        'message' => "Характеристика '{$stat}' улучшена: {$current_value} → {$new_value}",
        'cost' => $cost,
        'new_value' => $new_value
    ]);
} catch (Exception $e) {
    $conn->rollback();
    rpg_log("upgrade_stat error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка при прокачке.', 500);
}