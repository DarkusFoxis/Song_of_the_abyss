// jshint maxerr:1000
const canvas = document.getElementById('game');
const context = canvas.getContext('2d');
const grid = 10;

const VISIBLE_ROWS = 80;
const BUFFER_ROWS = 2;
const COLUMNS = 40;
const TOTAL_ROWS = BUFFER_ROWS + VISIBLE_ROWS;
let speed = 34;

let isSpeedBoosted = false;
let speedBoostTimeout = null;
let nextBoostTimeout = null;
let currentSpeed = speed;

let penaltyMode = false;
let penaltyTimeout = null;

var playfield = [];
for (let row = 0; row < TOTAL_ROWS; row++) {
  playfield[row] = [];
  for (let col = 0; col < COLUMNS; col++) {
    playfield[row][col] = 0;
  }
}

var tetrominoSequence = [];
let audio;
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
        [1,0,1,0,1],
        [1,0,1,0,1],
        [1,0,1,0,1],
        [1,0,1,0,1],
        [1,0,1,0,1]
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
        [1,0,0,0,1],
        [1,0,0,0,1],
        [1,0,0,0,1],
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
        [0,0,1,0,0],
        [1,1,1,1,1],
        [0,0,1,0,0],
        [0,0,1,0,0],
        [0,0,1,0,0]
        ],
    'Amogus':[
        [0,1,1,1,0],
        [1,1,0,0,1],
        [1,1,1,1,0],
        [1,1,1,1,0],
        [0,1,0,1,0]
        ],
    "ban":[
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1]
        ],
    "giperban":[
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
        [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
        ],
    "gigapiramide":[
        [0,0,0,0,0,0,0,0,0],
        [0,0,0,0,0,0,0,0,0],
        [1,1,1,1,1,1,1,1,1],
        [0,1,1,1,1,1,1,1,0],
        [0,0,1,1,1,1,1,0,0],
        [0,0,0,1,1,1,0,0,0],
        [0,0,0,0,1,0,0,0,0],
        [0,0,0,0,0,0,0,0,0],
        [0,0,0,0,0,0,0,0,0]
        ],
    'new':[
        [0,0,1,1,1,1,0,0],
        [0,0,1,1,1,1,0,0],
        [0,0,1,1,1,1,0,0],
        [0,0,1,1,1,1,0,0],
        [0,0,1,1,1,1,0,0],
        [0,0,1,1,1,1,0,0],
        [0,0,1,1,1,1,0,0],
        [0,0,1,1,1,1,0,0]
        ],
    'eblan':[
        [1,1,0],
        [1,1,1],
        [1,1,1]
        ],
    'tip':[
        [0,0,0,0,0],
        [0,0,0,0,0],
        [0,1,1,1,0],
        [1,1,1,1,1],
        [0,0,0,0,0]
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
  'r': '#4682B4',
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
  'Amogus':"#FA8072",
  'ban':'#EE82EE',
  "giperban":"#808000",
  'gigapiramide': '#DA70D6',
  'new': '#7B68EE',
  'eblan':'#FF6347',
  'tip':'#DDA0DD'
};

let count = 0;
let tetromino = getNextTetromino();
let rAF = null;
let gameOver = false;

let gameStartTime;
let gameTimerInterval;

let depositType = 'xp';
let depositAmount = 0;

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
  const sequence = ['I', 'J', 'L', 'O', 'S', 'T', 'Z', 'o', 'r', 'D', '-', '--', '0', '+', '---', 'X', 'oo', '_', 'x', 'Y', '.!.', 'Ama','=', 'ъ', 'ь', 'bx', 'rk','ы', 'water', 'E', '__', 'V', '<','ll', "Amogus",'ban','giperban','gigapiramide','new','eblan','tip'];
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
  const col = Math.floor(COLUMNS / 2 - matrix[0].length / 2);
  const row = name === 'I' ? BUFFER_ROWS - 1 : BUFFER_ROWS - 2;
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
      if (matrix[row][col]) {
        const testRow = cellRow + row;
        const testCol = cellCol + col;

        if (testCol < 0 || testCol >= COLUMNS) {
          return false;
        }

        if (testRow >= TOTAL_ROWS) {
            return false;
        }

        if (testRow >= 0 && testRow < TOTAL_ROWS) {
            if (playfield[testRow] && playfield[testRow][testCol]) { 
                return false;
            }
        }
      }
    }
  }
  return true;
}

let score = 0;
let isAchievementShown = false;

function sendXp(xp) {
    let new_xp = xp * 125;
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

function sendDepositResult() {
    const result = (score / 175) * depositAmount;
    $.ajax({
        url: 'items_core',
        type: 'POST',
        data: {
            action: 'process_deposit',
            deposit_type: depositType,
            result: result
        },
        success: function(response) {
        },
        error: function(xhr, status, error) {
            console.error('Ошибка AJAX: ' + error);
        }
    });
}

function startSpeedBoost(duration) {
    if (gameOver) return;
    isSpeedBoosted = true;
    currentSpeed = Math.floor(Math.random() * (60 - 3 + 1) + 3);
    console.log(`SpeedUp: ${currentSpeed} ${duration} second.`);
    speedBoostTimeout = setTimeout(() => {
        isSpeedBoosted = false;
        currentSpeed = speed;
    }, duration);
}

function scheduleNextBoost() {
    if (gameOver) return;
    const delay = getRandomInt(15, 45) * 1000; 
    console.log(`SpeedUp: delay ${delay} second.`);
    nextBoostTimeout = setTimeout(() => {
        const duration = getRandomInt(1, 10) * 1000; 
        startSpeedBoost(duration);
        scheduleNextBoost();
    }, delay);
}

function activatePenaltyMode() {
    penaltyMode = true;
    currentSpeed = 3;
    
    clearTimeout(speedBoostTimeout);
    clearTimeout(nextBoostTimeout);
    
    penaltyTimeout = setTimeout(() => {
        sessionStorage.removeItem('penaltyMode');
        penaltyMode = false;
        currentSpeed = speed;
        startSpeedBoost(1500);
        scheduleNextBoost();
    }, 60000);
}

function showReloadWarning() {
    if (gameOver) return;

    alert("Перезагрузка запрещена во время игры!\nВы будете наказаны штрафным режимом.");
    if (!penaltyMode) {
        activatePenaltyMode();
    }
}

function updateScore() {
    const randomNumber = Math.floor(Math.random() * 100);
    if (randomNumber >= 2 && randomNumber <= 5) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score += Math.floor(Math.random() * (4 - 1 + 1) + 1);
      audio = new Audio('../song/nissan.mp3');
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber >= 95 && randomNumber <= 98) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score += Math.floor(Math.random() * (85 - 65 + 1) + 65);
      audio = new Audio('../song/casino5.mp3');
      audio.volume = 0.3;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber >= 7 && randomNumber <= 13) {
      score += Math.floor(Math.random() * (9 - 6 + 1) + 6);
    } else if (randomNumber >= 48 && randomNumber <= 50) {
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
        score += Math.floor(Math.random() * (35 - 15 + 1) + 15);
    } else if (randomNumber == 1 || randomNumber == 15 || randomNumber == 99) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score += Math.floor(Math.random() * (115 - 90 + 1) + 90);
      audio = new Audio('../song/casino5.mp3');
      audio.volume = 0.3;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    } else if (randomNumber >= 60 && randomNumber <= 70) {
      if (isAudioPlaying()) {
        audio.pause();
      }
      score -= Math.floor(Math.random() * (65 - 35 + 1) + 35);
      audio = new Audio('../song/end_casino2.mp3');
      audio.volume = 0.5;
      audio.play();
      audio.addEventListener('ended', function(){
        playRandomMusic();
      });
    }  else if (randomNumber >= 71 && randomNumber <= 80) {
        let ouf = new Audio('../song/ouf.mp3');
        let defaultVolume = audio.volume;
        score -= Math.floor(Math.random() * (25 - 10 + 1) + 10);
        audio.volume = 0.1;
        ouf.play();
        ouf.addEventListener('ended', function(){
           audio.volume = defaultVolume;
        });
    } else  if (randomNumber >= 81 && randomNumber <= 85) {
        let rand = Math.floor(Math.random() * 100);
        if (rand >= 50) {
            (score > 0) ? score *=  Math.floor(Math.random() * (6 - 2 + 1) + 2) : score *= -1 * Math.floor(Math.random() * (6 - 2 + 1) + 2);
            audio = new Audio('../song/casino5.mp3');
            audio.volume = 0.3;
            audio.play();
            audio.addEventListener('ended', function(){
                playRandomMusic();
            });
        } else {
            (score > 0) ? score *= -1 * Math.floor(Math.random() * (5 - 2 + 1) + 2) : score *= Math.floor(Math.random() * (5 - 2 + 1) + 2);
            audio = new Audio('../song/minus.mp3');
            audio.volume = 0.1;
            audio.play();
            audio.addEventListener('ended', function(){
               audio.volume = defaultVolume;
            });
        }
    } else{
      score += Math.floor(Math.random() * (25 - 10 + 1) + 10);
    }

    if (!penaltyMode) {
        speed -= 2;
        if (speed < 8) speed = 8;
    }

    if (!isSpeedBoosted) {
        currentSpeed = speed;
    }
    document.getElementById('score').textContent = score;
    updateTimerDisplay();
}

function updateTimerDisplay() {
    if (!gameStartTime) return;
    const currentTime = Date.now();
    const elapsedMs = currentTime - gameStartTime;
    const elapsedSeconds = Math.floor(elapsedMs / 1000);
    const minutes = Math.floor(elapsedSeconds / 60);
    const seconds = elapsedSeconds % 60;
    const timerElement = document.getElementById('timer');
    if (timerElement) {
        timerElement.textContent = `Время: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
}

function placeTetromino() {
    for (let row = 0; row < tetromino.matrix.length; row++) {
        for (let col = 0; col < tetromino.matrix[row].length; col++) {
            if (tetromino.matrix[row][col]) {
                const playfieldRow = tetromino.row + row;
                const playfieldCol = tetromino.col + col;
                if (playfieldRow < BUFFER_ROWS) {
                    if (playfield[playfieldRow][playfieldCol]) {
                        return showGameOver();
                    }
                }
                if (playfieldRow >= 0 && playfieldRow < TOTAL_ROWS) {
                    playfield[playfieldRow][playfieldCol] = tetromino.name;
                }
            }
        }
    }
    const rowsToRemove = [];
    for (let row = BUFFER_ROWS; row < TOTAL_ROWS; row++) {
        if (playfield[row].every(cell => !!cell)) {
            rowsToRemove.push(row);
        }
    }

    rowsToRemove.sort((a, b) => b - a);
    const newPlayfield = [];
    let removedCount = 0;

    for (let row = 0; row < TOTAL_ROWS; row++) {
        if (!rowsToRemove.includes(row)) {
            newPlayfield.push(playfield[row]);
        } else {
            removedCount++;
        }
    }
    for (let i = 0; i < removedCount; i++) {
        newPlayfield.unshift(Array(COLUMNS).fill(0));
    }

    playfield = newPlayfield;

    for (let i = 0; i < removedCount; i++) {
        if (!penaltyMode) {
            updateScore();
        }
    }

    tetromino = getNextTetromino();

    if (!isValidMove(tetromino.matrix, tetromino.row, tetromino.col)) {
        showGameOver();
    }
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
    if (!penaltyMode) {
        if (depositType === 'xp' && score !== 0) {
            sendXp(score);
        }
        else if (depositType !== 'xp' && score !== 0) {
            sendDepositResult();
        }
    }
    if (penaltyTimeout) {
        clearTimeout(penaltyTimeout);
        penaltyMode = false;
    }
    cancelAnimationFrame(rAF);
    clearInterval(gameTimerInterval);
    gameOver = true;
    context.fillStyle = 'black';
    context.globalAlpha = 0.75;
    context.fillRect(0, canvas.height / 2 - 50, canvas.width, 120);
    context.globalAlpha = 1;
    context.fillStyle = 'white';
    context.font = '16px monospace';
    context.textAlign = 'center';
    context.textBaseline = 'middle';

    let randEnd = getRandomInt(1, 5);
    let TitleText = 'Я НЕ УМРУ В ТУАЛЕТЕ...';
    let Smaltext = 'А!!! сказанно: ';
    let TimeText = '';

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

    const currentTime = Date.now();
    const elapsedMs = currentTime - gameStartTime;
    const elapsedSeconds = Math.floor(elapsedMs / 1000);
    const minutes = Math.floor(elapsedSeconds / 60);
    const seconds = elapsedSeconds % 60;
    TimeText = `Вы играли: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    clearTimeout(speedBoostTimeout);
    clearTimeout(nextBoostTimeout);

    context.fillText(TitleText, canvas.width / 2, canvas.height / 2 - 20);
    context.fillText(Smaltext + score + ' раз.', canvas.width / 2, canvas.height / 2 + 10);
    context.fillText(TimeText, canvas.width / 2, canvas.height / 2 + 40);

    let end;
    if (depositType !== 'xp' && score <= 0) {
        end = new Audio('../song/dep.mp3');
        end.preload = "auto";
    } else {
        let rand = getRandomInt(1, 6);
        if (rand == 1) {
            end = new Audio('../song/end_casino.mp3');
            end.preload = "auto";
        } else if (rand == 2){
            end = new Audio('../song/nissan.mp3');
            end.preload = "auto";
        } else if (rand == 3) {
            end = new Audio ('../song/rikroll.mp3');
            end.preload = "auto";
        } else if (rand == 4) {
            end = new Audio('../song/end_casino2.mp3');
            end.preload = "auto";
        } else if (rand == 5){
            end = new Audio('../song/casino7.mp3');
            end.preload = "auto";
        } else if (rand == 6){
            end = new Audio('../song/end_casino3.mp3');
            end.preload = "auto";
        }
    }
    end.volume = 0.5;
    end.play();
    audio.pause();
}

function loop() {
  rAF = requestAnimationFrame(loop);
  context.clearRect(0,0,canvas.width,canvas.height);

  for (let row = BUFFER_ROWS; row < TOTAL_ROWS; row++) {
    for (let col = 0; col < COLUMNS; col++) {
      if (playfield[row][col]) {
        const name = playfield[row][col];
        context.fillStyle = colors[name];
        if ((row - BUFFER_ROWS) * grid < canvas.height && col * grid < canvas.width) {
            context.fillRect(col * grid, (row - BUFFER_ROWS) * grid, grid-1, grid-1);
        }
      }
    }
  }

  if (tetromino) {
    if (++count > currentSpeed) {
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
          const x = (tetromino.col + col) * grid;
          const y = (tetromino.row + row - BUFFER_ROWS) * grid;
          if (y >= 0 && y < canvas.height && x >= 0 && x < canvas.width) {
              context.fillRect(x, y, grid-1, grid-1);
          }
        }
      }
    }
  }
}

document.addEventListener('keydown', function(e) {
  if (gameOver) return;
  if (e.which === 37 || e.which === 39 || e.which === 65 || e.which === 68) {
    const col = (e.which === 37 || e.which === 65)
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
    if (sessionStorage.getItem('penaltyMode') === 'true') {
        activatePenaltyMode();
    }
    gameStartTime = Date.now();
    gameTimerInterval = setInterval(updateTimerDisplay, 1000);
    if (depositType !== 'xp') {
        const musicPath = `../song/dep.mp3`;
        const music = new Audio(musicPath);
        music.preload = "auto";
        music.volume = 0.3;
        audio = music;
        audio.play();
        audio.addEventListener('ended', function(){
            playRandomMusic();
        });
    } else {
        playRandomMusic();
    }
    if (!penaltyMode) {
        startSpeedBoost(3000);
        scheduleNextBoost();
    }
    rAF = requestAnimationFrame(loop);
    updateTimerDisplay();
}

document.getElementById('depositType').addEventListener('change', function() {
    const depositAmountGroup = document.getElementById('depositAmountGroup');
    if (this.value === 'xp') {
        depositAmountGroup.style.display = 'none';
    } else {
        depositAmountGroup.style.display = 'block';
    }
});

document.getElementById('enterCasinoBtn').addEventListener('click', function() {
    depositType = document.getElementById('depositType').value;
    const ageCheck = document.getElementById('ageCheck').checked;
    const languageCheck = document.getElementById('languageCheck').checked;

    if (!ageCheck || !languageCheck) {
        alert('Пожалуйста, подтвердите все условия!');
        return;
    }

    if (depositType !== 'xp') {
        depositAmount = parseInt(document.getElementById('depositAmount').value);
        
        if (isNaN(depositAmount) || depositAmount < 1) {
            alert('Введите корректную ставку!');
            return;
        }

        if (depositAmount < 25 && depositType !== "kase") {
            alert('Ставка не может быть менее 25!');
            return;
        }

        if (depositAmount < 3 && depositType == "kase") {
            alert('Ставка не может быть менее 3!');
            return;
        }

        let hasEnough = false;
        switch(depositType) {
            case 'coins':
                hasEnough = userResources.coins >= depositAmount;
                break;
            case 'sakura':
                hasEnough = userResources.sakura >= depositAmount;
                break;
            case 'kase':
                hasEnough = userResources.kase >= depositAmount;
                break;
        }
        
        if (!hasEnough) {
            alert('Недостаточно ресурсов для ставки!');
            return;
        }
    }
    
    document.getElementById('depositModal').style.display = 'none';
    document.querySelector('.game-content').style.display = 'block';
    
    startGame();
});

window.onload = function() {
    document.getElementById('depositAmountGroup').style.display = 'none';
    document.querySelector('.game-content').style.display = 'none';
};

$(document).on('contextmenu', function(e) {
    e.preventDefault();
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
    if ((e.ctrlKey && e.key === 'r') || (e.ctrlKey && e.shiftKey && e.key === 'R') || e.key === 'F5') {
        e.preventDefault();
        showReloadWarning();
    }
});

window.addEventListener('beforeunload', function(e) {
    if (!gameOver && rAF !== null) {
        const confirmationMessage = "Игра все еще идет! Вы уверены что хотите уйти? Это повлечёт за собой наказание.";
        e.returnValue = confirmationMessage;
        sessionStorage.setItem('penaltyMode', 'true');
        return confirmationMessage;
    }
});