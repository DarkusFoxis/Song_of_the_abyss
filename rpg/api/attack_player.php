<?php
// api/attack_player.php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

$input = json_decode(file_get_contents('php://input'), true);
$target_id = intval($input['target_id'] ?? 0);
$attack_type = $input['attack_type'] ?? 'physical'; // 'physical' или 'magical'
$action = $input['action'] ?? 'attack'; // 'attack' или 'flee'

if ($target_id <= 0) {
    json_error('Не указана цель.');
}

if ($target_id === $user['id']) {
    json_error('Нельзя атаковать себя.');
}

try {
    $conn->begin_transaction();

    $attacker_rpg = get_or_create_rpg_profile($conn, $user['id']);
    
    // Получаем данные цели
    $stmt = $conn->prepare("SELECT ru.*, u.username FROM rpg_user ru JOIN users u ON ru.id_user = u.id WHERE ru.id_user = ? FOR UPDATE");
    $stmt->bind_param('i', $target_id);
    $stmt->execute();
    $target_result = $stmt->get_result();

    if ($target_result->num_rows === 0) {
        $conn->rollback();
        json_error('Цель не найдена.');
    }

    $target_rpg = $target_result->fetch_assoc();

    // Проверяем кулдаун
    $cd = check_cooldown($conn, $user['id'], $target_id);
    if ($cd['on_cooldown']) {
        $conn->rollback();
        json_error("Кулдаун! Осталось: {$cd['remaining']}");
    }

    // Обработка бегства
    if ($action === 'flee') {
        set_cooldown($conn, $user['id'], $target_id, 10);
        $conn->commit();
        rpg_log("User {$user['id']} fled from {$target_id}");
        json_response([
            'success' => true,
            'message' => 'Вы сбежали из боя.',
            'fled' => true,
            'cooldown_minutes' => 10
        ]);
    }

    // Обрабатываем эффекты атакующего перед атакой
    $effect_state = process_effects($conn, $user['id'], 'pvp');
    $effect_results = $effect_state['results'];

    // Перечитываем данные атакующего (мог получить урон от эффектов)
    $stmt = $conn->prepare("SELECT * FROM rpg_user WHERE id_user = ? FOR UPDATE");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $attacker_rpg = $stmt->get_result()->fetch_assoc();

    // Проверяем, жив ли атакующий
    if ($attacker_rpg['hp'] <= 0) {
        $conn->commit();
        rpg_log("Attacker {$user['id']} died from effects before attacking");
        json_response([
            'success' => true,
            'message' => 'Вы погибли от эффектов перед атакой!',
            'attacker_dead' => true,
            'effect_results' => $effect_results
        ]);
    }

    $battle_log = [];
    $damage_dealt = 0;
    $attack_successful = true;
    $effect_applied = null;
    $attacker_dead = false;

    if ($attack_type === 'physical') {
        // Физическая атака
        $raw_damage = $attacker_rpg['phys_attack'];
        $defense = $target_rpg['phys_defense'];

        if ($raw_damage <= $defense) {
            $damage_dealt = 0;
            $battle_log[] = "Физическая атака ({$raw_damage}) заблокирована защитой ({$defense}).";
            $attack_successful = false;

            // Уменьшаем защиту цели на значение атаки
            $new_defense = max(0, $defense - $raw_damage);
            $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = ? WHERE id_user = ?");
            $stmt->bind_param('ii', $new_defense, $target_id);
            $stmt->execute();
            $battle_log[] = "Защита цели снижена: {$defense} → {$new_defense}";
        } else {
            $damage_dealt = $raw_damage - $defense;
            // Уменьшаем защиту до 0
            $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = 0 WHERE id_user = ?");
            $stmt->bind_param('i', $target_id);
            $stmt->execute();
            $battle_log[] = "Физический урон: {$raw_damage} - {$defense} (защита) = {$damage_dealt}";
        }
    } else {
        // Магическая атака
        $raw_mag_damage = $attacker_rpg['mag_attack'];
        $element_mod = get_element_modifier($attacker_rpg['element'], $target_rpg['element']);
        $effective_damage = floor($raw_mag_damage * $element_mod);

        $mod_text = '';
        if ($element_mod > 1) $mod_text = ' (стихия сильнее +15%)';
        elseif ($element_mod < 1) $mod_text = ' (стихия слабее -15%)';

        $battle_log[] = "Маг. атака: {$raw_mag_damage}{$mod_text} → {$effective_damage}";

        if (!empty($effect_state['explosive_wave_effect_ids'])) {
            $wave_damage = $effective_damage > 0 ? max(1, (int)floor($effective_damage * 0.5)) : 0;
            $new_attacker_hp = max(0, (int)$attacker_rpg['hp'] - $wave_damage);

            $stmt = $conn->prepare("UPDATE rpg_user SET hp = ? WHERE id_user = ?");
            $stmt->bind_param('ii', $new_attacker_hp, $user['id']);
            $stmt->execute();
            $attacker_rpg['hp'] = $new_attacker_hp;
            $attacker_dead = $new_attacker_hp <= 0;

            consume_effect_action($conn, (int)$effect_state['explosive_wave_effect_ids'][0]);
            $battle_log[] = "Взрывная волна: вы получили {$wave_damage} урона от собственной магии.";
        }

        // Проверяем маг. поглощение
        $absorb_chance = $target_rpg['mag_absorb'];
        $absorb_roll = mt_rand(1, 100);

        if ($absorb_roll <= $absorb_chance) {
            $damage_dealt = 0;
            $battle_log[] = "Магический урон полностью поглощён! (Шанс: {$absorb_chance}%, выпало: {$absorb_roll})";
            $attack_successful = false;
        } else {
            $damage_dealt = $effective_damage;
            $battle_log[] = "Маг. поглощение не сработало (Шанс: {$absorb_chance}%, выпало: {$absorb_roll})";

            // 30% шанс уменьшить защиту
            if (mt_rand(1, 100) <= 30) {
                $def_reduction = $effective_damage;
                $target_defense = $target_rpg['phys_defense'];

                if ($effective_damage > $target_defense) {
                    $bonus_damage = $effective_damage - $target_defense;
                    $damage_dealt += $bonus_damage;
                    $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = 0 WHERE id_user = ?");
                    $stmt->bind_param('i', $target_id);
                    $stmt->execute();
                    $battle_log[] = "Защита цели разрушена! Доп. урон: +{$bonus_damage}";
                } else {
                    $new_defense = $target_defense - $def_reduction;
                    $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = ? WHERE id_user = ?");
                    $stmt->bind_param('ii', $new_defense, $target_id);
                    $stmt->execute();
                    $battle_log[] = "Защита цели снижена на {$def_reduction}: {$target_defense} → {$new_defense}";
                }
            }

        }

        // Попытка наложить эффект при магической атаке (шанс 10%)
        $effect_applied = try_apply_effect(
            $conn,
            $user['id'],
            $attacker_rpg['level'],
            $attacker_rpg['element'],
            $target_id,
            $target_rpg['element'],
            'pvp'
        );
        if ($effect_applied) {
            $battle_log[] = "Наложен эффект: {$effect_applied['name']}!";
        }
    }

    // Наносим урон цели
    $target_killed = false;
    $loot = null;

    if ($damage_dealt > 0) {
        $new_hp = max(0, $target_rpg['hp'] - $damage_dealt);
        $stmt = $conn->prepare("UPDATE rpg_user SET hp = ? WHERE id_user = ?");
        $stmt->bind_param('ii', $new_hp, $target_id);
        $stmt->execute();
        $battle_log[] = "Нанесено {$damage_dealt} урона. ХП цели: {$target_rpg['hp']} → {$new_hp}";

        // Проверяем убийство
        if ($new_hp <= 0) {
            $target_killed = true;
            $battle_log[] = "Вы убили {$target_rpg['username']}!";

            // Забираем 10% ресурсов
            $stmt = $conn->prepare("SELECT coins, sakura FROM invent WHERE id_user = ?");
            $stmt->bind_param('i', $target_id);
            $stmt->execute();
            $target_invent = $stmt->get_result()->fetch_assoc();

            $coins_stolen = floor(($target_invent['coins'] ?? 0) * 0.1);
            $petals_stolen = floor(($target_invent['sakura'] ?? 0) * 0.1);

            // Забираем у жертвы
            $stmt = $conn->prepare("UPDATE invent SET coins = coins - ?, sakura = sakura - ? WHERE id_user = ?");
            $stmt->bind_param('iii', $coins_stolen, $petals_stolen, $target_id);
            $stmt->execute();

            // Даём атакующему
            $stmt = $conn->prepare("UPDATE invent SET coins = coins + ?, sakura = sakura + ? WHERE id_user = ?");
            $stmt->bind_param('iii', $coins_stolen, $petals_stolen, $user['id']);
            $stmt->execute();

            // Восстановление жертвы после смерти
            $death_state = calculate_death_recovery_state($target_rpg);
            $respawn_hp = (int)$death_state['respawn_hp'];
            $respawn_defense = (int)$death_state['respawn_defense'];
            $new_hp_max = (int)$death_state['hp_max_after'];
            $new_level = (int)$death_state['level_after'];
            $new_phys_attack = (int)$death_state['phys_attack_after'];
            $new_mag_attack = (int)$death_state['mag_attack_after'];
            $new_defense_max = (int)$death_state['phys_defense_max_after'];
            $new_mag_absorb = (int)$death_state['mag_absorb_after'];
            $new_xp = (int)$death_state['xp_after'];
            $new_xp_max = (int)$death_state['xp_max_after'];

            $stmt = $conn->prepare("UPDATE rpg_user SET hp = ?, hp_max = ?, xp = ?, xp_max = ?, level = ?, phys_attack = ?, mag_attack = ?, phys_defense = ?, phys_defense_max = ?, mag_absorb = ?, deaths = ? WHERE id_user = ?");
            $stmt->bind_param('iiiiiiiiiiii', $respawn_hp, $new_hp_max, $new_xp, $new_xp_max, $new_level, $new_phys_attack, $new_mag_attack, $respawn_defense, $new_defense_max, $new_mag_absorb, $death_state['deaths_after'], $target_id);
            $stmt->execute();

            // Удаляем эффекты с жертвы
            $stmt = $conn->prepare("DELETE FROM rpg_effects WHERE id_user = ?");
            $stmt->bind_param('i', $target_id);
            $stmt->execute();

            // Обновляем счётчики
            $stmt = $conn->prepare("UPDATE rpg_user SET kills = kills + 1 WHERE id_user = ?");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();

            if (!empty($death_state['penalty_applied'])) {
                $battle_log[] = "Жертва получила штраф: -1 уровень и -1 улучшение всех характеристик.";
            }

            // Опыт за убийство
            $xp_gained = 100 + ($target_rpg['level'] * 50);
            $stmt = $conn->prepare("UPDATE rpg_user SET xp = xp + ? WHERE id_user = ?");
            $stmt->bind_param('ii', $xp_gained, $user['id']);
            $stmt->execute();

            // Лог смерти
            $stmt = $conn->prepare("INSERT INTO rpg_death_log (victim_id, killer_id, coins_lost, petals_lost) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiii', $target_id, $user['id'], $coins_stolen, $petals_stolen);
            $stmt->execute();

            $loot = [
                'coins' => $coins_stolen,
                'petals' => $petals_stolen,
                'xp' => $xp_gained
            ];

            $battle_log[] = "Получено: {$coins_stolen} монет, {$petals_stolen} лепестков, {$xp_gained} опыта.";

            rpg_log("User {$user['id']} killed user {$target_id}. Loot: {$coins_stolen}c, {$petals_stolen}p, {$xp_gained}xp");
        }
    }

    // Устанавливаем кулдаун
    $cd_minutes = $attack_successful ? 10 : 15;
    set_cooldown($conn, $user['id'], $target_id, $cd_minutes);

    $conn->commit();

    rpg_log("PVP attack: user={$user['id']} -> target={$target_id}, type={$attack_type}, damage={$damage_dealt}, killed=" . ($target_killed ? 'yes' : 'no'));

    json_response([
        'success' => true,
        'battle_log' => $battle_log,
        'damage_dealt' => $damage_dealt,
        'target_killed' => $target_killed,
        'loot' => $loot,
        'effect_applied' => $effect_applied,
        'effect_results' => $effect_results,
        'cooldown_minutes' => $cd_minutes,
        'attack_successful' => $attack_successful,
        'attacker_dead' => $attacker_dead
    ]);
} catch (Exception $e) {
    $conn->rollback();
    rpg_log("attack_player error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка при атаке.', 500);
}
