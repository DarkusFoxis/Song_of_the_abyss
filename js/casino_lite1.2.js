// jshint maxerr:1000
const canvas = document.getElementById('game');
const context = canvas.getContext('2d');
const grid = 32;
var tetrominoSequence = [];

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
    '=':[
        [0,1,1,0],
        [0,1,1,0],
        [0,1,1,0],
        [0,1,1,0]
        ],
    'rk':[
        [0,0,0,0,0],
        [1,1,1,1,1],
        [0,1,1,1,0],
        [0,0,1,0,0],
        [0,0,0,0,0]
        ],
    'water':[
        [0,0,0,0,0],
        [0,1,1,1,0],
        [0,1,1,1,0],
        [0,1,1,1,0],
        [0,0,1,0,0]
        ],
    '__':[
        [0,0,0],
        [1,1,1],
        [0,0,0]
        ],
    'V':[
        [0,1,1],
        [0,1,0],
        [1,1,0]
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
  '=': '#FF1493',
  'rk':'#191970',
  'water': '#5F9EA0',
  '__':'#B8860B',
  'V': '#ADFF2F',
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
  const sequence = ['I', 'J', 'L', 'O', 'S', 'T', 'Z', 'o', 'r', 'D', '-', '--', '0', '+', '---', 'X', 'oo', '_', '=', 'rk', 'water', '__', 'V'];
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

function sendXp(xp) {
    let new_xp = xp * 4;
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

let score = 0;
let baseIncrement = 10;

function updateScore() {
    const randomNumber = Math.floor(Math.random() * 100);
    if (randomNumber >= 2 && randomNumber <= 7) {
      score += 2;
    } else if (randomNumber >= 95 && randomNumber <= 98) {
      score += 70;
    } else if (randomNumber >= 7 && randomNumber <= 15) {
      score += 7;
    } else if (randomNumber >= 16 && randomNumber <= 25) {
        score += 20;
    } else if (randomNumber == 1 || randomNumber == 100) {
      score += 99;
    } else if (randomNumber == 50) {
      score += 150;
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

function showGameOver() {
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
    let TitleText = 'Игра окончена!';
    let Smaltext = 'Вы получили: ';
    context.fillText(TitleText, canvas.width / 2, canvas.height / 2);
    context.fillText(Smaltext + score + ' опыта.', canvas.width / 2, canvas.height / 2 + 50);
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
    rAF = requestAnimationFrame(loop);
}

window.onload = function() {
    startGame();
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