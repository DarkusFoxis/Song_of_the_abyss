// jshint maxerr:1000
const canvas = document.getElementById('game');
const context = canvas.getContext('2d');
const grid = 32;
var tetrominoSequence = [];
let audio;

var playfield = [];

for (let row = -2; row < 20; row++) {
  playfield[row] = [];

  for (let col = 0; col < 10; col++) {
    playfield[row][col] = 0;
  }
}

const tetrominos = {
  'I': [
    [0,0,0,0],
    [1,1,1,1],
    [0,0,0,0],
    [0,0,0,0]
  ],
  'J': [
    [1,0,0],
    [1,1,1],
    [0,0,0]
  ],
  'L': [
    [0,0,1],
    [1,1,1],
    [0,0,0]
  ],
  'O': [
    [1,1],
    [1,1]
  ],
  'S': [
    [0,1,1],
    [1,1,0],
    [0,0,0]
  ],
  'Z': [
    [1,1,0],
    [0,1,1],
    [0,0,0]
  ],
  'T': [
    [0,1,0],
    [1,1,1],
    [0,0,0]
  ],
  "o":[
      [0,0,0,0,0],
      [1,1,1,1,1],
      [0,1,0,1,0],
      [0,0,0,0,0],
      [0,0,0,0,0]
    ],
    "r":[
        [1,1],
        [1,0]
    ],
    "D":[
        [1,1,1],
        [1,1,1],
        [1,1,1],
        ],
    "-": [
        [0,0,1,0,0],
        [1,0,1,0,1],
        [1,0,1,0,1],
        [1,0,1,0,1],
        [0,0,1,0,0]
        ],
    '--': [
        [0,0,0,0,0],
        [0,0,0,0,0],
        [1,1,1,1,1],
        [0,0,0,0,0],
        [0,0,0,0,0]
        ],
    "0":[
        [1,1,1],
        [1,0,1],
        [1,1,1]
        ],
    '+':[
        [0,1,0],
        [1,1,1],
        [0,1,0]
        ],
    '---': [
        [0,0,0,0,0,0,0],
        [0,0,0,0,0,0,0],
        [0,0,0,0,0,0,0],
        [1,1,1,1,1,1,1],
        [0,0,0,0,0,0,0],
        [0,0,0,0,0,0,0],
        [0,0,0,0,0,0,0]
        ],
    "X": [
        [1,1,1,1,1,1],
        [1,1,1,1,1,1],
        [1,1,1,1,1,1],
        [1,1,1,1,1,1],
        [1,1,1,1,1,1],
        [1,1,1,1,1,1]
        ],
    'oo':[
        [1]
        ],
    '_':[
        [1,1],
        [0,0]
        ],
    "x": [
        [1,0,1],
        [1,0,1],
        [1,0,1]
        ],
    "Y": [
        [1,0,1],
        [1,0,1],
        [1,1,1]
        ],
    '.!.':[
        [0,0,1,0,0],
        [0,0,1,0,0],
        [0,0,1,0,0],
        [0,0,1,0,0],
        [1,1,1,1,1]
        ],
    "Ama":[
        [1,0,0,0,1],
        [1,0,0,1,1],
        [1,0,1,0,1],
        [1,1,0,0,1],
        [1,0,0,0,1]
        ],
    '=':[
        [0,1,1,0],
        [0,1,1,0],
        [0,1,1,0],
        [0,1,1,0]
        ],
    'ъ':[
        [1,0,0,0,1],
        [1,0,0,0,1],
        [1,1,1,1,1],
        [1,0,0,0,1],
        [1,0,0,0,1]
        ],
    'ь':[
        [0,1,0,0],
        [0,1,1,0],
        [0,1,0,1],
        [0,1,1,0]
        ],
    'bx':[
        [1,0,0,0,1],
        [1,0,0,0,1],
        [1,0,0,0,1],
        [1,1,0,1,1],
        [1,1,1,1,1]
        ],
    'rk':[
        [0,0,0,0,0],
        [1,1,1,1,1],
        [0,1,1,1,0],
        [0,0,1,0,0],
        [0,0,0,0,0]
        ],
    'ы':[
        [0,0,1,0,0],
        [0,1,1,1,0],
        [1,1,1,1,1],
        [0,1,1,1,0],
        [0,0,1,0,0]
        ],
    'water':[
        [0,0,0,0,0],
        [0,1,1,1,0],
        [0,1,1,1,0],
        [0,1,1,1,0],
        [0,0,1,0,0]
        ],
    'E':[
        [0,1,1,1,0],
        [0,1,0,0,0],
        [0,1,1,1,0],
        [0,1,0,0,0],
        [0,1,1,1,0]
        ],
    '__':[
        [0,0,0],
        [1,1,1],
        [0,0,0]
        ],
    'V':[
        [0,1,1],
        [0,0,0],
        [1,1,0]
        ],
    '<':[
        [0,0,0,1],
        [0,0,0,1],
        [0,0,0,1],
        [1,1,1,1]
        ],
    'll':[
        [0,0,1,1,1],
        [0,0,1,0,0],
        [0,0,1,0,0],
        [0,0,1,0,0],
        [1,1,1,0,0]
        ]

};

const colors = {
  'I': 'cyan',
  'O': 'yellow',
  'T': 'purple',
  'S': 'green',
  'Z': 'red',
  'J': 'blue',
  'L': 'orange',
  'o': 'white',
  'r': 'Navy',
  'D': 'Aqua',
  '-': 'LightSkyBlue',
  '--': 'DarkRed',
  '0': 'SaddleBrown',
  '+': 'Lime',
  '---': 'DarkCyan',
  'X':'Indigo',
  'oo': 'Gray',
  '_':'Magenta',
  'x':'DarkMagenta',
  'Y': "DarkOliveGreen",
  '.!.': 'MediumVioletRed',
  'Ama': 'Crimson',
  '=': '#FF1493',
  "ъ": '#708090',
  'ь':'white',
  'bx': '#D2691E',
  'rk':'#191970',
  'ы':'#BDB76B',
  'water': '#5F9EA0',
  'E': '#FF4500',
  '__':'#B8860B',
  'V': '#ADFF2F',
  '<': '#D2691E',
  'll': '#808000',
};

let count = 0;
let tetromino = getNextTetromino();
let rAF = null;  
let gameOver = false;

function getRandomInt(min, max) {
  min = Math.ceil(min);
  max = Math.floor(max);

  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function isAudioPlaying() {
  if (audio && !audio.paused) {
    return true;
  } else {
    return false;
  }
}

function generateSequence() {
  const sequence = ['I', 'J', 'L', 'O', 'S', 'T', 'Z', 'o', 'r', 'D', '-', '--', '0', '+', '---', 'X', 'oo', '_', 'x', 'Y', '.!.', 'Ama','=', 'ъ', 'ь', 'bx', 'rk','ы', 'water', 'E', '__', 'V', '<','ll'];
  while (sequence.length) {
    const rand = getRandomInt(0, sequence.length - 1);
    const name = sequence.splice(rand, 1)[0];
    tetrominoSequence.push(name);
  }
}

function getNextTetromino() {
  if (tetrominoSequence.length === 0) {
    generateSequence();
  }
  const name = tetrominoSequence.pop();
  const matrix = tetrominos[name];
  const col = playfield[0].length / 2 - Math.ceil(matrix[0].length / 2);
  const row = name === 'I' ? -1 : -2;
  return {
    name: name,
    matrix: matrix,
    row: row,
    col: col
  };
}

function rotate(matrix) {
  const N = matrix.length - 1;
  const result = matrix.map((row, i) =>
    row.map((val, j) => matrix[N - j][i])
  );
  return result;
}

function isValidMove(matrix, cellRow, cellCol) {
  for (let row = 0; row < matrix.length; row++) {
    for (let col = 0; col < matrix[row].length; col++) {
      if (matrix[row][col] && (
          cellCol + col < 0 ||
          cellCol + col >= playfield[0].length ||
          cellRow + row >= playfield.length ||
          playfield[cellRow + row][cellCol + col])
        ) {
        return false;
      }
    }
  }
  return true;
}

let score = 0;
let baseIncrement = 10;
let isAchievementShown = false;

function showAchievementMessage() {
    const achievementDiv = document.getElementById('achievement');
    const messageDiv = document.createElement('div');
    messageDiv.innerHTML = '<p style="text-align: center;">Вам доступно достижение: "Диллер!"<br>Нажмите на кнопку ниже, чтобы получить!<br>(необходимо наличие аккаунта)</p>';
    messageDiv.style.color = 'white';
    achievementDiv.appendChild(messageDiv);
    const achievementButton = document.createElement('button');
    achievementButton.textContent = 'Получить достижение';
    achievementButton.addEventListener('click', () => {
        $.ajax({
            url: 'achievement_core',
            type: 'POST',
            data: {achievement: 'casino_100' },
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
function kasikMaster() {
    const achievementDiv = document.getElementById('achievement');
    const messageDiv = document.createElement('div');
    messageDiv.innerHTML = '<p style="text-align: center;">Вам доступно достижение: "Энтузиаст перфекционизма"!<br>Нажмите на кнопку ниже, чтобы получить!<br>(необходимо наличие аккаунта)</p>';
    messageDiv.style.color = 'white';
    achievementDiv.appendChild(messageDiv);
    const achievementButton = document.createElement('button');
    achievementButton.textContent = 'Получить достижение';
    achievementButton.addEventListener('click', () => {
        $.ajax({
            url: 'achievement_core',
            type: 'POST',
            data: {achievement: 'casino_200' },
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

function showAchievement_100() {
    const achievementDiv = document.getElementById('achievement');
    const messageDiv = document.createElement('div');
    messageDiv.innerHTML = '<p style="text-align: center;">Вам доступно достижение: "Горе игрок!"<br>Нажмите на кнопку ниже, чтобы получить!<br>(необходимо наличие аккаунта)</p>';
    messageDiv.style.color = 'white';
    achievementDiv.appendChild(messageDiv);
    const achievementButton = document.createElement('button');
    achievementButton.textContent = 'Получить достижение';
    achievementButton.addEventListener('click', () => {
        $.ajax({
            url: 'achievement_core',
            type: 'POST',
            data: {achievement: 'casino_-100' },
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

function showAchievement_200() {
    const achievementDiv = document.getElementById('achievement');
    const messageDiv = document.createElement('div');
    messageDiv.innerHTML = '<p style="text-align: center;">Вам доступно достижение: "Постоянный игрок!"<br>Нажмите на кнопку ниже, чтобы получить!<br>(необходимо наличие аккаунта)</p>';
    messageDiv.style.color = 'white';
    achievementDiv.appendChild(messageDiv);
    const achievementButton = document.createElement('button');
    achievementButton.textContent = 'Получить достижение';
    achievementButton.addEventListener('click', () => {
        $.ajax({
            url: 'achievement_core',
            type: 'POST',
            data: {achievement: 'casino_-200' },
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

function sendXp(xp) {
    let new_xp = xp * 20;
    $.ajax({
        url: 'items_core',
        type: 'POST',
        data: {action: 'add_xp', xp: new_xp},
        success: function(response) {
        },
        error: function(xhr, status, error) {
            console.error('Ошибка AJAX: ' + error);
        }
    });
}

function updateScore() {
    const randomNumber = Math.floor(Math.random() * 150);
    if (randomNumber >= 2 && randomNumber <= 7) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score += 2;
      audio = new Audio('../song/nissan.mp3');
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber >= 95 && randomNumber <= 98) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score += 70;
      audio = new Audio('../song/casino5.mp3');
      audio.volume = 0.3;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber >= 7 && randomNumber <= 15) {
      score += 7;
    } else if (randomNumber >= 48 && randomNumber <= 51) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score = 0;
      audio = new Audio('../song/end_casino3.mp3');
      audio.volume = 0.5;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber >= 16 && randomNumber <= 25) {
        score += 20;
    } else if (randomNumber == 1 || randomNumber == 101 || randomNumber == 115) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score += 99;
      audio = new Audio('../song/casino5.mp3');
      audio.volume = 0.3;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber >= 118 && randomNumber <= 125) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score -= 50;
      audio = new Audio('../song/end_casino2.mp3');
      audio.volume = 0.5;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber == 110 || randomNumber == 150) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score += 150;
      audio = new Audio('../song/casino5.mp3');
      audio.volume = 0.3;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    }  else if (randomNumber >= 143 && randomNumber <= 145) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score -= 100;
      audio = new Audio('../song/end_casino2.mp3');
      audio.volume = 0.5;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber >= 126 && randomNumber <= 137) {
        let ouf = new Audio('../song/ouf.mp3');
        let defaultVolume = audio.volume;
        score -= baseIncrement;
        audio.volume = 0.1;
        ouf.play();
        ouf.addEventListener('ended', function(){
           audio.volume = defaultVolume; 
        });
    } else {
      score += baseIncrement;
    }
    document.getElementById('score').textContent = score;
}

function placeTetromino() {
  for (let row = 0; row < tetromino.matrix.length; row++) {
    for (let col = 0; col < tetromino.matrix[row].length; col++) {
      if (tetromino.matrix[row][col]) {
        if (tetromino.row + row < 0) {
          return showGameOver();
        }
        playfield[tetromino.row + row][tetromino.col + col] = tetromino.name;
      }
    }
  }
  for (let row = playfield.length - 1; row >= 0; ) {
    if (playfield[row].every(cell => !!cell)) {
      updateScore();
      for (let r = row; r >= 0; r--) {
        for (let c = 0; c < playfield[r].length; c++) {
          playfield[r][c] = playfield[r-1][c];
        }
      }
    }
    else {
      row--;
    }
  }
  tetromino = getNextTetromino();
}

function playRandomMusic() {
    if (isAudioPlaying()) {
        audio.pause();
    }
    const musicNumber = getRandomInt(1, 8);
    const musicPath = `../song/casino${musicNumber}.mp3`;
    const music = new Audio(musicPath);
    music.preload = "auto";
    if (musicNumber == 1){
        music.volume = 1;
    } else {
        music.volume = 0.4;
    }
    audio = music;
    audio.play();
    audio.addEventListener('ended', function(){
    	playRandomMusic();
    });
}

function stopSong(){
    audio.pause();
}

function showGameOver() {
    if (score >= 100 && score < 200 && !isAchievementShown) {
        showAchievementMessage();
        isAchievementShown = true;
    } else if (score >= 200 && !isAchievementShown) {
        kasikMaster();
        isAchievementShown = true;
        stiker();
    } else if (score <= -100 && score > -200 && !isAchievementShown) {
        showAchievement_100();
        isAchievementShown = true;
    } else if (score <= -200 && !isAchievementShown) {
        showAchievement_200();
        isAchievementShown = true;
    }
    if (score !== 0) {
        sendXp(score);
    }
    cancelAnimationFrame(rAF);
    gameOver = true;
    context.fillStyle = 'black';
    context.globalAlpha = 0.75;
    context.fillRect(0, canvas.height / 2 - 10, canvas.width, 80);
    context.globalAlpha = 1;
    context.fillStyle = 'white';
    context.font = '20px monospace';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    let randEnd = getRandomInt(1, 5);
    let TitleText = 'Я НЕ УМРУ В ТУАЛЕТЕ...';
    let Smaltext = 'А!!! сказанно: ';
    if (randEnd == 1) {
        TitleText = 'ЁБАННЫЙ РОТ, ЭТОГО КАЗИНО...';
        Smaltext = 'БЛЯТЬ! сказанно: ';
    } else if (randEnd == 2) {
        TitleText = 'Я НЕ УМРУ В ТУАЛЕТЕ...';
        Smaltext = 'А!!! сказанно: ';
    } else if (randEnd == 3) {
        TitleText = 'ЁБ ТВОЮ МАТЬ, КАЗИНО...';
        Smaltext = 'БЛЯТЬ... сказанно: ';
    } else if (randEnd == 4){
        TitleText = 'Press F...';
        Smaltext = 'прожали: ';
    } else {
        TitleText = 'ПАЛКУ, ПАЛКУ ДАВАЙ!!!';
        Smaltext = 'ПАЛКУ ДАВАЙ, БЛЯТЬ: ';
    }
    context.fillText(TitleText, canvas.width / 2, canvas.height / 2);
    context.fillText(Smaltext + score + ' раз.', canvas.width / 2, canvas.height / 2 + 50);
    let rand = getRandomInt(1, 6);
    let end;
    if (rand == 1 && score < 150) {
        end = new Audio('../song/end_casino.mp3');
        end.preload = "auto";
    } else if (rand == 2 && score < 150){
        end = new Audio('../song/nissan.mp3');
        end.preload = "auto";
    } else if (rand == 3 && score < 150) {
        end = new Audio ('../song/rikroll.mp3');
        end.preload = "auto";
    } else if (rand == 4 && score < 150) {
        end = new Audio('../song/end_casino2.mp3');
        end.preload = "auto";
    } else if (rand == 5 && score <= 150){
        end = new Audio('../song/casino7.mp3');
        end.preload = "auto";
    } else if (rand == 6 || score >= 150){
        end = new Audio('../song/end_casino3.mp3');
        end.preload = "auto";
    }
    end.volume = 0.5;
    end.play();
    audio.pause();
}

function loop() {
  rAF = requestAnimationFrame(loop);
  context.clearRect(0,0,canvas.width,canvas.height);
  for (let row = 0; row < 20; row++) {
    for (let col = 0; col < 10; col++) {
      if (playfield[row][col]) {
        const name = playfield[row][col];
        context.fillStyle = colors[name];
        context.fillRect(col * grid, row * grid, grid-1, grid-1);
      }
    }
  }

  if (tetromino) {
    if (++count > 35) {
      tetromino.row++;
      count = 0;
      if (!isValidMove(tetromino.matrix, tetromino.row, tetromino.col)) {
        tetromino.row--;
        placeTetromino();
      }
    }
    context.fillStyle = colors[tetromino.name];
    for (let row = 0; row < tetromino.matrix.length; row++) {
      for (let col = 0; col < tetromino.matrix[row].length; col++) {
        if (tetromino.matrix[row][col]) {
          context.fillRect((tetromino.col + col) * grid, (tetromino.row + row) * grid, grid-1, grid-1);
        }
      }
    }
  }
}

document.addEventListener('keydown', function(e) {
  if (gameOver) return;
  if (e.which === 37 || e.which === 39) {
    const col = e.which === 37
      ? tetromino.col - 1
      : tetromino.col + 1;
    if (isValidMove(tetromino.matrix, tetromino.row, col)) {
      tetromino.col = col;
    }
  }
  if (e.which === 65 || e.which === 68) {
    const col = e.which === 65
      ? tetromino.col - 1
      : tetromino.col + 1;
      if (isValidMove(tetromino.matrix, tetromino.row, col)) {
      tetromino.col = col;
    }
  }
  if (e.which === 38 || e.which === 87) {
    const matrix = rotate(tetromino.matrix);
    if (isValidMove(matrix, tetromino.row, tetromino.col)) {
      tetromino.matrix = matrix;
    }
  }
  if(e.which === 40 || e.which === 83) {
    const row = tetromino.row + 1;
    if (!isValidMove(tetromino.matrix, row, tetromino.col)) {
      tetromino.row = row - 1;
      placeTetromino();
      return;
    }
    tetromino.row = row;
  }
});

function startGame() {
    playRandomMusic();
    rAF = requestAnimationFrame(loop);
}

window.onload = function() {
  const readyToPlay = confirm("Ты готов войти в казино? (Внимание! Тут играет музыка. Наденьте наушники (в игре есть мат)!");
  if (readyToPlay) {
    startGame();
  } else {
    window.location.href = "tetris";
  }
};
$(document).on('contextmenu', function(e) {
    e.preventDefault();
    showContextMenu(e.clientX, e.clientY);
});

// Скрыть контекстное меню при клике вне него.
$(document).on('click', function(e) {
    if (!$(e.target).closest('.context-menu').length) {
        hideContextMenu();
    }
});

function showContextMenu(x, y) {
    $('#custom-context-menu').css({
        display: 'block',
        left: x + 'px',
        top: y + 'px'
    });
}

function hideContextMenu() {
    $('#custom-context-menu').hide();
}
$(document).keydown(function(e) {
    if ((e.ctrlKey && e.shiftKey && e.which === 73) || e.which === 123) {
        e.preventDefault();
        if (gameOver) {
            return;
        } else {
            score = 0;
            showGameOver();
        }
    }
});