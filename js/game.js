const canvas = document.getElementById("game");
const ctx = canvas.getContext("2d");

const ground = new Image();
ground.src = "img/ground.png";

const foodImg = new Image();
switch(Math.floor(Math.random() * 4)) {
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




let box = 32;

let score = 0;

let gameOver = false;

let food = {
  x: Math.floor((Math.random() * 17 + 1)) * box,
  y: Math.floor((Math.random() * 15 + 3)) * box,
};

let snake = [];
snake[0] = {
  x: 9 * box,
  y: 10 * box
};

document.addEventListener("keydown", direction);

let dir;

function direction(event) {
  if(event.keyCode == 65 && dir != "right")
    dir = "left";
  else if(event.keyCode == 87 && dir != "down")
    dir = "up";
  else if(event.keyCode == 68 && dir != "left")
    dir = "right";
  else if(event.keyCode == 83 && dir != "up")
    dir = "down";
}

function eatTail(head, arr) {
  for(let i = 0; i < arr.length; i++) {
    if(head.x == arr[i].x && head.y == arr[i].y)
      clearInterval(game);
      gameOver = true;
      stopGame();
  }
}

function drawGame() {
  ctx.drawImage(ground, 0, 0);

  ctx.drawImage(foodImg, food.x, food.y);

for(let i = 0; i < snake.length; i++) {
    ctx.fillStyle = i === 0 ? "darkgreen" : "green";
    ctx.fillRect(snake[i].x, snake[i].y, box, box);
  }

  ctx.fillStyle = "white";
  ctx.font = "50px Arial";
  ctx.fillText(score, box * 2.5, box * 1.7);

  let snakeX = snake[0].x;
  let snakeY = snake[0].y;

  if(snakeX == food.x && snakeY == food.y) {
    score++;
    food = {
      x: Math.floor((Math.random() * 17 + 1)) * box,
      y: Math.floor((Math.random() * 15 + 3)) * box,
    };
  } else {
    snake.pop();
  }

  if(snakeX < box || snakeX > box * 17 || snakeY < 3 * box || snakeY > box * 17) {
    clearInterval(game);
    gameOver = true;
    stopGame();
  }

  if(dir == "left") snakeX -= box;
  if(dir == "right") snakeX += box;
  if(dir == "up") snakeY -= box;
  if(dir == "down") snakeY += box;

  let newHead = {
    x: snakeX,
    y: snakeY
  };

  eatTail(newHead, snake);
  snake.unshift(newHead);
}
function stopGame(){
    if (score >= 25 && score < 100 && gameOver){
        const achievementDiv = document.getElementById('achievement');
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
                success: function(response) {
                    const resultDiv = document.getElementById('result');
                    resultDiv.textContent = response;
                    resultDiv.style.color = 'white';
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка AJAX: ' + error);
                }
            });
        });
        achievementDiv.appendChild(achievementButton);
    }
    
    if (score >= 100 && gameOver){
        const achievementDiv = document.getElementById('achievement');
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
                success: function(response) {
                    const resultDiv = document.getElementById('result');
                    resultDiv.textContent = response;
                    resultDiv.style.color = 'white';
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка AJAX: ' + error);
                }
            });
        });
        achievementDiv.appendChild(achievementButton);
    }
}

let game = setInterval(drawGame, 60);