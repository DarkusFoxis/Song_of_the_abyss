<?php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

try {
    $rpg = get_or_create_rpg_profile($conn, $user['id']);

    $cooldown = check_pve_cooldown($conn, $user['id']);
    if ($cooldown['on_cooldown']) {
        json_response([
            'success' => false,
            'error' => 'Вы недавно погибли. Подождите.',
            'cooldown' => true,
            'remaining' => $cooldown['remaining_seconds']
        ], 400);
    }

    $stmt = $conn->prepare("SELECT * FROM rpg_mobs ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $mob = $stmt->get_result()->fetch_assoc();

    if (!$mob) {
        json_error('Враги не найдены в базе данных.');
    }

    $diff = ceil(mt_rand(1,7)) * 0.1;

    $scale = 1 + ($rpg['level'] * $diff);
    $mob_magic_effects = normalize_effect_name_list($mob['magic_effects'] ?? '');
    $mob_system_effects = normalize_effect_name_list($mob['system_effects'] ?? '');
    $mob_ai = max(0, min(100, (int)($mob['mob_ai'] ?? 0)));

    $scaled_mob = [
        'mob_id' => $mob['mob_id'],
        'name' => $mob['name'],
        'url' => $mob['url'],
        'hp' => floor($mob['hp'] * $scale),
        'hp_max' => floor($mob['hp'] * $scale),
        'phys_attack' => floor($mob['phys_attack'] * $scale),
        'phys_defense' => floor($mob['phys_defense'] * $scale),
        'mag_attack' => floor($mob['mag_attack'] * $scale),
        'mag_absorb' => min($mob['mag_absorb'], 75),
        'element' => $mob['element'],
        'coins' => floor($mob['coins'] * $scale),
        'petals' => floor($mob['petals'] * $scale),
        'mob_ai' => $mob_ai,
        'magic_effects' => $mob_magic_effects,
        'system_effects' => $mob_system_effects,
        'effects' => [],
        'user_url' => $user['avatar']
    ];

    rpg_log("PVE mob spawned for user_id={$user['id']}: {$mob['name']} (scaled x{$scale})");

    json_response([
        'success' => true,
        'mob' => $scaled_mob,
        'player' => [
            'hp' => $rpg['hp'],
            'hp_max' => $rpg['hp_max'],
            'phys_attack' => $rpg['phys_attack'],
            'mag_attack' => $rpg['mag_attack'],
            'phys_defense' => $rpg['phys_defense'],
            'mag_absorb' => $rpg['mag_absorb'],
            'element' => $rpg['element']
        ]
    ]);
} catch (Exception $e) {
    rpg_log("get_mob error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка получения врага.', 500);
}
