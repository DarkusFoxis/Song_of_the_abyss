<?php
session_start();
require_once '../template/conn.php';

if (!isset($_SESSION['user'])) {
    header("Location: login");
    exit;
}

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Error connection: " . mysqli_connect_error();
    exit;
}

$login = $_SESSION['user'];
$user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
$result = $conn->query($user_query);

if ($result->num_rows === 0) {
    $_SESSION["perm_error"] = "Пользователь не найден.";
    header("Location: ../403");
    exit;
}

$user = $result->fetch_assoc();
$userId = $user['id'];
$permissions = $user["lvl"];

if ($permissions < 2) {
    $_SESSION["perm_error"] = "Причина отказа: недостаточно прав. Требуемые права: ROOT. Ваши права: $permissions.";
    header("Location: ../403");
    exit;
}

$query = "SELECT * FROM invent WHERE id_user = '$userId'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    $_SESSION["perm_error"] = "Причина отказа: Необходимо активировать инвентарь профиля.";
    header("Location: ../403");
    exit;
}
$inv_data = $result->fetch_assoc();
$sum_kase = ($inv_data['lvl'] + 1) * 19;
mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shop</title>
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .section {
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid #33004b;
        }
        .section-title {
            color: #BA55D3;
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            justify-content: center;
        }
        .product {
            border: 1px solid #33004b;
            background: linear-gradient(135deg, #10001e, #200030);
            border-radius: 10px;
            padding: 20px;
            box-sizing: border-box;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .product:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
        }
        .product::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 50%, rgba(100, 0, 190, 0.1) 80%, rgba(74, 0, 122, 0.2) 100%);
            pointer-events: none;
        }
        .product h5 {
            color: #9370DB;
            font-size: 1.4rem;
            margin-bottom: 15px;
        }
        .product p {
            margin-bottom: 12px;
            line-height: 1.5;
            flex-grow: 1;
        }
        .product .price {
            color: #FFA500;
            font-weight: bold;
            font-size: 1.2rem;
            margin: 15px 0;
        }
        input[type="number"] {
            background-color: rgb(18, 18, 18);
            color: white;
            border: 1px solid #333;
            padding: 8px 12px;
            border-radius: 4px;
            width: 100%;
            margin-bottom: 15px;
            text-align: center;
        }
        .button {
            margin-top: auto;
        }
        .result {
            color: yellow;
            margin-top: 10px;
            min-height: 20px;
        }
        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="./main">Back</a>
    <a href="#" onclick="update_price()">Update Prices</a>
</div>

<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="header">
                    <h3>Рынок бездны</h3>
                </div>

                <div class="section">
                    <h4 class="section-title">Обмен ресурсов</h4>
                    <div class="product-container">
                        <div class="product">
                            <h5>Обмен кристаллов</h5>
                            <p>1 кристалл → <span id="cristal_coin">100</span> монет</p>
                            <form data-action="cristal_coin">
                                <input type="number" name="count" min="1" placeholder="Количество кристаллов">
                                <button type="submit" class="button">Обменять</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Обмен лепестков</h5>
                            <p>1 лепесток → <span id="sakura_coin">50</span> монет</p>
                            <form data-action="petal_coin">
                                <input type="number" name="count" min="1" placeholder="Количество лепестков">
                                <button type="submit" class="button">Обменять</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Покупка кристаллов</h5>
                            <p><span id="coin_cristal">250</span> монет → 1 кристалл</p>
                            <form data-action="coin_cristal">
                                <input type="number" name="count" min="1" placeholder="Количество кристаллов">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Покупка лепестков</h5>
                            <p><span id="coin_sakura">125</span> монет → 1 лепесток</p>
                            <form data-action="coin_petal">
                                <input type="number" name="count" min="1" placeholder="Количество лепестков">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Покупка кейсов</h5>
                            <p><?= $sum_kase ?> лепестков → 1 кейс</p>
                            <form data-action="petal_kase">
                                <input type="number" name="count" min="1" placeholder="Количество кейсов">
                                <button type="submit" class="button">Обменять</button>
                                <div class="result"></div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="section">
                    <h4 class="section-title">Различные товары</h4>
                    <div class="product-container">
                        <div class="product">
                            <h5>PREMIUM</h5>
                            <p>Группа "PREMIUM" даёт больше преимуществ: удвоенные лимиты, бонусы, больше сообщений нейросетям, больше возможностей в инструментах нейросетей и ранний доступ к новым разработкам. Дополнительно вы поддерживаете проект и помогаете ему развиваться!</p>
                            <p class="price">250 руб. с доната</p>
                            <form data-action="premium">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Доступ к инструментам</h5>
                            <p>На сайте вводятся различные инструменты. Покупая доступ к ним, вы моментально получаете возможность перевода ресурсов другим пользователям, добавлять ссылки в поисковик, использовать нейросети, использовать внутреннюю почту.</p>
                            <p class="price">250 монет</p>
                            <form data-action="tools_accses">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Титул: Шопоголик</h5>
                            <p>Покажите вашу любовь к шопингу с этим эксклюзивным титулом!</p>
                            <p class="price">50000 монет</p>
                            <form data-action="shopaholic">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Титул: Филантроп</h5>
                            <p>Покажите свой щедрый характер и поделитесь богатством с сообществом!</p>
                            <p class="price">100000 монет</p>
                            <form data-action="philanthropist">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Титул: Восставший</h5>
                            <p>Для тех, кто чувствует себя живым в цифровом мире!</p>
                            <p class="price">25000 монет</p>
                            <form data-action="rebel">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Титул: Демонолог</h5>
                            <p>Для истинных искателей тайн и загадок цифрового мира!</p>
                            <p class="price">666666 монет</p>
                            <form data-action="demonolog">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Титул: Горничная</h5>
                            <p>Создан для тех, кто готов служить своему господину!</p>
                            <p class="price">75000 лепестков</p>
                            <form data-action="maid">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                        <div class="product">
                            <h5>Титул: Босс Якудзе</h5>
                            <p>Покажите, кто настоящий босс на районе!</p>
                            <p class="price">5095 кристаллов</p>
                            <form data-action="yakudsa">
                                <button type="submit" class="button">Купить</button>
                                <div class="result"></div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/jquery-3.7.1.min.js"></script>
<script>
function update_price(){
    $.ajax({
        url: './pay_core',
        type: 'POST',
        data: {action: 'update_price'},
        dataType: 'json',
        success: function(response) {
            for (var key in response) {
                if (response.hasOwnProperty(key)) {
                    var elementId = "#" + key;
                    $(elementId).text(response[key]);
                }
            }
        },
    });
}
$(document).ready(function() {
    update_price();
});
</script>
<script src="./js/shop.js"></script>
</body>
</html>
<?php 
session_write_close();
?>