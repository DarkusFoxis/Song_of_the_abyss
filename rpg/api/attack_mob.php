<?php
require_once __DIR__ . '/init.php';

function sanitize_runtime_mob_effects($raw_effects) {
    if (!is_array($raw_effects)) {
        return [];
    }

    $definitions = get_effect_definitions();
    $result = [];

    foreach ($raw_effects as $eff) {
        if (!is_array($eff)) {
            continue;
        }

        $name = trim((string)($eff['name'] ?? ''));
        if ($name === '' || !isset($definitions[$name])) {
            continue;
        }

        $remaining = max(1, (int)($eff['remaining_actions'] ?? 1));
        $damage = max(0, (int)($eff['damage_per_action'] ?? 0));
        $caster_level = max(0, (int)($eff['caster_level'] ?? 0));
        $source_element = trim((string)($eff['element'] ?? ($definitions[$name]['element'] ?? '')));
        if ($source_element === '') {
            $source_element = $definitions[$name]['element'] ?? '';
        }

        $result[] = [
            'name' => $name,
            'element' => $source_element,
            'remaining_actions' => $remaining,
            'damage_per_action' => $damage,
            'caster_level' => $caster_level
        ];
    }

    return $result;
}

function consume_runtime_mob_effect_action(array &$effects, $index) {
    if (!isset($effects[$index])) {
        return;
    }

    $remaining = (int)$effects[$index]['remaining_actions'];
    if ($remaining <= 1) {
        array_splice($effects, $index, 1);
    } else {
        $effects[$index]['remaining_actions'] = $remaining - 1;
    }
}

function clamp_mob_ai($value) {
    return max(0, min(100, (int)$value));
}

function choose_best_mob_attack_type($mob_phys_attack, $mob_mag_attack, $mob_element, array $player_state) {
    if ($mob_mag_attack <= 0) {
        return 'physical';
    }

    $player_defense = max(0, (int)($player_state['phys_defense'] ?? 0));
    $player_mag_absorb = max(0, min(100, (int)($player_state['mag_absorb'] ?? 0)));
    $player_element = (string)($player_state['element'] ?? '');

    $expected_physical = max(0, (int)$mob_phys_attack - $player_defense);
    $element_mod = in_array($player_element, ELEMENTS, true) ? get_element_modifier($mob_element, $player_element) : 1.0;
    $expected_magical = (int)floor((int)$mob_mag_attack * $element_mod * (1 - ($player_mag_absorb / 100)));

    if ($expected_magical === $expected_physical) {
        return ((int)$mob_mag_attack >= (int)$mob_phys_attack) ? 'magical' : 'physical';
    }

    return ($expected_magical > $expected_physical) ? 'magical' : 'physical';
}

function choose_legacy_mob_action($mob_mag_attack) {
    if (mt_rand(1, 100) <= 20) {
        return ['action' => 'block', 'attack_type' => 'physical'];
    }

    $attack_type = (mt_rand(0, 1) === 1 && (int)$mob_mag_attack > 0) ? 'magical' : 'physical';
    return ['action' => 'attack', 'attack_type' => $attack_type];
}

function choose_advanced_mob_action($mob_ai, $requested_attack_type, array $mob_state, array $player_state) {
    $best_attack_type = choose_best_mob_attack_type(
        (int)($mob_state['phys_attack'] ?? 0),
        (int)($mob_state['mag_attack'] ?? 0),
        (string)($mob_state['element'] ?? ''),
        $player_state
    );

    $prediction_success = false;
    $predicted_action = null;

    if ((int)$mob_ai >= 100) {
        $prediction_success = true;
        $predicted_action = $requested_attack_type;
    } elseif ((int)$mob_ai >= 70 && mt_rand(1, 100) <= 35) {
        $prediction_success = true;
        $predicted_action = $requested_attack_type;
    }

    $mob_hp = max(0, (int)($mob_state['hp'] ?? 0));
    $mob_hp_max = max(1, (int)($mob_state['hp_max'] ?? 1));
    $player_hp = max(0, (int)($player_state['hp'] ?? 0));
    $player_hp_max = max(1, (int)($player_state['hp_max'] ?? max(1, $player_hp)));
    $mob_hp_ratio = $mob_hp / $mob_hp_max;
    $player_hp_ratio = $player_hp / $player_hp_max;

    $mob_phys = max(0, (int)($mob_state['phys_attack'] ?? 0));
    $mob_mag = max(0, (int)($mob_state['mag_attack'] ?? 0));
    $player_def = max(0, (int)($player_state['phys_defense'] ?? 0));
    $player_absorb = max(0, min(100, (int)($player_state['mag_absorb'] ?? 0)));

    $expected_physical = max(0, $mob_phys - $player_def);
    $expected_magical = max(0, (int)floor($mob_mag * (1 - ($player_absorb / 100))));
    $expected_best_damage = ($best_attack_type === 'magical') ? $expected_magical : $expected_physical;

    if ($prediction_success && ($predicted_action === 'physical' || $predicted_action === 'magical')) {
        $counter_risk = max(0, (int)($player_state['phys_attack'] ?? 0))
            + max(0, (int)($player_state['mag_attack'] ?? 0));
        $should_block = $counter_risk >= max(1, (int)floor($mob_hp * 0.25))
            || $mob_hp_ratio <= 0.45
            || mt_rand(1, 100) <= 70;

        return [
            'action' => $should_block ? 'block' : 'attack',
            'attack_type' => $best_attack_type,
            'prediction_success' => true,
            'predicted_action' => $predicted_action
        ];
    }

    $block_score = 0;
    if ($mob_hp_ratio <= 0.35) {
        $block_score += 35;
    }
    if ($player_hp_ratio >= 0.75) {
        $block_score += 10;
    }
    if ($requested_attack_type === 'physical' || $requested_attack_type === 'magical') {
        $block_score += 20;
    }

    $attack_score = 35 + (int)floor((int)$mob_ai * 0.35);
    if ($player_hp_ratio <= 0.35) {
        $attack_score += 25;
    }
    if ($expected_best_damage >= max(1, (int)floor($player_hp * 0.35))) {
        $attack_score += 20;
    }
    if ($requested_attack_type === 'block') {
        $attack_score += 10;
    }
    if ($requested_attack_type === 'flee' || $requested_attack_type === 'skip') {
        $attack_score += 20;
    }

    if ($block_score > $attack_score && mt_rand(1, 100) <= min(90, 40 + $block_score - $attack_score)) {
        return [
            'action' => 'block',
            'attack_type' => $best_attack_type,
            'prediction_success' => $prediction_success,
            'predicted_action' => $predicted_action
        ];
    }

    return [
        'action' => 'attack',
        'attack_type' => $best_attack_type,
        'prediction_success' => $prediction_success,
        'predicted_action' => $predicted_action
    ];
}

function resolve_mob_first_strike($mob_ai, array $mob_state, array $player_state) {
    $mob_power = max(0, (int)($mob_state['hp'] ?? 0))
        + max(0, (int)($mob_state['phys_attack'] ?? 0)) * 3
        + max(0, (int)($mob_state['mag_attack'] ?? 0)) * 3
        + max(0, (int)($mob_state['phys_defense'] ?? 0)) * 2
        + max(0, (int)($mob_state['mag_absorb'] ?? 0)) * 8;

    $player_power = max(1, max(0, (int)($player_state['hp'] ?? 0))
        + max(0, (int)($player_state['phys_attack'] ?? 0)) * 3
        + max(0, (int)($player_state['mag_attack'] ?? 0)) * 3
        + max(0, (int)($player_state['phys_defense'] ?? 0)) * 2
        + max(0, (int)($player_state['mag_absorb'] ?? 0)) * 8);

    $stats_advantage = $mob_power >= ($player_power * 1.3);
    $stats_double = $mob_power >= ($player_power * 2);

    if ((int)$mob_ai > 75 || $stats_double) {
        return ['attack_first' => true, 'guaranteed' => true, 'chance' => 100];
    }

    $chance = 0;
    if ((int)$mob_ai > 50) {
        $chance = max($chance, 35);
    }
    if ($stats_advantage) {
        $chance = max($chance, 45);
    }
    if ((int)$mob_ai > 50 && $stats_advantage) {
        $chance = 60;
    }

    if ($chance <= 0) {
        return ['attack_first' => false, 'guaranteed' => false, 'chance' => 0];
    }

    return [
        'attack_first' => mt_rand(1, 100) <= $chance,
        'guaranteed' => false,
        'chance' => $chance
    ];
}

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

$input = json_decode(file_get_contents('php://input'), true);
$attack_type = $input['attack_type'] ?? 'physical';

$mob_hp = (int)($input['mob_hp'] ?? 0);
$mob_hp_max = max(1, (int)($input['mob_hp_max'] ?? $mob_hp));
$mob_phys_attack = (int)($input['mob_phys_attack'] ?? 0);
$mob_phys_defense = (int)($input['mob_phys_defense'] ?? 0);
$mob_mag_attack = (int)($input['mob_mag_attack'] ?? 0);
$mob_mag_absorb = (int)($input['mob_mag_absorb'] ?? 0);
$mob_ai = clamp_mob_ai($input['mob_ai'] ?? 0);
$mob_element = $input['mob_element'] ?? '';
$mob_coins = (int)($input['mob_coins'] ?? 0);
$mob_petals = (int)($input['mob_petals'] ?? 0);
$mob_id = (int)($input['mob_id'] ?? 0);
$mob_magic_effects = normalize_effect_name_list($input['mob_magic_effects'] ?? []);
$mob_system_effects = normalize_effect_name_list($input['mob_system_effects'] ?? []);
$mob_effects = sanitize_runtime_mob_effects($input['mob_effects'] ?? []);
$player_dead = false;

$flee_attempts = (int)($input['flee_attempts'] ?? 0);

if (!in_array($attack_type, ['physical', 'magical', 'block', 'flee'], true)) {
    json_error('Неверный тип атаки.');
}

if ($mob_hp <= 0) {
    json_error('Враг уже мёртв.');
}

if (!in_array($mob_element, ELEMENTS, true)) {
    json_error('Неверная стихия врага.');
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT * FROM rpg_user WHERE id_user = ? FOR UPDATE");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $rpg = $stmt->get_result()->fetch_assoc();

    if (!$rpg) {
        $conn->rollback();
        json_error('RPG профиль не найден.');
    }

    if ((int)$rpg['hp'] <= 0) {
        $conn->rollback();
        json_error('Вы мертвы и не можете атаковать.');
    }

    $effect_state = process_effects($conn, $user['id'], 'pve');
    $effect_results = $effect_state['results'];

    $stmt = $conn->prepare("SELECT * FROM rpg_user WHERE id_user = ? FOR UPDATE");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $rpg = $stmt->get_result()->fetch_assoc();

    if ((int)$rpg['hp'] <= 0) {
        set_pve_cooldown($conn, $user['id'], 30);
        $conn->commit();
        json_response([
            'success' => true,
            'player_dead' => true,
            'message' => 'Вы погибли от эффектов!',
            'effect_results' => $effect_results
        ]);
    }

    $battle_log = [];
    $player_damage = 0;
    $mob_damage = 0;

    $player_phys_attack = max(0, (int)$rpg['phys_attack'] - (int)($effect_state['stat_penalty']['phys_attack'] ?? 0));
    $player_mag_attack = max(0, (int)$rpg['mag_attack'] - (int)($effect_state['stat_penalty']['mag_attack'] ?? 0));
    $player_mag_absorb = max(0, (int)$rpg['mag_absorb'] - (int)($effect_state['stat_penalty']['mag_absorb'] ?? 0));
    $player_damage_multiplier = max(0.0, (float)($effect_state['damage_multiplier'] ?? 1.0));
    $player_redirect_attack = !empty($effect_state['redirect_attack_to_self']);
    $player_skip_turn = !empty($effect_state['skip_turn']);

    $apply_player_damage = function ($damage, $source_text = 'Враг') use ($conn, $user, &$rpg, &$battle_log, &$player_dead) {
        $damage = (int)$damage;
        if ($damage <= 0 || $player_dead) {
            return;
        }

        $new_player_hp = max(0, (int)$rpg['hp'] - $damage);
        $stmt = $conn->prepare("UPDATE rpg_user SET hp = ? WHERE id_user = ?");
        $stmt->bind_param('ii', $new_player_hp, $user['id']);
        $stmt->execute();
        $rpg['hp'] = $new_player_hp;

        if ($new_player_hp <= 0) {
            $less_xp = (int)floor(mt_rand(10, 375) * (int)$rpg['level']);
            $less_coin = (int)floor(mt_rand(50, 500) * (int)$rpg['level']);
            $battle_log[] = "Вы погибли от {$source_text}! Потеряно: {$less_xp} опыта, {$less_coin} монет.";
            set_pve_cooldown($conn, $user['id'], 30);
            $player_dead = true;

            $death_state = calculate_death_recovery_state($rpg);
            $xp_after_loss = max(0, (int)$death_state['xp_after'] - $less_xp);
            $xp_max_after = (int)$death_state['xp_max_after'];
            $new_level = (int)$death_state['level_after'];
            $new_hp_max = (int)$death_state['hp_max_after'];
            $new_phys_attack = (int)$death_state['phys_attack_after'];
            $new_mag_attack = (int)$death_state['mag_attack_after'];
            $new_defense_max = (int)$death_state['phys_defense_max_after'];
            $new_mag_absorb = (int)$death_state['mag_absorb_after'];
            $respawn_hp = (int)$death_state['respawn_hp'];
            $respawn_defense = (int)$death_state['respawn_defense'];

            $stmt = $conn->prepare("UPDATE rpg_user SET hp = ?, hp_max = ?, xp = ?, xp_max = ?, level = ?, phys_attack = ?, mag_attack = ?, phys_defense = ?, phys_defense_max = ?, mag_absorb = ?, deaths = ? WHERE id_user = ?");
            $stmt->bind_param('iiiiiiiiiiii', $respawn_hp, $new_hp_max, $xp_after_loss, $xp_max_after, $new_level, $new_phys_attack, $new_mag_attack, $respawn_defense, $new_defense_max, $new_mag_absorb, $death_state['deaths_after'], $user['id']);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE invent SET coins = coins - ? WHERE id_user = ?");
            $stmt->bind_param('ii', $less_coin, $user['id']);
            $stmt->execute();

            $rpg['hp'] = $respawn_hp;
            $rpg['hp_max'] = $new_hp_max;
            $rpg['xp'] = $xp_after_loss;
            $rpg['xp_max'] = $xp_max_after;
            $rpg['level'] = $new_level;
            $rpg['phys_attack'] = $new_phys_attack;
            $rpg['mag_attack'] = $new_mag_attack;
            $rpg['phys_defense'] = $respawn_defense;
            $rpg['phys_defense_max'] = $new_defense_max;
            $rpg['mag_absorb'] = $new_mag_absorb;
            $rpg['deaths'] = (int)$death_state['deaths_after'];

            if (!empty($death_state['penalty_applied'])) {
                $battle_log[] = "Штраф за смерти: уровень {$death_state['level_before']} → {$death_state['level_after']}, характеристики снижены на 1 улучшение.";
            }
        }
    };

    $requested_attack_type = $attack_type;
    if ($player_skip_turn) {
        $attack_type = 'skip';
        $battle_log[] = 'Заморозка лишила вас хода.';
    }

    $flee_failed = false;
    $flee_attempts_remaining = $flee_attempts;
    if ($attack_type === 'flee') {
        if ($flee_attempts <= 0) {
            $conn->rollback();
            json_error('Не осталось попыток побега.');
        }

        $hp_ratio = $rpg['hp'] / max(1, $mob_hp);
        $phys_ratio = $rpg['phys_attack'] / max(1, $mob_phys_attack);
        $mag_ratio = $rpg['mag_attack'] / max(1, $mob_mag_attack);
        $defense_ratio = $rpg['phys_defense'] / max(1, $mob_phys_defense);
        $avg_ratio = ($hp_ratio + $phys_ratio + $mag_ratio + $defense_ratio) / 4;
        $chance = 0.5 + 0.4 * ($avg_ratio - 1);
        $chance = max(0.05, min(0.9, $chance));

        $roll = mt_rand() / mt_getrandmax();
        if ($roll <= $chance) {
            $loss_coins = max(1, (int)floor((int)$rpg['level'] * 20 + mt_rand(25, 255)));
            $loss_petals = max(1, (int)floor((int)$rpg['level'] * 2 + mt_rand(15, 199)));

            $stmt = $conn->prepare("UPDATE invent SET coins = GREATEST(0, coins - ?), sakura = GREATEST(0, sakura - ?) WHERE id_user = ?");
            $stmt->bind_param('iii', $loss_coins, $loss_petals, $user['id']);
            $stmt->execute();

            $battle_log[] = "Вы успешно сбежали! Потеряно: {$loss_coins}💰, {$loss_petals}🌸";
            rpg_log("PVE flee success: user_id={$user['id']}, loss_coins={$loss_coins}, loss_petals={$loss_petals}");

            $conn->commit();
            json_response([
                'success' => true,
                'fled' => true,
                'loss' => ['coins' => $loss_coins, 'petals' => $loss_petals],
                'battle_log' => $battle_log,
                'effect_results' => $effect_results
            ]);
        }

        $flee_failed = true;
        $flee_attempts_remaining = $flee_attempts - 1;
        $attack_type = 'skip';
        $battle_log[] = "Побег не удался! Осталось попыток: {$flee_attempts_remaining}";
    }

    $mob_state_for_ai = [
        'hp' => $mob_hp,
        'hp_max' => $mob_hp_max,
        'phys_attack' => $mob_phys_attack,
        'phys_defense' => $mob_phys_defense,
        'mag_attack' => $mob_mag_attack,
        'mag_absorb' => $mob_mag_absorb,
        'element' => $mob_element
    ];
    $player_state_for_ai = [
        'hp' => (int)$rpg['hp'],
        'hp_max' => (int)$rpg['hp_max'],
        'phys_attack' => $player_phys_attack,
        'mag_attack' => $player_mag_attack,
        'phys_defense' => (int)$rpg['phys_defense'],
        'mag_absorb' => $player_mag_absorb,
        'element' => (string)$rpg['element']
    ];

    $mob_uses_advanced_ai = mt_rand(1, 100) <= $mob_ai;
    $mob_decision = $mob_uses_advanced_ai
        ? choose_advanced_mob_action($mob_ai, $requested_attack_type, $mob_state_for_ai, $player_state_for_ai)
        : choose_legacy_mob_action($mob_mag_attack);

    $mob_action = $mob_decision['action'] ?? 'attack';
    $mob_attack_type = $mob_decision['attack_type'] ?? 'physical';
    $mob_prediction_success = !empty($mob_decision['prediction_success']);
    $mob_predicted_action = $mob_decision['predicted_action'] ?? null;
    $mob_prediction_damage_multiplier = 1.0;
    if ($mob_prediction_success && is_string($mob_predicted_action) && $mob_predicted_action !== '' && $mob_predicted_action === $requested_attack_type) {
        $mob_prediction_damage_multiplier = 1.1;
    }

    if ($flee_failed) {
        $mob_action = 'attack';
        $mob_attack_type = choose_best_mob_attack_type($mob_phys_attack, $mob_mag_attack, $mob_element, $player_state_for_ai);
    }

    $first_strike = resolve_mob_first_strike($mob_ai, $mob_state_for_ai, $player_state_for_ai);
    if (!$flee_failed && !empty($first_strike['attack_first'])) {
        $mob_action = 'attack';
        $mob_attack_type = choose_best_mob_attack_type($mob_phys_attack, $mob_mag_attack, $mob_element, $player_state_for_ai);
        $mob_attacks_first = true;
    } else {
        $mob_attacks_first = false;
    }

    $mob_has_scatter = in_array('Рассеянность', $mob_system_effects, true);
    $mob_has_melting = in_array('Таяние', $mob_system_effects, true);

    $base_mob_effect_state = [
        'skip_turn' => false,
        'damage_multiplier' => 1.0,
        'redirect_attack_to_self' => false,
        'explosive_wave_index' => null
    ];

    $execute_mob_attack = function (array $active_mob_effect_state, $allow_player_block) use (
        $conn,
        $user,
        $mob_id,
        $mob_phys_attack,
        $mob_mag_attack,
        $mob_mag_absorb,
        $mob_element,
        $mob_attack_type,
        $mob_magic_effects,
        $mob_has_scatter,
        $mob_has_melting,
        $requested_attack_type,
        $player_skip_turn,
        $player_mag_absorb,
        $mob_prediction_damage_multiplier,
        &$mob_hp,
        $mob_hp_max,
        &$mob_damage,
        &$mob_effects,
        &$rpg,
        &$battle_log,
        &$player_dead,
        $apply_player_damage
    ) {
        if ($mob_hp <= 0 || $player_dead) {
            return;
        }

        if ($mob_has_scatter && mt_rand(1, 100) <= 35) {
            $scatter_damage = max(1, (int)floor(($mob_phys_attack + $mob_mag_attack) / 4));
            $mob_hp = max(0, $mob_hp - $scatter_damage);
            $battle_log[] = "Рассеянность: враг нанёс себе {$scatter_damage} урона.";
        }

        if ($mob_hp > 0 && $mob_attack_type === 'magical' && $mob_has_melting) {
            $min_hp = max(1, (int)floor($mob_hp_max * 0.1));
            if ($mob_hp > $min_hp) {
                $melt_damage = max(1, (int)floor($mob_hp_max * 0.01));
                $new_mob_hp = max($min_hp, $mob_hp - $melt_damage);
                $actual_melt = $mob_hp - $new_mob_hp;
                if ($actual_melt > 0) {
                    $mob_hp = $new_mob_hp;
                    $battle_log[] = "Таяние: враг теряет {$actual_melt} HP за использование магии.";
                }
            }
        }

        if ($mob_hp <= 0) {
            return;
        }

        if ($mob_attack_type === 'magical') {
            $block_active = $allow_player_block && $requested_attack_type === 'block' && !$player_skip_turn;
            $mob_raw = (int)floor($mob_mag_attack * $mob_prediction_damage_multiplier);
            $mob_mod = get_element_modifier($mob_element, $rpg['element']);
            $mob_effective = (int)floor($mob_raw * $mob_mod);

            if ($active_mob_effect_state['explosive_wave_index'] !== null) {
                $mob_wave_damage = $mob_effective > 0 ? max(1, (int)floor($mob_effective * 0.5)) : 0;
                if ($mob_wave_damage > 0) {
                    $mob_hp = max(0, $mob_hp - $mob_wave_damage);
                    $battle_log[] = "Взрывная волна: враг получил {$mob_wave_damage} урона от собственной магии.";
                }
                consume_runtime_mob_effect_action($mob_effects, (int)$active_mob_effect_state['explosive_wave_index']);
            }

            $mob_effective = (int)floor($mob_effective * $active_mob_effect_state['damage_multiplier']);

            if ($mob_hp <= 0) {
                return;
            }

            if ($active_mob_effect_state['redirect_attack_to_self']) {
                $mob_hp = max(0, $mob_hp - $mob_effective);
                $battle_log[] = "Обманчивый мир: магическая атака врага попала по нему самому ({$mob_effective}).";
                return;
            }

            if ($block_active) {
                $block_absorb = mt_rand(20, 50);
                $mob_effective = max(0, (int)floor($mob_effective * ((100 - $block_absorb) / 100)));
                $battle_log[] = "Блок поглотил {$block_absorb}% магического урона.";

                $absorb_percent = max(0, min(100, (int)$player_mag_absorb));
                $absorbed = (int)floor($mob_effective * ($absorb_percent / 100));
                $mob_effective = max(0, $mob_effective - $absorbed);
                if ($absorbed > 0) {
                    $battle_log[] = "Маг. поглощение снизило магический урон на {$absorbed}.";
                }

                if ($mob_effective > 0 && mt_rand(1, 100) <= 35) {
                    $reflect_damage = max(1, (int)floor($mob_effective * 0.75));
                    $mob_hp = max(0, $mob_hp - $reflect_damage);
                    $mob_damage = 0;
                    $battle_log[] = "Контрблок! Вы отразили {$reflect_damage} урона обратно во врага.";
                } elseif ($mob_effective > 0) {
                    $mob_damage = $mob_effective;
                    $battle_log[] = "Враг нанёс {$mob_effective} маг. урона вам.";
                    $apply_player_damage($mob_damage, 'Врага');
                } else {
                    $mob_damage = 0;
                    $battle_log[] = "Магический урон полностью поглощён.";
                }
            } elseif (mt_rand(1, 100) <= $player_mag_absorb) {
                $mob_damage = 0;
                $battle_log[] = "Вы поглотили маг. атаку врага ({$mob_effective})!";
            } else {
                $mob_damage = $mob_effective;
                $battle_log[] = "Враг нанёс {$mob_effective} маг. урона вам.";
                $apply_player_damage($mob_damage, 'Врага');
            }
            if (!$player_dead) {
                $mob_effect_level = max(1, (int)round(($mob_phys_attack + $mob_mag_attack + $mob_mag_absorb) / 20));
                $mob_effect_applied = try_apply_effect(
                    $conn,
                    -max(1, $mob_id),
                    $mob_effect_level,
                    $mob_element,
                    $user['id'],
                    $rpg['element'],
                    'pve',
                    $mob_magic_effects
                );
                if ($mob_effect_applied) {
                    $battle_log[] = "Враг наложил эффект: {$mob_effect_applied['name']}.";
                }
            }
            return;
        }

        $mob_raw = (int)floor($mob_phys_attack * $active_mob_effect_state['damage_multiplier'] * $mob_prediction_damage_multiplier);
        $player_def = (int)$rpg['phys_defense'];
        $raw_after_block = $mob_raw;
        $block_active = $allow_player_block && $requested_attack_type === 'block' && !$player_skip_turn;

        if ($block_active) {
            $block_absorb = mt_rand(20, 50);
            $raw_after_block = max(0, (int)floor($mob_raw * ((100 - $block_absorb) / 100)));
            $battle_log[] = "Блок поглотил {$block_absorb}% физического урона.";
        }

        if ($active_mob_effect_state['redirect_attack_to_self']) {
            $mob_hp = max(0, $mob_hp - $raw_after_block);
            $battle_log[] = "Обманчивый мир: физическая атака врага попала по нему самому ({$raw_after_block}).";
            return;
        }

        if ($raw_after_block <= $player_def) {
            $mob_damage = 0;
            $new_player_def = max(0, $player_def - $raw_after_block);
            $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = ? WHERE id_user = ?");
            $stmt->bind_param('ii', $new_player_def, $user['id']);
            $stmt->execute();
            $rpg['phys_defense'] = $new_player_def;
            $battle_log[] = "Физ. атака врага ({$raw_after_block}) поглощена щитом. Щит: {$player_def} -> {$new_player_def}";
            return;
        }

        $mob_damage = $raw_after_block - $player_def;
        $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = 0 WHERE id_user = ?");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $rpg['phys_defense'] = 0;

        if ($block_active && $mob_damage > 0 && mt_rand(1, 100) <= 35) {
            $reflect_damage = max(1, (int)floor($mob_damage * 0.75));
            $mob_hp = max(0, $mob_hp - $reflect_damage);
            $mob_damage = 0;
            $battle_log[] = "Контрблок! Вы отразили {$reflect_damage} физ. урона обратно во врага.";
            return;
        }

        $battle_log[] = "Враг нанёс {$mob_damage} физ. урона вам.";
        $apply_player_damage($mob_damage, 'Врага');
    };

    $mob_turn_consumed = false;

    if ($mob_uses_advanced_ai && $mob_prediction_success && is_string($mob_predicted_action) && $mob_predicted_action !== '') {
        $predicted_action_map = [
            'physical' => 'физическая атака',
            'magical' => 'магическая атака',
            'block' => 'блок',
            'flee' => 'побег',
            'skip' => 'пропуск хода'
        ];
        $predicted_action_text = $predicted_action_map[$mob_predicted_action] ?? $mob_predicted_action;
        $battle_log[] = "🧠 Враг предугадал ваш ход: {$predicted_action_text}.";
        if ($mob_prediction_damage_multiplier > 1.0) {
            $battle_log[] = "Враг получает +10% к урону за точное предугадывание.";
        }
    }

    if ($mob_attacks_first && !$player_dead) {
        if (!empty($first_strike['guaranteed'])) {
            $battle_log[] = "⚡ Враг мгновенно перехватывает инициативу и атакует первым!";
        } else {
            $battle_log[] = "⚡ Враг перехватывает инициативу и атакует первым!";
        }
        $execute_mob_attack($base_mob_effect_state, false);
        $mob_turn_consumed = true;
    }

    if (!$player_dead) {
        if ($attack_type === 'block') {
            $battle_log[] = "Вы встали в защитную стойку (блок).";
        } elseif ($attack_type === 'physical') {
            $raw = (int)floor($player_phys_attack * $player_damage_multiplier);

            if ($player_redirect_attack) {
                $self_raw = $raw;
                $player_def = (int)$rpg['phys_defense'];
                if ($self_raw <= $player_def) {
                    $new_player_def = max(0, $player_def - $self_raw);
                    $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = ? WHERE id_user = ?");
                    $stmt->bind_param('ii', $new_player_def, $user['id']);
                    $stmt->execute();
                    $rpg['phys_defense'] = $new_player_def;
                    $battle_log[] = "Обманчивый мир: вы ударили себя, урон поглощён защитой ({$player_def} → {$new_player_def}).";
                } else {
                    $self_damage = $self_raw - $player_def;
                    $stmt = $conn->prepare("UPDATE rpg_user SET phys_defense = 0 WHERE id_user = ?");
                    $stmt->bind_param('i', $user['id']);
                    $stmt->execute();
                    $rpg['phys_defense'] = 0;
                    $battle_log[] = "Обманчивый мир: вы нанесли себе {$self_damage} физ. урона.";
                    $apply_player_damage($self_damage, 'Обманчивого мира');
                }
            } else {
                if ($mob_action === 'block') {
                    $def = mt_rand(75, 85);
                    $raw = (int)floor($raw * ($def / 100));
                    $battle_log[] = "Враг блокирует, ваш урон уменьшен на " . (100 - $def) . "%.";
                }

                if ($raw <= $mob_phys_defense) {
                    $player_damage = 0;
                    $mob_phys_defense = max(0, $mob_phys_defense - $raw);
                    $battle_log[] = "Ваша физ. атака ({$raw}) заблокирована защитой врага. Защита врага: {$mob_phys_defense}.";
                } else {
                    $player_damage = $raw - $mob_phys_defense;
                    $mob_phys_defense = 0;
                    $battle_log[] = "Вы нанесли {$player_damage} физ. урона врагу.";
                }
            }
        } elseif ($attack_type === 'magical') {
            $raw = (int)floor($player_mag_attack * $player_damage_multiplier);
            if ($mob_action === 'block' && !$player_redirect_attack) {
                $def = mt_rand(75, 85);
                $raw = (int)floor($raw * ($def / 100));
                $battle_log[] = "Враг блокирует, ваш урон уменьшен на " . (100 - $def) . "%.";
            }

            $mod = get_element_modifier($rpg['element'], $mob_element);
            $effective = (int)floor($raw * $mod);
            $mod_text = '';
            if ($mod > 1) $mod_text = ' (стихия +15%)';
            elseif ($mod < 1) $mod_text = ' (стихия -15%)';

            if (!empty($effect_state['explosive_wave_effect_ids'])) {
                $wave_damage = $effective > 0 ? max(1, (int)floor($effective * 0.5)) : 0;
                if ($wave_damage > 0) {
                    $battle_log[] = "Взрывная волна: вы получили {$wave_damage} урона от собственной магии.";
                    $apply_player_damage($wave_damage, 'Взрывной волны');
                }
                consume_effect_action($conn, (int)$effect_state['explosive_wave_effect_ids'][0]);
            }

            if ($player_redirect_attack) {
                $self_effective = (int)floor($raw * get_element_modifier($rpg['element'], $rpg['element']));
                if (mt_rand(1, 100) <= $player_mag_absorb) {
                    $battle_log[] = "Обманчивый мир: вы направили магию в себя, но поглощение спасло вас.";
                } else {
                    $battle_log[] = "Обманчивый мир: вы нанесли себе {$self_effective} маг. урона.";
                    $apply_player_damage($self_effective, 'Обманчивого мира');
                }
            } else {
                if (mt_rand(1, 100) <= $mob_mag_absorb) {
                    $player_damage = 0;
                    $battle_log[] = "Ваша маг. атака ({$effective}{$mod_text}) поглощена врагом!";
                } else {
                    $player_damage = $effective;
                    $battle_log[] = "Вы нанесли {$effective} маг. урона врагу{$mod_text}.";

                    if (mt_rand(1, 100) <= 30) {
                        if ($effective > $mob_phys_defense) {
                            $bonus = $effective - $mob_phys_defense;
                            $player_damage += $bonus;
                            $mob_phys_defense = 0;
                            $battle_log[] = "Защита врага разрушена! Доп. урон: +{$bonus}";
                        } else {
                            $mob_phys_defense -= $effective;
                            $battle_log[] = "Защита врага снижена на {$effective}.";
                        }
                    }
                }

                $rolled_effect = roll_effect_for_target((int)$rpg['level'], $rpg['element'], $mob_element, 'pve');
                if ($rolled_effect) {
                    $mob_effects[] = [
                        'name' => $rolled_effect['name'],
                        'element' => $rolled_effect['element'],
                        'remaining_actions' => (int)$rolled_effect['actions'],
                        'damage_per_action' => (int)$rolled_effect['damage'],
                        'caster_level' => (int)$rpg['level']
                    ];
                    $battle_log[] = "На врага наложен эффект: {$rolled_effect['name']}.";
                }
            }
        }
    }

    $mob_hp = max(0, $mob_hp - $player_damage);
    $mob_killed = $mob_hp <= 0;

    $mob_effect_state = [
        'skip_turn' => false,
        'damage_multiplier' => 1.0,
        'redirect_attack_to_self' => false,
        'explosive_wave_index' => null
    ];

    if (!$mob_killed) {
        $processed_effects = [];
        foreach ($mob_effects as $eff) {
            $name = $eff['name'];
            $remaining = (int)$eff['remaining_actions'];
            $damage = (int)$eff['damage_per_action'];
            $should_decrement = true;

            if ($name === 'Поджог') {
                $mob_hp = max(0, $mob_hp - $damage);
                $battle_log[] = "Поджог наносит врагу {$damage} урона.";
            } elseif ($name === 'Теневой взрыв') {
                $mob_hp = max(0, $mob_hp - $damage);
                $battle_log[] = "Теневой взрыв наносит врагу {$damage} урона.";
            } elseif ($name === 'Заморозка') {
                $mob_effect_state['skip_turn'] = true;
                $battle_log[] = "Заморозка: враг пропускает ход.";
            } elseif ($name === 'Обманчивый мир') {
                if (mt_rand(1, 100) <= 10) {
                    $mob_effect_state['damage_multiplier'] *= 0.5;
                    $battle_log[] = "Обманчивый мир: урон врага снижен на 50%.";
                } else {
                    $mob_effect_state['redirect_attack_to_self'] = true;
                    $battle_log[] = "Обманчивый мир: атака врага будет направлена в него самого.";
                }
            } elseif ($name === 'Взрывная волна') {
                if ($mob_effect_state['explosive_wave_index'] === null) {
                    $mob_effect_state['explosive_wave_index'] = count($processed_effects);
                }
                $should_decrement = false;
                $battle_log[] = "На враге активна Взрывная волна.";
            }

            if ($should_decrement) {
                $remaining--;
            }

            if ($remaining > 0) {
                $eff['remaining_actions'] = $remaining;
                $processed_effects[] = $eff;
            }
        }
        $mob_effects = $processed_effects;
        $mob_killed = $mob_hp <= 0;
    }

    if (!$mob_killed && !$player_dead && !$mob_turn_consumed) {
        if ($mob_effect_state['skip_turn']) {
            $mob_action = 'skip';
        }

        if ($mob_action === 'attack') {
            $execute_mob_attack($mob_effect_state, true);
        } elseif ($mob_action === 'block') {
            $battle_log[] = "Враг блокирует, готовясь к защите.";
        } elseif ($mob_action === 'skip') {
            $battle_log[] = "Враг пропускает ход.";
        }
    }

    $mob_hp = max(0, (int)$mob_hp);
    $mob_killed = $mob_hp <= 0;
    $reward = null;

    if ($mob_killed) {
        $xp_gained = (int)round(mt_rand(325, 1000)) + ((int)$rpg['level'] * 325);

        if ($mob_coins > 0 || $mob_petals > 0) {
            $stmt = $conn->prepare("UPDATE invent SET coins = coins + ?, sakura = sakura + ? WHERE id_user = ?");
            $stmt->bind_param('iii', $mob_coins, $mob_petals, $user['id']);
            $stmt->execute();
        }

        $stmt = $conn->prepare("UPDATE rpg_user SET xp = xp + ?, kills = kills + 1 WHERE id_user = ?");
        $stmt->bind_param('ii', $xp_gained, $user['id']);
        $stmt->execute();

        $reward = [
            'coins' => $mob_coins,
            'petals' => $mob_petals,
            'xp' => $xp_gained
        ];

        $battle_log[] = "Враг повержен! Награда: {$mob_coins} монет, {$mob_petals} лепестков, {$xp_gained} опыта.";
        rpg_log("PVE: User {$user['id']} killed mob_id={$mob_id}. Reward: {$mob_coins}c, {$mob_petals}p, {$xp_gained}xp");
    }

    $stmt = $conn->prepare("SELECT * FROM rpg_user WHERE id_user = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $final_rpg = $stmt->get_result()->fetch_assoc();

    $conn->commit();

    $response = [
        'success' => true,
        'battle_log' => $battle_log,
        'player_damage' => $player_damage,
        'mob_damage' => $mob_damage,
        'new_mob_hp' => $mob_hp,
        'mob_hp_max' => $mob_hp_max,
        'mob_killed' => $mob_killed,
        'mob_phys_defense' => $mob_phys_defense,
        'mob_effects' => array_values($mob_effects),
        'mob_magic_effects' => $mob_magic_effects,
        'mob_system_effects' => $mob_system_effects,
        'mob_ai' => $mob_ai,
        'mob_ai_mode' => $mob_uses_advanced_ai ? 'advanced' : 'legacy',
        'mob_attacked_first' => $mob_turn_consumed,
        'player_hp' => (int)$final_rpg['hp'],
        'player_hp_max' => (int)$final_rpg['hp_max'],
        'player_defense' => (int)$final_rpg['phys_defense'],
        'player_dead' => $player_dead,
        'reward' => $reward,
        'effect_results' => $effect_results,
        'mob_action' => $mob_action
    ];

    if ($flee_failed) {
        $response['flee_failed'] = true;
        $response['flee_attempts_remaining'] = $flee_attempts_remaining;
    }

    json_response($response);
} catch (Exception $e) {
    $conn->rollback();
    rpg_log("attack_mob error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка при атаке врага.', 500);
}
