<?php
// api/get_profile.php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

try {
    $rpg = get_or_create_rpg_profile($conn, $user['id']);
    $max_stats = get_max_stats($rpg['level']);

    // Получить инвентарь (монеты, лепестки)
    $stmt = $conn->prepare("SELECT coins, sakura FROM invent WHERE id_user = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $invent = $stmt->get_result()->fetch_assoc();

    // Получить активные эффекты
    $stmt = $conn->prepare("SELECT effect_name, remaining_actions, damage_per_action FROM rpg_effects WHERE id_user = ? AND remaining_actions > 0");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $effects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Стоимость прокачки
    $upgrade_costs = [
        'hp' => get_upgrade_cost('hp', $rpg['level']),
        'phys_attack' => get_upgrade_cost('phys_attack', $rpg['level']),
        'mag_attack' => get_upgrade_cost('mag_attack', $rpg['level']),
        'phys_defense' => get_upgrade_cost('phys_defense', $rpg['level']),
        'mag_absorb' => get_upgrade_cost('mag_absorb', $rpg['level'], $rpg['mag_absorb'])
    ];

    // Значения прокачки
    $upgrade_values = [
        'hp' => get_upgrade_value('hp', $rpg['level']),
        'phys_attack' => get_upgrade_value('phys_attack', $rpg['level']),
        'mag_attack' => get_upgrade_value('mag_attack', $rpg['level']),
        'phys_defense' => get_upgrade_value('phys_defense', $rpg['level']),
        'mag_absorb' => get_upgrade_value('mag_absorb', $rpg['level'])
    ];

    // Проверка: можно ли поднять уровень
    $can_level_up = false;
    if ($rpg['xp'] >= $rpg['xp_max'] &&
        $rpg['hp_max'] >= $max_stats['hp_max'] &&
        $rpg['phys_attack'] >= $max_stats['phys_attack_max'] &&
        $rpg['phys_defense_max'] >= $max_stats['phys_defense_max'] &&
        $rpg['mag_attack'] >= $max_stats['mag_attack_max']) {
        $can_level_up = true;
    }

    rpg_log("Profile loaded for user_id={$user['id']}, rpg_level={$rpg['level']}");

    json_response([
        'success' => true,
        'profile' => [
            'username' => $user['username'],
            'avatar' => "../../profile/avatars/" . $user['avatar'],
            'rpg' => $rpg,
            'max_stats' => $max_stats,
            'coins' => $invent ? (int)$invent['coins'] : 0,
            'petals' => $invent ? (int)$invent['sakura'] : 0,
            'effects' => $effects,
            'upgrade_costs' => $upgrade_costs,
            'upgrade_values' => $upgrade_values,
            'can_level_up' => $can_level_up
        ]
    ]);
} catch (Exception $e) {
    rpg_log("get_profile error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка получения профиля.', 500);
}