<?php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

try {
    $conn->begin_transaction();

    $rpg = get_or_create_rpg_profile($conn, $user['id']);

    if ($rpg['hp'] >= $rpg['hp_max']) {
        $conn->rollback();
        json_error('У вас уже полное здоровье.');
    }

    $percent = ($rpg['level'] < 5) ? 0.35 : 0.15;
    $max_heal = (int) floor($rpg['hp_max'] * $percent);
    $heal = min($max_heal, $rpg['hp_max'] - $rpg['hp']);

    if ($heal <= 0) {
        $conn->rollback();
        json_error('Нельзя восстановить здоровье.');
    }

    $cost_coins = 350;
    $cost_petals = 100;

    $stmt = $conn->prepare("SELECT coins, sakura FROM invent WHERE id_user = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $inv = $stmt->get_result()->fetch_assoc();

    if (!$inv || $inv['coins'] < $cost_coins || $inv['sakura'] < $cost_petals) {
        $conn->rollback();
        json_error("Недостаточно ресурсов. Нужно: {$cost_coins} монет и {$cost_petals} лепестков.");
    }

    $new_hp = $rpg['hp'] + $heal;
    $stmt = $conn->prepare("UPDATE rpg_user SET hp = ? WHERE id_user = ?");
    $stmt->bind_param('ii', $new_hp, $user['id']);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE invent SET coins = coins - ?, sakura = sakura - ? WHERE id_user = ?");
    $stmt->bind_param('iii', $cost_coins, $cost_petals, $user['id']);
    $stmt->execute();

    $conn->commit();

    rpg_log("User {$user['id']} healed: hp {$rpg['hp']} → {$new_hp}, cost {$cost_coins} coins, {$cost_petals} petals.");

    json_response([
        'success' => true,
        'message' => "Восстановлено {$heal} HP. Текущее здоровье: {$new_hp}/{$rpg['hp_max']}.",
        'heal_amount' => $heal,
        'new_hp' => $new_hp
    ]);

} catch (Exception $e) {
    $conn->rollback();
    rpg_log("heal error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка при восстановлении здоровья.', 500);
}