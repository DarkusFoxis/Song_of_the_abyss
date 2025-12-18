<?php
header('Content-Type: application/json');

$dataDir = 'users_data';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0755, true);
}

session_start();

$data = json_decode(file_get_contents('php://input'), true);

$filename = $dataDir . '/' . session_id() . '_data.json';

try {
    if (file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(['success' => true, 'message' => 'Данные сохранены']);
    } else {
        throw new Exception('Ошибка записи файла');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}