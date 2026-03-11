<?php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

try {
    $conn->begin_transaction();

    $rpg = get_or_create_rpg_profile($conn, $user['id']);

    if ($rpg['phys_defense'] >= $rpg['phys_defense_max']) {
        $conn->rollback();
        json_error('Физическая защита уже полностью восстановлена.');
    }

    $deficit = $rpg['phys_defense_max'] - $rpg['phys_defense'];
    $repair = (int) ceil(0.55 * $deficit);
    if ($repair > $deficit) {
        $repair = $deficit;
    }

    if ($repair <= 0) {
        $conn->rollback();
        json_error('Недостаточно повреждений для восстановления.');
    }

    $cost_coins = 555;

    $stmt = $conn->prepare("SELECT coins FROM invent WHERE id_user = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $inv = $stmt->get_result()->fetch_assoc();

    if (!$inv || $inv['coins'] < $cost_coins) {
        $conn->rollback();
        json_error("Недостаточно монет. Нужно: {$cost_coins}.");
    }

    $new_defense = $rpg['phys_defense'] + $repair;
    $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = ? WHERE id_user = ?");
    $stmt->bind_param('ii', $new_defense, $user['id']);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE invent SET coins = coins - ? WHERE id_user = ?");
    $stmt->bind_param('ii', $cost_coins, $user['id']);
    $stmt->execute();

    $conn->commit();

    rpg_log("User {$user['id']} repaired defense: {$rpg['phys_defense']} → {$new_defense}, cost {$cost_coins} coins.");

    json_response([
        'success' => true,
        'message' => "Восстановлено {$repair} ед. защиты. Текущая защита: {$new_defense}/{$rpg['phys_defense_max']}.",
        'repair_amount' => $repair,
        'new_defense' => $new_defense
    ]);

} catch (Exception $e) {
    $conn->rollback();
    rpg_log("repair_defense error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка при восстановлении защиты.', 500);
}