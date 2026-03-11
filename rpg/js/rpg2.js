(function() {
    'use strict';
    let fleeAttempts = 3;
    const API_BASE = 'api/';

    async function apiCall(endpoint, data = null) {
        const options = {
            method: data ? 'POST' : 'GET',
            headers: { 'Content-Type': 'application/json' }
        };
        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(API_BASE + endpoint, options);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Неизвестная ошибка');
            }

            return result;
        } catch (err) {
            console.error(`API Error [${endpoint}]:`, err);
            throw err;
        }
    }

    function notify(message, type = 'info') {
        const container = document.getElementById('rpg-notifications');
        const notif = document.createElement('div');
        notif.className = `rpg-notification ${type}`;
        notif.textContent = message;
        container.appendChild(notif);

        setTimeout(() => {
            if (notif.parentNode) {
                notif.parentNode.removeChild(notif);
            }
        }, 4000);
    }

    function getElementClass(element) {
        const map = {
            'Огонь': 'element-fire',
            'Трава': 'element-grass',
            'Вода': 'element-water',
            'Лёд': 'element-ice',
            'Тьма': 'element-dark',
            'Свет': 'element-light',
            'Контроль разума': 'element-mind',
            'Бездна': 'element-abyss'
        };
        return map[element] || '';
    }

    function getElementEmoji(element) {
        const map = {
            'Огонь': '🔥',
            'Трава': '🌿',
            'Вода': '💧',
            'Лёд': '🧊',
            'Тьма': '🌑',
            'Свет': '☀️',
            'Контроль разума': '🧠',
            'Бездна': '🕳️'
        };
        return map[element] || '❓';
    }

    let currentProfile = null;
    let currentPveMob = null;
    let pvpTargetId = null;

    document.querySelectorAll('.rpg-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;

            document.querySelectorAll('.rpg-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            document.querySelectorAll('.rpg-section').forEach(s => s.classList.remove('active'));
            document.getElementById('section-' + tabName).classList.add('active');

            switch(tabName) {
                case 'profile':
                    loadProfile();
                    break;
                case 'upgrade':
                    loadProfile().then(() => renderUpgrades());
                    break;
                case 'pvp':
                    break;
                case 'pve':
                    break;
                case 'deathlog':
                    break;
            }
        });
    });

    async function loadProfile() {
        try {
            const data = await apiCall('get_profile.php');
            currentProfile = data.profile;
            renderProfile(currentProfile);
            return data;
        } catch (err) {
            notify(err.message, 'error');
        }
    }

    function renderProfile(profile) {
        const rpg = profile.rpg;
        const maxStats = profile.max_stats;

        const avatarEl = document.getElementById('profile-avatar');
        if (profile.avatar) {
            avatarEl.src = '../uploads/avatars/' + profile.avatar;
        } else {
            avatarEl.src = '../assets/default_avatar.png';
        }

        document.getElementById('profile-username').textContent = profile.username;
        document.getElementById('profile-level').textContent = rpg.level;

        const elemEl = document.getElementById('profile-element');
        elemEl.textContent = getElementEmoji(rpg.element) + ' ' + rpg.element;
        elemEl.className = 'rpg-element ' + getElementClass(rpg.element);

        const xpPercent = Math.min((rpg.xp / rpg.xp_max) * 100, 100);
        document.getElementById('profile-xp-fill').style.width = xpPercent + '%';
        document.getElementById('profile-xp-text').textContent = `${rpg.xp} / ${rpg.xp_max} XP`;

        document.getElementById('stat-hp').textContent = `${rpg.hp}/${rpg.hp_max}`;
        document.getElementById('stat-phys-attack').textContent = rpg.phys_attack;
        document.getElementById('stat-mag-attack').textContent = rpg.mag_attack;
        document.getElementById('stat-phys-defense').textContent = `${rpg.phys_defense}/${rpg.phys_defense_max}`;
        document.getElementById('stat-mag-absorb').textContent = rpg.mag_absorb + '%';
        document.getElementById('stat-kd').textContent = `${rpg.kills}/${rpg.deaths}`;

        document.getElementById('nav-coins').textContent = '💰 ' + profile.coins.toLocaleString();
        document.getElementById('nav-petals').textContent = '🌸 ' + profile.petals.toLocaleString();

        const levelBtn = document.getElementById('btn-level-up');
        if (profile.can_level_up) {
            levelBtn.style.display = 'inline-block';
        } else {
            levelBtn.style.display = 'none';
        }

        const effectsContainer = document.getElementById('effects-container');
        const effectsList = document.getElementById('effects-list');

        if (profile.effects && profile.effects.length > 0) {
            effectsContainer.style.display = 'block';
            effectsList.innerHTML = profile.effects.map(eff => `<div class="rpg-effect-item">
                    <span class="rpg-effect-name">${eff.effect_name}</span>
                    <span class="rpg-effect-actions">Осталось действий: ${eff.remaining_actions}${eff.damage_per_action > 0 ? ' | Урон: ' + eff.damage_per_action : ''}</span>
                </div>
            `).join('');
        } else {
            effectsContainer.style.display = 'none';
        }

        const btnHeal = document.getElementById('btn-heal');
        const btnRepair = document.getElementById('btn-repair-defense');

        if (btnHeal) {
            const hpFull = rpg.hp >= rpg.hp_max;
            const enoughCoins = profile.coins >= 500;
            const enoughPetals = profile.petals >= 50;
            btnHeal.disabled = hpFull || !enoughCoins || !enoughPetals;
            
            if (currentPveMob) {
                btnHeal.disabled = true;
                btnHeal.title = 'Недоступно во время боя';
            } else {
                btnHeal.title = '';
            }
        }

        if (btnRepair) {
            const defFull = rpg.phys_defense >= rpg.phys_defense_max;
            const enoughCoins = profile.coins >= 1000;
            btnRepair.disabled = defFull || !enoughCoins;
        }
    }

    function renderUpgrades() {
        if (!currentProfile) return;

        const rpg = currentProfile.rpg;
        const maxStats = currentProfile.max_stats;
        const costs = currentProfile.upgrade_costs;
        const values = currentProfile.upgrade_values;
        const grid = document.getElementById('upgrade-grid');

        const stats = [
            {
                key: 'hp', name: '❤️ Здоровье (ХП)', 
                current: rpg.hp_max, max: maxStats.hp_max, absMax: 10000,
                cost: costs.hp, value: values.hp
            },
            {
                key: 'phys_attack', name: '⚔️ Физическая атака',
                current: rpg.phys_attack, max: maxStats.phys_attack_max, absMax: 1000,
                cost: costs.phys_attack, value: values.phys_attack
            },
            {
                key: 'mag_attack', name: '🔮 Магическая атака',
                current: rpg.mag_attack, max: maxStats.mag_attack_max, absMax: 1000,
                cost: costs.mag_attack, value: values.mag_attack
            },
            {
                key: 'phys_defense', name: '🛡️ Физическая защита',
                current: rpg.phys_defense_max, max: maxStats.phys_defense_max, absMax: 1000,
                cost: costs.phys_defense, value: values.phys_defense
            },
            {
                key: 'mag_absorb', name: '✨ Маг. поглощение',
                current: rpg.mag_absorb, max: maxStats.mag_absorb_max, absMax: 80,
                cost: costs.mag_absorb, value: values.mag_absorb
            }
        ];

        grid.innerHTML = stats.map(s => {
            const progress = Math.min((s.current / s.max) * 100, 100);
            const isMaxed = s.current >= s.max;
            const canAfford = currentProfile.coins >= s.cost.coins && currentProfile.petals >= s.cost.petals;

            let costText = '';
            if (s.cost.coins > 0) costText += `<span>💰 ${s.cost.coins.toLocaleString()}</span>`;
            if (s.cost.petals > 0) costText += `<span>🌸 ${s.cost.petals.toLocaleString()}</span>`;

            return `<div class="rpg-upgrade-item">
                    <div class="rpg-upgrade-header">
                        <span class="rpg-upgrade-name">${s.name}</span>
                        <span class="rpg-upgrade-current">${s.current}${s.key === 'mag_absorb' ? '%' : ''}</span>
                    </div>
                    <div class="rpg-upgrade-progress">
                        <div class="rpg-upgrade-progress-fill" style="width: ${progress}%"></div>
                    </div>
                    <div class="rpg-upgrade-max">Макс. (ур. ${rpg.level}): ${s.max}${s.key === 'mag_absorb' ? '%' : ''} | Абс. макс: ${s.absMax}${s.key === 'mag_absorb' ? '%' : ''}</div>
                    <div class="rpg-upgrade-value">+${s.value}${s.key === 'mag_absorb' ? '%' : ''} за улучшение</div>
                    <div class="rpg-upgrade-cost">Стоимость: ${costText || 'Бесплатно'}</div>
                    <button class="rpg-btn ${isMaxed ? 'rpg-btn-secondary' : (canAfford ? 'rpg-btn-success' : 'rpg-btn-secondary')}" 
                            ${isMaxed || !canAfford ? 'disabled' : ''}
                            onclick="RPG.upgradeStat('${s.key}')">
                        ${isMaxed ? '✅ Максимум' : (canAfford ? '⬆️ Улучшить' : '🔒 Не хватает')}
                    </button>
                </div>
            `;
        }).join('');
    }

    async function upgradeStat(stat) {
        try {
            const data = await apiCall('upgrade_stat.php', { stat });
            notify(data.message, 'success');
            await loadProfile();
            renderUpgrades();
        } catch (err) {
            notify(err.message, 'error');
        }
    }

    document.getElementById('btn-level-up').addEventListener('click', async () => {
        try {
            const data = await apiCall('level_up.php', {});
            notify(data.message, 'success');
            await loadProfile();
            renderUpgrades();
        } catch (err) {
            notify(err.message, 'error');
        }
    });

    document.getElementById('btn-refresh-players').addEventListener('click', loadPlayers);

    async function loadPlayers() {
        const list = document.getElementById('players-list');
        list.innerHTML = '<div class="rpg-loading">Загрузка игроков</div>';

        try {
            const data = await apiCall('get_players');
            const players = data.players;

            if (players.length === 0) {
                list.innerHTML = '<p class="rpg-hint">Нет доступных игроков для PVP.</p>';
                return;
            }

            if (data.is_blind) {
                notify('⚠️ На вас наложена Слепота! Вы видите только ники.', 'warning');
            }

            list.innerHTML = players.map(p => {
                let detailsHtml = '';
                if (p.show_details) {
                    detailsHtml = `<div class="rpg-player-details">
                            ❤️ ${p.hp}/${p.hp_max} | 🛡️ ${p.phys_defense} | ✨ ${p.mag_absorb}% | Ур. ${p.level}
                        </div>`;
                }

                let actionHtml = '';
                if (p.on_cooldown) {
                    actionHtml = `<span class="rpg-cooldown-text">⏳ КД: ${p.cooldown_remaining}</span>`;
                } else {
                    actionHtml = `<button class="rpg-btn rpg-btn-danger rpg-btn-sm" onclick="RPG.startPvp(${p.id_user}, '${p.username.replace(/'/g, "\\'")}')">⚔️ Атаковать</button>`;
                }

                const elemClass = getElementClass(p.element);

                return `<div class="rpg-player-card">
                        <div class="rpg-player-info">
                            ${p.avatar ? `<img src="../uploads/avatars/${p.avatar}" class="rpg-player-avatar" alt="">` : ''}
                            <div>
                                <div class="rpg-player-name">${p.username}</div>
                                <div class="rpg-element ${elemClass}" style="font-size:12px;padding:2px 8px;margin-top:4px;">
                                    ${getElementEmoji(p.element)} ${p.element}
                                </div>
                                ${detailsHtml}
                            </div>
                        </div>
                        <div class="rpg-player-actions">
                            ${actionHtml}
                        </div>
                    </div>`;
            }).join('');
        } catch (err) {
            list.innerHTML = `<p class="rpg-hint" style="color:#ff6347;">${err.message}</p>`;
        }
    }

    function startPvp(targetId, targetName) {
        pvpTargetId = targetId;
        const modal = document.getElementById('pvp-modal');
        modal.style.display = 'flex';

        document.getElementById('pvp-target-name').textContent = targetName;
        document.getElementById('pvp-target-info').innerHTML = '';
        document.getElementById('pvp-battle-log').innerHTML = '<div class="log-entry log-info">Выберите тип атаки...</div>';

        enablePvpButtons(true);
    }

    function enablePvpButtons(enabled) {
        document.getElementById('btn-pvp-phys').disabled = !enabled;
        document.getElementById('btn-pvp-mag').disabled = !enabled;
        document.getElementById('btn-pvp-flee').disabled = !enabled;
    }

    document.getElementById('pvp-close').addEventListener('click', async () => {
        if (pvpTargetId) {
            try {
                await apiCall('attack_player', {
                    target_id: pvpTargetId,
                    attack_type: 'physical',
                    action: 'flee'
                });
                notify('Вы сбежали из боя. КД: 10 минут.', 'warning');
            } catch (err) {
            }
        }
        document.getElementById('pvp-modal').style.display = 'none';
        pvpTargetId = null;
        loadPlayers();
    });

    document.getElementById('btn-pvp-phys').addEventListener('click', () => pvpAttack('physical'));
    document.getElementById('btn-pvp-mag').addEventListener('click', () => pvpAttack('magical'));
    document.getElementById('btn-pvp-flee').addEventListener('click', async () => {
        try {
            enablePvpButtons(false);
            const data = await apiCall('attack_player.php', {
                target_id: pvpTargetId,
                attack_type: 'physical',
                action: 'flee'
            });
            addBattleLog('pvp', '🏃 Вы сбежали! КД: 10 минут.', 'info');
            notify('Вы сбежали из боя.', 'warning');
            setTimeout(() => {
                document.getElementById('pvp-modal').style.display = 'none';
                pvpTargetId = null;
                loadPlayers();
                loadProfile();
            }, 1500);
        } catch (err) {
            notify(err.message, 'error');
            enablePvpButtons(true);
        }
    });

    async function pvpAttack(type) {
        if (!pvpTargetId) return;

        enablePvpButtons(false);

        try {
            const data = await apiCall('attack_player.php', {
                target_id: pvpTargetId,
                attack_type: type,
                action: 'attack'
            });

            if (data.effect_results && data.effect_results.length > 0) {
                data.effect_results.forEach(eff => {
                    addBattleLog('pvp', '🔥 ' + eff.message, 'effect');
                });
            }

            if (data.battle_log) {
                data.battle_log.forEach(msg => {
                    let logClass = 'info';
                    if (msg.includes('урон') || msg.includes('Нанесено')) logClass = 'damage';
                    if (msg.includes('заблокирован') || msg.includes('поглощ')) logClass = 'block';
                    if (msg.includes('Получено') || msg.includes('Награда')) logClass = 'reward';
                    if (msg.includes('эффект') || msg.includes('Эффект')) logClass = 'effect';
                    addBattleLog('pvp', msg, logClass);
                });
            }

            if (data.effect_applied) {
                notify(`Наложен эффект: ${data.effect_applied.name}!`, 'info');
            }

            if (data.target_killed) {
                notify(`Цель убита! ${data.loot ? `Получено: ${data.loot.coins}💰, ${data.loot.petals}🌸, ${data.loot.xp}XP` : ''}`, 'success');
                setTimeout(() => {
                    document.getElementById('pvp-modal').style.display = 'none';
                    pvpTargetId = null;
                    loadPlayers();
                    loadProfile();
                }, 2000);
                return;
            }

            if (data.attacker_dead) {
                notify('Вы погибли от эффектов!', 'error');
                setTimeout(() => {
                    document.getElementById('pvp-modal').style.display = 'none';
                    pvpTargetId = null;
                    loadProfile();
                }, 2000);
                return;
            }

            addBattleLog('pvp', `⏳ КД: ${data.cooldown_minutes} минут`, 'info');

            setTimeout(() => {
                document.getElementById('pvp-modal').style.display = 'none';
                pvpTargetId = null;
                loadPlayers();
                loadProfile();
            }, 2500);

        } catch (err) {
            addBattleLog('pvp', '❌ ' + err.message, 'damage');
            notify(err.message, 'error');
            enablePvpButtons(true);
        }
    }

    function addBattleLog(mode, message, type = 'info') {
        const logId = mode === 'pvp' ? 'pvp-battle-log' : 'pve-battle-log';
        const logEl = document.getElementById(logId);
        const entry = document.createElement('div');
        entry.className = `log-entry log-${type}`;
        entry.textContent = message;
        logEl.appendChild(entry);
        logEl.scrollTop = logEl.scrollHeight;
    }

    document.getElementById('btn-find-mob').addEventListener('click', findMob);

    async function findMob() {
        try {
            const data = await apiCall('get_mob');
            currentPveMob = data.mob;

            const container = document.getElementById('pve-battle-container');
            container.style.display = 'block';

            document.getElementById('pve-mob-img').src = 'mobs/' + data.mob.url;
            document.getElementById('pve-mob-name').textContent = data.mob.name + ' ' + getElementEmoji(data.mob.element);

            updatePveBars(data.mob.hp, data.mob.hp_max, data.player.hp, data.player.hp_max);

            document.getElementById('pve-mob-stats').innerHTML = `⚔️ ${data.mob.phys_attack} | 🔮 ${data.mob.mag_attack} | 🛡️ ${data.mob.phys_defense} | ✨ ${data.mob.mag_absorb}%
                <br>🧠 Индекс размышления: ${data.mob.mob_ai ?? 0}
                <br>${getElementEmoji(data.mob.element)} ${data.mob.element} | 💰 ${data.mob.coins} | 🌸 ${data.mob.petals}`;

            document.getElementById('pve-battle-log').innerHTML = '<div class="log-entry log-info">Бой начался! Выберите атаку.</div>';
            enablePveButtons(true);

            document.getElementById('btn-find-mob').disabled = true;

            document.getElementById('pve-user-img').src = '../../profile/avatars/' + data.mob.user_url;

            fleeAttempts = 3;
            const fleeBtn = document.getElementById('btn-pve-flee');
            if (fleeBtn) {
                fleeBtn.disabled = false;
                fleeBtn.textContent = `🏃 Сбежать (${fleeAttempts})`;
            }

            await loadProfile();
            notify(`Появился ${data.mob.name}!`, 'info');
        } catch (err) {
            if (err.cooldown) {
                notify(`Вы недавно погибли. Подождите ${err.remaining} секунд.`, 'warning');
                disableFindMobButton(err.remaining);
            } else {
                notify(err.message, 'error');
            }
        }
    }

    function updatePveBars(mobHp, mobHpMax, playerHp, playerHpMax) {
        const mobPercent = Math.max(0, (mobHp / mobHpMax) * 100);
        const playerPercent = Math.max(0, (playerHp / playerHpMax) * 100);

        document.getElementById('pve-mob-hp-fill').style.width = mobPercent + '%';
        document.getElementById('pve-mob-hp-text').textContent = `${mobHp} / ${mobHpMax}`;

        document.getElementById('pve-player-hp-fill').style.width = playerPercent + '%';
        document.getElementById('pve-player-hp-text').textContent = `${playerHp} / ${playerHpMax}`;
    }

    function enablePveButtons(enabled) {
        document.getElementById('btn-pve-phys').disabled = !enabled;
        document.getElementById('btn-pve-block').disabled = !enabled;
        document.getElementById('btn-pve-mag').disabled = !enabled;
        document.getElementById('btn-pve-flee').disabled = !enabled;
    }

    document.getElementById('btn-pve-phys').addEventListener('click', () => pveAttack('physical'));
    document.getElementById('btn-pve-block').addEventListener('click', () => pveAttack('block'));
    document.getElementById('btn-pve-mag').addEventListener('click', () => pveAttack('magical'));
    document.getElementById('btn-pve-flee').addEventListener('click', () => pveAttack('flee'));

    async function pveAttack(type) {
        if (!currentPveMob) return;
        const dataToSend = {
            attack_type: type,
            mob_id: currentPveMob.mob_id,
            mob_hp: currentPveMob.hp,
            mob_hp_max: currentPveMob.hp_max,
            mob_phys_attack: currentPveMob.phys_attack,
            mob_phys_defense: currentPveMob.phys_defense,
            mob_mag_attack: currentPveMob.mag_attack,
            mob_mag_absorb: currentPveMob.mag_absorb,
            mob_ai: currentPveMob.mob_ai ?? 0,
            mob_element: currentPveMob.element,
            mob_coins: currentPveMob.coins,
            mob_petals: currentPveMob.petals,
            mob_magic_effects: currentPveMob.magic_effects || [],
            mob_system_effects: currentPveMob.system_effects || [],
            mob_effects: currentPveMob.effects || []
        };
        if (type === 'flee') {
            dataToSend.flee_attempts = fleeAttempts;
        }

        enablePveButtons(false);

        try {
            const data = await apiCall('attack_mob', dataToSend);

            if (data.effect_results && data.effect_results.length > 0) {
                data.effect_results.forEach(eff => {
                    addBattleLog('pve', '🔥 ' + eff.message, 'effect');
                });
            }

            if (data.battle_log) {
                data.battle_log.forEach(msg => {
                    let logClass = 'info';
                    if (msg.includes('нанесли') || msg.includes('Нанесено') || msg.includes('урона вам')) logClass = 'damage';
                    if (msg.includes('заблокирован') || msg.includes('поглощ')) logClass = 'block';
                    if (msg.includes('Награда') || msg.includes('повержен')) logClass = 'reward';
                    addBattleLog('pve', msg, logClass);
                });
            }

            if (data.fled) {
                notify(`Вы сбежали! Потеряно: ${data.loss.coins}💰, ${data.loss.petals}🌸`, 'warning');
                currentPveMob = null;
                document.getElementById('btn-find-mob').disabled = false;
                document.getElementById('pve-battle-container').style.display = 'none';
                loadProfile();
                return;
            }

            if (data.flee_failed) {
                fleeAttempts = data.flee_attempts_remaining;
                const fleeBtn = document.getElementById('btn-pve-flee');
                fleeBtn.textContent = `🏃 Сбежать (${fleeAttempts})`;
                if (fleeAttempts <= 0) {
                    fleeBtn.disabled = true;
                }
            }

            currentPveMob.hp = data.new_mob_hp;
            currentPveMob.phys_defense = data.mob_phys_defense;
            currentPveMob.hp_max = data.mob_hp_max || currentPveMob.hp_max;
            currentPveMob.effects = Array.isArray(data.mob_effects) ? data.mob_effects : (currentPveMob.effects || []);
            currentPveMob.magic_effects = Array.isArray(data.mob_magic_effects) ? data.mob_magic_effects : (currentPveMob.magic_effects || []);
            currentPveMob.system_effects = Array.isArray(data.mob_system_effects) ? data.mob_system_effects : (currentPveMob.system_effects || []);

            updatePveBars(data.new_mob_hp, currentPveMob.hp_max, data.player_hp, data.player_hp_max);

            if (data.player_damage > 0) {
                document.querySelector('.rpg-pve-mob').classList.add('rpg-shake');
                setTimeout(() => document.querySelector('.rpg-pve-mob').classList.remove('rpg-shake'), 500);
            }
            if (data.mob_damage > 0) {
                document.querySelector('.rpg-pve-player').classList.add('rpg-shake');
                setTimeout(() => document.querySelector('.rpg-pve-player').classList.remove('rpg-shake'), 500);
            }

            if (data.mob_killed) {
                const reward = data.reward;
                notify(`Враг повержен! Получено: ${reward.coins}💰, ${reward.petals}🌸, ${reward.xp}XP`, 'success');
                currentPveMob = null;
                enablePveButtons(false);
                document.getElementById('btn-find-mob').disabled = false;
                loadProfile();
                return;
            }

            if (data.player_dead) {
                notify('Вы погибли! Пустив по кругу, вы были восстановлены.', 'error');
                currentPveMob = null;
                enablePveButtons(false);
                document.getElementById('btn-find-mob').disabled = false;
                document.getElementById('pve-battle-container').style.display = 'none';
                loadProfile();
                return;
            }

            enablePveButtons(true);

            if (!data.mob_killed && !data.player_dead && !data.fled) {
                enablePveButtons(true);
            } else {
                fleeAttempts = 3;
            }

        } catch (err) {
            addBattleLog('pve', '❌ ' + err.message, 'damage');
            notify(err.message, 'error');
            enablePveButtons(true);
        }
    }

    document.getElementById('btn-refresh-deathlog').addEventListener('click', loadDeathLog);

    async function loadDeathLog() {
        const list = document.getElementById('death-log-list');
        list.innerHTML = '<div class="rpg-loading">Загрузка</div>';

        try {
            const data = await apiCall('get_death_log.php');
            const logs = data.death_log;

            if (logs.length === 0) {
                list.innerHTML = '<p class="rpg-hint">У вас нет записей о смертях. Так держать!</p>';
                return;
            }

            list.innerHTML = logs.map(log => {
                const date = new Date(log.created_at);
                const dateStr = date.toLocaleString('ru-RU');

                return `<div class="rpg-death-log-entry">
                        <div>
                            <span class="rpg-death-killer">💀 Убит: ${log.killer_name}</span>
                            <div class="rpg-death-loss">Потеряно: 💰 ${log.coins_lost} | 🌸 ${log.petals_lost}</div>
                        </div>
                        <span class="rpg-death-date">${dateStr}</span>
                    </div>`;
            }).join('');
        } catch (err) {
            list.innerHTML = `<p class="rpg-hint" style="color:#ff6347;">${err.message}</p>`;
        }
    }
    
    document.getElementById('btn-heal').addEventListener('click', async () => {
        try {
            const data = await apiCall('heal', {});
            notify(data.message, 'success');
            await loadProfile();
        } catch (err) {
            notify(err.message, 'error');
        }
    });

    document.getElementById('btn-repair-defense').addEventListener('click', async () => {
        try {
            const data = await apiCall('repair_defense', {});
            notify(data.message, 'success');
            await loadProfile();
        } catch (err) {
            notify(err.message, 'error');
        }
    });

    window.RPG = {
        upgradeStat,
        startPvp,
        loadProfile,
        loadPlayers
    };

    loadProfile();

        const menuToggle = document.getElementById('rpg-menu-toggle');
        const navTabs = document.getElementById('rpg-nav-tabs');
        if (menuToggle && navTabs) {
            menuToggle.addEventListener('click', function() {
                navTabs.classList.toggle('show');
            });
            navTabs.addEventListener('click', function(e) {
                if (e.target.classList.contains('rpg-tab')) {
                    navTabs.classList.remove('show');
                }
            });
        } else {
            console.warn('Бургер-меню не найдено');
        }
})();