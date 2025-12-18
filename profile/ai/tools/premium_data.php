<?php
session_start();
require_once '../../../template/conn.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

$login = $_SESSION['user'];
$stmt = $conn->prepare("SELECT u.username, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = ?");
$stmt->bind_param('s', $login);
$stmt->execute();
$res = $stmt->get_result();
if (!$res->num_rows) {
    echo json_encode(['error' => 'Пользователь не найден']);
    exit;
}
$user = $res->fetch_assoc();
$username = $user['username'];
$lvl = (int)$user['lvl'];
if ($lvl == 6) {
    echo json_encode(['message' => "У $username админ права (включая и премиум)! Если ник пользователя: DarkusFoxis или NekoSama, то это создатели, это прекрасно! Но, если ник не совпадает... Возможно, перед вами хакер (или ошибка системы, что врядли)! Если хакер: Саботируйте работу! (пояснение: Премиум работает на основе уровне прав. Т. е. админ права имеют и премиум. Это не ошибка, а особенность сервиса)"]);
} else if ($lvl >= 3) {
    echo json_encode(['message' => "Спасибо за поддержку, $username! Вам покупать премиум не нужно, он у вас уже есть! Напомним: PREMIUM предоставляет эксклюзивные преимущества: увеличенные лимиты (теста постов; размер файлов, прикрепляемые к постам; символов в рассказах; переводов ресурсов пользователям), увеличенный бонус, больше стикеров в ваш аккаунт, расширенные возможности взаимодействия с нейросетями, приоритетный доступ к новым функциям и инструментам. Кроме того, вы вносите вклад в развитие проекта, помогая нам становиться лучше!"]);
} else {
    $message = "К сожалению, у вас нет премиума... PREMIUM предоставляет эксклюзивные преимущества: увеличенные лимиты (теста постов; размер файлов, прикрепляемые к постам; символов в рассказах; переводов ресурсов пользователям), увеличенный бонус, больше стикеров в ваш аккаунт, расширенные возможности взаимодействия с нейросетями, приоритетный доступ к новым функциям и инструментам. Кроме того, вы вносите вклад в развитие проекта, помогая нам становиться лучше! Ссылка для приобретения: https://so-ta.ru/profile/shop. Стоимость: 250 руб. (единоразово, списывается с баланса доната).";
    echo json_encode(['message' => $message]);
}
session_write_close();