<?php
// api/get_death_log.php
require_once __DIR__ . '/init.php';

$access = rpg_check_access();
$conn = $access['conn'];
$user = $access['user'];

try {
    $stmt = $conn->prepare("
        SELECT dl.*, u.username as killer_name 
        FROM rpg_death_log dl 
        JOIN users u ON dl.killer_id = u.id 
        WHERE dl.victim_id = ? 
        ORDER BY dl.created_at DESC 
        LIMIT 20
    ");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    rpg_log("Death log loaded for user_id={$user['id']}, entries=" . count($logs));

    json_response([
        'success' => true,
        'death_log' => $logs
    ]);
} catch (Exception $e) {
    rpg_log("get_death_log error: " . $e->getMessage(), 'ERROR');
    json_error('Ошибка получения лога смертей.', 500);
}