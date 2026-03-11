<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();
require_once 'api/helpers.php';

$error_message = null;
if (!isset($_SESSION['user'])) {
    $error_message = 'Необходимо авторизоваться. <a href="../../profile/login">Войти в аккаунт</a>';
} else {
    try {
        $conn = get_db_connection();
        $user = get_current_rpg_user($conn);
        if (!$user) {
            $error_message = 'Пользователь не найден.';
        } elseif ((int)$user['perm_lvl'] < 2) {
            $error_message = 'Ваш аккаунт не подтверждён.';
            rpg_log("Access denied on page load: user={$user['login']}, perm_lvl={$user['perm_lvl']}", 'WARN');
        }
        if ($conn) $conn->close();
    } catch (Exception $e) {
        $error_message = 'Ошибка сервера.';
        rpg_log("Page load error: " . $e->getMessage(), 'ERROR');
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPG Режим</title>
    <link rel="stylesheet" href="css/rpg1.1.css">
</head>
<body>
    <?php if ($error_message): ?>
        <div class="rpg-error-screen">
            <div class="rpg-error-box">
                <div class="rpg-error-icon">⚠️</div>
                <h2>Доступ запрещён</h2>
                <p><?= $error_message ?></p>
            </div>
        </div>
    <?php else: ?>
        <div id="rpg-app">
            <nav class="rpg-nav">
                <div class="rpg-nav-title">⚔️ RPG Режим</div>
                <button class="rpg-menu-toggle" id="rpg-menu-toggle" aria-label="Меню">☰</button>
                <div class="rpg-nav-tabs" id="rpg-nav-tabs">
                    <button class="rpg-tab active" data-tab="profile">Профиль</button>
                    <button class="rpg-tab" data-tab="upgrade">Прокачка</button>
                    <button class="rpg-tab" data-tab="pvp">PVP</button>
                    <button class="rpg-tab" data-tab="pve">PVE</button>
                    <button class="rpg-tab" data-tab="deathlog">Лог смертей</button>
                    <a href="../index"><button class="rpg-tab">Домой</button></a>
                </div>
                <div class="rpg-nav-resources">
                    <span class="rpg-coins" id="nav-coins">💰 0</span>
                    <span class="rpg-petals" id="nav-petals">🌸 0</span>
                </div>
            </nav>

            <div id="rpg-notifications"></div>

            <section class="rpg-section active" id="section-profile">
                <div class="rpg-card rpg-profile-card">
                    <div class="rpg-profile-header">
                        <div class="rpg-avatar-container">
                            <img id="profile-avatar" src="" alt="Avatar" class="rpg-avatar" loading="lazy">
                        </div>
                        <div class="rpg-profile-info">
                            <h2 id="profile-username"></h2>
                            <div class="rpg-level-badge">
                                <span>Уровень: <strong id="profile-level">0</strong></span>
                                <button id="btn-level-up" class="rpg-btn rpg-btn-gold rpg-btn-sm" style="display:none;">⬆️ Повысить уровень</button>
                            </div>
                            <div class="rpg-element" id="profile-element"></div>
                        </div>
                    </div>

                    <div class="rpg-xp-bar-container">
                        <div class="rpg-xp-bar">
                            <div class="rpg-xp-fill" id="profile-xp-fill"></div>
                        </div>
                        <span class="rpg-xp-text" id="profile-xp-text">0 / 10000 XP</span>
                    </div>

                    <div class="rpg-stats-grid">
                        <div class="rpg-stat-item">
                            <div class="rpg-stat-icon">❤️</div>
                            <div class="rpg-stat-label">ХП</div>
                            <div class="rpg-stat-value" id="stat-hp">100/100</div>
                        </div>
                        <div class="rpg-stat-item">
                            <div class="rpg-stat-icon">⚔️</div>
                            <div class="rpg-stat-label">Физ. атака</div>
                            <div class="rpg-stat-value" id="stat-phys-attack">1</div>
                        </div>
                        <div class="rpg-stat-item">
                            <div class="rpg-stat-icon">🔮</div>
                            <div class="rpg-stat-label">Маг. атака</div>
                            <div class="rpg-stat-value" id="stat-mag-attack">1</div>
                        </div>
                        <div class="rpg-stat-item">
                            <div class="rpg-stat-icon">🛡️</div>
                            <div class="rpg-stat-label">Физ. защита</div>
                            <div class="rpg-stat-value" id="stat-phys-defense">1/10</div>
                        </div>
                        <div class="rpg-stat-item">
                            <div class="rpg-stat-icon">✨</div>
                            <div class="rpg-stat-label">Маг. поглощение</div>
                            <div class="rpg-stat-value" id="stat-mag-absorb">0%</div>
                        </div>
                        <div class="rpg-stat-item">
                            <div class="rpg-stat-icon">💀</div>
                            <div class="rpg-stat-label">K/D</div>
                            <div class="rpg-stat-value" id="stat-kd">0/0</div>
                        </div>
                    </div>

                    <div class="rpg-recovery-section">
                        <h3>💊 Восстановление</h3>
                        <div class="rpg-recovery-actions">
                            <button class="rpg-btn rpg-btn-success" id="btn-heal" title="Восстановить 35%/15% от максимального HP">❤️ Лечиться (350💰 + 100🌸)</button>
                            <button class="rpg-btn rpg-btn-primary" id="btn-repair-defense" title="Восстановить 55% от повреждённой защиты">🛡️ Починить защиту (555💰)</button>
                        </div>
                    </div>

                    <div class="rpg-effects-container" id="effects-container" style="display:none;">
                        <h3>🔥 Активные эффекты</h3>
                        <div id="effects-list"></div>
                    </div>
                </div>
            </section>

            <section class="rpg-section" id="section-upgrade">
                <div class="rpg-card">
                    <h2>📈 Прокачка характеристик</h2>
                    <div class="rpg-upgrade-grid" id="upgrade-grid">
                    </div>
                </div>
            </section>

            <section class="rpg-section" id="section-pvp">
                <div class="rpg-card">
                    <h2>⚔️ PVP — Арена</h2>
                    <button class="rpg-btn rpg-btn-primary" id="btn-refresh-players">🔄 Обновить список</button>
                    <div class="rpg-players-list" id="players-list">
                        <p class="rpg-hint">Нажмите «Обновить», чтобы увидеть других игроков.</p>
                    </div>
                </div>

                <div class="rpg-modal" id="pvp-modal" style="display:none;">
                    <div class="rpg-modal-content rpg-battle-screen">
                        <div class="rpg-battle-header">
                            <h2>⚔️ PVP Бой</h2>
                            <span class="rpg-modal-close" id="pvp-close">&times;</span>
                        </div>
                        <div class="rpg-battle-arena">
                            <div class="rpg-battle-target">
                                <h3 id="pvp-target-name"></h3>
                                <div id="pvp-target-info"></div>
                            </div>
                        </div>
                        <div class="rpg-battle-actions">
                            <button class="rpg-btn rpg-btn-danger" id="btn-pvp-phys">⚔️ Физ. атака</button>
                            <button class="rpg-btn rpg-btn-magic" id="btn-pvp-mag">🔮 Маг. атака</button>
                            <button class="rpg-btn rpg-btn-secondary" id="btn-pvp-flee">🏃 Сбежать</button>
                        </div>
                        <div class="rpg-battle-log" id="pvp-battle-log"></div>
                    </div>
                </div>
            </section>

            <section class="rpg-section" id="section-pve">
                <div class="rpg-card">
                    <h2>🐉 PVE — Охота в лесу</h2>
                    <button class="rpg-btn rpg-btn-primary" id="btn-find-mob">🔍 Поиск...</button>

                    <div id="pve-battle-container" style="display:none;">
                        <div class="rpg-pve-arena">
                            <div class="rpg-pve-mob">
                                <img id="pve-mob-img" src="" alt="Mob" class="rpg-mob-sprite" loading="lazy">
                                <h3 id="pve-mob-name"></h3>
                                <div class="rpg-hp-bar-container">
                                    <div class="rpg-hp-bar rpg-hp-bar-enemy">
                                        <div class="rpg-hp-fill" id="pve-mob-hp-fill"></div>
                                    </div>
                                    <span id="pve-mob-hp-text"></span>
                                </div>
                                <div class="rpg-mob-stats" id="pve-mob-stats"></div>
                            </div>

                            <div class="rpg-pve-vs">VS</div>

                            <div class="rpg-pve-player">
                                <img id="pve-user-img" src="" alt="User" class="rpg-user-avatar" loading="lazy">
                                <h3>Вы</h3>
                                <div class="rpg-hp-bar-container">
                                    <div class="rpg-hp-bar rpg-hp-bar-player">
                                        <div class="rpg-hp-fill" id="pve-player-hp-fill"></div>
                                    </div>
                                    <span id="pve-player-hp-text"></span>
                                </div>
                            </div>
                        </div>

                        <div class="rpg-battle-actions">
                            <button class="rpg-btn rpg-btn-danger" id="btn-pve-phys">⚔️ Физ. атака</button>
                            <button class="rpg-btn rpg-btn-secondary" id="btn-pve-block">🛡️ Блок</button>
                            <button class="rpg-btn rpg-btn-magic" id="btn-pve-mag">🔮 Маг. атака</button>
                            <button class="rpg-btn rpg-btn-secondary" id="btn-pve-flee">🏃 Сбежать (3)</button>
                        </div>

                        <div class="rpg-battle-log" id="pve-battle-log"></div>
                    </div>
                </div>
            </section>

            <section class="rpg-section" id="section-deathlog">
                <div class="rpg-card">
                    <h2>💀 Лог смертей</h2>
                    <button class="rpg-btn rpg-btn-primary" id="btn-refresh-deathlog">🔄 Обновить</button>
                    <div class="rpg-death-log-list" id="death-log-list">
                        <p class="rpg-hint">Нажмите «Обновить», чтобы загрузить историю.</p>
                    </div>
                </div>
            </section>
        </div>

        <script src="js/rpg2.js"></script>
    <?php endif; ?>
</body>
</html>

