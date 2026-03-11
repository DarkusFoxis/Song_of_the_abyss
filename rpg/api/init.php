<?php
session_start();
require_once __DIR__ . '/../../template/auth.php';
auth_sync_session_from_token();

require_once __DIR__ . '/helpers.php';

function rpg_check_access() {
    if (!isset($_SESSION['user'])) {
        rpg_log("Unauthorized access attempt", 'WARN');
        json_error('Необходимо авторизоваться. <a href="../../profile/login">Войти в аккаунт</a>', 401);
    }

    try {
        $conn = get_db_connection();
        $user = get_current_rpg_user($conn);

        if (!$user) {
            rpg_log("User not found: session user = " . $_SESSION['user'], 'ERROR');
            json_error('Пользователь не найден.', 404);
        }

        if ((int)$user['perm_lvl'] < 2) {
            rpg_log("Access denied: user={$user['login']}, perm_lvl={$user['perm_lvl']}", 'WARN');
            json_error('Ваш аккаунт не подтверждён.', 403);
        }

        return ['conn' => $conn, 'user' => $user];
    } catch (Exception $e) {
        rpg_log("Init error: " . $e->getMessage(), 'CRITICAL');
        json_error('Внутренняя ошибка сервера.', 500);
    }
}