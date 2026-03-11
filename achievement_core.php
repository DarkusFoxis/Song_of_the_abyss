<?php
$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
if (!$isAjaxRequest) {
    header('Location: index');
    exit;
}

session_start();
require_once __DIR__ . '/./template/auth.php';
auth_sync_session_from_token();
$authUser = auth_get_current_user();
if ($authUser === null) {
    echo "Вы должны быть авторизованы.";
    exit;
}

$achievement = $_POST['achievement'] ?? null;
if (!$achievement) exit;

require_once './template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Ошибка соединения: " . mysqli_connect_error();
    exit;
}

$login = $authUser['login'];
$user_query = "SELECT u.*, sg.lvl FROM users u 
               JOIN site_group sg ON u.permissions = sg.name 
               WHERE u.login = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param('s', $login);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) exit;
if ($user['lvl'] == 0) {
    echo "Вы заблокированны на проекте, поэтому возможности ограничены.";
    exit;
} else if ($user['lvl'] == 1) {
    echo "Вы не подтверждены на сайте.";
    exit;
}

$userId = $user['id'];
$username = $authUser['username'] ?? '';

$achievementsConfig = [
    'casino_100' => [
        'title' => 'Диллер',
        'description' => 'Набрать 100 очков в тетрисе казино и правильно распечатать колоде карт.',
        'award_title' => true,
        'message' => "Поздравляем! Вы получили достижение и титул 'Диллер'!",
        'duplicate_message' => "Вы уже получили это достижение ранее."
    ],
    'casino_200' => [
        'title' => 'Энтузиаст перфекционизма',
        'description' => 'Набрать 200 очков в тетрисе казино, доказав, что порядок карт в колоде имеет значение, невзирая на объяснения.',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Энтузиаст перфекционизма'!",
        'duplicate_message' => "Вы уже доказали про порядк карт."
    ],
    'casino_-100' => [
        'title' => 'Горе игрок',
        'description' => 'Проиграть 100 очков в тетрисе казино. КАК ОНИ В ДРУГОМ ПОРЯДКЕ?!?',
        'award_title' => true,
        'message' => "Поздравляем! Вы получили достижение и титул 'Горе игрок'! Ёбаный рот этого казино, блядь!",
        'duplicate_message' => "Вы уже получили это достижение ранее."
    ],
    'casino_-200' => [
        'title' => 'Постоянный игрок',
        'description' => 'Проиграть 200 очков в тетрисе казино. Вип-статус вы не получили, зато набрали 200 кредитов.',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Постоянный игрок'! Ты что, дурак блять?!",
        'duplicate_message' => "Вы уже получили это достижение ранее."
    ],
    'tetris_1000' => [
        'title' => 'Боб строитель',
        'description' => 'Набрать 1000 и более очков в тетрисе и пойти на стройку!',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Боб строитель'! План шикарен. Строим!",
        'duplicate_message' => "Делайте новый план, насяника."
    ],
    'gachi' => [
        'title' => 'Dungeon Master',
        'description' => '♂Tetris♂ is ♂three hundred bucks♂.',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Dungeon Master', и не перепутали дверь!",
        'duplicate_message' => "Вы уже получили это достижение ранее."
    ],
    'snake_25' => [
        'title' => 'Водила',
        'description' => 'Оседлать змею и схавать 25 блюд!',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Водила'! А что вы будете делать с объедками?",
        'duplicate_message' => "Вы уже получили это достижение ранее."
    ],
    'snake_100' => [
        'title' => 'Заклинатель змей',
        'description' => 'Заставить змей напасть на ресторан и сожрать 50 блюд.',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Заклинатель змей'... Зачем вы напали на ресторан?",
        'duplicate_message' => "Вы уже получили это достижение ранее."
    ],
    'code403' => [
        'title' => 'Открывайте',
        'description' => 'Да свой я, свой! Пустите!',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Открывайте'! А теперь прекратите стучать, не откроем!",
        'duplicate_message' => "По бошке себе стучи!"
    ],
    'code404' => [
        'title' => 'Междумирье',
        'description' => 'Где я? Спасите! Я застрял!',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Междумирье'! Ну... Вроде тут есть выход. Поищите лучше.",
        'duplicate_message' => "Выход где-то рядом... Поищите лучше!"
    ],
    'secret' => [
        'title' => 'А зачем?',
        'description' => 'Зачем я это нашёл?',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'А зачем?'! А теперь думайте, зачем вы это открыли, и зачем вы тут ещё сидите.",
        'duplicate_message' => "Хм... А зачем... А почему... А для кого... А на что... А за что... А за кого... А на кого... А для чего... А для кого... А зачем... А почему... А для кого... А на что... А за что... А за что... А за кого... А на кого... А для чего... А для кого... А зачем... А почему... А для кого... А на что... А за что... А за что... А за кого... А на кого... А для чего... А для кого... А зачем... А почему... А для кого... А на что... А за что... А за что... А за кого... А на кого... А для чего... А для кого..."
    ],
    'quant' => [
        'title' => 'Затянуло меня',
        'description' => 'Воспользуйтесь ящиком квантовой запутанности, и не запутайтесь в квантовой теории.',
        'award_title' => false,
        'message' => '',
        'duplicate_message' => ''
    ],
    'arknights' => [
        'title' => 'Воин Родос',
        'description' => '{username}, не забывайте ваше призвание. Наше дело ещё не подошло к концу.',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Воин Родос'! Надеемся, вы знаете, что делаете, оператор {username}. Теперь жизнь многих лежит на вас.",
        'duplicate_message' => "Не отлынивайте от работы, {username}. У вас ещё много работы."
    ],
    'coins_250' => [
        'title' => 'Подозрительная личность',
        'description' => 'На вашей карте замечена странная активность...',
        'award_title' => false,
        'message' => "Поздравляем! Вы получили достижение 'Подозрительная личность'... А где вы столько денег достали?",
        'duplicate_message' => "Вы получали код для подтверждения оплаты? Нет?... Тогда не душите змею..."
    ],
    'teapot' => [
        'title' => 'Король кофеина',
        'description' => 'Пытается у чайника выпросить кофе, не понимая, что чайник не делает кофе.',
        'award_title' => true,
        'message' => "Поздравляем! Вы получили достижение и титул 'Король кофеина'! Нет и не просите! Чайник вам кофе не заварит!",
        'duplicate_message' => "Не пытайтесь у чайника выпросить кофе, мы не в военкомате! Он вам его не сделает!!!"
    ]
];

if (!isset($achievementsConfig[$achievement])) {
    echo "Неизвестное достижение.";
    mysqli_close($conn);
    exit;
}

$config = $achievementsConfig[$achievement];
$replace = ['{username}' => $username];
foreach (['description', 'message', 'duplicate_message'] as $field) {
    $config[$field] = strtr($config[$field], $replace);
}

$checkQuery = "SELECT 1 FROM achievement WHERE id_user = ? AND title = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param('is', $userId, $config['title']);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($exists) {
    echo $config['duplicate_message'];
    mysqli_close($conn);
    exit;
}

$conn->begin_transaction();
try {
    $insertAchievement = "INSERT INTO achievement (id_user, title, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertAchievement);
    $stmt->bind_param('iss', $userId, $config['title'], $config['description']);
    $stmt->execute();
    $stmt->close();

    if ($config['award_title']) {
        $insertTitle = "INSERT INTO title (id_user, title) VALUES (?, ?)";
        $stmt = $conn->prepare($insertTitle);
        $stmt->bind_param('is', $userId, $config['title']);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    echo $config['message'];
} catch (Exception $e) {
    $conn->rollback();
    echo "Ошибка при обработке запроса: " . $e->getMessage();
}

mysqli_close($conn);
session_write_close();

