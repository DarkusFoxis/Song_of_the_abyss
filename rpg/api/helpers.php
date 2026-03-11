<?php
define('RPG_LOG_FILE', __DIR__ . '/../logs/rpg.log');

define('ELEMENTS', ['Огонь', 'Трава', 'Вода', 'Лёд', 'Тьма', 'Свет', 'Контроль разума', 'Бездна']);

function rpg_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logDir = dirname(RPG_LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents(RPG_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message, $code = 400) {
    rpg_log("ERROR RESPONSE: {$message}", 'ERROR');
    json_response(['success' => false, 'error' => $message], $code);
}

function get_db_connection() {
    require __DIR__ . '/../../template/conn.php';
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if (!$conn) {
        rpg_log("DB Connection failed: " . mysqli_connect_error(), 'CRITICAL');
        throw new Exception("Ошибка соединения с БД");
    }
    mysqli_set_charset($conn, 'utf8');
    return $conn;
}

function get_current_rpg_user($conn) {
    if (!isset($_SESSION['user'])) {
        return null;
    }
    $login = $_SESSION['user'];
    $stmt = $conn->prepare("SELECT u.*, sg.lvl as perm_lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?");
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return null;
    }
    return $result->fetch_assoc();
}

function get_or_create_rpg_profile($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM rpg_user WHERE id_user = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    $element = ELEMENTS[array_rand(ELEMENTS)];
    $stmt = $conn->prepare("INSERT INTO rpg_user (id_user, level, xp, xp_max, hp, hp_max, phys_attack, mag_attack, phys_defense, phys_defense_max, mag_absorb, element, kills, deaths) VALUES (?, 0, 0, 10000, 100, 100, 1, 1, 1, 10, 0, ?, 0, 0)");
    $stmt->bind_param('is', $user_id, $element);
    $stmt->execute();

    rpg_log("Created RPG profile for user_id={$user_id}, element={$element}");

    $stmt = $conn->prepare("SELECT * FROM rpg_user WHERE id_user = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_element_modifier($attacker_element, $defender_element) {
    if ($attacker_element === $defender_element) {
        return 0.85;
    }

    $advantages = [
        'Огонь' => ['Трава'],
        'Трава' => ['Вода'],
        'Вода' => ['Огонь'],
        'Лёд' => ['Огонь', 'Вода', "Трава"],
        'Тьма' => ['Свет', 'Лёд'],
        'Свет' => ['Контроль разума', 'Тьма'],
        'Контроль разума' => ['Огонь', 'Трава', 'Вода', 'Тьма'],
        'Бездна' => ['Огонь', 'Трава', 'Вода', 'Лёд',  'Свет', 'Контроль разума']
    ];

    if (isset($advantages[$attacker_element]) && in_array($defender_element, $advantages[$attacker_element])) {
        return 1.15;
    }

    if (isset($advantages[$defender_element]) && in_array($attacker_element, $advantages[$defender_element])) {
        return 0.85;
    }
    return 1.0;
}

function get_max_stats($level) {
    return [
        'hp_max' => min(100 * ($level + 2), 10000),
        'phys_attack_max' => min(10 * ($level + 2), 1000),
        'phys_defense_max' => min(10 * ($level + 2), 1000),
        'mag_attack_max' => min(10 * ($level + 2), 1000),
        'mag_absorb_max' => 80
    ];
}

function get_upgrade_value($stat, $level) {
    switch ($stat) {
        case 'hp':
            return ($level + 1) * 10;
        case 'phys_attack':
        case 'mag_attack':
        case 'phys_defense':
            return ($level + 1);
        case 'mag_absorb':
            return 5;
        default:
            return 0;
    }
}

function get_upgrade_cost($stat, $level, $current_mag_absorb = 0) {
    switch ($stat) {
        case 'hp':
            return ['coins' => ($level + 1) * 1323, 'petals' => ($level + 1) * 167];
        case 'phys_attack':
            return ['coins' => ($level + 1) * 222, 'petals' => ($level + 1) * 32];
        case 'mag_attack':
            return ['coins' => 0, 'petals' => ($level + 1) * 195];
        case 'phys_defense':
            return ['coins' => ($level + 1) * 515, 'petals' => ($level + 1) * 32];
        case 'mag_absorb':
            return ['coins' => 0, 'petals' => 112 + ($current_mag_absorb * 17)];
        default:
            return ['coins' => 0, 'petals' => 0];
    }
}

function calculate_death_recovery_state(array $rpg_profile) {
    $level_before = max(0, (int)($rpg_profile['level'] ?? 0));
    $deaths_before = max(0, (int)($rpg_profile['deaths'] ?? 0));
    $deaths_after = $deaths_before + 1;

    $hp_max = max(1, (int)($rpg_profile['hp_max'] ?? 1));
    $phys_attack = max(1, (int)($rpg_profile['phys_attack'] ?? 1));
    $mag_attack = max(1, (int)($rpg_profile['mag_attack'] ?? 1));
    $phys_defense_max = max(0, (int)($rpg_profile['phys_defense_max'] ?? 0));
    $mag_absorb = max(0, (int)($rpg_profile['mag_absorb'] ?? 0));
    $xp = max(0, (int)($rpg_profile['xp'] ?? 0));
    $xp_max = max(1, (int)($rpg_profile['xp_max'] ?? 1));

    $level_after = $level_before;
    $penalty_applied = false;
    if ($level_before >= 11 && intdiv($deaths_after, 10) > intdiv($deaths_before, 10)) {
        $penalty_applied = true;
        $level_after = max(0, $level_before - 1);

        $hp_max = max(1, $hp_max - get_upgrade_value('hp', $level_before));
        $phys_attack = max(1, $phys_attack - get_upgrade_value('phys_attack', $level_before));
        $mag_attack = max(1, $mag_attack - get_upgrade_value('mag_attack', $level_before));
        $phys_defense_max = max(0, $phys_defense_max - get_upgrade_value('phys_defense', $level_before));
        $mag_absorb = max(0, $mag_absorb - get_upgrade_value('mag_absorb', $level_before));

        $xp_max = max(1, 10000 * ($level_after + 1));
        $xp = min($xp, max(0, $xp_max - 1));
    }

    $respawn_hp = max(1, (int)ceil($hp_max * 0.25));
    $respawn_defense = max(0, (int)ceil($phys_defense_max * 0.25));

    return [
        'deaths_after' => $deaths_after,
        'level_before' => $level_before,
        'level_after' => $level_after,
        'hp_max_after' => $hp_max,
        'phys_attack_after' => $phys_attack,
        'mag_attack_after' => $mag_attack,
        'phys_defense_max_after' => $phys_defense_max,
        'mag_absorb_after' => $mag_absorb,
        'xp_after' => $xp,
        'xp_max_after' => $xp_max,
        'respawn_hp' => $respawn_hp,
        'respawn_defense' => $respawn_defense,
        'penalty_applied' => $penalty_applied
    ];
}

function normalize_effect_name_list($raw) {
    if (is_array($raw)) {
        $items = $raw;
    } elseif (is_string($raw)) {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        if (($raw[0] ?? '') === '[') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $items = $decoded;
            } else {
                $items = preg_split('/[;,]+/u', $raw);
            }
        } else {
            $items = preg_split('/[;,]+/u', $raw);
        }
    } else {
        return [];
    }

    $result = [];
    foreach ($items as $item) {
        if (!is_string($item)) {
            continue;
        }
        $name = trim($item);
        if ($name === '') {
            continue;
        }
        $result[$name] = true;
    }
    return array_keys($result);
}

function get_effect_definitions() {
    return [
        'Поджог' => [
            'element' => 'Огонь',
            'modes' => ['pvp', 'pve'],
            'actions' => function () { return mt_rand(1, 5); },
            'damage' => function ($level) { return $level * 10; }
        ],
        'Слепота' => [
            'element' => 'Свет',
            'modes' => ['pvp', 'pve'],
            'actions' => 1,
            'damage' => 0
        ],
        'Теневой взрыв' => [
            'element' => 'Бездна',
            'modes' => ['pvp', 'pve'],
            'actions' => 1,
            'damage' => function ($level) { return $level * 50; }
        ],
        'Заморозка' => [
            'element' => 'Лёд',
            'modes' => ['pve'],
            'actions' => 1,
            'damage' => 0
        ],
        'Обманчивый мир' => [
            'element' => 'Контроль разума',
            'modes' => ['pve'],
            'actions' => 1,
            'damage' => 0
        ],
        'Взрывная волна' => [
            'element' => 'Огонь',
            'modes' => ['pvp', 'pve'],
            'actions' => 1,
            'damage' => 0
        ]
    ];
}

function resolve_effect_runtime_value($value, $caster_level) {
    if (is_callable($value)) {
        return (int)$value($caster_level);
    }
    return (int)$value;
}

function get_effect_candidates($caster_element, $battle_mode = 'pvp', $allowed_effects = null) {
    $definitions = get_effect_definitions();
    $allowed_map = [];
    $allowed_list = normalize_effect_name_list($allowed_effects);
    foreach ($allowed_list as $name) {
        $allowed_map[$name] = true;
    }

    $candidates = [];
    foreach ($definitions as $name => $def) {
        if (($def['element'] ?? '') !== $caster_element) {
            continue;
        }
        if (!in_array($battle_mode, $def['modes'] ?? [], true)) {
            continue;
        }
        if (!empty($allowed_map) && !isset($allowed_map[$name])) {
            continue;
        }
        $candidates[$name] = $def;
    }
    return $candidates;
}

function can_apply_effect($effect_name, $target_element) {
    $immunities = [
        'Поджог' => ['Вода', 'Огонь', 'Свет', 'Бездна'],
        'Слепота' => ['Свет', 'Бездна', 'Контроль разума', 'Тьма'],
        'Теневой взрыв' => ['Бездна', 'Тьма'],
        'Заморозка' => ["Свет", "Лёд"],
        'Обманчивый мир' => ["Контроль разума", "Бездна", "Свет", "Тьма"],
    ];

    if (isset($immunities[$effect_name])) {
        return !in_array($target_element, $immunities[$effect_name]);
    }
    return true;
}

function roll_effect_for_target($caster_level, $caster_element, $target_element, $battle_mode = 'pvp', $allowed_effects = null) {
    if (mt_rand(1, 100) > 10) {
        return null;
    }

    $effects = get_effect_candidates($caster_element, $battle_mode, $allowed_effects);

    $available = [];
    foreach ($effects as $name => $eff) {
        if (can_apply_effect($name, $target_element)) {
            $available[] = [
                'name' => $name,
                'element' => $eff['element'],
                'actions' => resolve_effect_runtime_value($eff['actions'], $caster_level),
                'damage' => resolve_effect_runtime_value($eff['damage'], $caster_level)
            ];
        }
    }

    if (empty($available)) {
        rpg_log("No applicable effects for target element={$target_element}, caster_element={$caster_element}, mode={$battle_mode}");
        return null;
    }

    return $available[array_rand($available)];
}

function try_apply_effect($conn, $caster_id, $caster_level, $caster_element, $target_id, $target_element, $battle_mode = 'pvp', $allowed_effects = null) {
    $chosen = roll_effect_for_target($caster_level, $caster_element, $target_element, $battle_mode, $allowed_effects);
    if (!$chosen) {
        return null;
    }

    $stmt = $conn->prepare("INSERT INTO rpg_effects (id_user, effect_name, effect_source_element, remaining_actions, damage_per_action, caster_level, caster_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('issiiii', $target_id, $chosen['name'], $chosen['element'], $chosen['actions'], $chosen['damage'], $caster_level, $caster_id);
    $stmt->execute();

    rpg_log("Effect '{$chosen['name']}' applied to user_id={$target_id} by caster_id={$caster_id}, actions={$chosen['actions']}, damage={$chosen['damage']}");
    return $chosen;
}

function consume_effect_action($conn, $effect_id) {
    $stmt = $conn->prepare("SELECT remaining_actions FROM rpg_effects WHERE effect_id = ?");
    $stmt->bind_param('i', $effect_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        return;
    }

    $remaining = (int)$row['remaining_actions'];
    if ($remaining <= 1) {
        $stmt = $conn->prepare("DELETE FROM rpg_effects WHERE effect_id = ?");
        $stmt->bind_param('i', $effect_id);
        $stmt->execute();
    } else {
        $new_remaining = $remaining - 1;
        $stmt = $conn->prepare("UPDATE rpg_effects SET remaining_actions = ? WHERE effect_id = ?");
        $stmt->bind_param('ii', $new_remaining, $effect_id);
        $stmt->execute();
    }
}

function process_effects($conn, $user_id, $battle_mode = 'pvp') {
    $state = [
        'results' => [],
        'skip_turn' => false,
        'damage_multiplier' => 1.0,
        'redirect_attack_to_self' => false,
        'stat_penalty' => [
            'phys_attack' => 0,
            'mag_attack' => 0,
            'phys_defense' => 0,
            'mag_absorb' => 0
        ],
        'explosive_wave_effect_ids' => []
    ];

    $definitions = get_effect_definitions();

    $stmt = $conn->prepare("SELECT * FROM rpg_effects WHERE id_user = ? AND remaining_actions > 0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $effects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($effects as $eff) {
        $effect_name = $eff['effect_name'];
        $definition = $definitions[$effect_name] ?? null;

        if ($definition && !in_array($battle_mode, $definition['modes'] ?? [], true)) {
            continue;
        }

        $effect_result = ['name' => $eff['effect_name'], 'damage' => 0, 'message' => ''];
        $should_decrement = true;

        switch ($effect_name) {
            case 'Поджог':
                $dmg = $eff['damage_per_action'];
                $stmt2 = $conn->prepare("UPDATE rpg_user SET hp = GREATEST(0, hp - ?) WHERE id_user = ?");
                $stmt2->bind_param('ii', $dmg, $user_id);
                $stmt2->execute();
                $effect_result['damage'] = $dmg;
                $effect_result['message'] = "Поджог наносит {$dmg} урона!";
                rpg_log("Burn effect dealt {$dmg} damage to user_id={$user_id}");
                break;

            case 'Слепота':
                $effect_result['message'] = "Вы ослеплены! Видите только ник противника.";
                rpg_log("Blindness active on user_id={$user_id}");
                break;

            case 'Теневой взрыв':
                $dmg = $eff['damage_per_action'];
                $stmt2 = $conn->prepare("UPDATE rpg_user SET hp = GREATEST(0, hp - ?) WHERE id_user = ?");
                $stmt2->bind_param('ii', $dmg, $user_id);
                $stmt2->execute();
                $effect_result['damage'] = $dmg;
                $effect_result['message'] = "Теневой взрыв! Вы получаете {$dmg} урона при атаке!";
                rpg_log("Shadow explosion dealt {$dmg} self-damage to user_id={$user_id}");
                break;

            case 'Заморозка':
                if (mt_rand(1, 100) <= 75) {
                    $state['skip_turn'] = true;
                    $effect_result['message'] = "Заморозка! Вы пропускаете ход.";
                } else {
                    $caster_level = max(0, (int)$eff['caster_level']);
                    $state['stat_penalty']['phys_attack'] += get_upgrade_value('phys_attack', $caster_level) * 2;
                    $state['stat_penalty']['mag_attack'] += get_upgrade_value('mag_attack', $caster_level) * 2;
                    $state['stat_penalty']['phys_defense'] += get_upgrade_value('phys_defense', $caster_level) * 2;
                    $state['stat_penalty']['mag_absorb'] += get_upgrade_value('mag_absorb', $caster_level) * 2;
                    $effect_result['message'] = "Заморозка! Ваши боевые показатели ослаблены на 2 прокачки.";
                }
                break;

            case 'Обманчивый мир':
                if (mt_rand(1, 100) <= 10) {
                    $state['damage_multiplier'] *= 0.5;
                    $effect_result['message'] = "Обманчивый мир: урон в этом ходу снижен на 50%.";
                } else {
                    $state['redirect_attack_to_self'] = true;
                    $effect_result['message'] = "Обманчивый мир: ваша атака в этом ходу попадёт по вам.";
                }
                break;

            case 'Взрывная волна':
                $state['explosive_wave_effect_ids'][] = (int)$eff['effect_id'];
                $effect_result['message'] = "Взрывная волна активна: следующая магическая атака ранит вас на 50%.";
                $should_decrement = false;
                break;

            default:
                $effect_result['message'] = "Эффект {$effect_name} активен.";
                break;
        }

        if ($should_decrement) {
            $new_remaining = $eff['remaining_actions'] - 1;
            if ($new_remaining <= 0) {
                $stmt3 = $conn->prepare("DELETE FROM rpg_effects WHERE effect_id = ?");
                $stmt3->bind_param('i', $eff['effect_id']);
                $stmt3->execute();
                $effect_result['message'] .= " (Эффект закончился)";
            } else {
                $stmt3 = $conn->prepare("UPDATE rpg_effects SET remaining_actions = ? WHERE effect_id = ?");
                $stmt3->bind_param('ii', $new_remaining, $eff['effect_id']);
                $stmt3->execute();
            }
        }

        if ($effect_result['message'] !== '') {
            $state['results'][] = $effect_result;
        }
    }
    return $state;
}

function has_blindness($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM rpg_effects WHERE id_user = ? AND effect_name = 'Слепота' AND remaining_actions > 0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['cnt'] > 0;
}

function check_cooldown($conn, $attacker_id, $target_id) {
    $stmt = $conn->prepare("SELECT cooldown_until FROM rpg_cooldowns WHERE attacker_id = ? AND target_id = ?");
    $stmt->bind_param('ii', $attacker_id, $target_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cd_until = new DateTime($row['cooldown_until']);
        $now = new DateTime();
        if ($now < $cd_until) {
            $diff = $now->diff($cd_until);
            return [
                'on_cooldown' => true,
                'remaining' => $diff->format('%i мин. %s сек.')
            ];
        }
    }
    return ['on_cooldown' => false];
}

function set_cooldown($conn, $attacker_id, $target_id, $minutes) {
    $cd_until = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));

    $stmt = $conn->prepare("INSERT INTO rpg_cooldowns (attacker_id, target_id, cooldown_until) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cooldown_until = ?");
    $stmt->bind_param('iiss', $attacker_id, $target_id, $cd_until, $cd_until);
    $stmt->execute();

    rpg_log("Cooldown set: attacker={$attacker_id}, target={$target_id}, until={$cd_until}");
}

function set_pve_cooldown($conn, $user_id, $seconds) {
    $cooldown_until = date('Y-m-d H:i:s', time() + $seconds);
    $stmt = $conn->prepare("INSERT INTO rpg_pve_cooldown (user_id, cooldown_until) VALUES (?, ?) ON DUPLICATE KEY UPDATE cooldown_until = ?");
    $stmt->bind_param('iss', $user_id, $cooldown_until, $cooldown_until);
    $stmt->execute();
    rpg_log("PVE cooldown set for user_id={$user_id} until {$cooldown_until}");
}

function check_pve_cooldown($conn, $user_id) {
    $stmt = $conn->prepare("SELECT cooldown_until FROM rpg_pve_cooldown WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cooldown_until = new DateTime($row['cooldown_until']);
        $now = new DateTime();
        if ($now < $cooldown_until) {
            $diff = $now->diff($cooldown_until);
            $seconds = $diff->s + $diff->i * 60 + $diff->h * 3600 + $diff->days * 86400;
            return ['on_cooldown' => true, 'remaining_seconds' => $seconds];
        }
    }
    return ['on_cooldown' => false];
}