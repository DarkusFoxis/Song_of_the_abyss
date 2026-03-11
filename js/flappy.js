var cvs = document.getElementById("canvas");
var ctx = cvs.getContext("2d");

var bird = new Image();
var bg = new Image();
var fg = new Image();
var pipeUp = new Image();
var pipeBottom = new Image();

bird.src = "img/bird.png";
bg.src = "img/bg.png";
fg.src = "img/fg.png";
pipeUp.src = "img/pipeUp.png";
pipeBottom.src = "img/pipeBottom.png";

var score_audio = new Audio();
score_audio.src = "sound/score.mp3";
var fly = new Audio();
fly.src = "sound/fly.mp3";

var gap = 100;
var pipe = [];
var score = 0;
var xPos = 10;
var yPos = 150;
var grav = 0.25;
var lift = 5;
var velocity = 0;
var gameOver = false;
var distance = 100;

document.addEventListener("keydown", function(event) {
    if (event.which === 82) {
        if (gameOver) {
            restartGame();
        } else {
            moveUp();
        }
    } else {
        moveUp();
    }
});

document.addEventListener("touchstart", function(event) {
    if (!gameOver) {
        fly.play();
        moveUp();
    }
});

cvs.addEventListener("dblclick", function(event) {
    if(gameOver) {
        restartGame();
    }
});

function moveUp() {
    if (!gameOver) {
        fly.play();
        velocity = -lift;
    }
}

function restartGame() {
    gameOver = false;
    score = 0;
    pipe = [];
    pipe[0] = { x: cvs.width, y: Math.floor(Math.random() * pipeUp.height) - pipeUp.height };
    yPos = 150;
    velocity = 0;
    draw();
    distance = 100;
    grav = 0.25;
}

function send(score) {
    let sakura = score * 5
    $.ajax({
        url: 'items_core',
        type: 'POST',
        data: {action: 'add_sakura', sakura: sakura},
        success: function(response) {
        },
        error: function(xhr, status, error) {
            console.error('Ошибка AJAX: ' + error);
        }
    });
}

pipe[0] = {
    x: cvs.width,
    y: Math.floor(Math.random() * pipeUp.height) - pipeUp.height
};

function draw() {
    ctx.drawImage(bg, 0, 0);

    for (var i = 0; i < pipe.length; i++) {
        ctx.drawImage(pipeUp, pipe[i].x, pipe[i].y);
        ctx.drawImage(pipeBottom, pipe[i].x, pipe[i].y + pipeUp.height + gap);

        pipe[i].x--;

        if (pipe[i].x == distance) {
            pipe.push({
                x: cvs.width,
                y: Math.floor(Math.random() * pipeUp.height) - pipeUp.height
            });
        }

        if (xPos + bird.width >= pipe[i].x
            && xPos <= pipe[i].x + pipeUp.width
            && (yPos <= pipe[i].y + pipeUp.height
            || yPos + bird.height >= pipe[i].y + pipeUp.height + gap) || yPos + bird.height >= cvs.height - fg.height) {
            gameOver = true;
        }

        if (pipe[i].x == 0) {
            score_audio.play();
            score++;
            if (score % 10 == 0) {
                distance += 15;
            }
            if (score % 20 == 0) {
                grav += 0.05;
            }
        }
    }

    ctx.drawImage(fg, 0, cvs.height - fg.height);
    ctx.drawImage(bird, xPos, yPos);

    velocity += grav;
    yPos += velocity;

    if (yPos + bird.height >= cvs.height - fg.height) {
        yPos = cvs.height - fg.height - bird.height;
        velocity = 0;
        gameOver = true;
    }

    if (yPos < 0) {
        yPos = 0;
        velocity = 0;
    }

    ctx.fillStyle = "#000";
    ctx.font = "24px Verdana";
    ctx.fillText(score, 10, cvs.height / 5 - 70);

    if (gameOver) {
        if (score > 0) {
            send(score);
        }
        ctx.fillStyle = "black";
        ctx.font = "30px Verdana";
        var text1 = "Игра окончена!";
        var text2 = "Ваш счёт: " + score;
        ctx.fillText(text1, (cvs.width - ctx.measureText(text1).width) / 2, cvs.height / 2 - 10);
        ctx.fillText(text2, (cvs.width - ctx.measureText(text2).width) / 2, cvs.height / 2 + 20);
        return;
    }

    requestAnimationFrame(draw);
}

pipeBottom.onload = draw;

