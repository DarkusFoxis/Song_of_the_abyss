<?php
$ticketsDir = 'tikets';
if (!file_exists($ticketsDir)) {
    if (!mkdir($ticketsDir, 0777, true)) {
        $error = "Ошибка при создании папки для заявок";
        header("Location: index.php?error=" . urlencode($error));
        exit;
    }
}
$requiredFields = ['firstName', 'lastName', 'age', 'team', 'skills', 'reason', 'telegram'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $error = "Пожалуйста, заполните все обязательные поля";
        header("Location: registr?error=" . urlencode($error));
        exit;
    }
}
if (!isset($_POST['doc1']) || !isset($_POST['doc2'])) {
    $error = "Вы должны согласиться со всеми документами";
    header("Location: registr?error=" . urlencode($error));
    exit;
}
$formData = [
    'firstName' => htmlspecialchars(trim($_POST['firstName'])),
    'lastName' => htmlspecialchars(trim($_POST['lastName'])),
    'nickname' => !empty($_POST['nickname']) ? htmlspecialchars(trim($_POST['nickname'])) : null,
    'age' => intval($_POST['age']),
    'team' => intval($_POST['team']),
    'skills' => htmlspecialchars(trim($_POST['skills'])),
    'reason' => htmlspecialchars(trim($_POST['reason'])),
    'telegram' => htmlspecialchars(trim($_POST['telegram'])),
    'timestamp' => date('Y-m-d H:i:s')
];
if (!empty($formData['nickname'])) {
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $formData['nickname']) . '.json';
} else {
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $formData['firstName']) . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $formData['lastName']) . '_' . time() . '.json';
}

$filePath = $ticketsDir . DIRECTORY_SEPARATOR . $filename;

$jsonData = json_encode($formData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents($filePath, $jsonData)) {
    header("Location: registr?status=success&filename=" . urlencode($filename));
    exit;
} else {
    $error = "Ошибка при сохранении файла. Проверьте права доступа к папке.";
    header("Location: registr?error=" . urlencode($error));
    exit;
}