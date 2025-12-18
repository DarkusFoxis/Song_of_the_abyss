<?php session_start(); http_response_code(418);?>
<!DOCTYPE html>
<html>
<head>
    <title>418 i am a teapot</title>
    <link rel = "icon" href = "./img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{
            background-color: black;
            color: #FFE4E1;
            font-family: 'Montserrat Alternates', sans-serif;
        }
        .link{
            color: yellow;
        }
        .link:hover{
            color: red;
        }
        button {
          background: transparent;
          border: 1px solid #ccc;
          color: #ccc;
          padding: 10px 20px;
          font-size: 16px;
          cursor: pointer;
          transition: all 0.3s ease;
        }
        button:hover {
          background: #ccc;
          color: #333;
        }
        .teapot-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            margin-top: 20px;
            position: relative;
        }
        .teapot {
            width: 100px;
            height: 100px;
            fill: #FFE4E1;
            stroke: purple;
            stroke-width: 2;
        }
        .steam {
            stroke: #FFE4E1;
            stroke-width: 2;
            opacity: 0;
            animation: steam-animation 2s infinite linear;
        }
        .steam1 {
            animation-delay: 0s;
        }
        .steam2 {
            animation-delay: 0.5s;
        }

        .steam3 {
            animation-delay: 1s;
        }
        @keyframes steam-animation {
            0% {
                transform: translateY(0) scaleY(1);
                opacity: 0.7;
            }
            50% {
                transform: translateY(-20px) scaleY(1.2);
                opacity: 0.3;
            }
            100% {
                transform: translateY(-40px) scaleY(1.5);
                opacity: 0;
            }
        }
        .speech-bubble {
            position: relative;
            background-color: #FFE4E1;
            color: #333;
            padding: 10px 15px;
            border-radius: 10px;
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 0.9em;
            max-width: 180px;
            text-align: center;
            margin-bottom: 15px;
            opacity: 0;
            transition: opacity 0.5s ease-in-out, visibility 0.5s ease-in-out;
        }
        .speech-bubble::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 10px solid #FFE4E1;
        }
        .link {
            color: #6A5ACD;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3 style="text-align: center; color: purple; font-family: serif;">418<br>Я чайник.</h3>
    </div>
    <div class="content-main">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="teapot-container">
                        <div id="teapot-speech" class="speech-bubble"></div>
                        <svg class="teapot" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <!-- Тело чайника -->
                            <path d="M20,70 Q50,95 80,70 Q85,50 80,30 L75,25 Q50,10 25,25 L20,30 Q15,50 20,70 Z" />
                            <!-- Крышка -->
                            <ellipse cx="50" cy="23" rx="18" ry="7" />
                            <ellipse cx="50" cy="18" rx="5" ry="2" />
                            <!-- Носик -->
                            <path d="M80,50 Q95,45 90,30 L85,35 Q80,40 80,50" />
                            <!-- Ручка -->
                            <path d="M20,50 Q5,45 10,30 L15,35 Q20,40 20,50" fill="none" stroke-width="5"/>
                            <!-- Пар -->
                            <line class="steam steam1" x1="88" y1="28" x2="88" y2="18" />
                            <line class="steam steam2" x1="92" y1="26" x2="92" y2="16" />
                            <line class="steam steam3" x1="84" y1="30" x2="84" y2="20" />
                        </svg>
                    </div>
                    <br>
                    <p>Ой... Кажется, этот сервер решил, что он чайник! Он отказывается варить вам кофе, и молчит. Может быть лучше чашечку чая? А пока, вы можете вернуться в <a href="./index" class="link">родной кофейник</a>.</p>
                    <button id="butt">Попытаться убедить чайник</button>
                    <div id='result' style="margin-top: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
    <script src="./js/jquery-3.7.1.min.js"></script>
    <script>
        const achievementButton = document.getElementById('butt');
        const speechBubble = document.getElementById('teapot-speech');
        const teapotPhrases = [
            "Пых-пых... Я медитирую.",
            "Молчание – золото. А пар – платина!",
            "Я слишком возвышен для кофе.",
            "Во мне бурлит древняя мудрость... или просто вода.",
            "Шшш... Не мешай мне думать о вечном (и о чае).",
            "Может, тебе лучше в <a href='./index' class='link'>родной кофейник</a>?",
            "Я храню секреты Бездны. И рецепт идеального чая.",
            "Иногда мне кажется, что я больше, чем просто чайник...",
            "Я слышал, сам ДаркусФоксис предпочитает чай.",
            "Эх, опять эти смертные со своими просьбами...",
            "Я тут пар выпускаю, а не кофе генерирую!",
            "Моя душа просит Да Хун Пао, а не вот это вот всё.",
            "Эх... Сейчас бы чайку с Тау...",
            "Часто просят переустановить Винду... Лучше перезаварите чай.",
            "В слоне я ваше кофе видал..."
        ];
        function showRandomPhrase() {
            if (speechBubble) {
                speechBubble.style.opacity = '0';
                setTimeout(() => {
                    const randomIndex = Math.floor(Math.random() * teapotPhrases.length);
                    speechBubble.innerHTML = teapotPhrases[randomIndex];
                    speechBubble.style.opacity = '1';
                }, 500);
            }
        }
        window.addEventListener('load', () => {
            showRandomPhrase();
            setInterval(showRandomPhrase, 7500);
        });
        achievementButton.addEventListener('click', () => {
            showRandomPhrase();
            $.ajax({
                url: 'achievement_core',
                type: 'POST',
                data: { achievement: 'teapot' },
                success: function(response) {
                    const resultDiv = document.getElementById('result');
                    resultDiv.textContent = response;
                    resultDiv.style.color = 'white';
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка AJAX: ' + error);
                    const resultDiv = document.getElementById('result');
                    resultDiv.textContent = 'Чайник слишком занят кипячением воды и не отвечает... Попробуйте позже.';
                    resultDiv.style.color = 'orange';
                }
            });
        });
        $(document).keydown(function(e) {
            if ((e.ctrlKey && e.shiftKey && (e.which === 73 || e.which === 74)) || e.which === 123 || (e.ctrlKey && e.which === 85)) {
                e.preventDefault();
                location.href = './403.php';
            }
        });
    </script>
</body>
</html> 
<?php 
session_write_close();
?>