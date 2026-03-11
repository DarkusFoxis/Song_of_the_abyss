<?php
$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
if ($isAjaxRequest) {
    session_start();
    if (!isset($_SESSION['user'])) {
        echo "Вы должны быть авторизованы.";
        exit;
    }

    $achievement = isset($_POST['achievement']) ? $_POST['achievement'] : null;

    if ($achievement) {
        require_once './template/conn.php';

        $conn = mysqli_connect($host, $log, $password_sql, $database);
        if (!$conn) {
            echo "Ошибка соединения: " . mysqli_connect_error();
            exit;
        }

        $login = $_SESSION['user'];
        $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
        $result = $conn->query($user_query);
        $user = $result -> fetch_assoc();
        $userId = $user['id'];
        if ($user['lvl'] == 0) {
            echo "Вы заблокированны на проекте, поэтому возможности ограничены.";
            exit;
        } else if ($user['lvl'] == 1) {
            echo "Вы не подтверждены на сайте.";
            exit;
        }
        $username = $_SESSION['username'];

        switch ($achievement) {
            case 'casino_100':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Диллер'";
                $result = mysqli_query($conn, $query); //Проверка на наличие достижения.

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Диллер', 'Набрать 100 очков в тетрисе казино, и правильно распечатать колоду карт.')";
                    mysqli_query($conn, $insertQuery);
                    $titleQuery = "INSERT INTO `title`(`id_title`, `id_user`, `title`) VALUES (NULL,'$userId','Диллер')";
                    mysqli_query($conn, $titleQuery);
                    echo "Поздравляем! Вы получили достижение и титул 'Диллер'!";
                } else {
                    echo "Вы уже получили это достижение ранее.";
                }
                break;
            case 'casino_200':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Энтузиаст перфекционизма'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Энтузиаст перфекционизма', 'Набрать 200 очков в тетрисе казино доказав, что порядок карт в колоде имеет значение, невзирая на объяснения.')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Энтузиаст перфекционизма'!";
                } else {
                    echo "Вы уже доказали про порядк карт.";
                }
                break;
            case 'casino_-100':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Горе игрок'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Горе игрок', 'Проиграть 100 очков в тетрисе казино. КАК ОНИ В ДРУГОМ ПОРЯДКЕ?!?')";
                    mysqli_query($conn, $insertQuery);
                    $titleQuery = "INSERT INTO `title`(`id_title`, `id_user`, `title`) VALUES (NULL,'$userId','Горе игрок')";
                    mysqli_query($conn, $titleQuery);
                    echo "Поздравляем! Вы получили достижение и титул 'Горе игрок'! Ёбаный рот этого казино, блядь!";
                } else {
                    echo "Вы уже получили это достижение ранее.";
                }
                break;
            case 'casino_-200':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Постоянный игрок'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Постоянный игрок', 'Проиграть 200 очков в тетрисе казино. Вип статус вы не получили, за то набрали 200 кредитов.')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Постоянный игрок'! Ты что, дурак блять?!";
                } else {
                    echo "Вы уже получили это достижение ранее.";
                }
                break;
            case 'tetris_1000':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Боб строитель'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Боб строитель', 'Набрать 1000 и более очков в тетрисе, и пойти на стройку!')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Боб строитель'! План шикарен. Строим!";
                } else {
                    echo "Делайте новый план, насяника.";
                }
                break;
            case 'gachi':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Dungeon Master'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Dungeon Master', '♂Tetris♂ is ♂three hundred bucks♂.')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Dungeon Master', и не перепутали дверь!";
                } else {
                    echo "Вы уже получили это достижение ранее.";
                }
                break;
            case 'snake_25':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Водила'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Водила', 'Оседлать змею, и схавать 25 блюд!')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Водила'! А что вы будете делать с объедками?";
                } else {
                    echo "Вы уже получили это достижение ранее.";
                }
                break;
            case 'snake_100':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Заклинатель змей'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Заклинатель змей', 'Заставить змей напасть на ресторан, и сожрать 50 блюд.')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Заклинатель змей'... Зачем вы напали на ресторан?";
                } else {
                    echo "Вы уже получили это достижение ранее.";
                }
                break;
            case 'code403':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Открывайте'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Открывайте', 'Да свой я, свой! Пустите!')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Открывайте'! А теперь прекратите стучать, не откроем!";
                } else {
                    echo "По бошке себе стучи!";
                }
                break;
            case 'code404':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Междумирье'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Междумирье', 'Где я? Спасите! Я застрял!')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Междумирье'! Ну... Вроде тут есть выход. Поищите лучше.";
                } else {
                    echo "Выход где-то рядом... Поищите лучше!";
                }
                break;
            case 'secret':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'А зачем?'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'А зачем?', 'Зачем я это нашёл?')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'А зачем?'! А теперь думайте, зачем вы это открыли, и зачем вы тут ещё сидите.";
                } else {
                    echo "Хм... А зачем... А почему... А для кого... А на что... А за что... А за кого... А на кого... А для чего... А для кого... А зачем... А почему... А для кого... А на что... А за что... А за что... А за кого... А на кого... А для чего... А для кого... А зачем... А почему... А для кого... А на что... А за что... А за что... А за кого... А на кого... А для чего... А для кого... А зачем... А почему... А для кого... А на что... А за что... А за что... А за кого... А на кого... А для чего... А для кого... А зачем... А почему... А для кого... А на что... А за что... А за что... А за кого... А на кого... А для чего... А для кого...";
                }
                break;
            case 'quant':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Затянуло меня'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Затянуло меня', ' Воспользуйтесь ящиком квантовой запутанности, и не запутайтесь в квантовой теории.')";
                    mysqli_query($conn, $insertQuery);
                }
                break;
            case 'arknights':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Воин Родос'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Воин Родос', '$username, не забывайте ваше призвание. Наше дело ещё не подошло к концу.')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Воин Родос'! Надеемся, вы знаете, что делаете, оператор ". $username . ". Теперь жизнь многих лежит на вас.";
                } else {
                    echo "Не отлынивайте от работы, " . $username . ". У вас ещё много работы.";
                }
                break;
            case 'coins_250':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Подозрительная личность'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Подозрительная личность', 'На вашей карте замеченая странная активность...')";
                    mysqli_query($conn, $insertQuery);
                    echo "Поздравляем! Вы получили достижение 'Подозрительная личность'... А где вы столько денег достали?";
                } else {
                    echo "Вы получали код для подтверждения оплаты? Нет?... Тогда не душите змею...";
                }
                break;
            case 'teapot':
                $query = "SELECT * FROM achievement WHERE id_user = '$userId' AND title = 'Король кофеина'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) === 0) {
                    $insertQuery = "INSERT INTO achievement (id_achievement, id_user, title, description) VALUES (NULL, '$userId', 'Король кофеина', 'Пытается у чайника выпросить кофе, не понимая, что чайник не делает кофе.')";
                    mysqli_query($conn, $insertQuery);
                    $titleQuery = "INSERT INTO `title`(`id_title`, `id_user`, `title`) VALUES (NULL,'$userId','Король кофеина')";
                    mysqli_query($conn, $titleQuery);
                    echo "Поздравляем! Вы получили достижение и титул 'Король кофеина'! Нет и не просите! Чайник вам кофе не заварит!";
                } else {
                    echo "Не пытайтесь у чайника выпросить кофе, мы не в военкомате! Он вам его не сделает!!!";
                }
                break;
            default:
                echo "Неизвестное достижение.";
                break;
        }
        mysqli_close($conn);
        exit;
    }
} else{
    header('Location: index');
    exit;
}
?>