<?php
// api/get_players.php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

try {
    $rpg = get_or_create_rpg_profile($conn, $user['id']);
    $is_blind = has_blindness($conn, $user['id']);

    // Получаем всех RPG-игроков кроме текущего
    $stmt = $conn->prepare("
        SELECT ru.*, u.username, u.avatar 
        FROM rpg_user ru 
        JOIN users u ON ru.id_user = u.id 
        WHERE ru.id_user != ? AND ru.hp > 0
        ORDER BY ru.level DESC
    ");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $result = [];
    foreach ($players as $p) {
        // Проверяем кулдаун
        $cd = check_cooldown($conn, $user['id'], $p['id_user']);

        $player_data = [
            'id_user' => $p['id_user'],
            'username' => $p['username'],
            'avatar' => "../../profile/avatars/" . $p['avatar'],
            'element' => $p['element'],
            'on_cooldown' => $cd['on_cooldown'],
            'cooldown_remaining' => $cd['on_cooldown'] ? $cd['remaining'] : null,
            'show_details' => false
        ];

        // Показываем детали, если наш урон выше и нет слепоты
        if (!$is_blind && ($rpg['phys_attack'] > $p['phys_attack'] || $rpg['mag_attack'] > $p['mag_attack'])) {
            $player_data['show_details'] = true;
            $player_data['hp'] = $p['hp'];
            $player_data['hp_max'] = $p['hp_max'];
            $player_data['phys_defense'] = $p['phys_defense'];
            $player_data['mag_absorb'] = $p['mag_absorb'];
            $player_data['level'] = $p['level'];
            $player_data['avatar'] = "../../profile/avatars/" . $p['avatar'];
        }

        $result[] = $player_data;
    }

    rpg_log("Players list loaded for user_id={$user['id']}, count=" . count($result));

    json_response([
        'success' => true,
        'players' => $result,
        'is_blind' => $is_blind
    ]);
} catch (Exception $e) {
    rpg_log("get_players error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка получения списка игроков.', 500);
}