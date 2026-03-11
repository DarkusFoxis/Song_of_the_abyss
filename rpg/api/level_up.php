<?php
// api/level_up.php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

try {
    $conn->begin_transaction();

    $rpg = get_or_create_rpg_profile($conn, $user['id']);
    $max_stats = get_max_stats($rpg['level']);

    // Проверяем условия
    if ($rpg['xp'] < $rpg['xp_max']) {
        $conn->rollback();
        json_error("Недостаточно опыта. Нужно: {$rpg['xp_max']}, имеется: {$rpg['xp']}.");
    }

    if ($rpg['hp_max'] < $max_stats['hp_max']) {
        $conn->rollback();
        json_error("ХП не на максимуме для текущего уровня.");
    }
    if ($rpg['phys_attack'] < $max_stats['phys_attack_max']) {
        $conn->rollback();
        json_error("Физ. атака не на максимуме для текущего уровня.");
    }
    if ($rpg['phys_defense_max'] < $max_stats['phys_defense_max']) {
        $conn->rollback();
        json_error("Физ. защита не на максимуме для текущего уровня.");
    }
    if ($rpg['mag_attack'] < $max_stats['mag_attack_max']) {
        $conn->rollback();
        json_error("Маг. атака не на максимуме для текущего уровня.");
    }

    $new_level = $rpg['level'] + 1;
    $new_xp = $rpg['xp'] - $rpg['xp_max'];
    $new_xp_max = 10000 * ($new_level + 1);

    $stmt = $conn->prepare("UPDATE rpg_user SET level = ?, xp = ?, xp_max = ? WHERE id_user = ?");
    $stmt->bind_param('iiii', $new_level, $new_xp, $new_xp_max, $user['id']);
    $stmt->execute();

    $conn->commit();

    rpg_log("User {$user['id']} leveled up: {$rpg['level']} -> {$new_level}");

    json_response([
        'success' => true,
        'message' => "Поздравляем! Уровень повышен до {$new_level}!",
        'new_level' => $new_level
    ]);
} catch (Exception $e) {
    $conn->rollback();
    rpg_log("level_up error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка при повышении уровня.', 500);
}