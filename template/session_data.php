<?php
require_once __DIR__ . '/auth.php';

function set_token($login, $user_id = null) {
    $conn = auth_get_db_connection();
    if (!$conn) {
        return null;
    }

    $token = auth_ensure_user_token($conn, (string)$login);
    mysqli_close($conn);

    if ($token) {
        auth_set_token_cookie($token);
    }

    return $token;
}
