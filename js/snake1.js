// jshint maxerr:1000
const canvas = document.getElementById("game");
const ctx = canvas.getContext("2d");
const achievementDiv = document.getElementById('achievement');
const resultDiv = document.getElementById('result');
const coinDiv = document.getElementById('coin');
const withdrawalDiv = document.getElementById('withdrawal_coin');
const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|toggle device toolbar/i.test(navigator.userAgent);

let coins = 0;
let withdrawal = false;

function coins_add(coin) {
    if (coin !== 0) {
        if (coins >= 150) {
            coins += Math.floor(coin / 2);
        } else if (coins >= 300) {
            coins += Math.floor(coin / 3);
        } else {
            coins += coin;
        }
        coinDiv.innerHTML = `Монет на выводе: ${coins}`;
        if (coins >= 50 && withdrawal === false) {
            withdrawalDiv.innerHTML = 'Вы можете вывести монеты! <button onclick="send_coins()">Вывести монеты</button>';
        }
    }
}

function send_coins() {
    if (coins < 50) {
        withdrawalDiv.innerHTML = 'Ошибка вывода: У вас недостаточно накоплений.';
    } else {
        if (coins >= 250) {
            $.ajax({
                url: 'achievement_core',
                type: 'POST',
                data: { achievement: 'coins_250' },
                success: function (response) {
                    resultDiv.textContent = response;
                },
                error: function (xhr, status, error) {
                    console.error('Ошибка AJAX: ' + error);
                }
            });
        }
        $.ajax({
            url: 'items_core',
            type: 'POST',
            data: { action: 'add_coins', coin: coins },
            success: function (response) {
                withdrawalDiv.textContent = response;
            },
            error: function (xhr, status, error) {
                console.error('Ошибка AJAX: ' + error);
            }
        });
        coins = 0;
        coins_add(1);
    }
}

const map = new Image();
map.src = "img/ground.png"; 

const foodImg = new Image();
function randomFood() {
    switch (Math.floor(Math.random() * 4)) {
        case 0:
            foodImg.src = "img/food.png";
            break;
        case 1:
            foodImg.src = "img/food2.png";
            break;
        case 2:
            foodImg.src = "img/food3.png";
            break;
        case 3:
            foodImg.src = "img/food4.png";
            break;
    }
}

const eatSound = new Audio("../song/eat.mp3");
const gameOverSound = new Audio("../song/game-over.mp3");
gameOverSound.volume = 0.5;

// Размер карты
const mapWidth = 608;
const mapHeight = 608;

// Размер ячейки (box)
const box = 32; // 608 / 19 = 32 (19 ячеек по ширине и высоте).

// Устанавливаем размеры canvas
canvas.width = mapWidth;
canvas.height = mapHeight;

const game = {
    score: 0,
    gameOver: false,
    food: null,
    snake: [],
    dir: null,
    isPaused: false,
    initGame() {
        this.score = 0;
        this.gameOver = false;
        
        this.food = {
            x: Math.floor(Math.random() * (mapWidth / box - 2)) * box + box, 
            y: Math.floor(Math.random() * (mapHeight / box - 4)) * box + 3 * box, 
        };
        this.snake = [];
        this.snake[0] = {
            x: Math.floor((mapWidth / box) / 2) * box, 
            y: Math.floor((mapHeight / box) / 2) * box,
        };
        this.dir = null;
    },

    resetGame() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        this.initGame();
        this.showStartMenu();
        achievementDiv.innerHTML = ' ';
    },

    drawScore() {
        ctx.fillStyle = "white";
        ctx.font = `${box * 1.5}px Arial`;
        ctx.fillText(this.score, box * 2.5, box * 1.7);
    },

    eatTail(head) {
        for (let i = 0; i < this.snake.length; i++) {
            if (head.x == this.snake[i].x && head.y == this.snake[i].y) {
                this.gameOver = true;
                gameOverSound.play();
                if (isMobile && navigator.vibrate) navigator.vibrate(200); // Вибрация при завершении игры.
                this.showGameOver();
            }
        }
    },

    drawGame() {
        if (this.gameOver || this.isPaused) {
            if (this.isPaused) {
                ctx.fillStyle = "white";
                ctx.font = `${box * 1.5}px Arial`;
                ctx.fillText("Пауза", box * 7, box * 10);
            }
            return;
        }

        // Отрисовка карты
        ctx.drawImage(map, 0, 0, mapWidth, mapHeight);

        // Отрисовка еды
        ctx.drawImage(foodImg, this.food.x, this.food.y);

        // Отрисовка змейки
        for (let i = 0; i < this.snake.length; i++) {
            ctx.fillStyle = i === 0 ? "darkgreen" : "green";
            ctx.fillRect(this.snake[i].x, this.snake[i].y, box, box);
        }

        this.drawScore();

        let snakeX = this.snake[0].x;
        let snakeY = this.snake[0].y;

        
        if (snakeX == this.food.x && snakeY == this.food.y) {
            eatSound.play();
            if (isMobile && navigator.vibrate) navigator.vibrate(100); // Вибрация при съедании еды.
            this.score++;
            
            this.food = {
                x: Math.floor(Math.random() * (mapWidth / box - 2)) * box + box, // Отступ 1 клетка слева и справа.
                y: Math.floor(Math.random() * (mapHeight / box - 4)) * box + 3 * box, // Отступ 3 клетки сверху и 1 снизу.
            };
        } else {
            this.snake.pop(); // Убираем последний элемент змейки, если еда не съедена.
        }

        
        if (
            snakeX < box || 
            snakeX >= mapWidth - box || 
            snakeY < 3 * box || 
            snakeY >= mapHeight - box 
        ) {
            this.gameOver = true;
            gameOverSound.play();
            if (isMobile && navigator.vibrate) navigator.vibrate(200); // Вибрация при завершении игры.
            this.showGameOver();
        }

        if (this.dir == "left") snakeX -= box;
        if (this.dir == "right") snakeX += box;
        if (this.dir == "up") snakeY -= box;
        if (this.dir == "down") snakeY += box;

        let newHead = {
            x: snakeX,
            y: snakeY,
        };

        this.eatTail(newHead);
        this.snake.unshift(newHead);

        if (!this.gameOver) {
            setTimeout(() => {
                this.gameLoop = requestAnimationFrame(this.drawGame.bind(this));
            }, delay);
        }
    },

    showStartMenu() {
        this.initGame();
        ctx.fillStyle = "white";
        ctx.font = `${box * 0.8}px Arial`;
        ctx.fillText("Нажмите клавишу или свайп для начала", box * 2, box * 5);
        if (!isMobile) {
            document.addEventListener("keydown", this.startGame.bind(this), { once: true });
        } else {
            document.addEventListener("touchstart", this.startGame.bind(this), { once: true });
        }
    },

    startGame() {
        randomFood();
        this.gameLoop = requestAnimationFrame(this.drawGame.bind(this));
    },

    showGameOver() {
        ctx.fillStyle = "white";
        ctx.font = `${box * 0.8}px Arial`;
        ctx.fillText(`Потрачено... Счёт: ${this.score}`, box * 2, box * 5);
        coins_add(this.score);
        if (this.score >= 25 && this.score < 50 && this.gameOver) {
            if (!isMobile) {
                const messageDiv = document.createElement('div');
                messageDiv.innerHTML = '<p>Вам доступно достижение: "Водила"!<br>Нажмите на кнопку ниже, чтобы получить!<br>(необходимо наличие аккаунта)</p>';
                messageDiv.style.color = 'white';
                achievementDiv.appendChild(messageDiv);
    
                const achievementButton = document.createElement('button');
                achievementButton.textContent = 'Получить достижение';
                achievementButton.addEventListener('click', () => {
                    $.ajax({
                        url: 'achievement_core',
                        type: 'POST',
                        data: { achievement: 'snake_25' },
                        success: function (response) {
                            resultDiv.textContent = response;
                        },
                        error: function (xhr, status, error) {
                            console.error('Ошибка AJAX: ' + error);
                        }
                    });
                });
                achievementDiv.appendChild(achievementButton);
            } else {
                $.ajax({
                    url: 'achievement_core',
                    type: 'POST',
                    data: { achievement: 'snake_25' },
                    success: function (response) {
                        resultDiv.textContent = response;
                    },
                    error: function (xhr, status, error) {
                        console.error('Ошибка AJAX: ' + error);
                    }
                });
            }
        }

        if (this.score >= 50 && this.gameOver) {
            if (!isMobile) {
                const messageDiv = document.createElement('div');
                messageDiv.innerHTML = '<p>Вам доступно достижение: "Заклинатель змей"!<br>Нажмите на кнопку ниже, чтобы получить!<br>(необходимо наличие аккаунта)</p>';
                messageDiv.style.color = 'white';
                achievementDiv.appendChild(messageDiv);
    
                const achievementButton = document.createElement('button');
                achievementButton.textContent = 'Получить достижение';
                achievementButton.addEventListener('click', () => {
                    $.ajax({
                        url: 'achievement_core',
                        type: 'POST',
                        data: { achievement: 'snake_100' },
                        success: function (response) {
                            resultDiv.textContent = response;
                        },
                        error: function (xhr, status, error) {
                            console.error('Ошибка AJAX: ' + error);
                        }
                    });
                });
                achievementDiv.appendChild(achievementButton);
            } else {
                $.ajax({
                    url: 'achievement_core',
                    type: 'POST',
                    data: { achievement: 'snake_100' },
                    success: function (response) {
                        resultDiv.textContent = response;
                    },
                    error: function (xhr, status, error) {
                        console.error('Ошибка AJAX: ' + error);
                    }
                });
            }
        }
        if (!isMobile) {
            document.addEventListener("keydown", this.resetGame.bind(this), { once: true });
        } else {
            document.addEventListener("touchstart", this.resetGame.bind(this), { once: true });
        }
    },
};

// Управление для ПК (клавиатура).
document.addEventListener("keydown", (event) => {
    if (!isMobile) { // Только для ПК.
        if ((event.keyCode == 65 && game.dir != "right") || (event.keyCode == 37 && game.dir != "right")) game.dir = "left";
        else if ((event.keyCode == 87 && game.dir != "down") || (event.keyCode == 38 && game.dir != "down")) game.dir = "up";
        else if ((event.keyCode == 68 && game.dir != "left") || (event.keyCode == 39 && game.dir != "left")) game.dir = "right";
        else if ((event.keyCode == 83 && game.dir != "up") || (event.keyCode == 40 && game.dir != "up")) game.dir = "down";
        else if (event.keyCode === 32) { // Пробел для паузы
            game.isPaused = !game.isPaused;
            if (!game.isPaused) {
                game.gameLoop = requestAnimationFrame(game.drawGame.bind(game));
            }
        }
    }
});

// Управление для мобильных устройств (свайпы).
const delay = isMobile ? 120 : 80;

let touchStartX = null;
let touchStartY = null;

if (isMobile) { // Только для мобильных устройств.
    canvas.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    });

    canvas.addEventListener('touchmove', (e) => {
        if (!touchStartX || !touchStartY) return;

        const touchEndX = e.touches[0].clientX;
        const touchEndY = e.touches[0].clientY;

        const diffX = touchStartX - touchEndX;
        const diffY = touchStartY - touchEndY;

        if (Math.abs(diffX) > Math.abs(diffY)) {
            if (diffX > 0 && game.dir != "right") game.dir = "left";
            else if (diffX < 0 && game.dir != "left") game.dir = "right";
        } else {
            if (diffY > 0 && game.dir != "down") game.dir = "up";
            else if (diffY < 0 && game.dir != "up") game.dir = "down";
        }

        touchStartX = null;
        touchStartY = null;
    });
    
    canvas.addEventListener("dblclick", function(event) {
        game.isPaused = !game.isPaused;
        if (!game.isPaused) {
            game.gameLoop = requestAnimationFrame(game.drawGame.bind(game));
        }
    });
}

// Начало игры.
game.showStartMenu();